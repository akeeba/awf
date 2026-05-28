<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Database;

use Awf\Database\QueryElement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class QueryElementTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    public function testConstructSetsNameGlueAndElements(): void
    {
        $element = new QueryElement('SELECT', ['a', 'b'], ', ');

        self::assertSame('SELECT', $element->getName());
        self::assertSame(', ', $element->getGlue());
        self::assertSame(['a', 'b'], $element->getElements());
    }

    public function testConstructDefaultGlueIsComma(): void
    {
        $element = new QueryElement('SELECT', 'a');

        self::assertSame(',', $element->getGlue());
    }

    public function testConstructWithStringSingleElement(): void
    {
        $element = new QueryElement('WHERE', 'id = 1');

        self::assertSame(['id = 1'], $element->getElements());
    }

    public function testConstructWithArrayElements(): void
    {
        $element = new QueryElement('SELECT', ['col1', 'col2', 'col3']);

        self::assertSame(['col1', 'col2', 'col3'], $element->getElements());
    }

    public function testConstructWithEmptyArray(): void
    {
        $element = new QueryElement('SELECT', []);

        self::assertSame([], $element->getElements());
    }

    // -------------------------------------------------------------------------
    // append()
    // -------------------------------------------------------------------------

    public function testAppendStringAddsToElements(): void
    {
        $element = new QueryElement('SELECT', 'a');
        $element->append('b');

        self::assertSame(['a', 'b'], $element->getElements());
    }

    public function testAppendArrayMergesElements(): void
    {
        $element = new QueryElement('SELECT', ['a', 'b']);
        $element->append(['c', 'd']);

        self::assertSame(['a', 'b', 'c', 'd'], $element->getElements());
    }

    public function testAppendMultipleTimes(): void
    {
        $element = new QueryElement('SELECT', 'a');
        $element->append('b');
        $element->append('c');

        self::assertSame(['a', 'b', 'c'], $element->getElements());
    }

    public function testAppendEmptyArrayDoesNotChangeElements(): void
    {
        $element = new QueryElement('SELECT', ['a', 'b']);
        $element->append([]);

        self::assertSame(['a', 'b'], $element->getElements());
    }

    // -------------------------------------------------------------------------
    // __toString()
    // -------------------------------------------------------------------------

    public static function toStringRegularProvider(): array
    {
        return [
            'single element' => [
                'SELECT',
                ['col1'],
                ',',
                PHP_EOL . 'SELECT col1',
            ],
            'multiple elements with comma glue' => [
                'SELECT',
                ['col1', 'col2', 'col3'],
                ',',
                PHP_EOL . 'SELECT col1,col2,col3',
            ],
            'multiple elements with comma-space glue' => [
                'SELECT',
                ['col1', 'col2'],
                ', ',
                PHP_EOL . 'SELECT col1, col2',
            ],
            'WHERE with AND glue' => [
                'WHERE',
                ['a = 1', 'b = 2'],
                ' AND ',
                PHP_EOL . 'WHERE a = 1 AND b = 2',
            ],
            'empty elements list' => [
                'SELECT',
                [],
                ',',
                PHP_EOL . 'SELECT ',
            ],
        ];
    }

    #[DataProvider('toStringRegularProvider')]
    public function testToStringRegular(
        string $name,
        array $elements,
        string $glue,
        string $expected
    ): void {
        $element = new QueryElement($name, $elements, $glue);

        self::assertSame($expected, (string) $element);
    }

    public static function toStringFunctionProvider(): array
    {
        return [
            'function-style name single arg' => [
                'VALUES()',
                ['1, 2, 3'],
                ',',
                PHP_EOL . 'VALUES(1, 2, 3)',
            ],
            'function-style name multiple args' => [
                'COALESCE()',
                ['col1', 'col2', "'default'"],
                ', ',
                PHP_EOL . 'COALESCE(col1, col2, \'default\')',
            ],
            'function-style with empty elements' => [
                'NOW()',
                [],
                ',',
                PHP_EOL . 'NOW()',
            ],
        ];
    }

    #[DataProvider('toStringFunctionProvider')]
    public function testToStringFunctionStyle(
        string $name,
        array $elements,
        string $glue,
        string $expected
    ): void {
        $element = new QueryElement($name, $elements, $glue);

        self::assertSame($expected, (string) $element);
    }

    public function testToStringStartsWithNewline(): void
    {
        $element = new QueryElement('FROM', 'table1');

        self::assertStringStartsWith(PHP_EOL, (string) $element);
    }

    public function testToStringFunctionStyleDoesNotHaveSpaceBetweenNameAndParens(): void
    {
        $element = new QueryElement('COUNT()', ['*']);
        $str = (string) $element;

        self::assertStringContainsString('COUNT(*)', $str);
        self::assertStringNotContainsString('COUNT() ', $str);
    }

    // -------------------------------------------------------------------------
    // setName()
    // -------------------------------------------------------------------------

    public function testSetNameChangesName(): void
    {
        $element = new QueryElement('SELECT', 'a');
        $element->setName('FROM');

        self::assertSame('FROM', $element->getName());
    }

    public function testSetNameReturnsSelf(): void
    {
        $element = new QueryElement('SELECT', 'a');
        $result = $element->setName('FROM');

        self::assertSame($element, $result);
    }

    // -------------------------------------------------------------------------
    // __clone() deep copy
    // -------------------------------------------------------------------------

    public function testCloneProducesIndependentElementsArray(): void
    {
        $original = new QueryElement('SELECT', ['col1', 'col2']);
        $clone = clone $original;

        $clone->append('col3');

        self::assertSame(['col1', 'col2'], $original->getElements());
        self::assertSame(['col1', 'col2', 'col3'], $clone->getElements());
    }

    public function testClonePreservesName(): void
    {
        $original = new QueryElement('FROM', 'table1');
        $clone = clone $original;

        self::assertSame('FROM', $clone->getName());
    }

    public function testClonePreservesGlue(): void
    {
        $original = new QueryElement('SELECT', ['a', 'b'], ' AND ');
        $clone = clone $original;

        self::assertSame(' AND ', $clone->getGlue());
    }

    public function testCloneChangingOriginalNameDoesNotAffectClone(): void
    {
        $original = new QueryElement('SELECT', 'a');
        $clone = clone $original;

        $original->setName('FROM');

        self::assertSame('SELECT', $clone->getName());
    }

    public function testCloneToStringAreEqual(): void
    {
        $original = new QueryElement('WHERE', ['x = 1', 'y = 2'], ' AND ');
        $clone = clone $original;

        self::assertSame((string) $original, (string) $clone);
    }
}
