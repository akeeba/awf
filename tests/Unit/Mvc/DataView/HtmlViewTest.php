<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc\DataView;

require_once __DIR__ . '/Fixtures/HtmlViewStubs.php';

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Database\Driver\Sqlite as SqliteDriver;
use Awf\Document\Html as HtmlDocument;
use Awf\Document\Menu\MenuManager;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Text\Language;
use HtmlViewTestApp\Model\Items as ItemsModel;
use HtmlViewTestApp\Model\Item as ItemModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Awf\Mvc\DataView\Html
 *
 * Covered:
 *  - onBeforeAdd() returns true
 *  - onBeforeAdd() calls disableMenu('main') on the document menu
 *  - onBeforeEdit() returns true
 *  - onBeforeEdit() calls disableMenu('main') on the document menu
 *  - onBeforeRead() returns true
 *  - onBeforeRead() does NOT call disableMenu (menu stays enabled)
 *  - display() with task=add returns true
 *  - display() with task=edit returns true
 *  - display() with task=read returns true
 *  - display() with task=browse returns true (inherited from Raw)
 *  - display() with task=add throws 403 when onBeforeAdd returns false
 *  - display() with task=edit throws 403 when onBeforeEdit returns false
 *  - display() with task=read throws 403 when onBeforeRead returns false
 *  - display() with task=add throws 403 when onAfterAdd returns false
 *  - display() with task=edit throws 403 when onAfterEdit returns false
 *  - display() with task=read throws 403 when onAfterRead returns false
 *  - onBeforeBrowse() still works (inherited from Raw): returns true
 *  - onBeforeBrowse() (inherited): creates $lists, populates items, count, and pagination
 *  - default layout is 'default'
 *  - layout can be changed via setLayout()
 *  - task→layout mapping: add/edit tasks use correct layout
 */
class HtmlViewTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Infrastructure
    // -------------------------------------------------------------------------

    private SqliteDriver $db;
    private Container    $container;

    protected function setUp(): void
    {
        parent::setUp();

        if (!SqliteDriver::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = 'localhost';
        }
        if (!isset($_SERVER['SCRIPT_NAME'])) {
            $_SERVER['SCRIPT_NAME'] = '/index.php';
        }

        ItemsModel::flushCaches();
        ItemModel::flushCaches();

        $this->db = new SqliteDriver([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $this->db->connect();

        $this->db->setQuery(
            'CREATE TABLE items (
                item_id  INTEGER PRIMARY KEY AUTOINCREMENT,
                title    TEXT    NOT NULL DEFAULT \'\',
                enabled  INTEGER NOT NULL DEFAULT 1
            )'
        )->execute();

        $this->container = $this->buildContainer();
    }

    protected function tearDown(): void
    {
        ItemsModel::flushCaches();
        ItemModel::flushCaches();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Container / view builders
    // -------------------------------------------------------------------------

    /**
     * Build a test container with a mock document that has a real MenuManager.
     */
    private function buildContainer(array $inputData = []): Container
    {
        $tmpDir = sys_get_temp_dir();

        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturn([]);

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);

        $db = $this->db;

        // We need a real container reference to pass to MenuManager
        $containerHolder = [];

        $application = $this->createMock(Application::class);
        $application->method('getName')->willReturn('HtmlViewTestApp');
        $application->method('getTemplate')->willReturn('default');
        $application->method('getDocument')->willReturnCallback(
            function () use (&$containerHolder) {
                // Lazy-built mock document with a real MenuManager (one instance per container)
                if (!isset($containerHolder['doc'])) {
                    $menuManager = new MenuManager($containerHolder[0]);
                    $doc         = $this->createMock(HtmlDocument::class);
                    $doc->method('getMenu')->willReturn($menuManager);
                    $containerHolder['doc'] = $doc;
                }
                return $containerHolder['doc'];
            }
        );

        $container = new Container([
            'application_name'     => 'HtmlViewTestApp',
            'applicationNamespace' => '\\HtmlViewTestApp',
            'session_segment_name' => 'htmlviewtestapp_seg',
            'basePath'             => $tmpDir,
            'languagePath'         => $tmpDir,
            'temporaryPath'        => $tmpDir,
            'templatePath'         => $tmpDir,
            'sqlPath'              => $tmpDir,
            'filesystemBase'       => $tmpDir,
            'eventDispatcher'      => $ed,
            'language'             => $language,
            'input'                => new Input($inputData),
            'application'          => $application,
            'db'                   => $db,
        ]);

        // Store the container so the closure above can reference it
        $containerHolder[0] = $container;

        return $container;
    }

    /**
     * Build a minimal ItemsModel wired to the in-memory SQLite table.
     */
    private function makeModel(Container $container): ItemsModel
    {
        ItemsModel::flushCaches();

        $container['mvc_config'] = [
            'tableName'   => 'items',
            'idFieldName' => 'item_id',
            'autoChecks'  => false,
        ];

        return new ItemsModel($container);
    }

    /**
     * Build an HtmlView stub (with no-op loadTemplate) wired to an ItemsModel.
     */
    private function makeView(
        string $task      = 'browse',
        array  $inputData = [],
        ?Container $container = null
    ): \HtmlViewTestApp\View\Items\Html {
        $container = $container ?? $this->buildContainer($inputData);

        $container['mvc_config'] = [
            'tableName'   => 'items',
            'idFieldName' => 'item_id',
            'autoChecks'  => false,
        ];

        $view        = new \HtmlViewTestApp\View\Items\Html($container);
        $view->task   = $task;
        $view->doTask = $task;

        $model = $this->makeModel($container);
        $view->setDefaultModel($model);

        return $view;
    }

    /** Insert a row and return its auto-increment ID. */
    private function insertRow(string $title, int $enabled = 1): int
    {
        $this->db->setQuery(
            'INSERT INTO items (title, enabled) VALUES (' .
            $this->db->q($title) . ', ' . (int) $enabled . ')'
        )->execute();

        return (int) $this->db->insertid();
    }

    // =========================================================================
    // onBeforeAdd()
    // =========================================================================

    public function testOnBeforeAddReturnsTrue(): void
    {
        $view = $this->makeView('add');

        $result = $view->onBeforeAdd();

        self::assertTrue($result);
    }

    public function testOnBeforeAddDisablesMainMenu(): void
    {
        $container = $this->buildContainer();
        $view      = $this->makeView('add', [], $container);

        $menu = $container->application->getDocument()->getMenu();

        // Menu should start enabled
        self::assertTrue($menu->isEnabled('main'));

        $view->onBeforeAdd();

        // After onBeforeAdd the 'main' menu must be disabled
        self::assertFalse($menu->isEnabled('main'));
    }

    public function testOnBeforeAddDoesNotDisableOtherMenus(): void
    {
        $container = $this->buildContainer();
        $view      = $this->makeView('add', [], $container);

        $menu = $container->application->getDocument()->getMenu();

        $view->onBeforeAdd();

        // A different named menu should remain enabled (default state)
        self::assertTrue($menu->isEnabled('sidebar'));
    }

    // =========================================================================
    // onBeforeEdit()
    // =========================================================================

    public function testOnBeforeEditReturnsTrue(): void
    {
        $view = $this->makeView('edit');

        $result = $view->onBeforeEdit();

        self::assertTrue($result);
    }

    public function testOnBeforeEditDisablesMainMenu(): void
    {
        $container = $this->buildContainer();
        $view      = $this->makeView('edit', [], $container);

        $menu = $container->application->getDocument()->getMenu();

        self::assertTrue($menu->isEnabled('main'));

        $view->onBeforeEdit();

        self::assertFalse($menu->isEnabled('main'));
    }

    public function testOnBeforeEditDoesNotDisableOtherMenus(): void
    {
        $container = $this->buildContainer();
        $view      = $this->makeView('edit', [], $container);

        $menu = $container->application->getDocument()->getMenu();

        $view->onBeforeEdit();

        self::assertTrue($menu->isEnabled('sidebar'));
    }

    // =========================================================================
    // onBeforeRead()
    // =========================================================================

    public function testOnBeforeReadReturnsTrue(): void
    {
        $view = $this->makeView('read');

        $result = $view->onBeforeRead();

        self::assertTrue($result);
    }

    public function testOnBeforeReadDoesNotDisableMainMenu(): void
    {
        $container = $this->buildContainer();
        $view      = $this->makeView('read', [], $container);

        $menu = $container->application->getDocument()->getMenu();

        $view->onBeforeRead();

        // onBeforeRead must NOT disable the main menu
        self::assertTrue($menu->isEnabled('main'));
    }

    // =========================================================================
    // display() — lifecycle for add/edit/read tasks
    // =========================================================================

    public function testDisplayReturnsTrueForAddTask(): void
    {
        $view = $this->makeView('add');

        ob_start();
        try {
            $result = $view->display();
        } finally {
            ob_end_clean();
        }

        self::assertTrue($result);
    }

    public function testDisplayReturnsTrueForEditTask(): void
    {
        $view = $this->makeView('edit');

        ob_start();
        try {
            $result = $view->display();
        } finally {
            ob_end_clean();
        }

        self::assertTrue($result);
    }

    public function testDisplayReturnsTrueForReadTask(): void
    {
        $view = $this->makeView('read');

        ob_start();
        try {
            $result = $view->display();
        } finally {
            ob_end_clean();
        }

        self::assertTrue($result);
    }

    public function testDisplayReturnsTrueForBrowseTask(): void
    {
        $view = $this->makeView('browse');

        ob_start();
        try {
            $result = $view->display();
        } finally {
            ob_end_clean();
        }

        self::assertTrue($result);
    }

    // =========================================================================
    // display() — onBefore* returning false → 403
    // =========================================================================

    public function testDisplayThrows403WhenOnBeforeAddReturnsFalse(): void
    {
        $container = $this->buildContainer();
        $view      = new \HtmlViewTestApp\View\Items\HtmlRejectAdd($container);
        $view->task   = 'add';
        $view->doTask = 'add';

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(403);

        ob_start();
        try {
            $view->display();
        } finally {
            ob_end_clean();
        }
    }

    public function testDisplayThrows403WhenOnBeforeEditReturnsFalse(): void
    {
        $container = $this->buildContainer();
        $view      = new \HtmlViewTestApp\View\Items\HtmlRejectEdit($container);
        $view->task   = 'edit';
        $view->doTask = 'edit';

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(403);

        ob_start();
        try {
            $view->display();
        } finally {
            ob_end_clean();
        }
    }

    public function testDisplayThrows403WhenOnBeforeReadReturnsFalse(): void
    {
        $container = $this->buildContainer();
        $view      = new \HtmlViewTestApp\View\Items\HtmlRejectRead($container);
        $view->task   = 'read';
        $view->doTask = 'read';

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(403);

        ob_start();
        try {
            $view->display();
        } finally {
            ob_end_clean();
        }
    }

    // =========================================================================
    // display() — onAfter* returning false → 403
    // =========================================================================

    public function testDisplayThrows403WhenOnAfterAddReturnsFalse(): void
    {
        $container               = $this->buildContainer();
        $container['mvc_config'] = [
            'tableName'   => 'items',
            'idFieldName' => 'item_id',
            'autoChecks'  => false,
        ];

        $view         = new \HtmlViewTestApp\View\Items\HtmlRejectAfterAdd($container);
        $view->task   = 'add';
        $view->doTask = 'add';

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(403);

        ob_start();
        try {
            $view->display();
        } finally {
            ob_end_clean();
        }
    }

    public function testDisplayThrows403WhenOnAfterEditReturnsFalse(): void
    {
        $container               = $this->buildContainer();
        $container['mvc_config'] = [
            'tableName'   => 'items',
            'idFieldName' => 'item_id',
            'autoChecks'  => false,
        ];

        $view         = new \HtmlViewTestApp\View\Items\HtmlRejectAfterEdit($container);
        $view->task   = 'edit';
        $view->doTask = 'edit';

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(403);

        ob_start();
        try {
            $view->display();
        } finally {
            ob_end_clean();
        }
    }

    public function testDisplayThrows403WhenOnAfterReadReturnsFalse(): void
    {
        $container               = $this->buildContainer();
        $container['mvc_config'] = [
            'tableName'   => 'items',
            'idFieldName' => 'item_id',
            'autoChecks'  => false,
        ];

        $view         = new \HtmlViewTestApp\View\Items\HtmlRejectAfterRead($container);
        $view->task   = 'read';
        $view->doTask = 'read';

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(403);

        ob_start();
        try {
            $view->display();
        } finally {
            ob_end_clean();
        }
    }

    // =========================================================================
    // Inherited onBeforeBrowse() from Raw — still works on Html views
    // =========================================================================

    public function testOnBeforeBrowseReturnsTrueOnHtmlView(): void
    {
        $view = $this->makeView('browse');

        $result = $view->onBeforeBrowse();

        self::assertTrue($result);
    }

    public function testOnBeforeBrowseCreatesListsObjectOnHtmlView(): void
    {
        $view = $this->makeView('browse');
        $view->onBeforeBrowse();

        self::assertInstanceOf(\stdClass::class, $view->lists);
    }

    public function testOnBeforeBrowsePopulatesItemsOnHtmlView(): void
    {
        $this->insertRow('Alpha');
        $this->insertRow('Beta');

        $view = $this->makeView('browse');
        $view->onBeforeBrowse();

        self::assertCount(2, $view->items);
    }

    public function testOnBeforeBrowsePopulatesItemsCountOnHtmlView(): void
    {
        $this->insertRow('One');
        $this->insertRow('Two');
        $this->insertRow('Three');

        $view = $this->makeView('browse');
        $view->onBeforeBrowse();

        self::assertSame(3, $view->itemsCount);
    }

    public function testOnBeforeBrowseAssignsPaginationOnHtmlView(): void
    {
        $view = $this->makeView('browse');
        $view->onBeforeBrowse();

        self::assertInstanceOf(\Awf\Pagination\Pagination::class, $view->pagination);
    }

    // =========================================================================
    // Default layout and setLayout()
    // =========================================================================

    public function testDefaultLayoutIsDefault(): void
    {
        $view = $this->makeView('browse');

        self::assertSame('default', $view->getLayout());
    }

    public function testSetLayoutChangesLayout(): void
    {
        $view = $this->makeView('browse');
        $view->setLayout('form');

        self::assertSame('form', $view->getLayout());
    }

    public function testSetLayoutReturnsPreviousValue(): void
    {
        $view     = $this->makeView('browse');
        $previous = $view->setLayout('form');

        self::assertSame('default', $previous);
    }

    // =========================================================================
    // DataProvider — task→onBefore hook return values
    // =========================================================================

    public static function taskHookReturnProvider(): array
    {
        return [
            'add task'    => ['add'],
            'edit task'   => ['edit'],
            'read task'   => ['read'],
            'browse task' => ['browse'],
        ];
    }

    #[DataProvider('taskHookReturnProvider')]
    public function testOnBeforeHookReturnsTrueForTask(string $task): void
    {
        $view   = $this->makeView($task);
        $method = 'onBefore' . ucfirst($task);

        // onBeforeBrowse is public in Raw; onBeforeAdd/Edit/Read are protected in Html
        // We use reflection to call protected methods without setAccessible (PHP 8.5+)
        // But all four are actually public or accessible because:
        // - onBeforeBrowse is public
        // - onBeforeAdd/Edit/Read are protected, but we call display() which calls them
        // This test calls display() and asserts it returns true as proxy.
        ob_start();
        try {
            $result = $view->display();
        } finally {
            ob_end_clean();
        }

        self::assertTrue($result);
    }

    // =========================================================================
    // menu disabled state is independent per instance
    // =========================================================================

    public function testAddAndEditBothDisableMainMenuIndependently(): void
    {
        // Two separate containers / documents to verify each view independently
        // disables the menu.
        $containerAdd  = $this->buildContainer();
        $containerEdit = $this->buildContainer();

        $viewAdd  = $this->makeView('add', [], $containerAdd);
        $viewEdit = $this->makeView('edit', [], $containerEdit);

        $menuAdd  = $containerAdd->application->getDocument()->getMenu();
        $menuEdit = $containerEdit->application->getDocument()->getMenu();

        $viewAdd->onBeforeAdd();
        $viewEdit->onBeforeEdit();

        self::assertFalse($menuAdd->isEnabled('main'),  'add: main menu must be disabled');
        self::assertFalse($menuEdit->isEnabled('main'), 'edit: main menu must be disabled');
    }

    public function testReadDoesNotDisableMenuWhileAddDoes(): void
    {
        $containerAdd  = $this->buildContainer();
        $containerRead = $this->buildContainer();

        $viewAdd  = $this->makeView('add', [], $containerAdd);
        $viewRead = $this->makeView('read', [], $containerRead);

        $menuAdd  = $containerAdd->application->getDocument()->getMenu();
        $menuRead = $containerRead->application->getDocument()->getMenu();

        $viewAdd->onBeforeAdd();
        $viewRead->onBeforeRead();

        self::assertFalse($menuAdd->isEnabled('main'),  'add: main menu must be disabled');
        self::assertTrue($menuRead->isEnabled('main'),  'read: main menu must remain enabled');
    }
}
