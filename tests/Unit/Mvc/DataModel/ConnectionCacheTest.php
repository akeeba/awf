<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc\ConnectionCache\Model;

use Awf\Mvc\DataModel;

class Widget extends DataModel
{
    protected $tableName = '#__widgets';

    protected $idFieldName = 'widget_id';
}

// ============================================================
// Test class
// ============================================================

namespace Awf\Tests\Unit\Mvc\ConnectionCache;

use Awf\Container\Container;
use Awf\Database\Driver\Sqlite as SqliteDriver;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Mvc\DataModel;
use Awf\Mvc\DataModel\Exception\NoTableColumns;
use Awf\Session\Segment;
use Awf\Tests\Unit\Mvc\ConnectionCache\Model\Widget;
use Awf\Text\Language;
use Awf\User\ManagerInterface as UserManagerInterface;
use Awf\User\UserInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * DataModel keeps its table list and column cache per database connection.
 *
 * Two connections with different table name prefixes (and different schemas) must not see each other's tables and
 * columns, whatever the order in which their models are created.
 */
#[CoversClass(DataModel::class)]
class ConnectionCacheTest extends TestCase
{
    private const COLUMNS = 'widget_id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT';

    /** @var SqliteDriver[] */
    private array $drivers = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (!SqliteDriver::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        $this->resetCaches();
    }

    protected function tearDown(): void
    {
        $this->resetCaches();

        foreach ($this->drivers as $driver) {
            $driver->disconnect();
        }

        $this->drivers = [];
    }

    public function testTwoConnectionsWithDifferentPrefixesSeeTheirOwnColumns(): void
    {
        $dbA = $this->connect('a_', self::COLUMNS);
        $dbB = $this->connect('b_', self::COLUMNS . ', extra TEXT');

        $modelA = new Widget($this->buildContainer($dbA));
        $modelB = new Widget($this->buildContainer($dbB));

        self::assertSame(['widget_id', 'title'], array_keys($modelA->getTableFields()));
        self::assertSame(['widget_id', 'title', 'extra'], array_keys($modelB->getTableFields()));
    }

    public function testConnectionWithoutTheTableIsNotPoisonedByAnotherConnection(): void
    {
        $dbA = $this->connect('a_', null);
        $dbB = $this->connect('b_', self::COLUMNS);

        // B's columns are cached first. A has no such table, so its model cannot be created at all.
        self::assertSame(['widget_id', 'title'], array_keys((new Widget($this->buildContainer($dbB)))->getTableFields()));

        try {
            new Widget($this->buildContainer($dbA));
            self::fail('A model on a connection without the table must not borrow the columns of another connection.');
        } catch (NoTableColumns $e) {
            self::assertStringContainsString('#__widgets', $e->getMessage());
        }

        // B is unaffected
        self::assertSame(['widget_id', 'title'], array_keys((new Widget($this->buildContainer($dbB)))->getTableFields()));
    }

    public function testSamePrefixOnTwoConnectionsWithDifferentSchemas(): void
    {
        $dbA = $this->connect('same_', self::COLUMNS);
        $dbB = $this->connect('same_', 'widget_id INTEGER PRIMARY KEY AUTOINCREMENT, other TEXT');

        self::assertSame(['widget_id', 'title'], array_keys((new Widget($this->buildContainer($dbA)))->getTableFields()));
        self::assertSame(['widget_id', 'other'], array_keys((new Widget($this->buildContainer($dbB)))->getTableFields()));
    }

    public function testModelsOnTheSameConnectionShareTheCache(): void
    {
        $db = $this->connect('a_', self::COLUMNS);

        (new Widget($this->buildContainer($db)))->getTableFields();

        // Dropping the table behind the models' back: a second model on the same connection still uses the cache
        $db->setQuery('DROP TABLE ' . $db->qn('#__widgets'))->execute();

        self::assertSame(['widget_id', 'title'], array_keys((new Widget($this->buildContainer($db)))->getTableFields()));
    }

    public function testRecycledObjectIdOfCollectedDriverDoesNotReuseItsCache(): void
    {
        // Prime the cache for driver A, then let A be collected.
        $dbA = $this->connect('a_', self::COLUMNS);
        $idA = spl_object_id($dbA);

        $modelA = new Widget($this->buildContainer($dbA));
        $modelA->getTableFields();

        $this->drivers = [];
        $dbA->disconnect();
        unset($modelA);
        gc_collect_cycles();
        unset($dbA); // last, so that its object ID is the first to be handed out again
        gc_collect_cycles();

        // PHP hands out freed object IDs last-freed-first. Drain the free list with throwaway objects until one of
        // them sits in A's old slot, then free that one so that the next object created takes the slot.
        $fillers = [];

        for ($i = 0; $i < 10000; $i++) {
            $filler = new \stdClass();

            if (spl_object_id($filler) === $idA) {
                break;
            }

            $fillers[] = $filler;
        }

        if (spl_object_id($filler) !== $idA) {
            $this->markTestSkipped('PHP did not reuse the object ID of the collected driver.');
        }

        unset($filler);

        // Same prefix, so that only the connection key can tell the two apart.
        $dbB = $this->connect('a_', 'widget_id INTEGER PRIMARY KEY AUTOINCREMENT, other TEXT');

        if (spl_object_id($dbB) !== $idA) {
            $this->markTestSkipped('The new driver did not get the object ID of the collected driver.');
        }

        self::assertSame(['widget_id', 'other'], array_keys((new Widget($this->buildContainer($dbB)))->getTableFields()));
    }

    private function connect(string $prefix, ?string $columns): SqliteDriver
    {
        $db = new SqliteDriver([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => $prefix,
        ]);
        $db->connect();

        if ($columns !== null) {
            $db->setQuery('CREATE TABLE ' . $db->qn('#__widgets') . ' (' . $columns . ')')->execute();
        }

        return $this->drivers[] = $db;
    }

    /**
     * The caches are static and survive between tests. Their shape is an implementation detail, so empty them
     * through reflection.
     */
    private function resetCaches(): void
    {
        $ref = new ReflectionClass(DataModel::class);

        foreach (['tableCache', 'tableFieldCache', 'prefixCasePermutations'] as $name) {
            $prop = $ref->getProperty($name);
            $prop->setValue(null, []);
        }
    }

    private function buildContainer(SqliteDriver $db): Container
    {
        $tmpDir = sys_get_temp_dir();

        $ed = $this->createStub(EventDispatcher::class);
        $ed->method('trigger')->willReturn([]);

        $language = $this->createStub(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);

        $segment = $this->createStub(Segment::class);
        $segment->method('get')->willReturn(0);
        $segment->method('__get')->willReturn(null);

        $application = $this->createStub(\Awf\Application\Application::class);
        $application->method('getName')->willReturn('Testapp');

        $user = $this->createStub(UserInterface::class);
        $user->method('getId')->willReturn(0);

        $userManager = $this->createStub(UserManagerInterface::class);
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
            'db'                   => $db,
        ]);
    }
}
