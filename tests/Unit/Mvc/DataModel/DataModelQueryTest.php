<?php

declare(strict_types=1);

/**
 * Concrete DataModel subclass used as a fixture for query-building tests.
 *
 * Kept in a "Model\" sub-namespace so that DataModel::getName() can derive
 * the model name ("Item") from the class name.
 */

namespace Awf\Tests\Unit\Mvc\DataModel\Query\Model;

use Awf\Mvc\DataModel;

/**
 * Minimal DataModel fixture backed by the "items" table:
 *   item_id  INTEGER PRIMARY KEY AUTOINCREMENT
 *   title    TEXT NOT NULL
 *   enabled  INTEGER NOT NULL DEFAULT 1
 *   ordering INTEGER DEFAULT 0
 */
class Item extends DataModel
{
    /** Reset the protected static caches so each test starts clean. */
    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }

    /**
     * A scope that filters only enabled items (enabled = 1).
     */
    public function scopeEnabled(): void
    {
        $this->where('enabled', '=', 1);
    }

    /**
     * A scope that filters items with ordering >= a given value.
     */
    public function scopeMinOrdering(int $min = 0): void
    {
        $this->where('ordering', 'ge', $min);
    }
}

// ============================================================
// Test class
// ============================================================

namespace Awf\Tests\Unit\Mvc\DataModel;

use Awf\Container\Container;
use Awf\Database\Driver\Sqlite as SqliteDriver;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Mvc\DataModel\Exception\InvalidSearchMethod;
use Awf\Session\Segment;
use Awf\Text\Language;
use Awf\User\ManagerInterface as UserManagerInterface;
use Awf\User\UserInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for DataModel state & query building against in-memory SQLite.
 *
 * Covers: buildQuery(), state filters → WHERE generation, where()/whereRaw,
 * get()/getItemsArray(), count(), ordering/limit (orderBy/skip/take), and scopes.
 */
class DataModelQueryTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Setup / helpers
    // -------------------------------------------------------------------------

    private SqliteDriver $db;
    private Container    $container;

    protected function setUp(): void
    {
        parent::setUp();

        if (!SqliteDriver::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        \Awf\Tests\Unit\Mvc\DataModel\Query\Model\Item::flushCaches();

        // ---- In-memory SQLite driver ----
        $this->db = new SqliteDriver([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $this->db->connect();

        $this->db->setQuery(
            'CREATE TABLE items (
                item_id  INTEGER PRIMARY KEY AUTOINCREMENT,
                title    TEXT    NOT NULL,
                enabled  INTEGER NOT NULL DEFAULT 1,
                ordering INTEGER NOT NULL DEFAULT 0
            )'
        )->execute();

        // ---- Minimal Container ----
        $tmpDir = sys_get_temp_dir();

        // Use a real EventDispatcher so that behaviours work correctly.
        $realEd = new \Awf\Event\Dispatcher($this->createStub(Container::class));

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);

        $input   = new Input([]);
        $segment = $this->createMock(Segment::class);
        $segment->method('get')->willReturn(0);
        $segment->method('__get')->willReturn(null);

        $application = $this->createMock(\Awf\Application\Application::class);
        $application->method('getName')->willReturn('Testapp');

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn(0);

        $userManager = $this->createMock(UserManagerInterface::class);
        $userManager->method('getUser')->willReturn($user);

        $db = $this->db;

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
            'eventDispatcher'      => $realEd,
            'language'             => $language,
            'input'                => $input,
            'application'          => $application,
            'segment'              => $segment,
            'userManager'          => $userManager,
            'db'                   => $db,
        ]);
    }

    /**
     * Create a fresh Item model, optionally merging extra mvc_config keys.
     */
    private function makeModel(array $mvcConfig = []): \Awf\Tests\Unit\Mvc\DataModel\Query\Model\Item
    {
        $config = array_merge([
            'tableName'      => 'items',
            'idFieldName'    => 'item_id',
            'autoChecks'     => false,
            'ignore_request' => true,
        ], $mvcConfig);

        $this->container['mvc_config'] = $config;

        return new \Awf\Tests\Unit\Mvc\DataModel\Query\Model\Item($this->container);
    }

    /** Insert a raw row directly. */
    private function insertRaw(string $title, int $enabled = 1, int $ordering = 0): int
    {
        $this->db->setQuery(
            "INSERT INTO items (title, enabled, ordering) VALUES ('{$title}', {$enabled}, {$ordering})"
        )->execute();

        return (int) $this->db->insertid();
    }

    // -------------------------------------------------------------------------
    // buildQuery() — basic structure
    // -------------------------------------------------------------------------

    public function testBuildQuerySelectsFromCorrectTable(): void
    {
        $model = $this->makeModel();
        $query = $model->buildQuery(true);

        $sql = (string) $query;
        self::assertStringContainsString('items', $sql);
    }

    public function testBuildQueryWithOverrideLimitsOmitsOrderBy(): void
    {
        $model = $this->makeModel();
        // overrideLimits = true → no ORDER BY appended
        $query = $model->buildQuery(true);
        $sql   = (string) $query;

        self::assertStringNotContainsString('ORDER BY', strtoupper($sql));
    }

    public function testBuildQueryWithoutOverrideLimitsIncludesOrderBy(): void
    {
        $model = $this->makeModel();
        $query = $model->buildQuery(false);
        $sql   = (string) $query;

        self::assertStringContainsString('ORDER BY', strtoupper($sql));
    }

    public function testBuildQueryDefaultOrderIsPrimaryKey(): void
    {
        $model = $this->makeModel();
        $query = $model->buildQuery(false);
        $sql   = (string) $query;

        // Default order field must be the primary-key column (item_id)
        self::assertStringContainsString('item_id', $sql);
    }

    // -------------------------------------------------------------------------
    // orderBy() / skip() / take()
    // -------------------------------------------------------------------------

    public function testOrderByChangesOrderColumn(): void
    {
        $model = $this->makeModel();
        $model->orderBy('ordering', 'DESC');
        $query = $model->buildQuery(false);
        $sql   = (string) $query;

        self::assertStringContainsString('ordering', $sql);
        self::assertStringContainsString('DESC', strtoupper($sql));
    }

    public function testOrderByNormalisesInvalidDirection(): void
    {
        $model = $this->makeModel();
        $model->orderBy('ordering', 'INVALID');
        $query = $model->buildQuery(false);
        $sql   = (string) $query;

        self::assertStringContainsString('ASC', strtoupper($sql));
    }

    public function testOrderByReturnsModelForChaining(): void
    {
        $model  = $this->makeModel();
        $result = $model->orderBy('title');
        self::assertSame($model, $result);
    }

    public function testSkipStoresLimitStart(): void
    {
        $model = $this->makeModel();
        $model->skip(5);
        self::assertSame(5, (int) $model->getState('limitstart'));
    }

    public function testSkipReturnsModelForChaining(): void
    {
        $model  = $this->makeModel();
        $result = $model->skip(3);
        self::assertSame($model, $result);
    }

    public function testSkipIgnoresNegativeValue(): void
    {
        $model = $this->makeModel();
        $model->skip(-5);
        self::assertSame(0, (int) $model->getState('limitstart', 0));
    }

    public function testTakeStoresLimit(): void
    {
        $model = $this->makeModel();
        $model->take(10);
        self::assertSame(10, (int) $model->getState('limit'));
    }

    public function testTakeReturnsModelForChaining(): void
    {
        $model  = $this->makeModel();
        $result = $model->take(2);
        self::assertSame($model, $result);
    }

    public function testTakeIgnoresNegativeValue(): void
    {
        $model = $this->makeModel();
        $model->take(-1);
        self::assertSame(0, (int) $model->getState('limit', 0));
    }

    // -------------------------------------------------------------------------
    // whereRaw()
    // -------------------------------------------------------------------------

    public function testWhereRawAppendsClause(): void
    {
        $this->insertRaw('Alpha', 1, 1);
        $this->insertRaw('Beta',  0, 2);

        $model = $this->makeModel();
        $model->whereRaw("enabled = 1");
        $items = $model->getItemsArray();

        self::assertCount(1, $items);
        $item = reset($items);
        self::assertSame('Alpha', $item->title);
    }

    public function testWhereRawReturnsModelForChaining(): void
    {
        $model  = $this->makeModel();
        $result = $model->whereRaw('1=1');
        self::assertSame($model, $result);
    }

    public function testMultipleWhereRawClausesAreAndedTogether(): void
    {
        $this->insertRaw('A', 1, 5);
        $this->insertRaw('B', 1, 2);
        $this->insertRaw('C', 0, 5);

        $model = $this->makeModel();
        $model->whereRaw('enabled = 1');
        $model->whereRaw('ordering >= 5');

        $items = $model->getItemsArray();
        self::assertCount(1, $items);
        $item = reset($items);
        self::assertSame('A', $item->title);
    }

    // -------------------------------------------------------------------------
    // where() — state filters via Filters behaviour
    // -------------------------------------------------------------------------

    public function testWhereExactMatchFiltersResults(): void
    {
        $this->insertRaw('Match',   1);
        $this->insertRaw('NoMatch', 0);

        $model = $this->makeModel();
        $model->where('enabled', '=', 1);
        $items = $model->getItemsArray();

        self::assertCount(1, $items);
        $item = reset($items);
        self::assertSame('Match', $item->title);
    }

    public function testWhereNotEqualFiltersResults(): void
    {
        $this->insertRaw('Active',   1);
        $this->insertRaw('Inactive', 0);

        $model = $this->makeModel();
        $model->where('enabled', '!=', 1);
        $items = $model->getItemsArray();

        self::assertCount(1, $items);
        $item = reset($items);
        self::assertSame('Inactive', $item->title);
    }

    public function testWhereGreaterThanFilters(): void
    {
        $this->insertRaw('Low',  1, 1);
        $this->insertRaw('High', 1, 10);

        $model = $this->makeModel();
        $model->where('ordering', 'gt', 5);
        $items = $model->getItemsArray();

        self::assertCount(1, $items);
        $item = reset($items);
        self::assertSame('High', $item->title);
    }

    public function testWhereLessThanFilters(): void
    {
        $this->insertRaw('Low',  1, 1);
        $this->insertRaw('High', 1, 10);

        $model = $this->makeModel();
        $model->where('ordering', 'lt', 5);
        $items = $model->getItemsArray();

        self::assertCount(1, $items);
        $item = reset($items);
        self::assertSame('Low', $item->title);
    }

    public function testWherePartialMatchFilters(): void
    {
        $this->insertRaw('Foobar', 1);
        $this->insertRaw('Bazqux', 1);

        $model = $this->makeModel();
        $model->where('title', 'like', 'Foo');
        $items = $model->getItemsArray();

        self::assertCount(1, $items);
        $item = reset($items);
        self::assertSame('Foobar', $item->title);
    }

    public function testWhereReturnsModelForChaining(): void
    {
        $model  = $this->makeModel();
        $result = $model->where('enabled', '=', 1);
        self::assertSame($model, $result);
    }

    public function testWhereThrowsOnUnsupportedMethod(): void
    {
        $this->expectException(InvalidSearchMethod::class);

        $model = $this->makeModel();
        $model->where('enabled', 'UNSUPPORTED_OP', 1);
    }

    // -------------------------------------------------------------------------
    // where() — aliases
    // -------------------------------------------------------------------------

    public static function whereAliasProvider(): array
    {
        return [
            'lt alias'  => ['lt',  '<'],
            'le alias'  => ['le',  '<='],
            'gt alias'  => ['gt',  '>'],
            'ge alias'  => ['ge',  '>='],
            'eq alias'  => ['eq',  '='],
            'neq alias' => ['neq', '!='],
            'ne alias'  => ['ne',  '!='],
            '<> alias'  => ['<>',  '!='],
        ];
    }

    #[DataProvider('whereAliasProvider')]
    public function testWhereMethodAliasesResolveWithoutException(string $alias, string $expectedOp): void
    {
        $model = $this->makeModel();

        // Should not throw — aliases must be resolved to known methods
        $result = $model->where('ordering', $alias, 5);
        self::assertSame($model, $result);
    }

    // -------------------------------------------------------------------------
    // get() / getItemsArray()
    // -------------------------------------------------------------------------

    public function testGetReturnsDataCollection(): void
    {
        $this->insertRaw('One');
        $this->insertRaw('Two');

        $model      = $this->makeModel();
        $collection = $model->get(true);

        self::assertInstanceOf(\Awf\Mvc\DataModel\Collection::class, $collection);
        self::assertCount(2, $collection);
    }

    public function testGetItemsArrayReturnsAllRows(): void
    {
        $this->insertRaw('Alpha');
        $this->insertRaw('Beta');
        $this->insertRaw('Gamma');

        $model = $this->makeModel();
        $items = $model->getItemsArray();

        self::assertCount(3, $items);
    }

    public function testGetItemsArrayRowsAreDataModelInstances(): void
    {
        $this->insertRaw('Test');

        $model = $this->makeModel();
        $items = $model->getItemsArray();

        foreach ($items as $item) {
            self::assertInstanceOf(\Awf\Mvc\DataModel::class, $item);
        }
    }

    public function testGetItemsArrayKeyedByPrimaryKey(): void
    {
        $id1 = $this->insertRaw('First');
        $id2 = $this->insertRaw('Second');

        $model = $this->makeModel();
        $items = $model->getItemsArray();

        self::assertArrayHasKey($id1, $items);
        self::assertArrayHasKey($id2, $items);
    }

    public function testGetItemsArrayRespectsLimitAndOffset(): void
    {
        $this->insertRaw('A', 1, 1);
        $this->insertRaw('B', 1, 2);
        $this->insertRaw('C', 1, 3);

        $model = $this->makeModel();
        $model->orderBy('ordering', 'ASC');

        // Skip 1, take 2
        $items = $model->getItemsArray(1, 2);

        self::assertCount(2, $items);
        $titles = array_map(static fn($i) => $i->title, $items);
        self::assertContains('B', $titles);
        self::assertContains('C', $titles);
    }

    public function testGetWithTakeAndSkipLimitsResults(): void
    {
        $this->insertRaw('P', 1, 1);
        $this->insertRaw('Q', 1, 2);
        $this->insertRaw('R', 1, 3);

        $model = $this->makeModel();
        $model->orderBy('ordering', 'ASC')->skip(1)->take(1);
        $collection = $model->get();

        self::assertCount(1, $collection);
    }

    public function testGetReturnsEmptyCollectionWhenTableIsEmpty(): void
    {
        $model      = $this->makeModel();
        $collection = $model->get(true);

        self::assertCount(0, $collection);
    }

    // -------------------------------------------------------------------------
    // count()
    // -------------------------------------------------------------------------

    public function testCountReturnsZeroForEmptyTable(): void
    {
        $model = $this->makeModel();
        self::assertSame(0, (int) $model->count());
    }

    public function testCountReturnsTotalRows(): void
    {
        $this->insertRaw('One');
        $this->insertRaw('Two');
        $this->insertRaw('Three');

        $model = $this->makeModel();
        self::assertSame(3, (int) $model->count());
    }

    public function testCountRespectsWhereFilters(): void
    {
        $this->insertRaw('A', 1);
        $this->insertRaw('B', 0);
        $this->insertRaw('C', 1);

        $model = $this->makeModel();
        $model->where('enabled', '=', 1);

        self::assertSame(2, (int) $model->count());
    }

    public function testCountRespectsWhereRaw(): void
    {
        $this->insertRaw('X', 1, 10);
        $this->insertRaw('Y', 1, 20);
        $this->insertRaw('Z', 1, 5);

        $model = $this->makeModel();
        $model->whereRaw('ordering > 9');

        self::assertSame(2, (int) $model->count());
    }

    public function testCountIgnoresLimitAndOffset(): void
    {
        // count() must always return the total matched rows, not a page subset
        $this->insertRaw('A');
        $this->insertRaw('B');
        $this->insertRaw('C');

        $model = $this->makeModel();
        $model->skip(1)->take(1);

        // count() calls buildQuery(true) — limits are overridden
        self::assertSame(3, (int) $model->count());
    }

    // -------------------------------------------------------------------------
    // Ordering round-trips (result ordering in DB)
    // -------------------------------------------------------------------------

    public function testOrderByAscReturnsRowsInAscendingOrder(): void
    {
        $this->insertRaw('C', 1, 3);
        $this->insertRaw('A', 1, 1);
        $this->insertRaw('B', 1, 2);

        // Use take(100) so limit > 0, which causes getItemsArray to call buildQuery(false)
        // and thus include the ORDER BY clause.
        $model = $this->makeModel();
        $model->orderBy('ordering', 'ASC')->take(100);
        $collection = $model->get();
        // $collection contains DataModel instances; extract title from each
        $titles = [];
        foreach ($collection as $item) {
            $titles[] = $item->title;
        }

        self::assertSame(['A', 'B', 'C'], array_values($titles));
    }

    public function testOrderByDescReturnsRowsInDescendingOrder(): void
    {
        $this->insertRaw('C', 1, 3);
        $this->insertRaw('A', 1, 1);
        $this->insertRaw('B', 1, 2);

        $model = $this->makeModel();
        $model->orderBy('ordering', 'DESC')->take(100);
        $collection = $model->get();
        $titles     = [];
        foreach ($collection as $item) {
            $titles[] = $item->title;
        }

        self::assertSame(['C', 'B', 'A'], array_values($titles));
    }

    public function testOrderByUnknownFieldFallsBackToPrimaryKey(): void
    {
        $this->insertRaw('Row1');
        $this->insertRaw('Row2');

        // Set an unknown field — buildQuery should fall back to idFieldName
        $model = $this->makeModel();
        $model->setState('filter_order', 'nonexistent_column');
        $query = $model->buildQuery(false);
        $sql   = (string) $query;

        // Should still contain item_id, not the unknown column
        self::assertStringContainsString('item_id', $sql);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function testScopeEnabledFiltersToEnabledRows(): void
    {
        $this->insertRaw('On',  1);
        $this->insertRaw('Off', 0);

        $model = $this->makeModel();
        $model->scopeEnabled();
        $items = $model->getItemsArray();

        self::assertCount(1, $items);
        $item = reset($items);
        self::assertSame('On', $item->title);
    }

    public function testScopeCanBeInvokedViaMagicCall(): void
    {
        $this->insertRaw('On',  1);
        $this->insertRaw('Off', 0);

        $model = $this->makeModel();
        $model->enabled(); // calls scopeEnabled() via __call
        $items = $model->getItemsArray();

        self::assertCount(1, $items);
    }

    public function testScopeMinOrderingFiltersRows(): void
    {
        $this->insertRaw('Low',    1, 1);
        $this->insertRaw('Medium', 1, 5);
        $this->insertRaw('High',   1, 10);

        $model = $this->makeModel();
        $model->scopeMinOrdering(5);
        $items = $model->getItemsArray();

        self::assertCount(2, $items);
        $titles = array_map(static fn($i) => $i->title, $items);
        self::assertContains('Medium', $titles);
        self::assertContains('High',   $titles);
    }

    // -------------------------------------------------------------------------
    // Combined: where + count + get round-trip
    // -------------------------------------------------------------------------

    public function testWhereAndGetCombined(): void
    {
        $this->insertRaw('Enable1', 1, 1);
        $this->insertRaw('Enable2', 1, 2);
        $this->insertRaw('Disable', 0, 3);

        $model = $this->makeModel();
        $model->where('enabled', '=', 1);

        $count      = (int) $model->count();
        $collection = $model->get(true);

        self::assertSame(2, $count);
        self::assertCount(2, $collection);
    }

    public function testWhereRawAndCountCombined(): void
    {
        $this->insertRaw('P', 1, 5);
        $this->insertRaw('Q', 1, 15);
        $this->insertRaw('R', 0, 25);

        $model = $this->makeModel();
        $model->whereRaw('ordering >= 10');

        self::assertSame(2, (int) $model->count());
    }

    // -------------------------------------------------------------------------
    // between / outside filter variants
    // -------------------------------------------------------------------------

    public function testWhereBetweenFiltersRange(): void
    {
        $this->insertRaw('Low',  1, 1);
        $this->insertRaw('Mid',  1, 5);
        $this->insertRaw('High', 1, 10);

        $model = $this->makeModel();
        $model->where('ordering', '()', ['from' => 3, 'to' => 7]);
        $items = $model->getItemsArray();

        self::assertCount(1, $items);
        $item = reset($items);
        self::assertSame('Mid', $item->title);
    }
}
