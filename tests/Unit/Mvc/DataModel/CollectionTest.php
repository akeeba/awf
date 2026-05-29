<?php

declare(strict_types=1);

/**
 * Minimal DataModel subclass used as a test fixture.
 *
 * Lives under the "DataModel\Model" sub-namespace so DataModel::getName()
 * can correctly strip "Model\" and derive the model name.
 *
 * We pass 'knownFields' in the container's mvc_config so that no database
 * connection is required to instantiate the model.
 */

namespace Awf\Tests\Unit\Mvc\DataModel\Model;

use Awf\Mvc\DataModel;

if (!class_exists(\Awf\Tests\Unit\Mvc\DataModel\Model\CollectionFixture::class, false)) {
    /**
     * A simple fixture model that can be fully instantiated without a real DB.
     */
    class CollectionFixture extends DataModel
    {
        public static function flushCaches(): void
        {
            static::$tableCache      = [];
            static::$tableFieldCache = [];
        }
    }
}

// ============================================================
// Test class
// ============================================================

namespace Awf\Tests\Unit\Mvc\DataModel;

use Awf\Container\Container;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Mvc\DataModel\Collection;
use Awf\Session\Segment;
use Awf\Text\Language;
use Awf\User\ManagerInterface as UserManagerInterface;
use Awf\User\UserInterface;
use Awf\Utils\Collection as BaseCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Awf\Mvc\DataModel\Collection.
 *
 * Covers: construction, find/contains/removeById, add/merge/diff/intersect/unique,
 * modelKeys, max/min, fetch, toBase, __call batch dispatch, and edge cases.
 */
class CollectionTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Infrastructure
    // -------------------------------------------------------------------------

    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        \Awf\Tests\Unit\Mvc\DataModel\Model\CollectionFixture::flushCaches();

        $tmpDir = sys_get_temp_dir();

        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturn([]);

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);

        $input = new Input([]);

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
            'eventDispatcher'      => $ed,
            'language'             => $language,
            'input'                => $input,
            'application'          => $application,
            'segment'              => $segment,
            'userManager'          => $userManager,
        ]);
    }

    /**
     * Create a fixture DataModel with the given ID (and optional extra fields).
     *
     * We use `knownFields` in mvc_config so that no actual database call is made.
     */
    private function makeModel(int $id, array $extra = []): \Awf\Tests\Unit\Mvc\DataModel\Model\CollectionFixture
    {
        $fields = array_merge(
            ['fixture_id' => (object)['Type' => 'int', 'Null' => 'NO', 'Key' => 'PRI', 'Default' => null, 'Extra' => 'auto_increment']],
            array_map(
                static fn($v) => (object)['Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => null, 'Extra' => ''],
                $extra
            )
        );

        $this->container['mvc_config'] = [
            'tableName'   => 'fixtures',
            'idFieldName' => 'fixture_id',
            'knownFields' => $fields,
            'autoChecks'  => false,
        ];

        $model = new \Awf\Tests\Unit\Mvc\DataModel\Model\CollectionFixture($this->container);
        $model->setFieldValue('fixture_id', $id);

        foreach ($extra as $k => $v) {
            $model->setFieldValue($k, $v);
        }

        return $model;
    }

    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    public function testEmptyCollectionIsCountableAndIterable(): void
    {
        $c = new Collection();

        self::assertCount(0, $c);
        self::assertTrue($c->isEmpty());
        self::assertSame([], $c->all());
    }

    public function testConstructWithItems(): void
    {
        $m1 = $this->makeModel(1);
        $m2 = $this->makeModel(2);

        $c = new Collection([$m1, $m2]);

        self::assertCount(2, $c);
        self::assertFalse($c->isEmpty());
    }

    public function testMakeFactoryMethod(): void
    {
        $m = $this->makeModel(5);
        $c = Collection::make([$m]);

        self::assertCount(1, $c);
        self::assertSame($m, $c->first());
    }

    // -------------------------------------------------------------------------
    // add
    // -------------------------------------------------------------------------

    public function testAddAppendsItemAndReturnsSelf(): void
    {
        $c  = new Collection();
        $m  = $this->makeModel(10);
        $ret = $c->add($m);

        self::assertSame($c, $ret, 'add() must return $this');
        self::assertCount(1, $c);
        self::assertSame($m, $c->first());
    }

    // -------------------------------------------------------------------------
    // modelKeys
    // -------------------------------------------------------------------------

    public function testModelKeysReturnsAllPrimaryKeys(): void
    {
        $m1 = $this->makeModel(1);
        $m2 = $this->makeModel(2);
        $m3 = $this->makeModel(3);

        $c = new Collection([$m1, $m2, $m3]);

        self::assertSame([1, 2, 3], $c->modelKeys());
    }

    public function testModelKeysOnEmptyCollectionReturnsEmptyArray(): void
    {
        $c = new Collection();
        self::assertSame([], $c->modelKeys());
    }

    // -------------------------------------------------------------------------
    // find
    // -------------------------------------------------------------------------

    public function testFindByIntegerKeyReturnsModel(): void
    {
        $m1 = $this->makeModel(1);
        $m2 = $this->makeModel(2);
        $c  = new Collection([$m1, $m2]);

        self::assertSame($m2, $c->find(2));
    }

    public function testFindByModelInstanceUsesItsId(): void
    {
        $m1 = $this->makeModel(1);
        $m2 = $this->makeModel(2);
        $c  = new Collection([$m1, $m2]);

        self::assertSame($m1, $c->find($m1));
    }

    public function testFindReturnsDefaultWhenNotFound(): void
    {
        $c = new Collection([$this->makeModel(1)]);

        self::assertNull($c->find(99));
        self::assertSame('fallback', $c->find(99, 'fallback'));
    }

    public function testFindOnEmptyCollectionReturnsDefault(): void
    {
        $c = new Collection();
        self::assertNull($c->find(1));
    }

    // -------------------------------------------------------------------------
    // contains
    // -------------------------------------------------------------------------

    public function testContainsByIdReturnsTrueWhenPresent(): void
    {
        $m  = $this->makeModel(7);
        $c  = new Collection([$m]);

        self::assertTrue($c->contains(7));
    }

    public function testContainsByModelInstanceReturnsTrueWhenPresent(): void
    {
        $m  = $this->makeModel(7);
        $c  = new Collection([$m]);

        self::assertTrue($c->contains($m));
    }

    public function testContainsReturnsFalseWhenAbsent(): void
    {
        $c = new Collection([$this->makeModel(1)]);

        self::assertFalse($c->contains(99));
    }

    public function testContainsOnEmptyCollectionReturnsFalse(): void
    {
        $c = new Collection();
        self::assertFalse($c->contains(1));
    }

    // -------------------------------------------------------------------------
    // removeById
    // -------------------------------------------------------------------------

    public function testRemoveByIdRemovesMatchingModel(): void
    {
        $m1 = $this->makeModel(1);
        $m2 = $this->makeModel(2);
        $c  = new Collection([$m1, $m2]);

        $c->removeById(1);

        self::assertCount(1, $c);
        self::assertFalse($c->contains(1));
        self::assertTrue($c->contains(2));
    }

    public function testRemoveByIdAcceptsModelInstance(): void
    {
        $m1 = $this->makeModel(1);
        $m2 = $this->makeModel(2);
        $c  = new Collection([$m1, $m2]);

        $c->removeById($m1);

        self::assertCount(1, $c);
        self::assertFalse($c->contains(1));
    }

    public function testRemoveByIdDoesNothingWhenIdAbsent(): void
    {
        $m  = $this->makeModel(1);
        $c  = new Collection([$m]);

        $c->removeById(99);

        self::assertCount(1, $c);
    }

    // -------------------------------------------------------------------------
    // merge
    // -------------------------------------------------------------------------

    public function testMergeAddsNewItems(): void
    {
        $m1 = $this->makeModel(1);
        $m2 = $this->makeModel(2);
        $m3 = $this->makeModel(3);

        $c1     = new Collection([$m1, $m2]);
        $c2     = new Collection([$m3]);
        $merged = $c1->merge($c2);

        self::assertCount(3, $merged);
        self::assertTrue($merged->contains(1));
        self::assertTrue($merged->contains(2));
        self::assertTrue($merged->contains(3));
    }

    public function testMergeDeduplicatesById(): void
    {
        $m1a = $this->makeModel(1);
        $m1b = $this->makeModel(1); // same ID — should replace
        $m2  = $this->makeModel(2);

        $c1     = new Collection([$m1a, $m2]);
        $c2     = new Collection([$m1b]);
        $merged = $c1->merge($c2);

        // ID 1 appears once after deduplication
        self::assertCount(2, $merged);
        self::assertSame([1, 2], $merged->modelKeys());
    }

    public function testMergeReturnsNewInstance(): void
    {
        $c1 = new Collection([$this->makeModel(1)]);
        $c2 = new Collection([$this->makeModel(2)]);

        $merged = $c1->merge($c2);

        self::assertNotSame($c1, $merged);
        self::assertNotSame($c2, $merged);
    }

    // -------------------------------------------------------------------------
    // diff
    // -------------------------------------------------------------------------

    public function testDiffExcludesItemsPresentInOther(): void
    {
        $m1 = $this->makeModel(1);
        $m2 = $this->makeModel(2);
        $m3 = $this->makeModel(3);

        $c1   = new Collection([$m1, $m2, $m3]);
        $c2   = new Collection([$m2]);
        $diff = $c1->diff($c2);

        self::assertCount(2, $diff);
        self::assertTrue($diff->contains(1));
        self::assertFalse($diff->contains(2));
        self::assertTrue($diff->contains(3));
    }

    public function testDiffReturnsEmptyWhenAllExcluded(): void
    {
        $m1 = $this->makeModel(1);
        $c1 = new Collection([$m1]);
        $c2 = new Collection([$m1]);

        $diff = $c1->diff($c2);

        self::assertCount(0, $diff);
    }

    public function testDiffWithEmptyOtherReturnsSelf(): void
    {
        $m1 = $this->makeModel(1);
        $c1 = new Collection([$m1]);

        $diff = $c1->diff(new Collection());

        self::assertCount(1, $diff);
        self::assertTrue($diff->contains(1));
    }

    // -------------------------------------------------------------------------
    // intersect
    // -------------------------------------------------------------------------

    public function testIntersectReturnsItemsPresentInBoth(): void
    {
        $m1 = $this->makeModel(1);
        $m2 = $this->makeModel(2);
        $m3 = $this->makeModel(3);

        $c1        = new Collection([$m1, $m2, $m3]);
        $c2        = new Collection([$m2, $m3]);
        $intersect = $c1->intersect($c2);

        self::assertCount(2, $intersect);
        self::assertFalse($intersect->contains(1));
        self::assertTrue($intersect->contains(2));
        self::assertTrue($intersect->contains(3));
    }

    public function testIntersectReturnsEmptyWhenNoCommonItems(): void
    {
        $c1        = new Collection([$this->makeModel(1)]);
        $c2        = new Collection([$this->makeModel(2)]);
        $intersect = $c1->intersect($c2);

        self::assertCount(0, $intersect);
    }

    // -------------------------------------------------------------------------
    // unique
    // -------------------------------------------------------------------------

    public function testUniqueRemovesDuplicateIds(): void
    {
        $m1a = $this->makeModel(1);
        $m1b = $this->makeModel(1);
        $m2  = $this->makeModel(2);

        $c      = new Collection([$m1a, $m1b, $m2]);
        $unique = $c->unique();

        self::assertCount(2, $unique);
    }

    public function testUniqueOnAlreadyUniqueCollectionReturnsSameSize(): void
    {
        $c      = new Collection([$this->makeModel(1), $this->makeModel(2)]);
        $unique = $c->unique();

        self::assertCount(2, $unique);
    }

    // -------------------------------------------------------------------------
    // max / min
    // -------------------------------------------------------------------------

    public function testMaxReturnsHighestFieldValue(): void
    {
        $m1 = $this->makeModel(1, ['score' => 10]);
        $m2 = $this->makeModel(2, ['score' => 30]);
        $m3 = $this->makeModel(3, ['score' => 20]);

        $c = new Collection([$m1, $m2, $m3]);

        self::assertSame(30, $c->max('score'));
    }

    public function testMinReturnsLowestFieldValue(): void
    {
        $m1 = $this->makeModel(1, ['score' => 10]);
        $m2 = $this->makeModel(2, ['score' => 30]);
        $m3 = $this->makeModel(3, ['score' => 20]);

        $c = new Collection([$m1, $m2, $m3]);

        self::assertSame(10, $c->min('score'));
    }

    public function testMaxOnSingleItemReturnsItsValue(): void
    {
        $m = $this->makeModel(1, ['score' => 42]);
        $c = new Collection([$m]);

        self::assertSame(42, $c->max('score'));
    }

    public function testMinOnSingleItemReturnsItsValue(): void
    {
        $m = $this->makeModel(1, ['score' => 42]);
        $c = new Collection([$m]);

        self::assertSame(42, $c->min('score'));
    }

    // -------------------------------------------------------------------------
    // toBase
    // -------------------------------------------------------------------------

    public function testToBaseReturnsBaseCollectionInstance(): void
    {
        $m  = $this->makeModel(1);
        $c  = new Collection([$m]);
        $b  = $c->toBase();

        self::assertInstanceOf(BaseCollection::class, $b);
        self::assertNotInstanceOf(Collection::class, $b);
        self::assertCount(1, $b);
    }

    public function testToBaseOnEmptyCollectionReturnsEmptyBaseCollection(): void
    {
        $c = new Collection();
        $b = $c->toBase();

        self::assertInstanceOf(BaseCollection::class, $b);
        self::assertCount(0, $b);
    }

    // -------------------------------------------------------------------------
    // fetch
    // -------------------------------------------------------------------------

    public function testFetchReturnsNewCollectionInstance(): void
    {
        // fetch() delegates to akeeba_array_fetch; use a simple nested field.
        $m1 = $this->makeModel(1, ['label' => 'alpha']);
        $m2 = $this->makeModel(2, ['label' => 'beta']);

        $c       = new Collection([$m1, $m2]);
        $fetched = $c->fetch('label');

        self::assertInstanceOf(Collection::class, $fetched);
    }

    // -------------------------------------------------------------------------
    // __call — batch dispatch
    // -------------------------------------------------------------------------

    public function testCallDispatchesMethodToAllItems(): void
    {
        // Build three real models with a 'status' field, then batch-update via __call.
        $fields = [
            'fixture_id' => (object)['Type' => 'int', 'Null' => 'NO', 'Key' => 'PRI', 'Default' => null, 'Extra' => ''],
            'status'     => (object)['Type' => 'varchar(255)', 'Null' => 'YES', 'Key' => '', 'Default' => null, 'Extra' => ''],
        ];

        $models = [];
        for ($i = 1; $i <= 3; $i++) {
            $this->container['mvc_config'] = [
                'tableName'   => 'fixtures',
                'idFieldName' => 'fixture_id',
                'knownFields' => $fields,
                'autoChecks'  => false,
            ];
            $m = new \Awf\Tests\Unit\Mvc\DataModel\Model\CollectionFixture($this->container);
            $m->setFieldValue('fixture_id', $i);
            $models[] = $m;
        }

        $c = new Collection($models);
        $c->setFieldValue('status', 'active');

        // Verify that all three models got the field value updated.
        foreach ($models as $model) {
            self::assertSame('active', $model->getFieldValue('status'));
        }
    }

    public function testCallDoesNothingOnEmptyCollection(): void
    {
        // Should not throw.
        $c = new Collection();
        $c->setFieldValue('status', 'active'); // no exception expected

        $this->addToAssertionCount(1); // explicit: reached here = pass
    }

    public function testCallDoesNothingWhenMethodDoesNotExist(): void
    {
        $m = $this->makeModel(1);
        $c = new Collection([$m]);

        // nonExistentMethod999 is not on DataModel — __call should silently skip.
        $c->nonExistentMethod999('arg');

        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // Inherited BaseCollection helpers (spot-check via Collection subclass)
    // -------------------------------------------------------------------------

    public function testFirstReturnsFirstItem(): void
    {
        $m1 = $this->makeModel(1);
        $m2 = $this->makeModel(2);
        $c  = new Collection([$m1, $m2]);

        self::assertSame($m1, $c->first());
    }

    public function testLastReturnsLastItem(): void
    {
        $m1 = $this->makeModel(1);
        $m2 = $this->makeModel(2);
        $c  = new Collection([$m1, $m2]);

        self::assertSame($m2, $c->last());
    }

    public function testCountReflectsItemCount(): void
    {
        $c = new Collection([$this->makeModel(1), $this->makeModel(2), $this->makeModel(3)]);
        self::assertSame(3, $c->count());
    }

    public function testFilterReturnsSubset(): void
    {
        $m1 = $this->makeModel(1);
        $m2 = $this->makeModel(2);
        $m3 = $this->makeModel(3);

        $c        = new Collection([$m1, $m2, $m3]);
        $filtered = $c->filter(static fn($m) => $m->getId() > 1);

        self::assertCount(2, $filtered);
    }

    public function testMapReturnsMappedCollection(): void
    {
        $m1 = $this->makeModel(1);
        $m2 = $this->makeModel(2);

        $c      = new Collection([$m1, $m2]);
        $mapped = $c->map(static fn($m, $k) => $m->getId() * 10);

        self::assertSame([10, 20], $mapped->all());
    }

    public function testReverseReversesOrder(): void
    {
        $m1 = $this->makeModel(1);
        $m2 = $this->makeModel(2);
        $m3 = $this->makeModel(3);

        $c        = new Collection([$m1, $m2, $m3]);
        $reversed = $c->reverse();

        self::assertSame([$m3, $m2, $m1], $reversed->all());
    }

    public function testSliceReturnsSubset(): void
    {
        $m1 = $this->makeModel(1);
        $m2 = $this->makeModel(2);
        $m3 = $this->makeModel(3);

        $c     = new Collection([$m1, $m2, $m3]);
        $slice = $c->slice(1, 2);

        self::assertCount(2, $slice);
    }

    public function testTakeReturnsFirstN(): void
    {
        $c = new Collection([
            $this->makeModel(1),
            $this->makeModel(2),
            $this->makeModel(3),
        ]);

        $taken = $c->take(2);

        self::assertCount(2, $taken);
        self::assertSame([1, 2], $taken->modelKeys());
    }

    public function testArrayAccessReadAndWrite(): void
    {
        $m = $this->makeModel(1);
        $c = new Collection();

        $c[] = $m;

        self::assertTrue(isset($c[0]));
        self::assertSame($m, $c[0]);

        unset($c[0]);
        self::assertFalse(isset($c[0]));
    }

    public function testIterationYieldsAllItems(): void
    {
        $m1 = $this->makeModel(1);
        $m2 = $this->makeModel(2);
        $c  = new Collection([$m1, $m2]);

        $collected = [];
        foreach ($c as $item) {
            $collected[] = $item;
        }

        self::assertSame([$m1, $m2], $collected);
    }
}
