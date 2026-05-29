<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Html\Helper;

use Awf\Container\Container;
use Awf\Html\Helper\Select;
use Awf\Html\HtmlService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Select::class)]
class SelectTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeContainer(): Container
    {
        return new Container(
            [
                'application_name'     => 'unittest',
                'applicationNamespace' => '\\UnitTest',
                'session_segment_name' => 'unittest_segment',
                'filesystemBase'       => '/dev/null',
                'basePath'             => '/dev/null',
                'templatePath'         => '/dev/null',
                'languagePath'         => '/dev/null',
                'temporaryPath'        => '/tmp',
                'sqlPath'              => '/dev/null',
            ]
        );
    }

    private function makeSelect(): Select
    {
        $container = $this->makeContainer();
        $helper    = new Select();
        $helper->setContainer($container);

        return $helper;
    }

    // =========================================================================
    // option()
    // =========================================================================

    public function testOptionReturnsStdClass(): void
    {
        $select = $this->makeSelect();

        $obj = $select->option('foo', 'Foo Label');

        self::assertIsObject($obj);
    }

    public function testOptionSetsValueAndText(): void
    {
        $select = $this->makeSelect();

        $obj = $select->option('bar', 'Bar Text');

        self::assertSame('bar', $obj->value);
        self::assertSame('Bar Text', $obj->text);
    }

    public function testOptionFallsBackToValueWhenTextIsEmpty(): void
    {
        $select = $this->makeSelect();

        $obj = $select->option('fallback', '');

        self::assertSame('fallback', $obj->text);
    }

    public function testOptionUsesCustomKeyAndTextPropertyNames(): void
    {
        $select = $this->makeSelect();

        $obj = $select->option('v', 'T', 'myKey', 'myText');

        self::assertSame('v', $obj->myKey);
        self::assertSame('T', $obj->myText);
        self::assertFalse(isset($obj->value));
        self::assertFalse(isset($obj->text));
    }

    public function testOptionWithArrayOptions(): void
    {
        $select = $this->makeSelect();

        $obj = $select->option('k', 'Label', [
            'option.key'  => 'id',
            'option.text' => 'name',
            'disable'     => false,
        ]);

        self::assertSame('k', $obj->id);
        self::assertSame('Label', $obj->name);
    }

    public function testOptionWithLabelInOptions(): void
    {
        $select = $this->makeSelect();

        $obj = $select->option('x', 'X Text', [
            'option.key'   => 'value',
            'option.text'  => 'text',
            'option.label' => 'label',
            'label'        => 'My Label',
        ]);

        self::assertSame('My Label', $obj->label);
    }

    public function testOptionWithNoLabelPropertyAndNoLabel(): void
    {
        // When option.label is null and no label key is given, label is not set
        $select = $this->makeSelect();

        $obj = $select->option('y', 'Y Text', [
            'option.key'   => 'value',
            'option.text'  => 'text',
            'option.label' => null,
        ]);

        self::assertFalse(isset($obj->label));
    }

    public function testOptionSetsAttrProperty(): void
    {
        $select = $this->makeSelect();

        $obj = $select->option('z', 'Z Text', [
            'option.key'  => 'value',
            'option.text' => 'text',
            'option.attr' => 'attrs',
            'attr'        => 'data-foo="bar"',
        ]);

        self::assertSame('data-foo="bar"', $obj->attrs);
    }

    public function testOptionSetsDisableProperty(): void
    {
        $select = $this->makeSelect();

        $obj = $select->option('d', 'Disabled', 'value', 'text', true);

        self::assertTrue($obj->disable);
    }

    // =========================================================================
    // options()
    // =========================================================================

    public function testOptionsWithArrayOfObjects(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('1', 'One'),
            $select->option('2', 'Two'),
            $select->option('3', 'Three'),
        ];

        $html = $select->options($data);

        self::assertStringContainsString('<option value="1"', $html);
        self::assertStringContainsString('>One</option>', $html);
        self::assertStringContainsString('<option value="2"', $html);
        self::assertStringContainsString('>Two</option>', $html);
        self::assertStringContainsString('<option value="3"', $html);
        self::assertStringContainsString('>Three</option>', $html);
    }

    public function testOptionsWithArrayOfArrays(): void
    {
        $select = $this->makeSelect();

        $data = [
            ['value' => 'a', 'text' => 'Alpha'],
            ['value' => 'b', 'text' => 'Beta'],
        ];

        $html = $select->options($data);

        self::assertStringContainsString('<option value="a"', $html);
        self::assertStringContainsString('>Alpha</option>', $html);
        self::assertStringContainsString('<option value="b"', $html);
        self::assertStringContainsString('>Beta</option>', $html);
    }

    public function testOptionsWithScalarValues(): void
    {
        // Scalar arrays: key is the option value, element is the text
        $select = $this->makeSelect();

        $data = ['foo' => 'Foo Label', 'bar' => 'Bar Label'];

        $html = $select->options($data);

        self::assertStringContainsString('<option value="foo"', $html);
        self::assertStringContainsString('>Foo Label</option>', $html);
        self::assertStringContainsString('<option value="bar"', $html);
        self::assertStringContainsString('>Bar Label</option>', $html);
    }

    public function testOptionsMarksSingleSelected(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('1', 'One'),
            $select->option('2', 'Two'),
        ];

        $html = $select->options($data, 'value', 'text', '2');

        self::assertStringContainsString('value="2" selected="selected"', $html);
        self::assertStringNotContainsString('value="1" selected="selected"', $html);
    }

    public function testOptionsMarksMultipleSelected(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('a', 'Alpha'),
            $select->option('b', 'Beta'),
            $select->option('c', 'Gamma'),
        ];

        $html = $select->options($data, 'value', 'text', ['a', 'c']);

        self::assertStringContainsString('value="a" selected="selected"', $html);
        self::assertStringNotContainsString('value="b" selected="selected"', $html);
        self::assertStringContainsString('value="c" selected="selected"', $html);
    }

    public function testOptionsRendersDisabledOption(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('x', 'X', ['option.key' => 'value', 'option.text' => 'text', 'disable' => true]),
        ];

        $html = $select->options($data);

        self::assertStringContainsString('disabled="disabled"', $html);
    }

    public function testOptionsHtmlEncodesKeyAndText(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('<script>', '<b>Bold</b>'),
        ];

        $html = $select->options($data);

        self::assertStringNotContainsString('<script>', $html);
        self::assertStringNotContainsString('<b>Bold</b>', $html);
        self::assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testOptionsWithNullKeyUsesArrayIndex(): void
    {
        $select = $this->makeSelect();

        $data = ['alpha', 'beta'];

        // Passing optKey=null via the array-options form
        $html = $select->options($data, ['option.key' => null, 'option.text' => null]);

        // Array keys 0, 1 become the option values
        self::assertStringContainsString('value="0"', $html);
        self::assertStringContainsString('value="1"', $html);
    }

    public function testOptionsWithOptionsArray(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('p', 'Pear'),
            $select->option('q', 'Quince'),
        ];

        $html = $select->options($data, ['list.select' => 'p']);

        self::assertStringContainsString('value="p" selected="selected"', $html);
        self::assertStringNotContainsString('value="q" selected="selected"', $html);
    }

    public function testOptionsIdAttribute(): void
    {
        $select = $this->makeSelect();

        // Build objects that have an id property set
        $obj       = new \stdClass();
        $obj->value = '10';
        $obj->text  = 'Ten';
        $obj->id    = 'opt-10';

        $html = $select->options([$obj], ['option.id' => 'id']);

        self::assertStringContainsString('id="opt-10"', $html);
    }

    public function testOptionsLabelAttribute(): void
    {
        $select = $this->makeSelect();

        $obj        = new \stdClass();
        $obj->value = '20';
        $obj->text  = 'Twenty';
        $obj->label = 'Label Twenty';

        $html = $select->options([$obj], ['option.label' => 'label']);

        self::assertStringContainsString('label="Label Twenty"', $html);
    }

    public function testOptionsEmptyArray(): void
    {
        $select = $this->makeSelect();

        $html = $select->options([]);

        self::assertSame('', $html);
    }

    // =========================================================================
    // genericList()
    // =========================================================================

    public function testGenericListBasicStructure(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('1', 'One'),
            $select->option('2', 'Two'),
        ];

        $html = $select->genericList($data, 'myselect');

        self::assertStringContainsString('<select', $html);
        self::assertStringContainsString('name="myselect"', $html);
        self::assertStringContainsString('id="myselect"', $html);
        self::assertStringContainsString('</select>', $html);
        self::assertStringContainsString('<option value="1"', $html);
        self::assertStringContainsString('<option value="2"', $html);
    }

    public function testGenericListWithSelectedValue(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('a', 'Alpha'),
            $select->option('b', 'Beta'),
        ];

        $html = $select->genericList($data, 'myselect', null, 'value', 'text', 'b');

        self::assertStringContainsString('value="b" selected="selected"', $html);
        self::assertStringNotContainsString('value="a" selected="selected"', $html);
    }

    public function testGenericListWithCustomIdTag(): void
    {
        $select = $this->makeSelect();

        $data = [$select->option('1', 'One')];

        $html = $select->genericList($data, 'myselect', null, 'value', 'text', null, 'custom-id');

        self::assertStringContainsString('id="custom-id"', $html);
    }

    public function testGenericListIdTagFalseUsesName(): void
    {
        $select = $this->makeSelect();

        $data = [$select->option('1', 'One')];

        $html = $select->genericList($data, 'fieldname', null, 'value', 'text', null, false);

        self::assertStringContainsString('id="fieldname"', $html);
    }

    public function testGenericListEmptyIdTagProducesNoIdAttribute(): void
    {
        $select = $this->makeSelect();

        $data = [$select->option('1', 'One')];

        // Passing empty string '' as idTag: id = '' (after stripping brackets), so '' !== false → uses ''
        $html = $select->genericList($data, 'fieldname', null, 'value', 'text', null, '');

        // When id is empty string, no id attribute should be rendered
        self::assertStringNotContainsString('id=', $html);
    }

    public function testGenericListWithAttribsArray(): void
    {
        $select = $this->makeSelect();

        $data = [$select->option('1', 'One')];

        $html = $select->genericList($data, 'myselect', ['class' => 'my-class'], 'value', 'text');

        self::assertStringContainsString('class="my-class"', $html);
    }

    public function testGenericListWithAttribsString(): void
    {
        $select = $this->makeSelect();

        $data = [$select->option('1', 'One')];

        $html = $select->genericList($data, 'myselect', ['list.attr' => 'data-custom="1"'], 'value', 'text');

        self::assertStringContainsString('data-custom="1"', $html);
    }

    public function testGenericListStripsSquareBracketsFromId(): void
    {
        $select = $this->makeSelect();

        $data = [$select->option('1', 'One')];

        $html = $select->genericList($data, 'items[]', null, 'value', 'text', null, 'items[]');

        self::assertStringContainsString('id="items"', $html);
    }

    public function testGenericListWithArrayOfScalars(): void
    {
        $select = $this->makeSelect();

        $data = [10 => 'Ten', 20 => 'Twenty'];

        // When option.key is null, use array index
        $html = $select->genericList($data, 'myselect', ['option.key' => null]);

        self::assertStringContainsString('value="10"', $html);
        self::assertStringContainsString('value="20"', $html);
        self::assertStringContainsString('>Ten</option>', $html);
        self::assertStringContainsString('>Twenty</option>', $html);
    }

    public function testGenericListWithMultipleSelected(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('x', 'X'),
            $select->option('y', 'Y'),
            $select->option('z', 'Z'),
        ];

        $html = $select->genericList($data, 'multi', null, 'value', 'text', ['x', 'z']);

        self::assertStringContainsString('value="x" selected="selected"', $html);
        self::assertStringNotContainsString('value="y" selected="selected"', $html);
        self::assertStringContainsString('value="z" selected="selected"', $html);
    }

    // =========================================================================
    // setOptionSettings() / getOptionSettings()
    // =========================================================================

    public function testGetOptionSettingsReturnsDefaults(): void
    {
        $select = $this->makeSelect();

        $settings = $select->getOptionSettings();

        self::assertArrayHasKey('option.key', $settings);
        self::assertArrayHasKey('option.text', $settings);
        self::assertArrayHasKey('option.disable', $settings);
        self::assertSame('value', $settings['option.key']);
        self::assertSame('text', $settings['option.text']);
        self::assertSame('disable', $settings['option.disable']);
    }

    public function testSetOptionSettingsUpdatesKnownKey(): void
    {
        $select = $this->makeSelect();

        $select->setOptionSettings(['option.key' => 'id', 'option.text' => 'label']);

        $settings = $select->getOptionSettings();

        self::assertSame('id', $settings['option.key']);
        self::assertSame('label', $settings['option.text']);
    }

    public function testSetOptionSettingsIgnoresUnknownKeys(): void
    {
        $select = $this->makeSelect();

        $select->setOptionSettings(['unknown.setting' => 'foo']);

        $settings = $select->getOptionSettings();

        self::assertArrayNotHasKey('unknown.setting', $settings);
    }

    // =========================================================================
    // options() — edge cases and attribute rendering
    // =========================================================================

    public function testOptionsAttrArrayIsConvertedToString(): void
    {
        $select = $this->makeSelect();

        $obj        = new \stdClass();
        $obj->value = 'v';
        $obj->text  = 'T';
        $obj->attrs = ['data-role' => 'item'];

        $html = $select->options([$obj], ['option.attr' => 'attrs']);

        self::assertStringContainsString('data-role="item"', $html);
    }

    public function testOptionsWithTranslateUsesLanguageService(): void
    {
        // The language service returns the key as-is for untranslated keys,
        // so we just verify the call path doesn't throw.
        $select = $this->makeSelect();

        $data = [
            $select->option('yes', 'AWF_YES'),
        ];

        $html = $select->options($data, 'value', 'text', null, true);

        self::assertIsString($html);
        self::assertStringContainsString('<option', $html);
    }

    public function testOptionsSelectedViaObjectInArray(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('a', 'Alpha'),
            $select->option('b', 'Beta'),
        ];

        // An array of objects where each has the option key property
        $selectedObj        = new \stdClass();
        $selectedObj->value = 'b';

        $html = $select->options($data, 'value', 'text', [$selectedObj]);

        self::assertStringContainsString('value="b" selected="selected"', $html);
        self::assertStringNotContainsString('value="a" selected="selected"', $html);
    }

    // =========================================================================
    // genericList() — options array form (3-argument call)
    // =========================================================================

    public function testGenericListOptionsArrayForm(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('1', 'One'),
            $select->option('2', 'Two'),
        ];

        // When called with exactly 3 args and the 3rd is an array, it is
        // treated as an options array.
        $html = $select->genericList($data, 'myselect', ['list.select' => '1']);

        self::assertStringContainsString('value="1" selected="selected"', $html);
    }

    // =========================================================================
    // radioList()
    // =========================================================================

    public function testRadioListBasicStructure(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('0', 'No'),
            $select->option('1', 'Yes'),
        ];

        $html = $select->radioList($data, 'myflag');

        self::assertStringContainsString('type="radio"', $html);
        self::assertStringContainsString('name="myflag"', $html);
        self::assertStringContainsString('value="0"', $html);
        self::assertStringContainsString('value="1"', $html);
    }

    public function testRadioListChecksSelectedValue(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('0', 'No'),
            $select->option('1', 'Yes'),
        ];

        $html = $select->radioList($data, 'myflag', [], 'value', 'text', '1');

        self::assertStringContainsString('checked="checked"', $html);
    }

    public function testRadioListAsCheckbox(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('a', 'Apple'),
            $select->option('b', 'Banana'),
        ];

        $html = $select->radioList($data, 'fruits', ['radioType' => 'checkbox']);

        self::assertStringContainsString('type="checkbox"', $html);
        self::assertStringNotContainsString('type="radio"', $html);
    }

    public function testRadioListInlineMode(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('1', 'One'),
        ];

        $html = $select->radioList($data, 'num', ['inline' => true]);

        self::assertStringContainsString('radio-inline', $html);
    }

    public function testRadioListButtonMode(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('1', 'One'),
        ];

        $html = $select->radioList($data, 'num', ['button' => true]);

        self::assertStringContainsString('btn-group', $html);
        self::assertStringContainsString('btn btn-default', $html);
    }

    public function testRadioListWithIdTag(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('1', 'One'),
        ];

        $html = $select->radioList($data, 'num', [], 'value', 'text', null, 'myid');

        self::assertStringContainsString('id="myid1"', $html);
    }

    public function testRadioListWithArraySelected(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('a', 'Alpha'),
            $select->option('b', 'Beta'),
        ];

        $html = $select->radioList($data, 'letters', [], 'value', 'text', ['a']);

        // selected="selected" is used for array-selected in radio buttons (matches source behavior)
        self::assertStringContainsString('value="a"', $html);
        self::assertStringContainsString('selected="selected"', $html);
    }

    public function testRadioListInvalidRadioTypeFallsBackToRadio(): void
    {
        $select = $this->makeSelect();

        $data = [
            $select->option('1', 'One'),
        ];

        $html = $select->radioList($data, 'num', ['radioType' => 'invalid']);

        self::assertStringContainsString('type="radio"', $html);
    }
}
