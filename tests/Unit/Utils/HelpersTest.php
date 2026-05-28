<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Utils;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the global helper functions defined in src/Utils/helpers.php.
 *
 * The file is autoloaded via Composer "files", so the functions are already
 * available — we just call them directly.
 */
class HelpersTest extends TestCase
{
	// -------------------------------------------------------------------------
	// akeeba_array_add
	// -------------------------------------------------------------------------

	public function testArrayAddInsertsWhenKeyAbsent(): void
	{
		$result = akeeba_array_add([], 'foo', 'bar');

		self::assertSame(['foo' => 'bar'], $result);
	}

	public function testArrayAddDoesNotOverwriteExistingKey(): void
	{
		$result = akeeba_array_add(['foo' => 'original'], 'foo', 'new');

		self::assertSame(['foo' => 'original'], $result);
	}

	public function testArrayAddPreservesOtherKeys(): void
	{
		$result = akeeba_array_add(['a' => 1, 'b' => 2], 'c', 3);

		self::assertSame(['a' => 1, 'b' => 2, 'c' => 3], $result);
	}

	// -------------------------------------------------------------------------
	// akeeba_array_build
	// -------------------------------------------------------------------------

	public function testArrayBuildTransformsKeysAndValues(): void
	{
		$result = akeeba_array_build(['a' => 1, 'b' => 2], function ($key, $value) {
			return [strtoupper($key), $value * 2];
		});

		self::assertSame(['A' => 2, 'B' => 4], $result);
	}

	public function testArrayBuildEmptyArrayReturnsEmpty(): void
	{
		$result = akeeba_array_build([], function ($key, $value) {
			return [$key, $value];
		});

		self::assertSame([], $result);
	}

	// -------------------------------------------------------------------------
	// akeeba_array_divide
	// -------------------------------------------------------------------------

	public function testArrayDivideReturnsKeysAndValues(): void
	{
		[$keys, $values] = akeeba_array_divide(['a' => 1, 'b' => 2, 'c' => 3]);

		self::assertSame(['a', 'b', 'c'], $keys);
		self::assertSame([1, 2, 3], $values);
	}

	public function testArrayDivideEmptyArray(): void
	{
		[$keys, $values] = akeeba_array_divide([]);

		self::assertSame([], $keys);
		self::assertSame([], $values);
	}

	// -------------------------------------------------------------------------
	// akeeba_array_dot
	// -------------------------------------------------------------------------

	public function testArrayDotFlattensNestedArray(): void
	{
		$input    = ['foo' => ['bar' => ['baz' => 'value']]];
		$expected = ['foo.bar.baz' => 'value'];

		self::assertSame($expected, akeeba_array_dot($input));
	}

	public function testArrayDotMixedDepth(): void
	{
		$input = [
			'a'  => 1,
			'b'  => ['c' => 2, 'd' => 3],
		];
		$expected = ['a' => 1, 'b.c' => 2, 'b.d' => 3];

		self::assertSame($expected, akeeba_array_dot($input));
	}

	public function testArrayDotAcceptsPrepend(): void
	{
		$result = akeeba_array_dot(['x' => 1], 'prefix.');

		self::assertSame(['prefix.x' => 1], $result);
	}

	public function testArrayDotEmptyArrayReturnsEmpty(): void
	{
		self::assertSame([], akeeba_array_dot([]));
	}

	// -------------------------------------------------------------------------
	// akeeba_array_except
	// -------------------------------------------------------------------------

	public function testArrayExceptRemovesSpecifiedKeys(): void
	{
		$array  = ['a' => 1, 'b' => 2, 'c' => 3];
		$result = akeeba_array_except($array, ['a', 'c']);

		self::assertSame(['b' => 2], $result);
	}

	public function testArrayExceptWithSingleKeyAsString(): void
	{
		$array  = ['a' => 1, 'b' => 2];
		$result = akeeba_array_except($array, 'a');

		self::assertSame(['b' => 2], $result);
	}

	public function testArrayExceptNonExistentKeyIsIgnored(): void
	{
		$array  = ['a' => 1];
		$result = akeeba_array_except($array, ['z']);

		self::assertSame(['a' => 1], $result);
	}

	// -------------------------------------------------------------------------
	// akeeba_array_fetch
	// -------------------------------------------------------------------------

	public function testArrayFetchExtractsNestedValues(): void
	{
		$array = [
			['name' => ['first' => 'Alice']],
			['name' => ['first' => 'Bob']],
		];

		$result = akeeba_array_fetch($array, 'name.first');

		self::assertSame(['Alice', 'Bob'], $result);
	}

	public function testArrayFetchSingleSegment(): void
	{
		$array = [
			['color' => 'red'],
			['color' => 'blue'],
		];

		$result = akeeba_array_fetch($array, 'color');

		self::assertSame(['red', 'blue'], $result);
	}

	// -------------------------------------------------------------------------
	// akeeba_array_first
	// -------------------------------------------------------------------------

	public function testArrayFirstReturnsMatchingElement(): void
	{
		$array  = [1, 2, 3, 4, 5];
		$result = akeeba_array_first($array, fn($key, $value) => $value > 3);

		self::assertSame(4, $result);
	}

	public function testArrayFirstReturnsDefaultWhenNoMatch(): void
	{
		$result = akeeba_array_first([1, 2, 3], fn($key, $value) => $value > 10, 'default');

		self::assertSame('default', $result);
	}

	public function testArrayFirstDefaultCanBeAClosure(): void
	{
		$result = akeeba_array_first([], fn($k, $v) => true, fn() => 'lazy');

		self::assertSame('lazy', $result);
	}

	public function testArrayFirstReturnsNullByDefault(): void
	{
		$result = akeeba_array_first([], fn($k, $v) => true);

		self::assertNull($result);
	}

	// -------------------------------------------------------------------------
	// akeeba_array_last
	// -------------------------------------------------------------------------

	public function testArrayLastReturnsLastMatchingElement(): void
	{
		$array  = [1, 2, 3, 4, 5];
		$result = akeeba_array_last($array, fn($key, $value) => $value < 4);

		self::assertSame(3, $result);
	}

	public function testArrayLastReturnsDefaultWhenNoMatch(): void
	{
		$result = akeeba_array_last([1, 2, 3], fn($key, $value) => $value > 10, 'fallback');

		self::assertSame('fallback', $result);
	}

	// -------------------------------------------------------------------------
	// akeeba_array_flatten
	// -------------------------------------------------------------------------

	public function testArrayFlattenFlattensMultiDimensional(): void
	{
		$input  = [1, [2, 3], [4, [5, 6]]];
		$result = akeeba_array_flatten($input);

		self::assertSame([1, 2, 3, 4, 5, 6], $result);
	}

	public function testArrayFlattenAlreadyFlatArrayIsUnchanged(): void
	{
		self::assertSame([1, 2, 3], akeeba_array_flatten([1, 2, 3]));
	}

	public function testArrayFlattenEmptyArray(): void
	{
		self::assertSame([], akeeba_array_flatten([]));
	}

	// -------------------------------------------------------------------------
	// akeeba_array_forget
	// -------------------------------------------------------------------------

	public function testArrayForgetRemovesTopLevelKey(): void
	{
		$array = ['a' => 1, 'b' => 2];
		akeeba_array_forget($array, 'a');

		self::assertSame(['b' => 2], $array);
	}

	public function testArrayForgetRemovesDotNotationKey(): void
	{
		$array = ['a' => ['b' => ['c' => 3, 'd' => 4]]];
		akeeba_array_forget($array, 'a.b.c');

		self::assertSame(['a' => ['b' => ['d' => 4]]], $array);
	}

	public function testArrayForgetNonExistentKeyLeavesArrayIntact(): void
	{
		$array    = ['a' => 1];
		$original = $array;
		akeeba_array_forget($array, 'z.x');

		self::assertSame($original, $array);
	}

	// -------------------------------------------------------------------------
	// akeeba_array_get
	// -------------------------------------------------------------------------

	public function testArrayGetTopLevelKey(): void
	{
		self::assertSame('bar', akeeba_array_get(['foo' => 'bar'], 'foo'));
	}

	public function testArrayGetDotNotation(): void
	{
		$array = ['a' => ['b' => ['c' => 'deep']]];

		self::assertSame('deep', akeeba_array_get($array, 'a.b.c'));
	}

	public function testArrayGetReturnsDefaultForMissingKey(): void
	{
		self::assertSame('default', akeeba_array_get([], 'missing', 'default'));
	}

	public function testArrayGetNullKeyReturnsWholeArray(): void
	{
		$array = ['a' => 1];

		self::assertSame($array, akeeba_array_get($array, null));
	}

	public function testArrayGetDefaultCanBeAClosure(): void
	{
		$result = akeeba_array_get([], 'missing', fn() => 'lazy');

		self::assertSame('lazy', $result);
	}

	// -------------------------------------------------------------------------
	// akeeba_array_only
	// -------------------------------------------------------------------------

	public function testArrayOnlyReturnsSubset(): void
	{
		$array  = ['a' => 1, 'b' => 2, 'c' => 3];
		$result = akeeba_array_only($array, ['a', 'c']);

		self::assertSame(['a' => 1, 'c' => 3], $result);
	}

	public function testArrayOnlyWithSingleKeyAsString(): void
	{
		$array  = ['a' => 1, 'b' => 2];
		$result = akeeba_array_only($array, 'b');

		self::assertSame(['b' => 2], $result);
	}

	// -------------------------------------------------------------------------
	// akeeba_array_pluck
	// -------------------------------------------------------------------------

	public function testArrayPluckFromArrayOfArrays(): void
	{
		$array = [
			['name' => 'Alice', 'age' => 30],
			['name' => 'Bob',   'age' => 25],
		];

		self::assertSame(['Alice', 'Bob'], akeeba_array_pluck($array, 'name'));
	}

	public function testArrayPluckWithKey(): void
	{
		$array = [
			['id' => 1, 'name' => 'Alice'],
			['id' => 2, 'name' => 'Bob'],
		];

		$result = akeeba_array_pluck($array, 'name', 'id');

		self::assertSame([1 => 'Alice', 2 => 'Bob'], $result);
	}

	public function testArrayPluckFromArrayOfObjects(): void
	{
		$obj1        = new \stdClass();
		$obj1->color = 'red';
		$obj2        = new \stdClass();
		$obj2->color = 'blue';

		$result = akeeba_array_pluck([$obj1, $obj2], 'color');

		self::assertSame(['red', 'blue'], $result);
	}

	// -------------------------------------------------------------------------
	// akeeba_array_pull
	// -------------------------------------------------------------------------

	public function testArrayPullReturnsAndRemovesValue(): void
	{
		$array  = ['a' => 1, 'b' => 2, 'c' => 3];
		$result = akeeba_array_pull($array, 'b');

		self::assertSame(2, $result);
		self::assertSame(['a' => 1, 'c' => 3], $array);
	}

	public function testArrayPullWithDotNotation(): void
	{
		$array  = ['x' => ['y' => 'deep']];
		$result = akeeba_array_pull($array, 'x.y');

		self::assertSame('deep', $result);
		self::assertSame(['x' => []], $array);
	}

	// -------------------------------------------------------------------------
	// akeeba_array_set
	// -------------------------------------------------------------------------

	public function testArraySetTopLevelKey(): void
	{
		$array = [];
		akeeba_array_set($array, 'foo', 'bar');

		self::assertSame(['foo' => 'bar'], $array);
	}

	public function testArraySetWithDotNotationCreatesNestedStructure(): void
	{
		$array = [];
		akeeba_array_set($array, 'a.b.c', 'value');

		self::assertSame(['a' => ['b' => ['c' => 'value']]], $array);
	}

	public function testArraySetNullKeyReplacesWholeArray(): void
	{
		$array = ['old' => 'data'];
		akeeba_array_set($array, null, 'replaced');

		self::assertSame('replaced', $array);
	}

	public function testArraySetOverwritesExistingValue(): void
	{
		$array = ['a' => ['b' => 'original']];
		akeeba_array_set($array, 'a.b', 'updated');

		self::assertSame(['a' => ['b' => 'updated']], $array);
	}

	// -------------------------------------------------------------------------
	// akeeba_array_sort
	// -------------------------------------------------------------------------

	public function testArraySortByClosureResult(): void
	{
		$array  = [3, 1, 4, 1, 5, 9, 2, 6];
		$result = akeeba_array_sort($array, fn($value) => $value);

		self::assertSame([1, 1, 2, 3, 4, 5, 6, 9], array_values($result));
	}

	public function testArraySortByProperty(): void
	{
		$array = [
			['name' => 'Charlie', 'age' => 30],
			['name' => 'Alice',   'age' => 25],
			['name' => 'Bob',     'age' => 28],
		];

		$result = akeeba_array_sort($array, fn($item) => $item['name']);

		self::assertSame(['Alice', 'Bob', 'Charlie'], array_column(array_values($result), 'name'));
	}

	// -------------------------------------------------------------------------
	// akeeba_array_where
	// -------------------------------------------------------------------------

	public function testArrayWhereFiltersElements(): void
	{
		$array  = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4];
		$result = akeeba_array_where($array, fn($key, $value) => $value % 2 === 0);

		self::assertSame(['b' => 2, 'd' => 4], $result);
	}

	public function testArrayWhereNoMatchReturnsEmpty(): void
	{
		$array  = ['a' => 1, 'b' => 3];
		$result = akeeba_array_where($array, fn($key, $value) => $value > 100);

		self::assertSame([], $result);
	}

	// -------------------------------------------------------------------------
	// akeeba_ends_with
	// -------------------------------------------------------------------------

	public static function endsWithProvider(): array
	{
		return [
			'ends with suffix'            => ['hello world', 'world', true],
			'does not end with suffix'    => ['hello world', 'hello', false],
			'empty needle always matches' => ['anything', '', false],  // '' has 0 length; substr($h, 0) === '' only if haystack is also ''
			'exact match'                 => ['foo', 'foo', true],
			'array needles — one match'   => ['hello.php', ['.txt', '.php'], true],
			'array needles — no match'    => ['hello.php', ['.txt', '.html'], false],
		];
	}

	#[DataProvider('endsWithProvider')]
	public function testEndsWith(string $haystack, string|array $needle, bool $expected): void
	{
		self::assertSame($expected, akeeba_ends_with($haystack, $needle));
	}

	public function testEndsWithEmptyNeedleOnNonEmptyHaystack(): void
	{
		// substr('hello', -0) === substr('hello', 0) which is 'hello', not '' — so no match
		self::assertFalse(akeeba_ends_with('hello', ''));
	}

	public function testEndsWithEmptyNeedleOnEmptyHaystack(): void
	{
		// substr('', -0) === '' === '' — match
		self::assertTrue(akeeba_ends_with('', ''));
	}

	// -------------------------------------------------------------------------
	// akeeba_last
	// -------------------------------------------------------------------------

	public function testLastReturnsLastElement(): void
	{
		self::assertSame(3, akeeba_last([1, 2, 3]));
	}

	public function testLastWithSingleElement(): void
	{
		self::assertSame('only', akeeba_last(['only']));
	}

	public function testLastWithAssociativeArray(): void
	{
		self::assertSame('z', akeeba_last(['a' => 'x', 'b' => 'y', 'c' => 'z']));
	}

	// -------------------------------------------------------------------------
	// akeeba_object_get
	// -------------------------------------------------------------------------

	public function testObjectGetTopLevelProperty(): void
	{
		$obj      = new \stdClass();
		$obj->foo = 'bar';

		self::assertSame('bar', akeeba_object_get($obj, 'foo'));
	}

	public function testObjectGetDotNotation(): void
	{
		$inner       = new \stdClass();
		$inner->baz  = 'deep';
		$outer       = new \stdClass();
		$outer->bar  = $inner;
		$root        = new \stdClass();
		$root->foo   = $outer;

		self::assertSame('deep', akeeba_object_get($root, 'foo.bar.baz'));
	}

	public function testObjectGetNullOrEmptyKeyReturnsObject(): void
	{
		$obj = new \stdClass();

		self::assertSame($obj, akeeba_object_get($obj, null));
		self::assertSame($obj, akeeba_object_get($obj, ''));
		self::assertSame($obj, akeeba_object_get($obj, '   '));
	}

	public function testObjectGetMissingPropertyReturnsDefault(): void
	{
		$obj = new \stdClass();

		self::assertSame('fallback', akeeba_object_get($obj, 'missing', 'fallback'));
	}

	public function testObjectGetDefaultCanBeAClosure(): void
	{
		$obj    = new \stdClass();
		$result = akeeba_object_get($obj, 'missing', fn() => 'lazy');

		self::assertSame('lazy', $result);
	}

	// -------------------------------------------------------------------------
	// akeeba_preg_replace_sub
	// -------------------------------------------------------------------------

	public function testPregReplaceSubConsumesReplacementsSequentially(): void
	{
		$replacements = ['first', 'second', 'third'];
		$result       = akeeba_preg_replace_sub('/\?/', $replacements, '? and ? and ?');

		self::assertSame('first and second and third', $result);
	}

	public function testPregReplaceSubFewerReplacementsThanMatches(): void
	{
		$replacements = ['A'];
		$result       = akeeba_preg_replace_sub('/X/', $replacements, 'X X X');

		// Only the first match gets a replacement; subsequent array_shifts return null → ''
		self::assertSame('A  ', $result);
	}

	// -------------------------------------------------------------------------
	// akeeba_starts_with
	// -------------------------------------------------------------------------

	public static function startsWithProvider(): array
	{
		return [
			'starts with prefix'          => ['hello world', 'hello', true],
			'does not start with prefix'  => ['hello world', 'world', false],
			'empty needle never matches'  => ['anything', '', false],
			'exact match'                 => ['foo', 'foo', true],
			'array needles — one match'   => ['/home/user', ['/', '/usr'], true],
			'array needles — no match'    => ['/home/user', ['/usr', '/var'], false],
		];
	}

	#[DataProvider('startsWithProvider')]
	public function testStartsWith(string $haystack, string|array $needle, bool $expected): void
	{
		self::assertSame($expected, akeeba_starts_with($haystack, $needle));
	}

	// -------------------------------------------------------------------------
	// akeeba_value
	// -------------------------------------------------------------------------

	public function testValueReturnsScalarDirectly(): void
	{
		self::assertSame(42, akeeba_value(42));
		self::assertSame('hello', akeeba_value('hello'));
		self::assertNull(akeeba_value(null));
	}

	public function testValueInvokesClosure(): void
	{
		$result = akeeba_value(fn() => 'from closure');

		self::assertSame('from closure', $result);
	}

	public function testValueClosureCanReturnNull(): void
	{
		self::assertNull(akeeba_value(fn() => null));
	}

	// -------------------------------------------------------------------------
	// akeeba_with
	// -------------------------------------------------------------------------

	public function testWithReturnsTheSameObject(): void
	{
		$obj = new \stdClass();

		self::assertSame($obj, akeeba_with($obj));
	}

	public function testWithReturnsPrimitivesUnchanged(): void
	{
		self::assertSame(99, akeeba_with(99));
		self::assertSame('test', akeeba_with('test'));
		self::assertNull(akeeba_with(null));
	}
}
