<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Registry;

use Awf\Registry\Registry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RegistryTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Constructor
    // -------------------------------------------------------------------------

    public function testConstructorWithNoArgCreatesEmptyRegistry(): void
    {
        $reg = new Registry();
        self::assertSame([], $reg->toArray());
    }

    public function testConstructorWithArrayBindsData(): void
    {
        $reg = new Registry(['foo' => 'bar', 'baz' => 42]);
        self::assertSame('bar', $reg->get('foo'));
        self::assertSame(42, $reg->get('baz'));
    }

    public function testConstructorWithObjectBindsData(): void
    {
        $obj = new \stdClass();
        $obj->foo = 'bar';
        $obj->num = 99;

        $reg = new Registry($obj);
        self::assertSame('bar', $reg->get('foo'));
        self::assertSame(99, $reg->get('num'));
    }

    public function testConstructorWithJsonStringLoadsData(): void
    {
        $reg = new Registry('{"key":"value","num":7}');
        self::assertSame('value', $reg->get('key'));
        self::assertSame(7, $reg->get('num'));
    }

    public function testConstructorWithEmptyStringCreatesEmptyRegistry(): void
    {
        $reg = new Registry('');
        self::assertSame([], $reg->toArray());
    }

    // -------------------------------------------------------------------------
    // set / get — flat keys
    // -------------------------------------------------------------------------

    public function testSetAndGetFlatKey(): void
    {
        $reg = new Registry();
        $reg->set('name', 'Alice');
        self::assertSame('Alice', $reg->get('name'));
    }

    public function testGetReturnsNullForMissingKey(): void
    {
        $reg = new Registry();
        self::assertNull($reg->get('missing'));
    }

    public function testGetReturnsDefaultForMissingKey(): void
    {
        $reg = new Registry();
        self::assertSame('default', $reg->get('missing', 'default'));
    }

    public function testGetReturnsDefaultForEmptyPath(): void
    {
        $reg = new Registry();
        self::assertSame('fallback', $reg->get('', 'fallback'));
    }

    public function testSetOverwritesExistingValue(): void
    {
        $reg = new Registry();
        $reg->set('key', 'first');
        $reg->set('key', 'second');
        self::assertSame('second', $reg->get('key'));
    }

    public function testSetReturnsStoredValue(): void
    {
        $reg = new Registry();
        $result = $reg->set('key', 'value');
        self::assertSame('value', $result);
    }

    public function testSetNullRemovesKey(): void
    {
        $reg = new Registry();
        $reg->set('key', 'value');
        $reg->set('key', null);
        self::assertFalse($reg->exists('key'));
    }

    // -------------------------------------------------------------------------
    // set / get — dot-path nesting
    // -------------------------------------------------------------------------

    public function testSetAndGetNestedPath(): void
    {
        $reg = new Registry();
        $reg->set('foo.bar', 'hello');
        self::assertSame('hello', $reg->get('foo.bar'));
    }

    public function testSetDeepNestedPath(): void
    {
        $reg = new Registry();
        $reg->set('a.b.c.d', 'deep');
        self::assertSame('deep', $reg->get('a.b.c.d'));
    }

    public function testSetNestedCreatesIntermediateNodes(): void
    {
        $reg = new Registry();
        $reg->set('x.y.z', 'value');
        self::assertSame('value', $reg->get('x.y.z'));
        // Parent node should exist
        self::assertTrue($reg->exists('x'));
        self::assertTrue($reg->exists('x.y'));
    }

    public function testSetOverwritesNestedValue(): void
    {
        $reg = new Registry();
        $reg->set('a.b', 'first');
        $reg->set('a.b', 'second');
        self::assertSame('second', $reg->get('a.b'));
    }

    public function testGetReturnsDefaultForMissingNestedKey(): void
    {
        $reg = new Registry();
        $reg->set('a.b', 'value');
        self::assertSame('default', $reg->get('a.c', 'default'));
    }

    public function testGetReturnsDefaultWhenIntermediateNodeMissing(): void
    {
        $reg = new Registry();
        self::assertNull($reg->get('x.y.z'));
    }

    // -------------------------------------------------------------------------
    // exists
    // -------------------------------------------------------------------------

    public function testExistsReturnsTrueForExistingFlatKey(): void
    {
        $reg = new Registry();
        $reg->set('key', 'value');
        self::assertTrue($reg->exists('key'));
    }

    public function testExistsReturnsFalseForMissingFlatKey(): void
    {
        $reg = new Registry();
        self::assertFalse($reg->exists('missing'));
    }

    public function testExistsReturnsTrueForExistingNestedKey(): void
    {
        $reg = new Registry();
        $reg->set('a.b.c', 'value');
        self::assertTrue($reg->exists('a.b.c'));
    }

    public function testExistsReturnsFalseForMissingNestedKey(): void
    {
        $reg = new Registry();
        $reg->set('a.b', 'value');
        self::assertFalse($reg->exists('a.c'));
    }

    public function testExistsReturnsFalseForEmptyPath(): void
    {
        $reg = new Registry();
        self::assertFalse($reg->exists(''));
    }

    // -------------------------------------------------------------------------
    // def
    // -------------------------------------------------------------------------

    public function testDefSetsValueWhenKeyDoesNotExist(): void
    {
        $reg = new Registry();
        $result = $reg->def('key', 'default');
        self::assertSame('default', $result);
        self::assertSame('default', $reg->get('key'));
    }

    public function testDefDoesNotOverwriteExistingValue(): void
    {
        $reg = new Registry();
        $reg->set('key', 'existing');
        $result = $reg->def('key', 'default');
        self::assertSame('existing', $result);
        self::assertSame('existing', $reg->get('key'));
    }

    public function testDefDefaultIsEmptyString(): void
    {
        $reg = new Registry();
        $result = $reg->def('key');
        self::assertSame('', $result);
    }

    // -------------------------------------------------------------------------
    // separator
    // -------------------------------------------------------------------------

    public function testCustomSeparator(): void
    {
        $reg = new Registry();
        $reg->separator = '/';
        $reg->set('foo/bar', 'hello');
        self::assertSame('hello', $reg->get('foo/bar'));
        self::assertTrue($reg->exists('foo/bar'));
    }

    public function testSetWithExplicitSeparator(): void
    {
        $reg = new Registry();
        $reg->set('foo->bar', 'value', '->');
        // get() uses the instance separator, so change it to match
        $reg->separator = '->';
        self::assertSame('value', $reg->get('foo->bar'));
    }

    public function testSetWithDoubleSeparatorSkipsEmptyNodes(): void
    {
        $reg = new Registry();
        // Double separator produces empty node that should be filtered
        $reg->set('foo..bar', 'value');
        // Should still be stored correctly
        self::assertSame('value', $reg->get('foo.bar'));
    }

    // -------------------------------------------------------------------------
    // loadArray
    // -------------------------------------------------------------------------

    public function testLoadArrayWithFlatArray(): void
    {
        $reg = new Registry();
        $reg->loadArray(['foo' => 'bar', 'baz' => 42]);
        self::assertSame('bar', $reg->get('foo'));
        self::assertSame(42, $reg->get('baz'));
    }

    public function testLoadArrayWithNestedArray(): void
    {
        $reg = new Registry();
        $reg->loadArray(['a' => ['b' => 'value']]);
        self::assertSame('value', $reg->get('a.b'));
    }

    public function testLoadArrayMergesWithExistingData(): void
    {
        $reg = new Registry();
        $reg->set('existing', 'kept');
        $reg->loadArray(['new' => 'added']);
        self::assertSame('kept', $reg->get('existing'));
        self::assertSame('added', $reg->get('new'));
    }

    public function testLoadArrayFlattenedMode(): void
    {
        $reg = new Registry();
        $reg->loadArray(['a.b' => 'value', 'c.d' => 'other'], true);
        self::assertSame('value', $reg->get('a.b'));
        self::assertSame('other', $reg->get('c.d'));
    }

    public function testLoadArrayReturnsThis(): void
    {
        $reg = new Registry();
        $result = $reg->loadArray(['foo' => 'bar']);
        self::assertSame($reg, $result);
    }

    // -------------------------------------------------------------------------
    // loadObject
    // -------------------------------------------------------------------------

    public function testLoadObjectBindsPublicProperties(): void
    {
        $obj = new \stdClass();
        $obj->key1 = 'val1';
        $obj->key2 = 'val2';

        $reg = new Registry();
        $reg->loadObject($obj);
        self::assertSame('val1', $reg->get('key1'));
        self::assertSame('val2', $reg->get('key2'));
    }

    public function testLoadObjectWithNestedObject(): void
    {
        $inner = new \stdClass();
        $inner->baz = 'deep';
        $obj = new \stdClass();
        $obj->foo = $inner;

        $reg = new Registry();
        $reg->loadObject($obj);
        self::assertSame('deep', $reg->get('foo.baz'));
    }

    public function testLoadObjectReturnsThis(): void
    {
        $obj = new \stdClass();
        $reg = new Registry();
        $result = $reg->loadObject($obj);
        self::assertSame($reg, $result);
    }

    // -------------------------------------------------------------------------
    // loadString
    // -------------------------------------------------------------------------

    public function testLoadStringJson(): void
    {
        $reg = new Registry();
        $reg->loadString('{"name":"test","val":123}');
        self::assertSame('test', $reg->get('name'));
        self::assertSame(123, $reg->get('val'));
    }

    public function testLoadStringReturnsThis(): void
    {
        $reg = new Registry();
        $result = $reg->loadString('{"x":1}');
        self::assertSame($reg, $result);
    }

    // -------------------------------------------------------------------------
    // merge
    // -------------------------------------------------------------------------

    public function testMergeAddsKeysFromSource(): void
    {
        $base = new Registry();
        $base->set('a', 'first');

        $source = new Registry();
        $source->set('b', 'second');

        $base->merge($source);
        self::assertSame('first', $base->get('a'));
        self::assertSame('second', $base->get('b'));
    }

    public function testMergeOverwritesExistingKeys(): void
    {
        $base = new Registry();
        $base->set('key', 'original');

        $source = new Registry();
        $source->set('key', 'overwritten');

        $base->merge($source);
        self::assertSame('overwritten', $base->get('key'));
    }

    public function testMergeWithRecursiveTrue(): void
    {
        $base = new Registry();
        $base->set('a.b', 'original');
        $base->set('a.c', 'kept');

        $source = new Registry();
        $source->set('a.b', 'replaced');

        $base->merge($source, true);
        self::assertSame('replaced', $base->get('a.b'));
        self::assertSame('kept', $base->get('a.c'));
    }

    public function testMergeReturnsFalseForNonRegistry(): void
    {
        $reg = new Registry();
        $result = $reg->merge('not a registry');
        self::assertFalse($result);
    }

    public function testMergeReturnsThis(): void
    {
        $base = new Registry();
        $source = new Registry();
        $result = $base->merge($source);
        self::assertSame($base, $result);
    }

    // -------------------------------------------------------------------------
    // toArray
    // -------------------------------------------------------------------------

    public function testToArrayOnEmptyRegistry(): void
    {
        $reg = new Registry();
        self::assertSame([], $reg->toArray());
    }

    public function testToArrayReturnsFlatData(): void
    {
        $reg = new Registry();
        $reg->set('a', 1);
        $reg->set('b', 'two');
        self::assertSame(['a' => 1, 'b' => 'two'], $reg->toArray());
    }

    public function testToArrayReturnsNestedData(): void
    {
        $reg = new Registry();
        $reg->set('x.y', 'value');
        $array = $reg->toArray();
        self::assertArrayHasKey('x', $array);
        self::assertArrayHasKey('y', $array['x']);
        self::assertSame('value', $array['x']['y']);
    }

    // -------------------------------------------------------------------------
    // toObject
    // -------------------------------------------------------------------------

    public function testToObjectReturnsStdClass(): void
    {
        $reg = new Registry();
        $reg->set('name', 'test');
        $obj = $reg->toObject();
        self::assertInstanceOf(\stdClass::class, $obj);
        self::assertSame('test', $obj->name);
    }

    // -------------------------------------------------------------------------
    // toString
    // -------------------------------------------------------------------------

    public function testToStringDefaultsToJson(): void
    {
        $reg = new Registry();
        $reg->set('key', 'value');
        $json = $reg->toString();
        $decoded = json_decode($json, true);
        self::assertSame('value', $decoded['key']);
    }

    public function testMagicToStringUsesToString(): void
    {
        $reg = new Registry();
        $reg->set('k', 'v');
        $str = (string) $reg;
        self::assertStringContainsString('"k"', $str);
        self::assertStringContainsString('"v"', $str);
    }

    // -------------------------------------------------------------------------
    // count
    // -------------------------------------------------------------------------

    public function testCountReturnsZeroForEmptyRegistry(): void
    {
        $reg = new Registry();
        self::assertCount(0, $reg);
    }

    public function testCountReturnsTopLevelKeyCount(): void
    {
        $reg = new Registry();
        $reg->set('a', 1);
        $reg->set('b', 2);
        $reg->set('c', 3);
        self::assertCount(3, $reg);
    }

    public function testCountDoesNotCountNestedKeys(): void
    {
        $reg = new Registry();
        $reg->set('parent.child1', 1);
        $reg->set('parent.child2', 2);
        // Only one top-level key
        self::assertCount(1, $reg);
    }

    // -------------------------------------------------------------------------
    // jsonSerialize
    // -------------------------------------------------------------------------

    public function testJsonSerializeProducesCorrectJson(): void
    {
        $reg = new Registry();
        $reg->set('foo', 'bar');
        $json = json_encode($reg);
        $decoded = json_decode($json, true);
        self::assertSame('bar', $decoded['foo']);
    }

    // -------------------------------------------------------------------------
    // clone
    // -------------------------------------------------------------------------

    public function testCloneCreatesIndependentCopy(): void
    {
        $original = new Registry();
        $original->set('a', 'original');

        $clone = clone $original;
        $clone->set('a', 'modified');

        self::assertSame('original', $original->get('a'));
        self::assertSame('modified', $clone->get('a'));
    }

    public function testCloneDeepCopiesNestedData(): void
    {
        $original = new Registry();
        $original->set('x.y', 'deep');

        $clone = clone $original;
        $clone->set('x.y', 'changed');

        self::assertSame('deep', $original->get('x.y'));
        self::assertSame('changed', $clone->get('x.y'));
    }

    // -------------------------------------------------------------------------
    // ArrayAccess interface
    // -------------------------------------------------------------------------

    public function testOffsetSetAndGet(): void
    {
        $reg = new Registry();
        $reg['key'] = 'value';
        self::assertSame('value', $reg['key']);
    }

    public function testOffsetExists(): void
    {
        $reg = new Registry();
        $reg['key'] = 'value';
        self::assertTrue(isset($reg['key']));
        self::assertFalse(isset($reg['missing']));
    }

    public function testOffsetUnset(): void
    {
        $reg = new Registry();
        $reg['key'] = 'value';
        unset($reg['key']);
        self::assertFalse(isset($reg['key']));
    }

    // -------------------------------------------------------------------------
    // IteratorAggregate interface
    // -------------------------------------------------------------------------

    public function testGetIteratorAllowsForeach(): void
    {
        $reg = new Registry();
        $reg->set('a', 1);
        $reg->set('b', 2);

        $collected = [];
        foreach ($reg as $key => $value) {
            $collected[$key] = $value;
        }

        self::assertSame(1, $collected['a']);
        self::assertSame(2, $collected['b']);
    }

    // -------------------------------------------------------------------------
    // getInstance (static singleton)
    // -------------------------------------------------------------------------

    public function testGetInstanceReturnsSameObjectForSameId(): void
    {
        $r1 = Registry::getInstance('test_instance_a');
        $r2 = Registry::getInstance('test_instance_a');
        self::assertSame($r1, $r2);
    }

    public function testGetInstanceReturnsDifferentObjectsForDifferentIds(): void
    {
        $r1 = Registry::getInstance('test_instance_x');
        $r2 = Registry::getInstance('test_instance_y');
        self::assertNotSame($r1, $r2);
    }

    // -------------------------------------------------------------------------
    // extract
    // -------------------------------------------------------------------------

    public function testExtractReturnsSubRegistry(): void
    {
        $reg = new Registry();
        $reg->set('parent.child', 'value');

        $sub = $reg->extract('parent');
        self::assertInstanceOf(Registry::class, $sub);
        self::assertSame('value', $sub->get('child'));
    }

    public function testExtractReturnsNullForMissingPath(): void
    {
        $reg = new Registry();
        self::assertNull($reg->extract('nonexistent'));
    }

    // -------------------------------------------------------------------------
    // append
    // -------------------------------------------------------------------------

    public function testAppendAddsValueToPath(): void
    {
        $reg = new Registry();
        $reg->append('list', 'first');
        $reg->append('list', 'second');

        $list = $reg->get('list');
        self::assertIsArray($list);
        self::assertContains('first', $list);
        self::assertContains('second', $list);
    }

    // -------------------------------------------------------------------------
    // flatten
    // -------------------------------------------------------------------------

    public function testFlattenProducesOneDimensionalArray(): void
    {
        $reg = new Registry();
        $reg->set('a.b', 'val1');
        $reg->set('a.c', 'val2');
        $reg->set('d', 'val3');

        $flat = $reg->flatten();
        self::assertArrayHasKey('a.b', $flat);
        self::assertArrayHasKey('a.c', $flat);
        self::assertArrayHasKey('d', $flat);
        self::assertSame('val1', $flat['a.b']);
        self::assertSame('val2', $flat['a.c']);
        self::assertSame('val3', $flat['d']);
    }

    public function testFlattenWithCustomSeparator(): void
    {
        $reg = new Registry();
        $reg->set('a.b', 'value');

        $flat = $reg->flatten('/');
        self::assertArrayHasKey('a/b', $flat);
        self::assertSame('value', $flat['a/b']);
    }

    // -------------------------------------------------------------------------
    // loadArray flattened round-trip
    // -------------------------------------------------------------------------

    public function testFlattenAndLoadArrayRoundTrip(): void
    {
        $reg = new Registry();
        $reg->set('foo.bar', 'hello');
        $reg->set('foo.baz', 'world');
        $reg->set('top', 'level');

        $flat = $reg->flatten();

        $reg2 = new Registry();
        $reg2->loadArray($flat, true);

        self::assertSame('hello', $reg2->get('foo.bar'));
        self::assertSame('world', $reg2->get('foo.baz'));
        self::assertSame('level', $reg2->get('top'));
    }
}
