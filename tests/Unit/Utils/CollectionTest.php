<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Utils;

use Awf\Utils\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction & make()
    // -------------------------------------------------------------------------

    public function testConstructEmptyByDefault(): void
    {
        $c = new Collection();

        $this->assertSame([], $c->all());
        $this->assertCount(0, $c);
        $this->assertTrue($c->isEmpty());
    }

    public function testConstructWithItems(): void
    {
        $c = new Collection(['a' => 1, 'b' => 2]);

        $this->assertSame(['a' => 1, 'b' => 2], $c->all());
        $this->assertFalse($c->isEmpty());
    }

    public function testMakeFromArray(): void
    {
        $c = Collection::make([1, 2, 3]);

        $this->assertInstanceOf(Collection::class, $c);
        $this->assertSame([1, 2, 3], $c->all());
    }

    public function testMakeFromNullCreatesEmpty(): void
    {
        $c = Collection::make(null);

        $this->assertInstanceOf(Collection::class, $c);
        $this->assertSame([], $c->all());
    }

    public function testMakeFromScalarWrapsInArray(): void
    {
        $c = Collection::make('hello');

        $this->assertSame(['hello'], $c->all());
    }

    public function testMakeFromExistingCollectionReturnsSameInstance(): void
    {
        $original = new Collection([1, 2]);
        $c        = Collection::make($original);

        $this->assertSame($original, $c);
    }

    // -------------------------------------------------------------------------
    // get / has / put / forget
    // -------------------------------------------------------------------------

    public function testGetExistingKey(): void
    {
        $c = new Collection(['name' => 'Akeeba']);

        $this->assertSame('Akeeba', $c->get('name'));
    }

    public function testGetMissingKeyReturnsNullByDefault(): void
    {
        $c = new Collection(['name' => 'Akeeba']);

        $this->assertNull($c->get('missing'));
    }

    public function testGetMissingKeyReturnsProvidedDefault(): void
    {
        $c = new Collection();

        $this->assertSame('fallback', $c->get('missing', 'fallback'));
    }

    public function testGetDefaultClosureIsEvaluated(): void
    {
        $c = new Collection();

        $this->assertSame(42, $c->get('missing', fn () => 42));
    }

    public function testHas(): void
    {
        $c = new Collection(['a' => 1, 'b' => null]);

        $this->assertTrue($c->has('a'));
        $this->assertTrue($c->has('b'));
        $this->assertFalse($c->has('c'));
    }

    public function testPut(): void
    {
        $c = new Collection();
        $c->put('key', 'value');

        $this->assertSame(['key' => 'value'], $c->all());
    }

    public function testForget(): void
    {
        $c = new Collection(['a' => 1, 'b' => 2]);
        $c->forget('a');

        $this->assertSame(['b' => 2], $c->all());
    }

    // -------------------------------------------------------------------------
    // push / prepend / pop / shift
    // -------------------------------------------------------------------------

    public function testPush(): void
    {
        $c = new Collection([1, 2]);
        $c->push(3);

        $this->assertSame([1, 2, 3], $c->all());
    }

    public function testPrepend(): void
    {
        $c = new Collection([2, 3]);
        $c->prepend(1);

        $this->assertSame([1, 2, 3], $c->all());
    }

    public function testPop(): void
    {
        $c   = new Collection([1, 2, 3]);
        $val = $c->pop();

        $this->assertSame(3, $val);
        $this->assertSame([1, 2], $c->all());
    }

    public function testPopOnEmptyReturnsNull(): void
    {
        $c = new Collection();

        $this->assertNull($c->pop());
    }

    public function testShift(): void
    {
        $c   = new Collection([1, 2, 3]);
        $val = $c->shift();

        $this->assertSame(1, $val);
        $this->assertSame([2, 3], $c->all());
    }

    public function testShiftOnEmptyReturnsNull(): void
    {
        $c = new Collection();

        $this->assertNull($c->shift());
    }

    // -------------------------------------------------------------------------
    // first / last
    // -------------------------------------------------------------------------

    public function testFirst(): void
    {
        $c = new Collection([10, 20, 30]);

        $this->assertSame(10, $c->first());
    }

    public function testFirstOnEmptyReturnsNull(): void
    {
        $c = new Collection();

        $this->assertNull($c->first());
    }

    public function testFirstWithCallback(): void
    {
        $c = new Collection([1, 2, 3, 4]);

        $result = $c->first(fn ($key, $value) => $value > 2);

        $this->assertSame(3, $result);
    }

    public function testFirstWithCallbackUsesDefaultWhenNoMatch(): void
    {
        $c = new Collection([1, 2, 3]);

        $result = $c->first(fn ($key, $value) => $value > 100, 'none');

        $this->assertSame('none', $result);
    }

    public function testLast(): void
    {
        $c = new Collection([10, 20, 30]);

        $this->assertSame(30, $c->last());
    }

    public function testLastOnEmptyReturnsNull(): void
    {
        $c = new Collection();

        $this->assertNull($c->last());
    }

    // -------------------------------------------------------------------------
    // map / filter / each / reduce / transform
    // -------------------------------------------------------------------------

    public function testMap(): void
    {
        $c      = new Collection([1, 2, 3]);
        $mapped = $c->map(fn ($value) => $value * 2);

        $this->assertInstanceOf(Collection::class, $mapped);
        $this->assertSame([2, 4, 6], $mapped->all());
        // Original is unchanged
        $this->assertSame([1, 2, 3], $c->all());
    }

    public function testMapReceivesKey(): void
    {
        $c      = new Collection(['a' => 1, 'b' => 2]);
        $mapped = $c->map(fn ($value, $key) => "$key=$value");

        $this->assertSame(['a=1', 'b=2'], $mapped->all());
    }

    public function testFilter(): void
    {
        $c        = new Collection([1, 2, 3, 4]);
        $filtered = $c->filter(fn ($value) => $value % 2 === 0);

        $this->assertInstanceOf(Collection::class, $filtered);
        $this->assertSame([1 => 2, 3 => 4], $filtered->all());
    }

    public function testEachReturnsSelfAndVisitsAll(): void
    {
        $c    = new Collection([1, 2, 3]);
        $seen = [];

        $result = $c->each(function ($value) use (&$seen) {
            $seen[] = $value;
        });

        $this->assertSame($c, $result);
        $this->assertSame([1, 2, 3], $seen);
    }

    public function testReduce(): void
    {
        $c = new Collection([1, 2, 3, 4]);

        $sum = $c->reduce(fn ($carry, $item) => $carry + $item, 0);

        $this->assertSame(10, $sum);
    }

    public function testReduceWithDefaultInitial(): void
    {
        $c = new Collection([]);

        $this->assertNull($c->reduce(fn ($carry, $item) => $carry + $item));
    }

    public function testTransformMutatesInPlace(): void
    {
        $c      = new Collection([1, 2, 3]);
        $result = $c->transform(fn ($value) => $value + 1);

        $this->assertSame($c, $result);
        $this->assertSame([2, 3, 4], $c->all());
    }

    // -------------------------------------------------------------------------
    // values
    // -------------------------------------------------------------------------

    public function testValuesResetsKeysInPlace(): void
    {
        $c      = new Collection([5 => 'a', 10 => 'b']);
        $result = $c->values();

        $this->assertSame($c, $result);
        $this->assertSame([0 => 'a', 1 => 'b'], $c->all());
    }

    // -------------------------------------------------------------------------
    // merge / diff / intersect / unique / reverse
    // -------------------------------------------------------------------------

    public function testMergeWithArray(): void
    {
        $c      = new Collection(['a' => 1]);
        $merged = $c->merge(['b' => 2]);

        $this->assertSame(['a' => 1, 'b' => 2], $merged->all());
    }

    public function testMergeWithCollection(): void
    {
        $c      = new Collection([1, 2]);
        $merged = $c->merge(new Collection([3, 4]));

        $this->assertSame([1, 2, 3, 4], $merged->all());
    }

    public function testDiff(): void
    {
        $c    = new Collection([1, 2, 3, 4]);
        $diff = $c->diff([2, 4]);

        $this->assertSame([0 => 1, 2 => 3], $diff->all());
    }

    public function testIntersect(): void
    {
        $c    = new Collection([1, 2, 3, 4]);
        $both = $c->intersect([2, 4, 6]);

        $this->assertSame([1 => 2, 3 => 4], $both->all());
    }

    public function testUnique(): void
    {
        $c      = new Collection([1, 1, 2, 2, 3]);
        $unique = $c->unique();

        $this->assertSame([0 => 1, 2 => 2, 4 => 3], $unique->all());
    }

    public function testReverse(): void
    {
        $c = new Collection([1, 2, 3]);

        $this->assertSame([3, 2, 1], $c->reverse()->all());
    }

    // -------------------------------------------------------------------------
    // slice / take / splice
    // -------------------------------------------------------------------------

    public function testSlice(): void
    {
        $c = new Collection([1, 2, 3, 4, 5]);

        $this->assertSame([3, 4], $c->slice(2, 2)->all());
    }

    public function testTakePositive(): void
    {
        $c = new Collection([1, 2, 3, 4, 5]);

        $this->assertSame([1, 2, 3], $c->take(3)->all());
    }

    public function testTakeNegative(): void
    {
        $c = new Collection([1, 2, 3, 4, 5]);

        $this->assertSame([4, 5], array_values($c->take(-2)->all()));
    }

    public function testSpliceReturnsRemovedAndMutatesSource(): void
    {
        $c       = new Collection([1, 2, 3, 4, 5]);
        $removed = $c->splice(1, 2);

        $this->assertSame([2, 3], $removed->all());
        $this->assertSame([1, 4, 5], $c->all());
    }

    // -------------------------------------------------------------------------
    // collapse / flatten / fetch
    // -------------------------------------------------------------------------

    public function testCollapse(): void
    {
        $c = new Collection([[1, 2], [3, 4], [5]]);

        $this->assertSame([1, 2, 3, 4, 5], $c->collapse()->all());
    }

    public function testFlatten(): void
    {
        $c = new Collection([1, [2, [3, 4]], 5]);

        $this->assertSame([1, 2, 3, 4, 5], $c->flatten()->all());
    }

    public function testFetch(): void
    {
        $c = new Collection([
            ['name' => 'Alpha'],
            ['name' => 'Beta'],
        ]);

        $this->assertSame(['Alpha', 'Beta'], $c->fetch('name')->all());
    }

    // -------------------------------------------------------------------------
    // lists / implode / groupBy
    // -------------------------------------------------------------------------

    public function testLists(): void
    {
        $c = new Collection([
            ['id' => 1, 'name' => 'A'],
            ['id' => 2, 'name' => 'B'],
        ]);

        $this->assertSame(['A', 'B'], $c->lists('name'));
        $this->assertSame([1 => 'A', 2 => 'B'], $c->lists('name', 'id'));
    }

    public function testImplodeWithGlue(): void
    {
        $c = new Collection([
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ]);

        $this->assertSame('A, B, C', $c->implode('name', ', '));
    }

    public function testImplodeWithoutGlue(): void
    {
        $c = new Collection([
            ['name' => 'A'],
            ['name' => 'B'],
        ]);

        $this->assertSame('AB', $c->implode('name'));
    }

    public function testGroupByString(): void
    {
        $c = new Collection([
            ['type' => 'fruit', 'name' => 'apple'],
            ['type' => 'veg', 'name' => 'carrot'],
            ['type' => 'fruit', 'name' => 'banana'],
        ]);

        $grouped = $c->groupBy('type')->all();

        $this->assertArrayHasKey('fruit', $grouped);
        $this->assertArrayHasKey('veg', $grouped);
        $this->assertCount(2, $grouped['fruit']);
        $this->assertCount(1, $grouped['veg']);
    }

    public function testGroupByCallback(): void
    {
        $c = new Collection([1, 2, 3, 4, 5]);

        $grouped = $c->groupBy(fn ($value) => $value % 2 === 0 ? 'even' : 'odd')->all();

        $this->assertSame([1, 3, 5], $grouped['odd']);
        $this->assertSame([2, 4], $grouped['even']);
    }

    // -------------------------------------------------------------------------
    // sort / sortBy / sortByDesc / sum
    // -------------------------------------------------------------------------

    public function testSort(): void
    {
        $c = new Collection([3, 1, 2]);

        $result = $c->sort(fn ($a, $b) => $a <=> $b);

        $this->assertSame($c, $result);
        $this->assertSame([1, 2, 3], array_values($c->all()));
    }

    public function testSortByString(): void
    {
        $c = new Collection([
            ['name' => 'Charlie', 'age' => 30],
            ['name' => 'Alice', 'age' => 25],
            ['name' => 'Bob', 'age' => 35],
        ]);

        $sorted = $c->sortBy('age')->values()->all();

        $this->assertSame(['Alice', 'Charlie', 'Bob'], array_column($sorted, 'name'));
    }

    public function testSortByDesc(): void
    {
        $c = new Collection([
            ['age' => 30],
            ['age' => 25],
            ['age' => 35],
        ]);

        $sorted = $c->sortByDesc('age')->values()->all();

        $this->assertSame([35, 30, 25], array_column($sorted, 'age'));
    }

    public function testSumWithString(): void
    {
        $c = new Collection([
            ['price' => 10],
            ['price' => 20],
            ['price' => 5],
        ]);

        $this->assertSame(35, $c->sum('price'));
    }

    public function testSumWithClosure(): void
    {
        $c = new Collection([1, 2, 3]);

        $this->assertSame(6, $c->sum(fn ($item) => $item));
    }

    // -------------------------------------------------------------------------
    // random
    // -------------------------------------------------------------------------

    public function testRandomSingle(): void
    {
        $c     = new Collection([1, 2, 3]);
        $value = $c->random();

        $this->assertContains($value, [1, 2, 3]);
    }

    public function testRandomMultiple(): void
    {
        $c      = new Collection([1, 2, 3, 4, 5]);
        $picked = $c->random(2);

        $this->assertIsArray($picked);
        $this->assertCount(2, $picked);
    }

    // -------------------------------------------------------------------------
    // toArray / toJson / jsonSerialize / __toString
    // -------------------------------------------------------------------------

    public function testToArray(): void
    {
        $c = new Collection(['a' => 1, 'b' => 2]);

        $this->assertSame(['a' => 1, 'b' => 2], $c->toArray());
    }

    public function testToArrayCallsNestedToArray(): void
    {
        $nested = new Collection([1, 2]);
        $c      = new Collection(['inner' => $nested, 'scalar' => 5]);

        $this->assertSame(['inner' => [1, 2], 'scalar' => 5], $c->toArray());
    }

    public function testToJson(): void
    {
        $c = new Collection(['a' => 1, 'b' => 2]);

        $this->assertSame('{"a":1,"b":2}', $c->toJson());
    }

    public function testJsonSerialize(): void
    {
        $c = new Collection([1, 2, 3]);

        $this->assertSame([1, 2, 3], $c->jsonSerialize());
        $this->assertSame('[1,2,3]', json_encode($c));
    }

    public function testToString(): void
    {
        $c = new Collection(['x' => 'y']);

        $this->assertSame('{"x":"y"}', (string) $c);
    }

    // -------------------------------------------------------------------------
    // Countable
    // -------------------------------------------------------------------------

    public function testCountable(): void
    {
        $c = new Collection([1, 2, 3]);

        $this->assertSame(3, $c->count());
        $this->assertCount(3, $c);
    }

    // -------------------------------------------------------------------------
    // ArrayAccess
    // -------------------------------------------------------------------------

    public function testArrayAccessOffsetExists(): void
    {
        $c = new Collection(['a' => 1]);

        $this->assertTrue(isset($c['a']));
        $this->assertFalse(isset($c['z']));
    }

    public function testArrayAccessOffsetGet(): void
    {
        $c = new Collection(['a' => 1]);

        $this->assertSame(1, $c['a']);
    }

    public function testArrayAccessOffsetSetWithKey(): void
    {
        $c      = new Collection();
        $c['k'] = 'v';

        $this->assertSame('v', $c['k']);
    }

    public function testArrayAccessOffsetSetWithoutKeyAppends(): void
    {
        $c   = new Collection([1, 2]);
        $c[] = 3;

        $this->assertSame([1, 2, 3], $c->all());
    }

    public function testArrayAccessOffsetUnset(): void
    {
        $c = new Collection(['a' => 1, 'b' => 2]);
        unset($c['a']);

        $this->assertSame(['b' => 2], $c->all());
    }

    // -------------------------------------------------------------------------
    // IteratorAggregate
    // -------------------------------------------------------------------------

    public function testGetIterator(): void
    {
        $c = new Collection([1, 2, 3]);

        $this->assertInstanceOf(\ArrayIterator::class, $c->getIterator());
    }

    public function testForeachIteration(): void
    {
        $c      = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
        $result = [];

        foreach ($c as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $result);
    }

    public function testGetCachingIterator(): void
    {
        $c = new Collection([1, 2, 3]);

        $this->assertInstanceOf(\CachingIterator::class, $c->getCachingIterator());
    }
}
