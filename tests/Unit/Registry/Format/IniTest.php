<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Registry\Format;

use Awf\Registry\Format\Ini;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class IniTest extends TestCase
{
    private Ini $formatter;

    protected function setUp(): void
    {
        $this->formatter = new Ini();

        // Reset the static cache between tests to avoid cross-test contamination.
        $prop = new \ReflectionProperty(Ini::class, 'cache');
        $prop->setValue(null, []);
    }

    // -------------------------------------------------------------------------
    // objectToString — happy path
    // -------------------------------------------------------------------------

    public function testObjectToStringSimpleGlobalKeys(): void
    {
        $obj        = new stdClass();
        $obj->foo   = 'bar';
        $obj->count = 42;

        $result = $this->formatter->objectToString($obj);

        self::assertStringContainsString('foo="bar"', $result);
        self::assertStringContainsString('count=42', $result);
    }

    public function testObjectToStringEmptyObjectProducesEmptyString(): void
    {
        $result = $this->formatter->objectToString(new stdClass());

        self::assertSame('', $result);
    }

    public function testObjectToStringBooleanTrue(): void
    {
        $obj       = new stdClass();
        $obj->flag = true;

        $result = $this->formatter->objectToString($obj);

        self::assertStringContainsString('flag=true', $result);
    }

    public function testObjectToStringBooleanFalse(): void
    {
        $obj       = new stdClass();
        $obj->flag = false;

        $result = $this->formatter->objectToString($obj);

        self::assertStringContainsString('flag=false', $result);
    }

    public function testObjectToStringFloat(): void
    {
        $obj      = new stdClass();
        $obj->val = 3.14;

        $result = $this->formatter->objectToString($obj);

        self::assertStringContainsString('val=3.14', $result);
    }

    public function testObjectToStringSection(): void
    {
        $section    = new stdClass();
        $section->x = 1;
        $section->y = 2;

        $obj          = new stdClass();
        $obj->section = $section;

        $result = $this->formatter->objectToString($obj);

        self::assertStringContainsString('[section]', $result);
        self::assertStringContainsString('x=1', $result);
        self::assertStringContainsString('y=2', $result);
    }

    public function testObjectToStringSectionComesAfterGlobals(): void
    {
        $section    = new stdClass();
        $section->k = 'v';

        $obj          = new stdClass();
        $obj->global  = 'top';
        $obj->section = $section;

        $result = $this->formatter->objectToString($obj);
        $lines  = array_values(array_filter(explode("\n", $result)));

        // Global key must appear before the section header.
        $globalPos  = array_search('global="top"', $lines);
        $sectionPos = array_search('[section]', $lines);

        self::assertNotFalse($globalPos);
        self::assertNotFalse($sectionPos);
        self::assertLessThan($sectionPos, $globalPos);
    }

    public function testObjectToStringNewlineInStringValueIsEscaped(): void
    {
        $obj      = new stdClass();
        $obj->msg = "line1\nline2";

        $result = $this->formatter->objectToString($obj);

        self::assertStringContainsString('\\n', $result);
        self::assertStringNotContainsString("\nline2", $result);
    }

    public function testObjectToStringCrLfInStringValueIsEscaped(): void
    {
        $obj      = new stdClass();
        $obj->msg = "line1\r\nline2";

        $result = $this->formatter->objectToString($obj);

        self::assertStringContainsString('\\n', $result);
    }

    // -------------------------------------------------------------------------
    // stringToObject — happy path
    // -------------------------------------------------------------------------

    public function testStringToObjectEmptyStringReturnsEmptyObject(): void
    {
        $result = $this->formatter->stringToObject('');

        self::assertInstanceOf(stdClass::class, $result);
        self::assertEmpty(get_object_vars($result));
    }

    public function testStringToObjectSimpleStringValue(): void
    {
        $result = $this->formatter->stringToObject('key="value"');

        self::assertSame('value', $result->key);
    }

    public function testStringToObjectInteger(): void
    {
        $result = $this->formatter->stringToObject('num=42');

        self::assertSame(42, $result->num);
    }

    public function testStringToObjectFloat(): void
    {
        $result = $this->formatter->stringToObject('val=3.14');

        self::assertEqualsWithDelta(3.14, $result->val, 0.0001);
        self::assertIsFloat($result->val);
    }

    public function testStringToObjectBooleanTrue(): void
    {
        $result = $this->formatter->stringToObject('flag=true');

        self::assertTrue($result->flag);
    }

    public function testStringToObjectBooleanFalse(): void
    {
        $result = $this->formatter->stringToObject('flag=false');

        self::assertFalse($result->flag);
    }

    public function testStringToObjectMultipleKeys(): void
    {
        $data   = "foo=\"bar\"\nnum=7\nflag=true";
        $result = $this->formatter->stringToObject($data);

        self::assertSame('bar', $result->foo);
        self::assertSame(7, $result->num);
        self::assertTrue($result->flag);
    }

    public function testStringToObjectIgnoresCommentLines(): void
    {
        $data   = "; this is a comment\nkey=1";
        $result = $this->formatter->stringToObject($data);

        self::assertSame(1, $result->key);
        $vars = get_object_vars($result);
        self::assertCount(1, $vars);
    }

    public function testStringToObjectIgnoresEmptyLines(): void
    {
        $data   = "\n\nkey=1\n\n";
        $result = $this->formatter->stringToObject($data);

        self::assertSame(1, $result->key);
    }

    public function testStringToObjectIgnoresSectionLinesWhenNotProcessing(): void
    {
        $data   = "[section]\nkey=1";
        $result = $this->formatter->stringToObject($data);

        self::assertSame(1, $result->key);
        // The section header itself must not become a property.
        self::assertFalse(isset($result->section));
    }

    public function testStringToObjectUnescapesNewlineInQuotedValue(): void
    {
        $data   = 'msg="line1\nline2"';
        $result = $this->formatter->stringToObject($data);

        self::assertStringContainsString("\n", $result->msg);
    }

    // -------------------------------------------------------------------------
    // stringToObject with sections
    // -------------------------------------------------------------------------

    public function testStringToObjectWithSections(): void
    {
        $data = "[section]\nfoo=\"bar\"\nnum=5";

        $result = $this->formatter->stringToObject($data, ['processSections' => true]);

        self::assertInstanceOf(stdClass::class, $result->section);
        self::assertSame('bar', $result->section->foo);
        self::assertSame(5, $result->section->num);
    }

    public function testStringToObjectWithMultipleSections(): void
    {
        $data = "[a]\nx=1\n[b]\ny=2";

        $result = $this->formatter->stringToObject($data, ['processSections' => true]);

        self::assertSame(1, $result->a->x);
        self::assertSame(2, $result->b->y);
    }

    public function testStringToObjectSectionDoesNotLeakToGlobalScope(): void
    {
        $data = "global=99\n[section]\nlocal=1";

        $result = $this->formatter->stringToObject($data, ['processSections' => true]);

        self::assertSame(99, $result->global);
        self::assertSame(1, $result->section->local);
        // 'local' must not exist at the top level.
        self::assertFalse(isset($result->local));
    }

    // -------------------------------------------------------------------------
    // stringToObject — edge cases / invalid input
    // -------------------------------------------------------------------------

    public function testStringToObjectInvalidKeyWithSpecialCharsIsSkipped(): void
    {
        // Keys with non-alphanumeric characters are silently discarded.
        $data   = "valid_key=1\ninvalid-key=2";
        $result = $this->formatter->stringToObject($data);

        self::assertSame(1, $result->valid_key);
        self::assertFalse(isset($result->{'invalid-key'}));
    }

    public function testStringToObjectLineWithoutEqualsSignIsSkipped(): void
    {
        $data   = "noequals\nkey=1";
        $result = $this->formatter->stringToObject($data);

        self::assertSame(1, $result->key);
        self::assertFalse(isset($result->noequals));
    }

    public function testStringToObjectResultIsCached(): void
    {
        $data    = 'cached=1';
        $first   = $this->formatter->stringToObject($data);
        $second  = $this->formatter->stringToObject($data);

        // Must return equal objects (from cache), but distinct instances
        // because stringToObject clones the cached value.
        self::assertEquals($first, $second);
        self::assertNotSame($first, $second);
    }

    // -------------------------------------------------------------------------
    // round-trips
    // -------------------------------------------------------------------------

    public function testRoundTripFlatObject(): void
    {
        $obj        = new stdClass();
        $obj->name  = 'Alice';
        $obj->count = 3;
        $obj->ratio = 0.5;
        $obj->flag  = true;

        $ini    = $this->formatter->objectToString($obj);
        $result = $this->formatter->stringToObject($ini);

        self::assertSame('Alice', $result->name);
        self::assertSame(3, $result->count);
        self::assertEqualsWithDelta(0.5, $result->ratio, 0.0001);
        self::assertTrue($result->flag);
    }

    public function testRoundTripWithSections(): void
    {
        $section    = new stdClass();
        $section->x = 10;
        $section->y = 20;

        $obj          = new stdClass();
        $obj->top     = 'root';
        $obj->section = $section;

        $ini    = $this->formatter->objectToString($obj);
        $result = $this->formatter->stringToObject($ini, ['processSections' => true]);

        self::assertSame('root', $result->top);
        self::assertInstanceOf(stdClass::class, $result->section);
        self::assertSame(10, $result->section->x);
        self::assertSame(20, $result->section->y);
    }

    public function testRoundTripStringWithEmbeddedNewline(): void
    {
        $obj      = new stdClass();
        $obj->msg = "hello\nworld";

        $ini    = $this->formatter->objectToString($obj);
        $result = $this->formatter->stringToObject($ini);

        self::assertSame("hello\nworld", $result->msg);
    }

    // -------------------------------------------------------------------------
    // getInstance (factory)
    // -------------------------------------------------------------------------

    public function testGetInstanceReturnsIniFormatter(): void
    {
        $formatter = Ini::getInstance('ini');

        self::assertInstanceOf(Ini::class, $formatter);
    }

    public function testGetInstanceReturnsSameInstance(): void
    {
        $a = Ini::getInstance('ini');
        $b = Ini::getInstance('ini');

        self::assertSame($a, $b);
    }

    public function testGetInstanceThrowsForUnknownType(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Ini::getInstance('nonexistentformat_xyzzy');
    }
}
