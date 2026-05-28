<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Registry\Format;

use Awf\Registry\Format\Json;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class JsonTest extends TestCase
{
    private Json $formatter;

    protected function setUp(): void
    {
        $this->formatter = new Json();
    }

    // -------------------------------------------------------------------------
    // objectToString
    // -------------------------------------------------------------------------

    public function testObjectToStringSimpleObject(): void
    {
        $obj = new stdClass();
        $obj->foo = 'bar';
        $obj->num = 42;

        $result = $this->formatter->objectToString($obj);
        $decoded = json_decode($result, true);

        self::assertSame('bar', $decoded['foo']);
        self::assertSame(42, $decoded['num']);
    }

    public function testObjectToStringEmptyObject(): void
    {
        $result = $this->formatter->objectToString(new stdClass());

        self::assertSame('{}', $result);
    }

    public function testObjectToStringNestedObject(): void
    {
        $child = new stdClass();
        $child->x = 1;
        $child->y = 2;

        $parent = new stdClass();
        $parent->name = 'parent';
        $parent->child = $child;

        $result  = $this->formatter->objectToString($parent);
        $decoded = json_decode($result);

        self::assertSame('parent', $decoded->name);
        self::assertSame(1, $decoded->child->x);
        self::assertSame(2, $decoded->child->y);
    }

    public function testObjectToStringWithPrettyPrint(): void
    {
        $obj      = new stdClass();
        $obj->key = 'value';

        $plain  = $this->formatter->objectToString($obj);
        $pretty = $this->formatter->objectToString($obj, ['pretty_print' => true]);

        // Pretty-printed output must contain newlines / indentation.
        self::assertStringContainsString("\n", $pretty);
        // Both must decode to the same data.
        self::assertEquals(json_decode($plain), json_decode($pretty));
    }

    public function testObjectToStringWithoutPrettyPrintOptionProducesCompact(): void
    {
        $obj      = new stdClass();
        $obj->key = 'value';

        $result = $this->formatter->objectToString($obj, ['pretty_print' => false]);

        // Without pretty-print the output should be on a single line.
        self::assertStringNotContainsString("\n", $result);
    }

    public function testObjectToStringPreservesTypesWithinArray(): void
    {
        $obj        = new stdClass();
        $obj->items = [1, 'two', true, null, 3.14];

        $result  = $this->formatter->objectToString($obj);
        $decoded = json_decode($result);

        self::assertSame(1, $decoded->items[0]);
        self::assertSame('two', $decoded->items[1]);
        self::assertTrue($decoded->items[2]);
        self::assertNull($decoded->items[3]);
        self::assertEqualsWithDelta(3.14, $decoded->items[4], 0.0001);
    }

    public function testObjectToStringRoundTripsUnicode(): void
    {
        $obj       = new stdClass();
        $obj->text = 'Héllo Wörld – 日本語';

        $result  = $this->formatter->objectToString($obj);
        $decoded = json_decode($result);

        self::assertSame($obj->text, $decoded->text);
    }

    // -------------------------------------------------------------------------
    // stringToObject
    // -------------------------------------------------------------------------

    public function testStringToObjectSimpleJson(): void
    {
        $json   = '{"foo":"bar","num":42}';
        $result = $this->formatter->stringToObject($json);

        self::assertInstanceOf(stdClass::class, $result);
        self::assertSame('bar', $result->foo);
        self::assertSame(42, $result->num);
    }

    public function testStringToObjectEmptyJsonObject(): void
    {
        $result = $this->formatter->stringToObject('{}');

        self::assertInstanceOf(stdClass::class, $result);
        self::assertEmpty(get_object_vars($result));
    }

    public function testStringToObjectNestedJson(): void
    {
        $json   = '{"outer":{"inner":"value"}}';
        $result = $this->formatter->stringToObject($json);

        self::assertInstanceOf(stdClass::class, $result->outer);
        self::assertSame('value', $result->outer->inner);
    }

    public function testStringToObjectTrimsWhitespace(): void
    {
        $json   = '   {"key":"val"}   ';
        $result = $this->formatter->stringToObject($json);

        self::assertSame('val', $result->key);
    }

    public function testStringToObjectPrettyPrintedJson(): void
    {
        $json = <<<'JSON'
        {
            "name": "test",
            "value": 99
        }
        JSON;

        $result = $this->formatter->stringToObject($json);

        self::assertSame('test', $result->name);
        self::assertSame(99, $result->value);
    }

    // -------------------------------------------------------------------------
    // round-trips
    // -------------------------------------------------------------------------

    public function testRoundTripSimpleObject(): void
    {
        $obj      = new stdClass();
        $obj->foo = 'bar';
        $obj->num = 7;

        $json   = $this->formatter->objectToString($obj);
        $result = $this->formatter->stringToObject($json);

        self::assertEquals($obj, $result);
    }

    public function testRoundTripNestedObject(): void
    {
        $inner      = new stdClass();
        $inner->x   = 1;
        $outer      = new stdClass();
        $outer->sub = $inner;

        $json   = $this->formatter->objectToString($outer);
        $result = $this->formatter->stringToObject($json);

        self::assertEquals($outer, $result);
    }

    // -------------------------------------------------------------------------
    // malformed / non-JSON input falls back to INI parser
    // -------------------------------------------------------------------------

    public function testStringToObjectEmptyStringReturnsObject(): void
    {
        // Non-JSON data (no surrounding braces) is delegated to the INI parser.
        // An empty string should result in an empty stdClass.
        $result = $this->formatter->stringToObject('');

        self::assertInstanceOf(stdClass::class, $result);
    }

    public function testStringToObjectIniDataFallback(): void
    {
        // Data without surrounding { } is treated as INI by stringToObject.
        $ini    = "foo=bar\nnum=42";
        $result = $this->formatter->stringToObject($ini);

        self::assertInstanceOf(stdClass::class, $result);
        self::assertSame('bar', $result->foo);
        self::assertSame(42, $result->num);
    }

    public function testStringToObjectMalformedJsonReturnsNull(): void
    {
        // A string that looks like JSON (has surrounding braces) but is invalid
        // JSON will be passed to json_decode(), which returns null on failure.
        $result = $this->formatter->stringToObject('{not valid json}');

        self::assertNull($result);
    }
}
