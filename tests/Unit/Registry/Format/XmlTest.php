<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Registry\Format;

use Awf\Registry\Format\Xml;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class XmlTest extends TestCase
{
    private Xml $formatter;

    protected function setUp(): void
    {
        if (!extension_loaded('simplexml') || !extension_loaded('libxml')) {
            self::markTestSkipped('ext-simplexml and ext-libxml are required for XML format tests.');
        }

        $this->formatter = new Xml();
    }

    // -------------------------------------------------------------------------
    // objectToString — happy path
    // -------------------------------------------------------------------------

    public function testObjectToStringProducesXmlString(): void
    {
        $obj      = new stdClass();
        $obj->foo = 'bar';

        $result = $this->formatter->objectToString($obj);

        self::assertIsString($result);
        self::assertStringContainsString('<?xml', $result);
    }

    public function testObjectToStringDefaultRootName(): void
    {
        $obj      = new stdClass();
        $obj->key = 'value';

        $result = $this->formatter->objectToString($obj);

        self::assertStringContainsString('<registry', $result);
    }

    public function testObjectToStringCustomRootName(): void
    {
        $obj      = new stdClass();
        $obj->key = 'value';

        $result = $this->formatter->objectToString($obj, ['name' => 'config']);

        self::assertStringContainsString('<config', $result);
        self::assertStringNotContainsString('<registry', $result);
    }

    public function testObjectToStringCustomNodeName(): void
    {
        $obj      = new stdClass();
        $obj->key = 'value';

        $result = $this->formatter->objectToString($obj, ['nodeName' => 'item']);

        self::assertStringContainsString('<item', $result);
    }

    public function testObjectToStringStringScalar(): void
    {
        $obj      = new stdClass();
        $obj->name = 'hello';

        $result = $this->formatter->objectToString($obj);
        $xml    = simplexml_load_string($result);

        $node = $xml->children()[0];
        self::assertSame('name', (string) $node['name']);
        self::assertSame('string', (string) $node['type']);
        self::assertSame('hello', (string) $node);
    }

    public function testObjectToStringIntegerScalar(): void
    {
        $obj      = new stdClass();
        $obj->count = 42;

        $result = $this->formatter->objectToString($obj);
        $xml    = simplexml_load_string($result);

        $node = $xml->children()[0];
        self::assertSame('count', (string) $node['name']);
        self::assertSame('integer', (string) $node['type']);
        self::assertSame('42', (string) $node);
    }

    public function testObjectToStringFloatScalar(): void
    {
        $obj        = new stdClass();
        $obj->price = 3.14;

        $result = $this->formatter->objectToString($obj);
        $xml    = simplexml_load_string($result);

        $node = $xml->children()[0];
        self::assertSame('price', (string) $node['name']);
        self::assertSame('double', (string) $node['type']);
    }

    public function testObjectToStringBooleanScalar(): void
    {
        $obj       = new stdClass();
        $obj->flag = true;

        $result = $this->formatter->objectToString($obj);
        $xml    = simplexml_load_string($result);

        $node = $xml->children()[0];
        self::assertSame('flag', (string) $node['name']);
        self::assertSame('boolean', (string) $node['type']);
    }

    public function testObjectToStringNestedObject(): void
    {
        $inner    = new stdClass();
        $inner->x = 1;
        $inner->y = 2;

        $outer       = new stdClass();
        $outer->sub  = $inner;
        $outer->name = 'parent';

        $result = $this->formatter->objectToString($outer);
        $xml    = simplexml_load_string($result);

        // Find the nested object node
        $found = false;
        foreach ($xml->children() as $child) {
            if ((string) $child['name'] === 'sub') {
                self::assertSame('object', (string) $child['type']);
                self::assertCount(2, $child->children());
                $found = true;
            }
        }

        self::assertTrue($found, 'Expected a nested "sub" node in the XML output.');
    }

    public function testObjectToStringArrayValue(): void
    {
        $obj         = new stdClass();
        $obj->colors = ['red', 'green', 'blue'];

        $result = $this->formatter->objectToString($obj);
        $xml    = simplexml_load_string($result);

        $found = false;
        foreach ($xml->children() as $child) {
            if ((string) $child['name'] === 'colors') {
                self::assertSame('array', (string) $child['type']);
                self::assertCount(3, $child->children());
                $found = true;
            }
        }

        self::assertTrue($found, 'Expected an "colors" array node in the XML output.');
    }

    public function testObjectToStringMultipleScalars(): void
    {
        $obj       = new stdClass();
        $obj->foo  = 'bar';
        $obj->baz  = 99;

        $result = $this->formatter->objectToString($obj);
        $xml    = simplexml_load_string($result);

        self::assertCount(2, $xml->children());
    }

    // -------------------------------------------------------------------------
    // stringToObject — happy path
    // -------------------------------------------------------------------------

    public function testStringToObjectReturnsStdClass(): void
    {
        $xmlStr = '<?xml version="1.0"?><registry><node name="foo" type="string">bar</node></registry>';
        $result = $this->formatter->stringToObject($xmlStr);

        self::assertInstanceOf(stdClass::class, $result);
    }

    public function testStringToObjectStringNode(): void
    {
        $xmlStr = '<?xml version="1.0"?><registry><node name="greeting" type="string">hello</node></registry>';
        $result = $this->formatter->stringToObject($xmlStr);

        self::assertSame('hello', $result->greeting);
    }

    public function testStringToObjectIntegerNode(): void
    {
        $xmlStr = '<?xml version="1.0"?><registry><node name="count" type="integer">42</node></registry>';
        $result = $this->formatter->stringToObject($xmlStr);

        self::assertSame(42, $result->count);
        self::assertIsInt($result->count);
    }

    public function testStringToObjectFloatNode(): void
    {
        $xmlStr = '<?xml version="1.0"?><registry><node name="price" type="double">3.14</node></registry>';
        $result = $this->formatter->stringToObject($xmlStr);

        self::assertEqualsWithDelta(3.14, $result->price, 0.0001);
        self::assertIsFloat($result->price);
    }

    public function testStringToObjectBooleanNode(): void
    {
        $xmlStr = '<?xml version="1.0"?><registry><node name="flag" type="boolean">1</node></registry>';
        $result = $this->formatter->stringToObject($xmlStr);

        self::assertTrue((bool) $result->flag);
    }

    public function testStringToObjectNestedObjectNode(): void
    {
        $xmlStr = <<<'XML'
        <?xml version="1.0"?>
        <registry>
            <node name="sub" type="object">
                <node name="x" type="integer">1</node>
                <node name="y" type="integer">2</node>
            </node>
        </registry>
        XML;

        $result = $this->formatter->stringToObject($xmlStr);

        self::assertInstanceOf(stdClass::class, $result->sub);
        self::assertSame(1, $result->sub->x);
        self::assertSame(2, $result->sub->y);
    }

    public function testStringToObjectArrayNode(): void
    {
        $xmlStr = <<<'XML'
        <?xml version="1.0"?>
        <registry>
            <node name="colors" type="array">
                <node name="0" type="string">red</node>
                <node name="1" type="string">green</node>
                <node name="2" type="string">blue</node>
            </node>
        </registry>
        XML;

        $result = $this->formatter->stringToObject($xmlStr);

        self::assertIsArray($result->colors);
        self::assertSame('red', $result->colors[0]);
        self::assertSame('green', $result->colors[1]);
        self::assertSame('blue', $result->colors[2]);
    }

    public function testStringToObjectEmptyXmlDocument(): void
    {
        $xmlStr = '<?xml version="1.0"?><registry/>';
        $result = $this->formatter->stringToObject($xmlStr);

        self::assertInstanceOf(stdClass::class, $result);
        self::assertEmpty(get_object_vars($result));
    }

    public function testStringToObjectMultipleTopLevelNodes(): void
    {
        $xmlStr = <<<'XML'
        <?xml version="1.0"?>
        <registry>
            <node name="foo" type="string">bar</node>
            <node name="num" type="integer">99</node>
        </registry>
        XML;

        $result = $this->formatter->stringToObject($xmlStr);

        self::assertSame('bar', $result->foo);
        self::assertSame(99, $result->num);
    }

    // -------------------------------------------------------------------------
    // round-trips
    // -------------------------------------------------------------------------

    public function testRoundTripSimpleScalars(): void
    {
        $obj        = new stdClass();
        $obj->name  = 'Alice';
        $obj->score = 100;

        $xmlStr = $this->formatter->objectToString($obj);
        $result = $this->formatter->stringToObject($xmlStr);

        self::assertSame('Alice', $result->name);
        self::assertSame(100, $result->score);
    }

    public function testRoundTripNestedObject(): void
    {
        $inner    = new stdClass();
        $inner->x = 10;
        $inner->y = 20;

        $outer      = new stdClass();
        $outer->sub = $inner;

        $xmlStr = $this->formatter->objectToString($outer);
        $result = $this->formatter->stringToObject($xmlStr);

        self::assertInstanceOf(stdClass::class, $result->sub);
        self::assertSame(10, $result->sub->x);
        self::assertSame(20, $result->sub->y);
    }

    public function testRoundTripArrayValue(): void
    {
        $obj         = new stdClass();
        $obj->fruits = ['apple', 'banana', 'cherry'];

        $xmlStr = $this->formatter->objectToString($obj);
        $result = $this->formatter->stringToObject($xmlStr);

        self::assertIsArray($result->fruits);
        self::assertSame('apple', $result->fruits[0]);
        self::assertSame('banana', $result->fruits[1]);
        self::assertSame('cherry', $result->fruits[2]);
    }

    public function testRoundTripStringType(): void
    {
        $obj      = new stdClass();
        $obj->key = 'hello world';

        $xmlStr = $this->formatter->objectToString($obj);
        $result = $this->formatter->stringToObject($xmlStr);

        self::assertIsString($result->key);
        self::assertSame('hello world', $result->key);
    }

    public function testRoundTripIntegerType(): void
    {
        $obj       = new stdClass();
        $obj->num  = 123;

        $xmlStr = $this->formatter->objectToString($obj);
        $result = $this->formatter->stringToObject($xmlStr);

        self::assertIsInt($result->num);
        self::assertSame(123, $result->num);
    }

    public function testRoundTripFloatType(): void
    {
        $obj        = new stdClass();
        $obj->ratio = 2.718;

        $xmlStr = $this->formatter->objectToString($obj);
        $result = $this->formatter->stringToObject($xmlStr);

        self::assertIsFloat($result->ratio);
        self::assertEqualsWithDelta(2.718, $result->ratio, 0.0001);
    }

    public function testRoundTripWithCustomRootAndNodeName(): void
    {
        $obj      = new stdClass();
        $obj->key = 'value';

        $options = ['name' => 'config', 'nodeName' => 'entry'];
        $xmlStr  = $this->formatter->objectToString($obj, $options);
        $result  = $this->formatter->stringToObject($xmlStr);

        self::assertSame('value', $result->key);
    }

    public function testRoundTripDeeplyNestedObject(): void
    {
        $level3        = new stdClass();
        $level3->value = 'deep';

        $level2       = new stdClass();
        $level2->sub3 = $level3;

        $level1       = new stdClass();
        $level1->sub2 = $level2;

        $root       = new stdClass();
        $root->sub1 = $level1;

        $xmlStr = $this->formatter->objectToString($root);
        $result = $this->formatter->stringToObject($xmlStr);

        self::assertSame('deep', $result->sub1->sub2->sub3->value);
    }

    // -------------------------------------------------------------------------
    // getInstance factory
    // -------------------------------------------------------------------------

    public function testGetInstanceReturnsXmlFormatter(): void
    {
        $instance = \Awf\Registry\AbstractRegistryFormat::getInstance('xml');

        self::assertInstanceOf(Xml::class, $instance);
    }

    // -------------------------------------------------------------------------
    // edge cases
    // -------------------------------------------------------------------------

    public function testObjectToStringEmptyObject(): void
    {
        $result = $this->formatter->objectToString(new stdClass());

        self::assertIsString($result);
        // The root element should be present but have no child nodes.
        $xml = simplexml_load_string($result);
        self::assertCount(0, $xml->children());
    }

    public function testObjectToStringZeroIntegerValue(): void
    {
        $obj      = new stdClass();
        $obj->num = 0;

        $xmlStr = $this->formatter->objectToString($obj);
        $result = $this->formatter->stringToObject($xmlStr);

        self::assertSame(0, $result->num);
    }

    public function testObjectToStringEmptyStringValue(): void
    {
        $obj      = new stdClass();
        $obj->str = '';

        $xmlStr = $this->formatter->objectToString($obj);
        $result = $this->formatter->stringToObject($xmlStr);

        self::assertSame('', $result->str);
    }
}
