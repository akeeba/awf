<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Mvc\HyphenatedPrefix;

use Awf\Container\Container;
use Awf\Database\Driver;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Session\Segment;
use Awf\Tests\Integration\AbstractIntegrationTestCase;
use Awf\Tests\Integration\Mvc\HyphenatedPrefix\Model\Branch;
use Awf\Tests\Integration\Mvc\HyphenatedPrefix\Model\Widget;
use Awf\Text\Language;
use Awf\User\ManagerInterface as UserManagerInterface;
use Awf\User\UserInterface;

/**
 * DataModel, TreeModel and driver operations against a real database server, on tables whose name prefix contains a
 * hyphen ("web-app_").
 *
 * Unquoted, "web-app_widgets" is parsed as "web - app_widgets" (MariaDB error 1064 on Akeeba Solo's Manage Backups
 * page). Each concrete subclass connects one driver and creates the schema in that server's dialect.
 */
abstract class HyphenatedPrefixTestCase extends AbstractIntegrationTestCase
{
    protected const PREFIX = 'web-app_';

    /** Every table a test may leave behind, with the "#__" placeholder. */
    private const TABLES = ['#__widgets', '#__gadgets', '#__branches'];

    protected ?Driver $db = null;

    private ?Container $container = null;

    /**
     * Connect to the server under test, using self::PREFIX as the table name prefix. Skip if it is not configured.
     */
    abstract protected function connectDriver(): Driver;

    /**
     * Create #__widgets (widget_id auto-increment primary key, title string) and #__branches (id auto-increment
     * primary key, title string, lft int, rgt int).
     */
    abstract protected function createSchema(): void;

    protected function setUp(): void
    {
        $this->db = $this->connectDriver();

        Widget::flushCaches();
        Branch::flushCaches();

        $this->dropTables();
        $this->createSchema();

        $this->container = $this->buildContainer();
    }

    protected function tearDown(): void
    {
        if ($this->db !== null) {
            $this->dropTables();
            $this->db->disconnect();
        }

        Widget::flushCaches();
        Branch::flushCaches();

        $this->db        = null;
        $this->container = null;
    }

    public function testTablesReallyHaveTheHyphenatedPrefix(): void
    {
        $tables = $this->db->getTableList();

        self::assertContains('web-app_widgets', $tables);
        self::assertContains('web-app_branches', $tables);
    }

    public function testGetTableFieldsReadsColumns(): void
    {
        $model = new Widget($this->container);

        self::assertSame(['widget_id', 'title'], array_keys($model->getTableFields()));
    }

    public function testGetReturnsRecords(): void
    {
        $this->insertWidget('One');
        $this->insertWidget('Two');

        $items = (new Widget($this->container))->get(true);

        self::assertCount(2, $items);
    }

    public function testCountReturnsNumberOfRecords(): void
    {
        $this->insertWidget('One');
        $this->insertWidget('Two');

        self::assertSame(2, (int) (new Widget($this->container))->count());
    }

    public function testSaveInsertsThenUpdatesRecord(): void
    {
        $model = new Widget($this->container);
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

        $model = (new Widget($this->container))->find($id);

        self::assertSame('Findable', $model->title);
    }

    public function testDeleteRemovesRecord(): void
    {
        $keep = $this->insertWidget('Kept');
        $gone = $this->insertWidget('Doomed');

        (new Widget($this->container))->delete($gone);

        self::assertSame(1, $this->countRows('#__widgets'));
        self::assertSame('Kept', $this->titleOf($keep));
    }

    public function testTreeModelForceDeleteRemovesNode(): void
    {
        $root        = new Branch($this->container);
        $root->title = 'Root';
        $root->insertAsRoot();

        self::assertSame(1, $this->countRows('#__branches'));

        (new Branch($this->container))->forceDelete($root->getId());

        self::assertSame(0, $this->countRows('#__branches'));
    }

    public function testDriverUpdateObjectUpdatesRow(): void
    {
        $id = $this->insertWidget('Before');

        $row = (object) ['widget_id' => $id, 'title' => 'After'];
        $this->db->updateObject('#__widgets', $row, 'widget_id');

        self::assertSame('After', $this->titleOf($id));
    }

    public function testDriverGetTableColumnsReadsColumns(): void
    {
        $columns = $this->db->getTableColumns('#__widgets');

        self::assertSame(['widget_id', 'title'], array_keys($columns));
    }

    public function testDriverRenameTableRenamesTable(): void
    {
        $this->db->renameTable(self::PREFIX . 'widgets', self::PREFIX . 'gadgets');

        $tables = $this->db->getTableList();

        self::assertContains('web-app_gadgets', $tables);
        self::assertNotContains('web-app_widgets', $tables);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function insertWidget(string $title): int
    {
        $row = (object) ['title' => $title];
        $this->db->insertObject('#__widgets', $row, 'widget_id');

        return (int) $row->widget_id;
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

    private function dropTables(): void
    {
        foreach (self::TABLES as $table) {
            try {
                $this->db->setQuery('DROP TABLE IF EXISTS ' . $this->db->qn($table))->execute();
            } catch (\Throwable $e) {
                // The table may not exist.
            }
        }
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

    /**
     * Split a "scheme:key=value;key=value" PDO DSN into its parts.
     *
     * @return array{host: string, port: int, dbname: string}
     */
    protected static function parseDsn(string $dsn, int $defaultPort): array
    {
        $ret = ['host' => '127.0.0.1', 'port' => $defaultPort, 'dbname' => ''];
        $dsn = preg_replace('/^[a-z]+:/i', '', $dsn);

        foreach (preg_split('/[;\s]+/', $dsn, -1, PREG_SPLIT_NO_EMPTY) as $segment) {
            [$key, $val] = array_map('trim', explode('=', $segment, 2)) + ['', ''];

            switch (strtolower($key)) {
                case 'host':
                    $ret['host'] = $val;
                    break;

                case 'port':
                    $ret['port'] = (int) $val;
                    break;

                case 'dbname':
                case 'database':
                    $ret['dbname'] = $val;
                    break;
            }
        }

        return $ret;
    }
}
