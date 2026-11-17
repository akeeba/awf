<?php

declare(strict_types=1);

/**
 * Fixture models for a database whose table name prefix contains a hyphen.
 *
 * They live under a "Model\" sub-namespace so that DataModel::getName() can derive the model name, and use the
 * "#__" placeholder so that the prefix is only resolved by the driver, exactly like a real application does.
 */

namespace Awf\Tests\Unit\Mvc\HyphenatedPrefix\Model;

use Awf\Mvc\DataModel;
use Awf\Mvc\TreeModel;

class Widget extends DataModel
{
    protected $tableName = '#__widgets';

    protected $idFieldName = 'widget_id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

class Branch extends TreeModel
{
    protected $tableName = '#__branches';

    protected $idFieldName = 'id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

// ============================================================
// Test class
// ============================================================

namespace Awf\Tests\Unit\Mvc\HyphenatedPrefix;

use Awf\Container\Container;
use Awf\Database\Driver\Sqlite as SqliteDriver;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Mvc\DataModel;
use Awf\Mvc\TreeModel;
use Awf\Session\Segment;
use Awf\Tests\Unit\Mvc\HyphenatedPrefix\Model\Branch;
use Awf\Tests\Unit\Mvc\HyphenatedPrefix\Model\Widget;
use Awf\Text\Language;
use Awf\User\ManagerInterface as UserManagerInterface;
use Awf\User\UserInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * DataModel and TreeModel against an in-memory SQLite database whose table name prefix contains a hyphen.
 *
 * An unquoted "web-app_widgets" is parsed by the database server as "web - app_widgets", so every query the models
 * build must quote the table name.
 *
 * The CRUD tests pass the known fields in the model configuration, so they exercise the model's own queries even
 * where the driver cannot read the table's columns. Reading the columns is covered separately by
 * testConstructorReadsColumnsOfTableWithHyphenatedPrefix().
 */
#[CoversClass(DataModel::class)]
#[CoversClass(TreeModel::class)]
#[CoversClass(SqliteDriver::class)]
class HyphenatedPrefixTest extends TestCase
{
    private const PREFIX = 'web-app_';

    private SqliteDriver $db;

    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        if (!SqliteDriver::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        // Both caches are static. The table list is only read once, so a stale list would hide our tables.
        Widget::flushCaches();
        Branch::flushCaches();

        $this->db = new SqliteDriver([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => self::PREFIX,
        ]);
        $this->db->connect();

        $this->db->setQuery(
            'CREATE TABLE ' . $this->db->qn('#__widgets') . ' (
                widget_id INTEGER PRIMARY KEY AUTOINCREMENT,
                title     TEXT    NOT NULL DEFAULT \'\'
            )'
        )->execute();

        $this->db->setQuery(
            'CREATE TABLE ' . $this->db->qn('#__branches') . ' (
                id    INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT    NOT NULL DEFAULT \'\',
                lft   INTEGER NOT NULL DEFAULT 0,
                rgt   INTEGER NOT NULL DEFAULT 0
            )'
        )->execute();

        $this->container = $this->buildContainer();
    }

    protected function tearDown(): void
    {
        Widget::flushCaches();
        Branch::flushCaches();

        $this->db->disconnect();
    }

    public function testTablesReallyHaveTheHyphenatedPrefix(): void
    {
        $tables = $this->db->getTableList();

        self::assertContains('web-app_widgets', $tables);
        self::assertContains('web-app_branches', $tables);
    }

    public function testConstructorReadsColumnsOfTableWithHyphenatedPrefix(): void
    {
        $model = new Widget($this->container);

        self::assertSame(['widget_id', 'title'], array_keys($model->getTableFields()));
    }

    public function testBuildQueryQuotesTableName(): void
    {
        $sql = $this->db->replacePrefix((string) $this->makeWidget()->buildQuery());

        self::assertStringContainsString('FROM `web-app_widgets`', $sql);
    }

    public function testGetReturnsRecords(): void
    {
        $this->insertWidget('One');
        $this->insertWidget('Two');

        $items = $this->makeWidget()->get(true);

        self::assertCount(2, $items);
    }

    public function testCountReturnsNumberOfRecords(): void
    {
        $this->insertWidget('One');
        $this->insertWidget('Two');

        self::assertSame(2, (int) $this->makeWidget()->count());
    }

    public function testSaveInsertsThenUpdatesRecord(): void
    {
        $model = $this->makeWidget();
        $model->save(['title' => 'Created']);

        $id = (int) $model->getId();
        self::assertGreaterThan(0, $id);
        self::assertSame('Created', $this->titleOf($id));

        $model->save(['title' => 'Updated']);

        self::assertSame('Updated', $this->titleOf($id));
    }

    public function testFindLoadsRecord(): void
    {
        $id = $this->insertWidget('Findable');

        $model = $this->makeWidget()->find($id);

        self::assertSame('Findable', $model->title);
    }

    public function testDeleteRemovesRecord(): void
    {
        $id = $this->insertWidget('Doomed');

        $this->makeWidget()->delete($id);

        self::assertSame(0, $this->countRows('#__widgets'));
    }

    public function testTreeModelForceDeleteRemovesNode(): void
    {
        $root = $this->makeBranch();
        $root->title = 'Root';
        $root->insertAsRoot();

        self::assertSame(1, $this->countRows('#__branches'));

        $this->makeBranch()->forceDelete($root->getId());

        self::assertSame(0, $this->countRows('#__branches'));
    }

    // -------------------------------------------------------------------------
    // SQLite driver
    // -------------------------------------------------------------------------

    public function testSqliteGetTableColumnsReadsTableWithHyphenatedPrefix(): void
    {
        $columns = $this->db->getTableColumns('#__widgets');

        self::assertSame(['widget_id', 'title'], array_map('strtolower', array_keys($columns)));
    }

    public function testSqliteGetTableKeysReadsTableWithHyphenatedPrefix(): void
    {
        $keys = $this->db->getTableKeys('#__widgets');

        self::assertSame(['widget_id'], array_map('strtolower', array_keys($keys)));
    }

    public function testSqliteRenameTableRenamesTableWithHyphenatedPrefix(): void
    {
        $this->db->renameTable('#__widgets', '#__gadgets');

        $tables = $this->db->getTableList();

        self::assertContains('web-app_gadgets', $tables);
        self::assertNotContains('web-app_widgets', $tables);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * A Widget model which does not read its columns from the database.
     */
    private function makeWidget(): Widget
    {
        $this->container['mvc_config'] = [
            'knownFields' => [
                'widget_id' => $this->field('widget_id', 'INTEGER', 'PRI'),
                'title'     => $this->field('title', 'TEXT'),
            ],
        ];

        try {
            return new Widget($this->container);
        } finally {
            $this->container['mvc_config'] = [];
        }
    }

    /**
     * A Branch model which does not read its columns from the database.
     */
    private function makeBranch(): Branch
    {
        $this->container['mvc_config'] = [
            'knownFields' => [
                'id'    => $this->field('id', 'INTEGER', 'PRI'),
                'title' => $this->field('title', 'TEXT'),
                'lft'   => $this->field('lft', 'INTEGER'),
                'rgt'   => $this->field('rgt', 'INTEGER'),
            ],
        ];

        try {
            return new Branch($this->container);
        } finally {
            $this->container['mvc_config'] = [];
        }
    }

    /**
     * A column definition in the format the SQLite driver's getTableColumns() returns.
     */
    private function field(string $name, string $type, string $key = ''): object
    {
        return (object) [
            'Field'   => $name,
            'Type'    => $type,
            'Null'    => $key === 'PRI' ? 'NO' : 'YES',
            'Default' => null,
            'Key'     => $key,
        ];
    }

    private function insertWidget(string $title): int
    {
        $this->db->setQuery(
            'INSERT INTO ' . $this->db->qn('#__widgets') . ' (' . $this->db->qn('title') . ') VALUES ('
            . $this->db->q($title) . ')'
        )->execute();

        return (int) $this->db->insertid();
    }

    private function titleOf(int $id): ?string
    {
        $title = $this->db->setQuery(
            'SELECT ' . $this->db->qn('title') . ' FROM ' . $this->db->qn('#__widgets')
            . ' WHERE ' . $this->db->qn('widget_id') . ' = ' . $id
        )->loadResult();

        return $title === null ? null : (string) $title;
    }

    private function countRows(string $table): int
    {
        return (int) $this->db->setQuery('SELECT COUNT(*) FROM ' . $this->db->qn($table))->loadResult();
    }

    private function buildContainer(): Container
    {
        $tmpDir = sys_get_temp_dir();

        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturn([]);

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);

        $segment = $this->createMock(Segment::class);
        $segment->method('get')->willReturn(0);
        $segment->method('__get')->willReturn(null);

        $application = $this->createMock(\Awf\Application\Application::class);
        $application->method('getName')->willReturn('Testapp');

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn(0);

        $userManager = $this->createMock(UserManagerInterface::class);
        $userManager->method('getUser')->willReturn($user);

        return new Container([
            'application_name'     => 'Testapp',
            'applicationNamespace' => '\\Testapp',
            'session_segment_name' => 'testapp_seg',
            'basePath'             => $tmpDir,
            'languagePath'         => $tmpDir,
            'temporaryPath'        => $tmpDir,
            'templatePath'         => $tmpDir,
            'sqlPath'              => $tmpDir,
            'filesystemBase'       => $tmpDir,
            'eventDispatcher'      => $ed,
            'language'             => $language,
            'input'                => new Input([]),
            'application'          => $application,
            'segment'              => $segment,
            'userManager'          => $userManager,
            'db'                   => $this->db,
        ]);
    }
}
