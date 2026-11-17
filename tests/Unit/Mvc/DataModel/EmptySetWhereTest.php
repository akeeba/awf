<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc\DataModel\EmptySetWhere\Model;

use Awf\Mvc\DataModel;

/**
 * Backed by "es_items": item_id INTEGER PK, title TEXT, enabled INTEGER (Number filter), flag TINYINT (Boolean filter).
 */
class Item extends DataModel
{
    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

namespace Awf\Tests\Unit\Mvc\DataModel;

use Awf\Container\Container;
use Awf\Database\Driver\Sqlite as SqliteDriver;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Session\Segment;
use Awf\Tests\Unit\Mvc\DataModel\EmptySetWhere\Model\Item;
use Awf\Text\Language;
use Awf\User\ManagerInterface as UserManagerInterface;
use Awf\User\UserInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * An explicit, programmatic DataModel::where() with an empty array for an "equals / in" method matches no rows, while an
 * empty request-state value keeps meaning "no filter".
 */
class EmptySetWhereTest extends TestCase
{
    private SqliteDriver $db;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        if (!SqliteDriver::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        Item::flushCaches();

        $this->db = new SqliteDriver(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $this->db->connect();
        $this->db->setQuery(
            'CREATE TABLE es_items (
                item_id INTEGER PRIMARY KEY AUTOINCREMENT,
                title   TEXT    NOT NULL,
                enabled INTEGER NOT NULL DEFAULT 1,
                flag    TINYINT NOT NULL DEFAULT 0
            )'
        )->execute();

        // Three rows: enabled 0 / 1 / 2, flag 0 / 1 / 1, titles "0" / "a" / "b"
        $this->db->setQuery("INSERT INTO es_items (title, enabled, flag) VALUES ('0', 0, 0), ('a', 1, 1), ('b', 2, 1)")
            ->execute();

        $tmpDir = sys_get_temp_dir();

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

        $this->container = new Container([
            'application_name'     => 'Testapp',
            'applicationNamespace' => '\\Testapp',
            'session_segment_name' => 'testapp_seg',
            'basePath'             => $tmpDir,
            'languagePath'         => $tmpDir,
            'temporaryPath'        => $tmpDir,
            'templatePath'         => $tmpDir,
            'sqlPath'              => $tmpDir,
            'filesystemBase'       => $tmpDir,
            'eventDispatcher'      => new EventDispatcher($this->createStub(Container::class)),
            'language'             => $language,
            'input'                => new Input([]),
            'application'          => $application,
            'segment'              => $segment,
            'userManager'          => $userManager,
            'db'                   => $this->db,
        ]);
    }

    private function makeModel(bool $ignoreRequest = true): Item
    {
        $this->container['mvc_config'] = [
            'tableName'      => 'es_items',
            'idFieldName'    => 'item_id',
            'autoChecks'     => false,
            'ignore_request' => $ignoreRequest,
        ];

        return new Item($this->container);
    }

    private function titles(Item $model): array
    {
        $titles = [];

        foreach ($model->get(true) as $item) {
            $titles[] = $item->title;
        }

        sort($titles);

        return $titles;
    }

    public static function emptySetCases(): array
    {
        $cases = [];

        foreach (['enabled' => 'Number', 'title' => 'Text', 'flag' => 'Boolean', 'item_id' => 'primary key'] as $field => $kind) {
            foreach (['in', '==', '=[]', '=()', '=', 'eq'] as $method) {
                $cases["$kind $field $method"] = [$field, $method];
            }
        }

        return $cases;
    }

    // ---- Item 19, rule 2 -------------------------------------------------------------------------------------------

    #[DataProvider('emptySetCases')]
    public function testExplicitEmptyArrayMatchesNoRows(string $field, string $method): void
    {
        $model = $this->makeModel();
        $model->where($field, $method, []);

        self::assertSame([], $this->titles($model));
        self::assertSame(0, (int) $model->count());
    }

    public function testEmptyArrayCombinesWithOtherFilters(): void
    {
        $model = $this->makeModel();
        $model->where('enabled', 'in', [])->where('flag', '==', 1);

        self::assertSame([], $this->titles($model));
    }

    public function testNonEmptyArrayStillWorks(): void
    {
        $model = $this->makeModel();
        $model->where('enabled', 'in', [1, 2]);

        self::assertSame(['a', 'b'], $this->titles($model));
    }

    public function testLaterWhereOnSameFieldReplacesTheEmptySetRule(): void
    {
        $model = $this->makeModel();
        $model->where('enabled', 'in', [])->where('enabled', 'in', [1]);

        self::assertSame(['a'], $this->titles($model));
    }

    public function testCloneKeepsItsOwnEmptySetRule(): void
    {
        $model = $this->makeModel();
        $clone = $model->getClone();
        $clone->where('enabled', 'in', []);

        self::assertSame([], $this->titles($clone));
        self::assertSame(['0', 'a', 'b'], $this->titles($model));
    }

    public function testClearStateRemovesTheEmptySetRule(): void
    {
        $model = $this->makeModel();
        $model->where('enabled', 'in', []);
        $model->clearState();

        self::assertSame(['0', 'a', 'b'], $this->titles($model));
    }

    public function testPrimaryKeyEmptySetIsGatedByIgnoreRequestLikeAnyValue(): void
    {
        // ignore_request on: the primary key filter applies (empty set -> nothing)
        self::assertSame(['0', 'a'], $this->titles($this->makeModel(true)->where('item_id', 'in', [1, 2])));
        self::assertSame([], $this->titles($this->makeModel(true)->where('item_id', 'in', [])));

        // ignore_request off: the primary key filter is skipped for any value, [] included
        $withValue = $this->makeModel(false)->where('item_id', 'in', [1]);
        $withEmpty = $this->makeModel(false)->where('item_id', 'in', []);
        self::assertSame($this->titles($withValue), $this->titles($withEmpty));
        self::assertSame(['0', 'a', 'b'], $this->titles($withEmpty));
    }

    // ---- Item 19, rule 3: left alone --------------------------------------------------------------------------------

    public static function untouchedCases(): array
    {
        return [
            '!= on Number'  => ['enabled', '!='],
            '<> on Number'  => ['enabled', '<>'],
            'neq on Number' => ['enabled', 'neq'],
            '!= on Text'    => ['title', '!='],
            'like on Text'  => ['title', 'like'],
            'between'       => ['enabled', '[]'],
            'outside'       => ['enabled', ')('],
        ];
    }

    #[DataProvider('untouchedCases')]
    public function testOtherMethodsWithEmptyArrayStillReturnEveryRow(string $field, string $method): void
    {
        $model = $this->makeModel();
        $model->where($field, $method, []);

        self::assertSame(['0', 'a', 'b'], $this->titles($model));
    }

    // ---- Request state is unchanged ---------------------------------------------------------------------------------

    public static function blankStates(): array
    {
        return [
            'Number []'  => ['enabled', []],
            'Number ""'  => ['enabled', ''],
            'Text []'    => ['title', []],
            'Text ""'    => ['title', ''],
            'Boolean ""' => ['flag', ''],
        ];
    }

    #[DataProvider('blankStates')]
    public function testBlankRequestStateMeansNoFilter(string $field, $value): void
    {
        $model = $this->makeModel();
        $model->setState($field, $value);

        self::assertSame(['0', 'a', 'b'], $this->titles($model));
        self::assertSame(3, (int) $model->count());
    }

    // ---- Item 19, rule 4: integer 0 is a value ----------------------------------------------------------------------

    public function testIntegerZeroIsAValueForEqualsAndIn(): void
    {
        foreach (['==', 'in', '=[]', '=()', '=', 'eq'] as $method) {
            self::assertSame(['0'], $this->titles($this->makeModel()->where('enabled', $method, 0)), "enabled $method 0");
            self::assertSame(['0'], $this->titles($this->makeModel()->where('flag', $method, 0)), "flag $method 0");
        }

        self::assertSame(['0'], $this->titles($this->makeModel()->where('title', '==', 0)));
    }

    public function testStringZeroStillWorks(): void
    {
        self::assertSame(['0'], $this->titles($this->makeModel()->where('enabled', '==', '0')));
        self::assertSame(['0'], $this->titles($this->makeModel()->where('enabled', 'in', ['0'])));
    }

    public function testBlankStringStillMeansNoFilterInExplicitWhere(): void
    {
        self::assertSame(['0', 'a', 'b'], $this->titles($this->makeModel()->where('enabled', '==', '')));
    }
}
