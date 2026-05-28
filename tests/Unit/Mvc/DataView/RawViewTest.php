<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc\DataView;

require_once __DIR__ . '/Fixtures/RawViewStubs.php';

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Database\Driver\Sqlite as SqliteDriver;
use Awf\Document\Raw as RawDocument;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Mvc\DataView\Raw as RawView;
use Awf\Pagination\Pagination;
use Awf\Text\Language;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RawViewTestApp\Model\Items as ItemsModel;
use RawViewTestApp\Model\Item as ItemModel;

/**
 * Tests for Awf\Mvc\DataView\Raw — raw passthrough/browse lifecycle.
 *
 * Covered:
 *  - onBeforeBrowse() returns true
 *  - onBeforeBrowse() creates $lists object
 *  - onBeforeBrowse() sets $lists->order from model state (default = id field name)
 *  - onBeforeBrowse() sets $lists->order_Dir from model state (default = DESC)
 *  - onBeforeBrowse() sets $lists->limitStart from model state (default = 0)
 *  - onBeforeBrowse() sets $lists->limit from model state (default = 0)
 *  - onBeforeBrowse() sets $lists->order from model state when explicitly set
 *  - onBeforeBrowse() sets $lists->order_Dir from model state when explicitly set
 *  - onBeforeBrowse() sets $lists->limitStart from model state when explicitly set
 *  - onBeforeBrowse() sets $lists->limit from model state when explicitly set
 *  - onBeforeBrowse() assigns items from model->get()
 *  - onBeforeBrowse() assigns itemsCount from model->count()
 *  - onBeforeBrowse() assigns a Pagination object to $this->pagination
 *  - onBeforeBrowse() pagination total matches model->count()
 *  - onBeforeBrowse() pagination limitStart matches state
 *  - onBeforeBrowse() pagination limit matches state
 *  - display() returns true when browse succeeds
 *  - display() throws 403 when onBeforeBrowse returns false
 *  - display() throws 403 when onAfterBrowse returns false
 *  - onBeforeBrowse() populates items correctly with multiple rows
 *  - onBeforeBrowse() populates items as empty collection when no rows
 */
class RawViewTest extends TestCase
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

    private function buildContainer(array $inputData = []): Container
    {
        $tmpDir = sys_get_temp_dir();

        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturn([]);

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);

        $rawDoc = $this->createMock(RawDocument::class);

        $application = $this->createMock(Application::class);
        $application->method('getName')->willReturn('RawViewTestApp');
        $application->method('getTemplate')->willReturn('default');
        $application->method('getDocument')->willReturn($rawDoc);

        $db = $this->db;

        return new Container([
            'application_name'     => 'RawViewTestApp',
            'applicationNamespace' => '\\RawViewTestApp',
            'session_segment_name' => 'rawviewtestapp_seg',
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
     * Build a RawView stub (with no-op loadTemplate) wired to an ItemsModel.
     */
    private function makeView(
        string $task      = 'browse',
        array  $inputData = []
    ): \RawViewTestApp\View\Items\Raw {
        $container = $this->buildContainer($inputData);

        $container['mvc_config'] = [
            'tableName'   => 'items',
            'idFieldName' => 'item_id',
            'autoChecks'  => false,
        ];

        $view        = new \RawViewTestApp\View\Items\Raw($container);
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
    // onBeforeBrowse() — return value
    // =========================================================================

    public function testOnBeforeBrowseReturnsTrue(): void
    {
        $view = $this->makeView('browse');

        $result = $view->onBeforeBrowse();

        self::assertTrue($result);
    }

    // =========================================================================
    // onBeforeBrowse() — $lists object creation
    // =========================================================================

    public function testOnBeforeBrowseCreatesListsObject(): void
    {
        $view = $this->makeView('browse');
        $view->onBeforeBrowse();

        self::assertInstanceOf(\stdClass::class, $view->lists);
    }

    // =========================================================================
    // onBeforeBrowse() — ordering defaults
    // =========================================================================

    public function testOnBeforeBrowseSetsOrderDefaultToIdFieldName(): void
    {
        $view = $this->makeView('browse');
        $view->onBeforeBrowse();

        // Default value comes from model->getIdFieldName() = 'item_id'
        self::assertSame('item_id', $view->lists->order);
    }

    public function testOnBeforeBrowseSetsOrderDirDefaultToDesc(): void
    {
        $view = $this->makeView('browse');
        $view->onBeforeBrowse();

        self::assertSame('DESC', $view->lists->order_Dir);
    }

    public function testOnBeforeBrowseReadsOrderFromModelState(): void
    {
        $view  = $this->makeView('browse');
        $model = $view->getModel();
        $model->setState('filter_order', 'title');

        $view->onBeforeBrowse();

        self::assertSame('title', $view->lists->order);
    }

    public function testOnBeforeBrowseReadsOrderDirFromModelState(): void
    {
        $view  = $this->makeView('browse');
        $model = $view->getModel();
        $model->setState('filter_order_Dir', 'ASC');

        $view->onBeforeBrowse();

        self::assertSame('ASC', $view->lists->order_Dir);
    }

    // =========================================================================
    // onBeforeBrowse() — pagination state defaults
    // =========================================================================

    public function testOnBeforeBrowseSetsLimitStartDefaultToZero(): void
    {
        $view = $this->makeView('browse');
        $view->onBeforeBrowse();

        self::assertSame(0, $view->lists->limitStart);
    }

    public function testOnBeforeBrowseSetsLimitDefaultToZero(): void
    {
        $view = $this->makeView('browse');
        $view->onBeforeBrowse();

        self::assertSame(0, $view->lists->limit);
    }

    public function testOnBeforeBrowseReadsLimitStartFromModelState(): void
    {
        $view  = $this->makeView('browse');
        $model = $view->getModel();
        $model->setState('limitstart', 20);

        $view->onBeforeBrowse();

        self::assertSame(20, $view->lists->limitStart);
    }

    public function testOnBeforeBrowseReadsLimitFromModelState(): void
    {
        $view  = $this->makeView('browse');
        $model = $view->getModel();
        $model->setState('limit', 5);

        $view->onBeforeBrowse();

        self::assertSame(5, $view->lists->limit);
    }

    // =========================================================================
    // onBeforeBrowse() — items & itemsCount
    // =========================================================================

    public function testOnBeforeBrowseAssignsItemsFromModel(): void
    {
        $this->insertRow('Alpha');
        $this->insertRow('Beta');

        $view = $this->makeView('browse');
        $view->onBeforeBrowse();

        // items should be a traversable collection with 2 elements
        $items = $view->items;
        self::assertNotNull($items);
        self::assertCount(2, $items);
    }

    public function testOnBeforeBrowseAssignsEmptyItemsWhenNoRows(): void
    {
        $view = $this->makeView('browse');
        $view->onBeforeBrowse();

        self::assertCount(0, $view->items);
    }

    public function testOnBeforeBrowseAssignsItemsCountFromModel(): void
    {
        $this->insertRow('One');
        $this->insertRow('Two');
        $this->insertRow('Three');

        $view = $this->makeView('browse');
        $view->onBeforeBrowse();

        self::assertSame(3, $view->itemsCount);
    }

    public function testOnBeforeBrowseItemsCountIsZeroWhenNoRows(): void
    {
        $view = $this->makeView('browse');
        $view->onBeforeBrowse();

        self::assertSame(0, $view->itemsCount);
    }

    // =========================================================================
    // onBeforeBrowse() — Pagination object
    // =========================================================================

    public function testOnBeforeBrowseAssignsPaginationObject(): void
    {
        $view = $this->makeView('browse');
        $view->onBeforeBrowse();

        self::assertInstanceOf(Pagination::class, $view->pagination);
    }

    public function testOnBeforeBrowsePaginationTotalMatchesItemsCount(): void
    {
        $this->insertRow('A');
        $this->insertRow('B');

        $view = $this->makeView('browse');
        $view->onBeforeBrowse();

        self::assertSame($view->itemsCount, $view->pagination->total);
    }

    public function testOnBeforeBrowsePaginationLimitStartMatchesState(): void
    {
        // Insert enough rows so Pagination does not reset limitStart to 0.
        // Pagination resets limitStart=0 when limit=0 (show all), so provide
        // a non-zero limit along with a limitStart that is valid.
        for ($i = 0; $i < 20; $i++) {
            $this->insertRow("Row$i");
        }

        $view  = $this->makeView('browse');
        $model = $view->getModel();
        $model->setState('limitstart', 10);
        $model->setState('limit', 5);

        $view->onBeforeBrowse();

        self::assertSame(10, $view->pagination->limitStart);
    }

    public function testOnBeforeBrowsePaginationLimitMatchesState(): void
    {
        $this->insertRow('Row1');

        $view  = $this->makeView('browse');
        $model = $view->getModel();
        $model->setState('limit', 15);

        $view->onBeforeBrowse();

        self::assertSame(15, $view->pagination->limit);
    }

    // =========================================================================
    // display() — lifecycle
    // =========================================================================

    public function testDisplayReturnsTrueOnSuccess(): void
    {
        $this->insertRow('Row');

        $view = $this->makeView('browse');

        ob_start();
        try {
            $result = $view->display();
        } finally {
            ob_end_clean();
        }

        self::assertTrue($result);
    }

    public function testDisplayThrowsOn403WhenOnBeforeBrowseReturnsFalse(): void
    {
        $container = $this->buildContainer();

        $view = new \RawViewTestApp\View\Items\RawRejectBrowse($container);
        $view->task   = 'browse';
        $view->doTask = 'browse';

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(403);

        ob_start();
        try {
            $view->display();
        } finally {
            ob_end_clean();
        }
    }

    public function testDisplayThrowsOn403WhenOnAfterBrowseReturnsFalse(): void
    {
        $container = $this->buildContainer();

        $container['mvc_config'] = [
            'tableName'   => 'items',
            'idFieldName' => 'item_id',
            'autoChecks'  => false,
        ];

        $view = new \RawViewTestApp\View\Items\RawRejectAfterBrowse($container);
        $view->task   = 'browse';
        $view->doTask = 'browse';

        $model = $this->makeModel($container);
        $view->setDefaultModel($model);

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
    // DataProvider — ordering state variants
    // =========================================================================

    public static function orderingStateProvider(): array
    {
        return [
            'default order / default dir' => [null, null, 'item_id', 'DESC'],
            'custom order / default dir'  => ['title', null, 'title', 'DESC'],
            'default order / ASC dir'     => [null, 'ASC', 'item_id', 'ASC'],
            'custom order / ASC dir'      => ['enabled', 'ASC', 'enabled', 'ASC'],
        ];
    }

    #[DataProvider('orderingStateProvider')]
    public function testOrderingStateResolvedCorrectly(
        ?string $stateOrder,
        ?string $stateDir,
        string  $expectedOrder,
        string  $expectedDir
    ): void {
        $view  = $this->makeView('browse');
        $model = $view->getModel();

        if ($stateOrder !== null) {
            $model->setState('filter_order', $stateOrder);
        }
        if ($stateDir !== null) {
            $model->setState('filter_order_Dir', $stateDir);
        }

        $view->onBeforeBrowse();

        self::assertSame($expectedOrder, $view->lists->order);
        self::assertSame($expectedDir, $view->lists->order_Dir);
    }

    // =========================================================================
    // DataProvider — pagination state variants
    // =========================================================================

    public static function listsStateProvider(): array
    {
        // Verifies that $lists->limitStart and $lists->limit reflect the raw model
        // state values (before any Pagination normalisation).
        return [
            'defaults'          => [null, null, 0, 0],
            'custom limit only' => [null, 10, 0, 10],
            'custom both'       => [20, 5, 20, 5],
        ];
    }

    #[DataProvider('listsStateProvider')]
    public function testListsStateResolvedCorrectly(
        ?int $stateLimitStart,
        ?int $stateLimit,
        int  $expectedLimitStart,
        int  $expectedLimit
    ): void {
        $view  = $this->makeView('browse');
        $model = $view->getModel();

        if ($stateLimitStart !== null) {
            $model->setState('limitstart', $stateLimitStart);
        }
        if ($stateLimit !== null) {
            $model->setState('limit', $stateLimit);
        }

        $view->onBeforeBrowse();

        self::assertSame($expectedLimitStart, $view->lists->limitStart);
        self::assertSame($expectedLimit, $view->lists->limit);
    }
}
