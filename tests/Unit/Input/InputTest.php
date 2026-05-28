<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Input;

use Awf\Input\Filter;
use Awf\Input\Input;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Input::class)]
class InputTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction / getData / setData
    // -------------------------------------------------------------------------

    public function testConstructWithExplicitArrayStoresData(): void
    {
        $input = new Input(['foo' => 'bar', 'num' => '42']);

        self::assertSame(['foo' => 'bar', 'num' => '42'], $input->getData());
    }

    public function testConstructWithNullReferencesRequest(): void
    {
        // Just verify the object is created without error and is an Input.
        $input = new Input(null);
        self::assertInstanceOf(Input::class, $input);
    }

    public function testConstructWithEmptyArray(): void
    {
        $input = new Input([]);
        self::assertSame([], $input->getData());
    }

    public function testSetDataReplacesAllData(): void
    {
        $input = new Input(['old' => 'value']);
        $input->setData(['new' => 'data', 'extra' => '1']);

        self::assertSame(['new' => 'data', 'extra' => '1'], $input->getData());
    }

    public function testSetDataWithObjectConvertsToArray(): void
    {
        $input  = new Input([]);
        $object = new \stdClass();
        $object->key = 'val';
        $input->setData($object);

        self::assertSame(['key' => 'val'], $input->getData());
    }

    // -------------------------------------------------------------------------
    // count()
    // -------------------------------------------------------------------------

    public function testCountReturnsNumberOfKeys(): void
    {
        $input = new Input(['a' => 1, 'b' => 2, 'c' => 3]);
        self::assertCount(3, $input);
    }

    public function testCountOnEmptyInputReturnsZero(): void
    {
        $input = new Input([]);
        self::assertCount(0, $input);
    }

    // -------------------------------------------------------------------------
    // get() — happy path with various filter types
    // -------------------------------------------------------------------------

    public static function getWithFilterProvider(): array
    {
        return [
            'cmd strips special chars'        => ['val', '  hello!world  ', 'cmd',     'helloworld'],
            'int extracts integer'             => ['val', '42abc',           'int',     42],
            'uint returns absolute value'      => ['val', '-5xyz',           'uint',    5],
            'float extracts float'             => ['val', '3.14abc',         'float',   3.14],
            'bool truthy string is true'       => ['val', '1',               'bool',    true],
            'bool falsy string is false'       => ['val', '0',               'bool',    false],
            'word keeps only alpha and _'      => ['val', 'foo_bar123',      'word',    'foo_bar'],
            'alnum keeps alphanumerics'        => ['val', 'foo_bar-123',     'alnum',   'foobar123'],
            'string passes plain text'         => ['val', 'hello world',     'string',  'hello world'],
            'base64 allows base64 chars'       => ['val', 'aGVsbG8=!@#',    'base64',  'aGVsbG8='],
            'raw returns as-is'                => ['val', '<b>test</b>',    'raw',     '<b>test</b>'],
            'path keeps valid path chars'      => ['val', 'path/to/file',   'path',    'path/to/file'],
        ];
    }

    #[DataProvider('getWithFilterProvider')]
    public function testGetWithFilter(string $key, mixed $rawValue, string $filter, mixed $expected): void
    {
        $input = new Input([$key => $rawValue]);
        self::assertSame($expected, $input->get($key, null, $filter));
    }

    public function testGetReturnsDefaultWhenKeyMissing(): void
    {
        $input = new Input([]);
        self::assertSame('fallback', $input->get('missing', 'fallback', 'string'));
    }

    public function testGetReturnsNullDefaultByDefault(): void
    {
        $input = new Input([]);
        self::assertNull($input->get('missing'));
    }

    public function testGetDefaultFilterIsCmd(): void
    {
        $input  = new Input(['key' => 'hello world!']);
        $result = $input->get('key');
        // 'cmd' filter strips spaces and special chars but keeps dots, dashes, underscores
        self::assertSame('helloworld', $result);
    }

    // -------------------------------------------------------------------------
    // Magic __call (getInt, getString, getCmd, etc.)
    // -------------------------------------------------------------------------

    public function testMagicGetIntReturnsInteger(): void
    {
        $input = new Input(['age' => '25abc']);
        self::assertSame(25, $input->getInt('age', 0));
    }

    public function testMagicGetIntReturnsDefaultWhenMissing(): void
    {
        $input = new Input([]);
        self::assertSame(99, $input->getInt('missing', 99));
    }

    public function testMagicGetStringReturnsFilteredString(): void
    {
        $input  = new Input(['name' => 'hello world']);
        self::assertSame('hello world', $input->getString('name', ''));
    }

    public function testMagicGetCmdStripsSpecialChars(): void
    {
        $input = new Input(['task' => 'do.something-nice_here!@#']);
        self::assertSame('do.something-nice_here', $input->getCmd('task', ''));
    }

    public function testMagicGetFloatReturnsFloat(): void
    {
        $input = new Input(['price' => '9.99abc']);
        self::assertSame(9.99, $input->getFloat('price', 0.0));
    }

    public function testMagicGetBoolReturnsBool(): void
    {
        $inputTrue  = new Input(['flag' => '1']);
        $inputFalse = new Input(['flag' => '0']);
        self::assertTrue($inputTrue->getBool('flag', false));
        self::assertFalse($inputFalse->getBool('flag', true));
    }

    public function testMagicGetWordKeepsOnlyLettersAndUnderscore(): void
    {
        $input = new Input(['w' => 'hello_world123']);
        self::assertSame('hello_world', $input->getWord('w', ''));
    }

    public function testMagicGetAlnumKeepsAlphanumeric(): void
    {
        $input = new Input(['a' => 'abc-def_123']);
        self::assertSame('abcdef123', $input->getAlnum('a', ''));
    }

    public function testMagicGetUintReturnsAbsoluteInt(): void
    {
        $input = new Input(['n' => '-7']);
        self::assertSame(7, $input->getUint('n', 0));
    }

    public function testMagicGetBase64AllowsValidBase64(): void
    {
        $input  = new Input(['enc' => 'SGVsbG8gV29ybGQ=!?']);
        self::assertSame('SGVsbG8gV29ybGQ=', $input->getBase64('enc', ''));
    }

    // -------------------------------------------------------------------------
    // set() and def()
    // -------------------------------------------------------------------------

    public function testSetOverwritesExistingValue(): void
    {
        $input = new Input(['foo' => 'original']);
        $input->set('foo', 'changed');

        self::assertSame('changed', $input->get('foo', null, 'raw'));
    }

    public function testSetAddsNewKey(): void
    {
        $input = new Input([]);
        $input->set('newkey', 'value');

        self::assertSame('value', $input->get('newkey', null, 'raw'));
    }

    public function testDefDoesNotOverwriteExistingKey(): void
    {
        $input = new Input(['key' => 'original']);
        $input->def('key', 'default');

        self::assertSame('original', $input->get('key', null, 'raw'));
    }

    public function testDefSetsValueWhenKeyAbsent(): void
    {
        $input = new Input([]);
        $input->def('key', 'default_value');

        self::assertSame('default_value', $input->get('key', null, 'raw'));
    }

    // -------------------------------------------------------------------------
    // exists (checking key presence via get default)
    // -------------------------------------------------------------------------

    public function testKeyPresentReturnNonNullValue(): void
    {
        $input = new Input(['exists' => 'yes']);
        self::assertNotNull($input->get('exists', null, 'raw'));
    }

    public function testKeyAbsentReturnsNull(): void
    {
        $input = new Input([]);
        self::assertNull($input->get('absent', null, 'raw'));
    }

    // -------------------------------------------------------------------------
    // getArray()
    // -------------------------------------------------------------------------

    public function testGetArrayWithFlatSpec(): void
    {
        $input = new Input([
            'username' => 'admin123',
            'age'      => '30abc',
            'score'    => '9.5xyz',
        ]);

        $result = $input->getArray([
            'username' => 'cmd',
            'age'      => 'int',
            'score'    => 'float',
        ]);

        self::assertSame([
            'username' => 'admin123',
            'age'      => 30,
            'score'    => 9.5,
        ], $result);
    }

    public function testGetArrayReturnsNullForMissingKeys(): void
    {
        // When the key is absent from the data, get() returns the default (null),
        // so getArray returns null — the filter is not invoked for missing keys.
        $input  = new Input([]);
        $result = $input->getArray(['missing_key' => 'int']);

        self::assertNull($result['missing_key']);
    }

    public function testGetArrayWithExplicitDatasource(): void
    {
        $input  = new Input([]);
        $result = $input->getArray(
            ['foo' => 'string', 'bar' => 'int'],
            ['foo' => 'hello', 'bar' => '42xyz']
        );

        self::assertSame(['foo' => 'hello', 'bar' => 42], $result);
    }

    public function testGetArrayWithNestedSpec(): void
    {
        $input = new Input([
            'user' => [
                'name' => 'John<script>',
                'age'  => '25abc',
            ],
        ]);

        $result = $input->getArray([
            'user' => [
                'name' => 'string',
                'age'  => 'int',
            ],
        ]);

        self::assertSame(25, $result['user']['age']);
        self::assertIsString($result['user']['name']);
    }

    // -------------------------------------------------------------------------
    // Custom Filter via options
    // -------------------------------------------------------------------------

    public function testCustomFilterIsUsed(): void
    {
        $mockFilter = $this->createMock(Filter::class);
        $mockFilter->expects(self::once())
            ->method('clean')
            ->with('raw_value', 'string')
            ->willReturn('cleaned_value');

        $input  = new Input(['key' => 'raw_value'], ['filter' => $mockFilter]);
        $result = $input->get('key', null, 'string');

        self::assertSame('cleaned_value', $result);
    }

    // -------------------------------------------------------------------------
    // Serialization
    // -------------------------------------------------------------------------

    public function testSerializeAndUnserializeRoundTrip(): void
    {
        // Use the object's own serialize/unserialize methods (not PHP's global
        // serialize() function) to avoid the Serializable-interface deprecation
        // that PHP 8.1+ emits for classes still implementing Serializable.
        $input = new Input(['alpha' => 'hello', 'num' => '99']);

        $serialized   = $input->serialize();
        $unserialized = new Input([]);
        $unserialized->unserialize($serialized);

        self::assertSame('hello', $unserialized->get('alpha', null, 'raw'));
        self::assertSame('99', $unserialized->get('num', null, 'raw'));
    }

    public function testSerializeExcludesEnvAndServer(): void
    {
        $input      = new Input(['foo' => 'bar']);
        $serialized = $input->serialize();

        $data = unserialize($serialized);
        // $data[2] is the inputs array — env and server should be absent
        self::assertArrayNotHasKey('env', $data[2]);
        self::assertArrayNotHasKey('server', $data[2]);
    }

    public function testUnserializeRestoresData(): void
    {
        $input = new Input(['key' => 'value']);

        $serialized = $input->serialize();
        $input2     = new Input([]);
        $input2->unserialize($serialized);

        self::assertSame('value', $input2->get('key', null, 'raw'));
    }

    // -------------------------------------------------------------------------
    // count() integration after set/def
    // -------------------------------------------------------------------------

    public function testCountIncreasesAfterSet(): void
    {
        $input = new Input(['a' => 1]);
        $input->set('b', 2);

        self::assertCount(2, $input);
    }

    public function testCountDoesNotIncreaseAfterDefOnExistingKey(): void
    {
        $input = new Input(['a' => 1]);
        $input->def('a', 99);

        self::assertCount(1, $input);
    }

    // -------------------------------------------------------------------------
    // __get magic (superglobal sub-inputs)
    // -------------------------------------------------------------------------

    public function testMagicGetReturnsInputForKnownSuperglobal(): void
    {
        $input    = new Input([]);
        $subInput = $input->get;

        // $_GET is a known superglobal, __get should return an Input or null
        // We just verify it doesn't throw
        self::assertTrue($subInput === null || $subInput instanceof Input);
    }

    // -------------------------------------------------------------------------
    // Edge cases
    // -------------------------------------------------------------------------

    public function testGetWithIntegerZeroValue(): void
    {
        $input = new Input(['count' => 0]);
        self::assertSame(0, $input->getInt('count', 5));
    }

    public function testGetWithEmptyStringValue(): void
    {
        $input = new Input(['key' => '']);
        // empty string present, not missing — should return empty string as cmd
        self::assertSame('', $input->get('key', 'fallback', 'cmd'));
    }

    public function testGetWithNullValueInDataReturnsDefault(): void
    {
        // If the key is absent entirely, default is returned
        $input = new Input([]);
        self::assertSame('default', $input->get('absent', 'default', 'string'));
    }

    public function testGetArrayWithMissingDatasourceKeyReturnsCleanedNull(): void
    {
        $input  = new Input([]);
        $result = $input->getArray(
            ['missing' => 'int'],
            ['other' => 'value']
        );

        // Filter::clean(null, 'int') → 0
        self::assertSame(0, $result['missing']);
    }

    public function testSetDataAndThenGet(): void
    {
        $input = new Input([]);
        $input->setData(['x' => '123abc', 'y' => 'hello']);

        self::assertSame(123, $input->getInt('x', 0));
        self::assertSame('hello', $input->getString('y', ''));
    }
}
