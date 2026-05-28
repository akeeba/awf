<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Registry\Format;

use Awf\Registry\Format\Php;
use BadMethodCallException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class PhpTest extends TestCase
{
    private Php $formatter;

    protected function setUp(): void
    {
        $this->formatter = new Php();
    }

    // -------------------------------------------------------------------------
    // objectToString — structure
    // -------------------------------------------------------------------------

    public function testObjectToStringProducesPhpOpenTag(): void
    {
        $obj = new stdClass();
        $result = $this->formatter->objectToString($obj, ['class' => 'MyConfig']);

        self::assertStringStartsWith('<?php', $result);
    }

    public function testObjectToStringProducesClassDeclaration(): void
    {
        $obj = new stdClass();
        $result = $this->formatter->objectToString($obj, ['class' => 'MyConfig']);

        self::assertStringContainsString('class MyConfig {', $result);
    }

    public function testObjectToStringIncludesClosingTagByDefault(): void
    {
        $obj = new stdClass();
        $result = $this->formatter->objectToString($obj, ['class' => 'Cfg']);

        self::assertStringContainsString('?>', $result);
    }

    public function testObjectToStringOmitsClosingTagWhenParamFalse(): void
    {
        $obj = new stdClass();
        $result = $this->formatter->objectToString($obj, ['class' => 'Cfg', 'closingtag' => false]);

        self::assertStringNotContainsString('?>', $result);
    }

    public function testObjectToStringIncludesClosingTagWhenParamTrue(): void
    {
        $obj = new stdClass();
        $result = $this->formatter->objectToString($obj, ['class' => 'Cfg', 'closingtag' => true]);

        self::assertStringContainsString('?>', $result);
    }

    // -------------------------------------------------------------------------
    // objectToString — scalar properties
    // -------------------------------------------------------------------------

    public function testObjectToStringStringProperty(): void
    {
        $obj = new stdClass();
        $obj->name = 'hello';

        $result = $this->formatter->objectToString($obj, ['class' => 'Cfg']);

        self::assertStringContainsString("\tpublic \$name = 'hello';", $result);
    }

    public function testObjectToStringIntegerProperty(): void
    {
        $obj = new stdClass();
        $obj->count = 42;

        $result = $this->formatter->objectToString($obj, ['class' => 'Cfg']);

        self::assertStringContainsString("\tpublic \$count = '42';", $result);
    }

    public function testObjectToStringFloatProperty(): void
    {
        $obj = new stdClass();
        $obj->ratio = 3.14;

        $result = $this->formatter->objectToString($obj, ['class' => 'Cfg']);

        self::assertStringContainsString("\tpublic \$ratio = '3.14';", $result);
    }

    public function testObjectToStringBoolTrueProperty(): void
    {
        $obj = new stdClass();
        $obj->active = true;

        $result = $this->formatter->objectToString($obj, ['class' => 'Cfg']);

        self::assertStringContainsString("\tpublic \$active = true;", $result);
    }

    public function testObjectToStringBoolFalseProperty(): void
    {
        $obj = new stdClass();
        $obj->active = false;

        $result = $this->formatter->objectToString($obj, ['class' => 'Cfg']);

        self::assertStringContainsString("\tpublic \$active = false;", $result);
    }

    public function testObjectToStringNullProperty(): void
    {
        $obj = new stdClass();
        $obj->nothing = null;

        $result = $this->formatter->objectToString($obj, ['class' => 'Cfg']);

        self::assertStringContainsString("\tpublic \$nothing = NULL;", $result);
    }

    // -------------------------------------------------------------------------
    // objectToString — string escaping
    // -------------------------------------------------------------------------

    public function testObjectToStringEscapesSingleQuoteInString(): void
    {
        $obj = new stdClass();
        $obj->msg = "it's fine";

        $result = $this->formatter->objectToString($obj, ['class' => 'Cfg']);

        // The single quote must be escaped with a backslash inside the single-quoted PHP string.
        self::assertStringContainsString("\tpublic \$msg = 'it\\'s fine';", $result);
    }

    public function testObjectToStringEscapesBackslashInString(): void
    {
        $obj = new stdClass();
        $obj->path = 'C:\\Windows';

        $result = $this->formatter->objectToString($obj, ['class' => 'Cfg']);

        // Both backslashes must be escaped.
        self::assertStringContainsString("\tpublic \$path = 'C:\\\\Windows';", $result);
    }

    // -------------------------------------------------------------------------
    // objectToString — array property
    // -------------------------------------------------------------------------

    public function testObjectToStringArrayProperty(): void
    {
        $obj = new stdClass();
        $obj->items = ['a', 'b', 'c'];

        $result = $this->formatter->objectToString($obj, ['class' => 'Cfg']);

        self::assertStringContainsString('public $items = [', $result);
        self::assertStringContainsString('"a"', $result);
        self::assertStringContainsString('"b"', $result);
        self::assertStringContainsString('"c"', $result);
    }

    public function testObjectToStringArrayPropertyHasCorrectKeys(): void
    {
        $obj = new stdClass();
        $obj->map = ['foo' => 'bar', 'baz' => 'qux'];

        $result = $this->formatter->objectToString($obj, ['class' => 'Cfg']);

        self::assertStringContainsString('"foo" => "bar"', $result);
        self::assertStringContainsString('"baz" => "qux"', $result);
    }

    public function testObjectToStringArrayWithNullValue(): void
    {
        $obj = new stdClass();
        $obj->data = ['key' => null];

        $result = $this->formatter->objectToString($obj, ['class' => 'Cfg']);

        self::assertStringContainsString('"key" => NULL', $result);
    }

    public function testObjectToStringArrayWithBoolValues(): void
    {
        $obj = new stdClass();
        $obj->flags = ['yes' => true, 'no' => false];

        $result = $this->formatter->objectToString($obj, ['class' => 'Cfg']);

        self::assertStringContainsString('"yes" => true', $result);
        self::assertStringContainsString('"no" => false', $result);
    }

    public function testObjectToStringNestedArray(): void
    {
        $obj = new stdClass();
        $obj->nested = ['outer' => ['inner' => 'value']];

        $result = $this->formatter->objectToString($obj, ['class' => 'Cfg']);

        self::assertStringContainsString('"outer" => [', $result);
        self::assertStringContainsString('"inner" => "value"', $result);
    }

    // -------------------------------------------------------------------------
    // objectToString — object property
    // -------------------------------------------------------------------------

    public function testObjectToStringNestedObject(): void
    {
        $child = new stdClass();
        $child->x = 1;

        $obj = new stdClass();
        $obj->sub = $child;

        $result = $this->formatter->objectToString($obj, ['class' => 'Cfg']);

        // A nested object is cast to array for rendering.
        self::assertStringContainsString('public $sub = [', $result);
        self::assertStringContainsString('"x" => "1"', $result);
    }

    // -------------------------------------------------------------------------
    // objectToString — empty object
    // -------------------------------------------------------------------------

    public function testObjectToStringEmptyObjectProducesEmptyClassBody(): void
    {
        $obj = new stdClass();
        $result = $this->formatter->objectToString($obj, ['class' => 'Empty']);

        self::assertStringContainsString('class Empty {', $result);
        // No public property lines present.
        self::assertStringNotContainsString('public $', $result);
    }

    // -------------------------------------------------------------------------
    // objectToString — class name in params
    // -------------------------------------------------------------------------

    public static function classNameProvider(): array
    {
        return [
            'simple name'       => ['SimpleConfig'],
            'with underscore'   => ['My_Config'],
            'with namespace sep'=> ['JConfig'],
        ];
    }

    #[DataProvider('classNameProvider')]
    public function testObjectToStringUsesProvidedClassName(string $className): void
    {
        $obj = new stdClass();
        $result = $this->formatter->objectToString($obj, ['class' => $className]);

        self::assertStringContainsString('class ' . $className . ' {', $result);
    }

    // -------------------------------------------------------------------------
    // objectToString — multiple properties
    // -------------------------------------------------------------------------

    public function testObjectToStringMultiplePropertiesAllPresent(): void
    {
        $obj = new stdClass();
        $obj->alpha = 'one';
        $obj->beta  = 2;
        $obj->gamma = null;
        $obj->delta = true;

        $result = $this->formatter->objectToString($obj, ['class' => 'Multi']);

        self::assertStringContainsString("\tpublic \$alpha = 'one';", $result);
        self::assertStringContainsString("\tpublic \$beta = '2';", $result);
        self::assertStringContainsString("\tpublic \$gamma = NULL;", $result);
        self::assertStringContainsString("\tpublic \$delta = true;", $result);
    }

    // -------------------------------------------------------------------------
    // stringToObject — must throw BadMethodCallException
    // -------------------------------------------------------------------------

    public function testStringToObjectThrowsBadMethodCallException(): void
    {
        $this->expectException(BadMethodCallException::class);

        $this->formatter->stringToObject('<?php class Foo {}');
    }

    public function testStringToObjectThrowsEvenForEmptyString(): void
    {
        $this->expectException(BadMethodCallException::class);

        $this->formatter->stringToObject('');
    }

    public function testStringToObjectExceptionMessageContainsHint(): void
    {
        try {
            $this->formatter->stringToObject('anything');
            self::fail('Expected BadMethodCallException was not thrown.');
        } catch (BadMethodCallException $e) {
            self::assertStringContainsString('loadFile', $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // getInstance factory
    // -------------------------------------------------------------------------

    public function testGetInstanceReturnsPHPFormatter(): void
    {
        $instance = \Awf\Registry\AbstractRegistryFormat::getInstance('php');

        self::assertInstanceOf(Php::class, $instance);
    }

    public function testGetInstanceReturnsSameInstanceOnSecondCall(): void
    {
        $a = \Awf\Registry\AbstractRegistryFormat::getInstance('php');
        $b = \Awf\Registry\AbstractRegistryFormat::getInstance('php');

        self::assertSame($a, $b);
    }
}
