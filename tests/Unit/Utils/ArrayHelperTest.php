<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Utils;

use Awf\Utils\ArrayHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class ArrayHelperTest extends TestCase
{
    // -------------------------------------------------------------------------
    // toInteger
    // -------------------------------------------------------------------------

    public static function toIntegerProvider(): array
    {
        return [
            'numeric strings'        => [['1', '2', '3'], null, [1, 2, 3]],
            'mixed values'           => [['1', 2, '3.7', 'abc'], null, [1, 2, 3, 0]],
            'preserves keys'         => [['a' => '5', 'b' => '6'], null, ['a' => 5, 'b' => 6]],
            'empty array'            => [[], null, []],
            'non-array null default' => ['not-an-array', null, []],
            'non-array scalar default' => ['not-an-array', 7, [7]],
            'non-array array default'  => ['not-an-array', ['8', '9'], [8, 9]],
            'null with scalar default' => [null, 3, [3]],
        ];
    }

    #[DataProvider('toIntegerProvider')]
    public function testToInteger($input, $default, array $expected): void
    {
        self::assertSame($expected, ArrayHelper::toInteger($input, $default));
    }

    // -------------------------------------------------------------------------
    // toObject
    // -------------------------------------------------------------------------

    public function testToObjectFlat(): void
    {
        $obj = ArrayHelper::toObject(['foo' => 'bar', 'baz' => 1]);

        self::assertInstanceOf(stdClass::class, $obj);
        self::assertSame('bar', $obj->foo);
        self::assertSame(1, $obj->baz);
    }

    public function testToObjectNested(): void
    {
        $obj = ArrayHelper::toObject(['a' => ['b' => 'c']]);

        self::assertInstanceOf(stdClass::class, $obj);
        self::assertInstanceOf(stdClass::class, $obj->a);
        self::assertSame('c', $obj->a->b);
    }

    public function testToObjectEmpty(): void
    {
        $obj = ArrayHelper::toObject([]);

        self::assertInstanceOf(stdClass::class, $obj);
        self::assertSame([], get_object_vars($obj));
    }

    public function testToObjectCustomClass(): void
    {
        $obj = ArrayHelper::toObject(['x' => 1], ArrayHelperTestObject::class);

        self::assertInstanceOf(ArrayHelperTestObject::class, $obj);
        self::assertSame(1, $obj->x);
    }

    // -------------------------------------------------------------------------
    // toString
    // -------------------------------------------------------------------------

    public function testToStringDefaults(): void
    {
        self::assertSame('a="1" b="2"', ArrayHelper::toString(['a' => 1, 'b' => 2]));
    }

    public function testToStringCustomGlue(): void
    {
        self::assertSame('a:"1"|b:"2"', ArrayHelper::toString(['a' => 1, 'b' => 2], ':', '|'));
    }

    public function testToStringEmpty(): void
    {
        self::assertSame('', ArrayHelper::toString([]));
    }

    public function testToStringNestedWithoutOuterKey(): void
    {
        self::assertSame('x="1" y="2"', ArrayHelper::toString(['outer' => ['x' => 1, 'y' => 2]]));
    }

    public function testToStringNestedKeepingOuterKey(): void
    {
        self::assertSame('outer x="1"', ArrayHelper::toString(['outer' => ['x' => 1]], '=', ' ', true));
    }

    // -------------------------------------------------------------------------
    // fromObject
    // -------------------------------------------------------------------------

    public function testFromObjectFlat(): void
    {
        $obj = new stdClass();
        $obj->foo = 'bar';
        $obj->num = 5;

        self::assertSame(['foo' => 'bar', 'num' => 5], ArrayHelper::fromObject($obj));
    }

    public function testFromObjectRecurses(): void
    {
        $inner = new stdClass();
        $inner->b = 'c';
        $obj = new stdClass();
        $obj->a = $inner;

        self::assertSame(['a' => ['b' => 'c']], ArrayHelper::fromObject($obj));
    }

    public function testFromObjectNoRecurseKeepsObject(): void
    {
        $inner = new stdClass();
        $inner->b = 'c';
        $obj = new stdClass();
        $obj->a = $inner;

        $result = ArrayHelper::fromObject($obj, false);

        self::assertSame($inner, $result['a']);
    }

    public function testFromObjectWithRegex(): void
    {
        $obj = new stdClass();
        $obj->keep_me = 1;
        $obj->drop = 2;

        self::assertSame(['keep_me' => 1], ArrayHelper::fromObject($obj, true, '/^keep/'));
    }

    public function testFromObjectNonObjectReturnsNull(): void
    {
        self::assertNull(ArrayHelper::fromObject('not an object'));
        self::assertNull(ArrayHelper::fromObject(123));
    }

    // -------------------------------------------------------------------------
    // getColumn
    // -------------------------------------------------------------------------

    public function testGetColumnFromArrays(): void
    {
        $source = [
            ['id' => 1, 'name' => 'a'],
            ['id' => 2, 'name' => 'b'],
        ];

        self::assertSame([1, 2], ArrayHelper::getColumn($source, 'id'));
    }

    public function testGetColumnFromObjects(): void
    {
        $a = new stdClass();
        $a->id = 1;
        $b = new stdClass();
        $b->id = 2;

        self::assertSame([1, 2], ArrayHelper::getColumn([$a, $b], 'id'));
    }

    public function testGetColumnSkipsMissingKeys(): void
    {
        $source = [
            ['id' => 1],
            ['name' => 'b'],
            ['id' => 3],
        ];

        self::assertSame([1, 3], ArrayHelper::getColumn($source, 'id'));
    }

    public function testGetColumnEmpty(): void
    {
        self::assertSame([], ArrayHelper::getColumn([], 'id'));
    }

    // -------------------------------------------------------------------------
    // getValue
    // -------------------------------------------------------------------------

    public function testGetValueReturnsValue(): void
    {
        self::assertSame('bar', ArrayHelper::getValue(['foo' => 'bar'], 'foo'));
    }

    public function testGetValueReturnsDefaultWhenMissing(): void
    {
        self::assertSame('fallback', ArrayHelper::getValue([], 'foo', 'fallback'));
    }

    public function testGetValueReturnsDefaultWhenNull(): void
    {
        self::assertSame('fallback', ArrayHelper::getValue(['foo' => null], 'foo', 'fallback'));
    }

    public static function getValueTypeProvider(): array
    {
        return [
            'INT from string'      => ['abc-42xyz', 'INT', -42],
            'INTEGER alias'        => ['100', 'INTEGER', 100],
            'FLOAT'                => ['price 3.14 each', 'FLOAT', 3.14],
            'DOUBLE alias'         => ['2.5', 'DOUBLE', 2.5],
            'BOOL true'            => ['1', 'BOOL', true],
            'BOOLEAN false empty'  => ['', 'BOOLEAN', false],
            'STRING'               => [123, 'STRING', '123'],
            'WORD strips non-word' => ['a b-c!d', 'WORD', 'abcd'],
        ];
    }

    #[DataProvider('getValueTypeProvider')]
    public function testGetValueWithType($value, string $type, $expected): void
    {
        self::assertSame($expected, ArrayHelper::getValue(['k' => $value], 'k', null, $type));
    }

    public function testGetValueTypeArray(): void
    {
        self::assertSame(['x'], ArrayHelper::getValue(['k' => 'x'], 'k', null, 'ARRAY'));
        self::assertSame([1, 2], ArrayHelper::getValue(['k' => [1, 2]], 'k', null, 'ARRAY'));
    }

    public function testGetValueTypeNoneDoesNotCast(): void
    {
        self::assertSame('5', ArrayHelper::getValue(['k' => '5'], 'k', null, 'NONE'));
    }

    // -------------------------------------------------------------------------
    // invert
    // -------------------------------------------------------------------------

    public function testInvert(): void
    {
        $input = [
            'New'  => ['1000', '1500', '1750'],
            'Used' => ['3000', '4000'],
        ];

        $expected = [
            '1000' => 'New',
            '1500' => 'New',
            '1750' => 'New',
            '3000' => 'Used',
            '4000' => 'Used',
        ];

        self::assertSame($expected, ArrayHelper::invert($input));
    }

    public function testInvertSkipsNonArrayValues(): void
    {
        $input = [
            'a' => ['1'],
            'b' => 'not-an-array',
        ];

        self::assertSame(['1' => 'a'], ArrayHelper::invert($input));
    }

    public function testInvertSkipsNonScalarKeys(): void
    {
        $input = [
            'a' => ['ok', ['nested']],
        ];

        self::assertSame(['ok' => 'a'], ArrayHelper::invert($input));
    }

    public function testInvertEmpty(): void
    {
        self::assertSame([], ArrayHelper::invert([]));
    }

    // -------------------------------------------------------------------------
    // isAssociative
    // -------------------------------------------------------------------------

    public static function isAssociativeProvider(): array
    {
        return [
            'sequential list'    => [['a', 'b', 'c'], false],
            'associative'        => [['x' => 1, 'y' => 2], true],
            'string keys'        => [['foo' => 'bar'], true],
            'mixed keys'         => [[0 => 'a', 'b' => 'c'], true],
            'empty array'        => [[], false],
            'gapped numeric'     => [[0 => 'a', 2 => 'b'], true],
        ];
    }

    #[DataProvider('isAssociativeProvider')]
    public function testIsAssociative(array $array, bool $expected): void
    {
        self::assertSame($expected, ArrayHelper::isAssociative($array));
    }

    public function testIsAssociativeNonArray(): void
    {
        self::assertFalse(ArrayHelper::isAssociative('not an array'));
    }

    // -------------------------------------------------------------------------
    // pivot
    // -------------------------------------------------------------------------

    public function testPivotScalars(): void
    {
        // For scalars the value becomes the key, the index becomes the value.
        $result = ArrayHelper::pivot(['a', 'b']);

        self::assertSame(['a' => 0, 'b' => 1], $result);
    }

    public function testPivotScalarsWithDuplicates(): void
    {
        $result = ArrayHelper::pivot(['a', 'b', 'a']);

        self::assertSame(['a' => [0, 2], 'b' => 1], $result);
    }

    public function testPivotArraysOnKey(): void
    {
        $source = [
            ['id' => 'x', 'v' => 1],
            ['id' => 'y', 'v' => 2],
        ];

        $result = ArrayHelper::pivot($source, 'id');

        self::assertSame(
            [
                'x' => ['id' => 'x', 'v' => 1],
                'y' => ['id' => 'y', 'v' => 2],
            ],
            $result
        );
    }

    public function testPivotArraysOnKeyWithCollisions(): void
    {
        $source = [
            ['id' => 'x', 'v' => 1],
            ['id' => 'x', 'v' => 2],
            ['id' => 'x', 'v' => 3],
        ];

        $result = ArrayHelper::pivot($source, 'id');

        self::assertSame(
            [
                'x' => [
                    ['id' => 'x', 'v' => 1],
                    ['id' => 'x', 'v' => 2],
                    ['id' => 'x', 'v' => 3],
                ],
            ],
            $result
        );
    }

    public function testPivotArraysSkipsMissingKey(): void
    {
        $source = [
            ['id' => 'x'],
            ['other' => 'y'],
        ];

        self::assertSame(['x' => ['id' => 'x']], ArrayHelper::pivot($source, 'id'));
    }

    public function testPivotObjectsOnKey(): void
    {
        $a = new stdClass();
        $a->id = 'x';
        $b = new stdClass();
        $b->id = 'y';

        $result = ArrayHelper::pivot([$a, $b], 'id');

        self::assertSame(['x' => $a, 'y' => $b], $result);
    }

    public function testPivotEmpty(): void
    {
        self::assertSame([], ArrayHelper::pivot([], 'id'));
    }

    // -------------------------------------------------------------------------
    // sortObjects
    // -------------------------------------------------------------------------

    private static function makeObj(array $props): stdClass
    {
        $o = new stdClass();

        foreach ($props as $k => $v)
        {
            $o->$k = $v;
        }

        return $o;
    }

    public function testSortObjectsNumericAscending(): void
    {
        $a = self::makeObj(['n' => 3]);
        $b = self::makeObj(['n' => 1]);
        $c = self::makeObj(['n' => 2]);

        $sorted = ArrayHelper::sortObjects([$a, $b, $c], 'n');

        self::assertSame([$b, $c, $a], $sorted);
    }

    public function testSortObjectsNumericDescending(): void
    {
        $a = self::makeObj(['n' => 1]);
        $b = self::makeObj(['n' => 3]);
        $c = self::makeObj(['n' => 2]);

        $sorted = ArrayHelper::sortObjects([$a, $b, $c], 'n', -1);

        self::assertSame([$b, $c, $a], $sorted);
    }

    public function testSortObjectsStringCaseSensitive(): void
    {
        $a = self::makeObj(['s' => 'banana']);
        $b = self::makeObj(['s' => 'apple']);
        $c = self::makeObj(['s' => 'cherry']);

        $sorted = ArrayHelper::sortObjects([$a, $b, $c], 's');

        self::assertSame([$b, $a, $c], $sorted);
    }

    public function testSortObjectsCaseInsensitive(): void
    {
        $a = self::makeObj(['s' => 'Banana']);
        $b = self::makeObj(['s' => 'apple']);

        $sorted = ArrayHelper::sortObjects([$a, $b], 's', 1, false);

        self::assertSame([$b, $a], $sorted);
    }

    public function testSortObjectsMultiKey(): void
    {
        $a = self::makeObj(['grp' => 1, 'n' => 2]);
        $b = self::makeObj(['grp' => 1, 'n' => 1]);
        $c = self::makeObj(['grp' => 0, 'n' => 9]);

        $sorted = ArrayHelper::sortObjects([$a, $b, $c], ['grp', 'n']);

        self::assertSame([$c, $b, $a], $sorted);
    }

    // -------------------------------------------------------------------------
    // arrayUnique
    // -------------------------------------------------------------------------

    public function testArrayUniqueScalars(): void
    {
        $result = ArrayHelper::arrayUnique([1, 2, 2, 3, 1]);

        self::assertSame([0 => 1, 1 => 2, 3 => 3], $result);
    }

    public function testArrayUniqueNestedArrays(): void
    {
        $result = ArrayHelper::arrayUnique([
            ['a', 'b'],
            ['a', 'b'],
            ['c', 'd'],
        ]);

        self::assertSame([0 => ['a', 'b'], 2 => ['c', 'd']], $result);
    }

    public function testArrayUniqueEmpty(): void
    {
        self::assertSame([], ArrayHelper::arrayUnique([]));
    }

    // -------------------------------------------------------------------------
    // arraySearch
    // -------------------------------------------------------------------------

    public function testArraySearchExactPrefixMatch(): void
    {
        $haystack = ['k1' => 'apple', 'k2' => 'banana'];

        self::assertSame('k2', ArrayHelper::arraySearch('ban', $haystack));
    }

    public function testArraySearchRequiresPrefixPosition(): void
    {
        // 'ana' appears in 'banana' but not at position 0.
        self::assertFalse(ArrayHelper::arraySearch('ana', ['k' => 'banana']));
    }

    public function testArraySearchCaseSensitiveMiss(): void
    {
        self::assertFalse(ArrayHelper::arraySearch('APP', ['k' => 'apple']));
    }

    public function testArraySearchCaseInsensitive(): void
    {
        self::assertSame('k', ArrayHelper::arraySearch('APP', ['k' => 'apple'], false));
    }

    public function testArraySearchNotFound(): void
    {
        self::assertFalse(ArrayHelper::arraySearch('zzz', ['k' => 'apple']));
    }
}

/**
 * A simple object allowing dynamic properties, used for the toObject() custom-class test.
 */
class ArrayHelperTestObject extends stdClass
{
}
