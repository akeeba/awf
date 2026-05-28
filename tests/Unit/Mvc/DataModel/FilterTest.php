<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc\DataModel;

use Awf\Database\Driver\Sqlite as SqliteDriver;
use Awf\Mvc\DataModel\Filter\AbstractFilter;
use Awf\Mvc\DataModel\Filter\Boolean as BooleanFilter;
use Awf\Mvc\DataModel\Filter\Date as DateFilter;
use Awf\Mvc\DataModel\Filter\Number as NumberFilter;
use Awf\Mvc\DataModel\Filter\Relation as RelationFilter;
use Awf\Mvc\DataModel\Filter\Text as TextFilter;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit / integration tests for Awf\Mvc\DataModel\Filter\* classes.
 *
 * An in-memory SQLite driver is used solely for its quote() and quoteName()
 * methods — no actual table or schema is needed.
 */
class FilterTest extends TestCase
{
    private SqliteDriver $db;

    protected function setUp(): void
    {
        parent::setUp();

        if (!SqliteDriver::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        $this->db = new SqliteDriver([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $this->db->connect();
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /** Build a minimal field stub. */
    private function field(string $name, string $type, array $extra = []): object
    {
        $f       = new \stdClass();
        $f->name = $name;
        $f->type = $type;

        foreach ($extra as $k => $v) {
            $f->$k = $v;
        }

        return $f;
    }

    // =========================================================================
    // AbstractFilter::getFieldType
    // =========================================================================

    public static function fieldTypeProvider(): array
    {
        return [
            // Text-family
            'varchar'                      => ['varchar', 'Text'],
            'VARCHAR(255)'                 => ['VARCHAR(255)', 'Text'],
            'text'                         => ['text', 'Text'],
            'char'                         => ['char', 'Text'],
            'mediumtext'                   => ['mediumtext', 'Text'],
            'longtext'                     => ['longtext', 'Text'],
            'nvarchar'                     => ['nvarchar', 'Text'],
            'nchar'                        => ['nchar', 'Text'],
            'json'                         => ['json', 'Text'],
            'character varying'            => ['character varying', 'Text'],

            // Date-family
            'date'                         => ['date', 'Date'],
            'datetime'                     => ['datetime', 'Date'],
            'time'                         => ['time', 'Date'],
            'year'                         => ['year', 'Date'],
            'timestamp'                    => ['timestamp', 'Date'],
            'timestamp without time zone'  => ['timestamp without time zone', 'Date'],
            'timestamp with time zone'     => ['timestamp with time zone', 'Date'],

            // Boolean-family
            'tinyint'                      => ['tinyint', 'Boolean'],
            'smallint'                     => ['smallint', 'Boolean'],

            // Number (default / fallback)
            'int'                          => ['int', 'Number'],
            'integer'                      => ['integer', 'Number'],
            'bigint'                       => ['bigint', 'Number'],
            'float'                        => ['float', 'Number'],
            'double'                       => ['double', 'Number'],
            'decimal'                      => ['decimal', 'Number'],
            'unknown_type'                 => ['unknown_type', 'Number'],
            'empty string'                 => ['', 'Number'],
        ];
    }

    #[DataProvider('fieldTypeProvider')]
    public function testGetFieldType(string $rawType, string $expectedClass): void
    {
        self::assertSame($expectedClass, AbstractFilter::getFieldType($rawType));
    }

    // =========================================================================
    // AbstractFilter::getField (factory)
    // =========================================================================

    public function testGetFieldReturnsNumberFilterForIntegerType(): void
    {
        $filter = AbstractFilter::getField(
            $this->field('age', 'int'),
            ['dbo' => $this->db]
        );

        self::assertInstanceOf(NumberFilter::class, $filter);
    }

    public function testGetFieldReturnsTextFilterForVarchar(): void
    {
        $filter = AbstractFilter::getField(
            $this->field('title', 'varchar'),
            ['dbo' => $this->db]
        );

        self::assertInstanceOf(TextFilter::class, $filter);
    }

    public function testGetFieldReturnsDateFilterForDatetime(): void
    {
        $filter = AbstractFilter::getField(
            $this->field('created_at', 'datetime'),
            ['dbo' => $this->db]
        );

        self::assertInstanceOf(DateFilter::class, $filter);
    }

    public function testGetFieldReturnsBooleanFilterForTinyint(): void
    {
        $filter = AbstractFilter::getField(
            $this->field('enabled', 'tinyint'),
            ['dbo' => $this->db]
        );

        self::assertInstanceOf(BooleanFilter::class, $filter);
    }

    public function testGetFieldThrowsWhenFieldIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AbstractFilter::getField(new \stdClass(), ['dbo' => $this->db]);
    }

    public function testGetFieldThrowsWhenDboIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AbstractFilter::getField($this->field('col', 'int'), []);
    }

    public function testGetFieldReturnsNullForUnknownButEmptyTypeWithNoFallback(): void
    {
        // An entirely unknown type still falls through to Number; getField must
        // return an instance, not null, for a valid field object.
        $filter = AbstractFilter::getField(
            $this->field('col', 'some_exotic_type'),
            ['dbo' => $this->db]
        );

        self::assertNotNull($filter);
    }

    // =========================================================================
    // AbstractFilter constructor guard
    // =========================================================================

    public function testConstructorThrowsForInvalidFieldObject(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new NumberFilter($this->db, new \stdClass());
    }

    // =========================================================================
    // AbstractFilter::isEmpty
    // =========================================================================

    public static function isEmptyProvider(): array
    {
        return [
            'null'                => [null,  true],
            'empty string'        => ['',    true],
            'zero string'         => ['0',   false],  // filterZero=true (default)
            'zero int'            => [0,     true],   // 0 != "0"
            'non-empty string'    => ['foo', false],
            'non-zero int'        => [42,    false],
        ];
    }

    #[DataProvider('isEmptyProvider')]
    public function testIsEmptyOnAbstractFilter(mixed $value, bool $expected): void
    {
        $filter = new NumberFilter($this->db, $this->field('n', 'int'));

        self::assertSame($expected, $filter->isEmpty($value));
    }

    public function testIsEmptyWithFilterZeroFalse(): void
    {
        $filter = new NumberFilter($this->db, $this->field('n', 'int', ['filterZero' => false]));

        // With filterZero=false, "0" should count as empty.
        self::assertTrue($filter->isEmpty('0'));
    }

    // =========================================================================
    // AbstractFilter::getFieldName / getSearchMethods
    // =========================================================================

    public function testGetFieldNameReturnsQuotedName(): void
    {
        $filter = new NumberFilter($this->db, $this->field('my_col', 'int'));

        // SQLite uses backtick quoting.
        self::assertSame('`my_col`', $filter->getFieldName());
    }

    public function testGetSearchMethodsIsNotEmpty(): void
    {
        $filter = new NumberFilter($this->db, $this->field('n', 'int'));

        self::assertNotEmpty($filter->getSearchMethods());
    }

    // =========================================================================
    // AbstractFilter::search
    // =========================================================================

    public function testSearchReturnsEmptyForEmptyValue(): void
    {
        $filter = new NumberFilter($this->db, $this->field('n', 'int'));

        self::assertSame('', $filter->search(null));
    }

    public function testSearchWithEqualsOperator(): void
    {
        $filter = new NumberFilter($this->db, $this->field('n', 'int'));
        $sql    = $filter->search(42, '=');

        self::assertStringContainsString('`n`', $sql);
        self::assertStringContainsString("'42'", $sql);
        self::assertStringContainsString('=', $sql);
    }

    public function testSearchWithNegationPrefix(): void
    {
        $filter = new NumberFilter($this->db, $this->field('n', 'int'));
        $sql    = $filter->search(5, '!=');

        self::assertStringContainsString('NOT', $sql);
    }

    // =========================================================================
    // Number filter
    // =========================================================================

    public function testNumberExactSingleValue(): void
    {
        $filter = new NumberFilter($this->db, $this->field('age', 'int'));
        $sql    = $filter->exact(30);

        self::assertSame("(`age` = '30')", $sql);
    }

    public function testNumberExactArray(): void
    {
        $filter = new NumberFilter($this->db, $this->field('age', 'int'));
        $sql    = $filter->exact([1, 2, 3]);

        self::assertStringContainsString('IN', $sql);
        self::assertStringContainsString("'1'", $sql);
        self::assertStringContainsString("'3'", $sql);
    }

    public function testNumberExactEmptyReturnsEmpty(): void
    {
        $filter = new NumberFilter($this->db, $this->field('age', 'int'));

        self::assertSame('', $filter->exact(null));
    }

    public function testNumberBetweenInclusive(): void
    {
        $filter = new NumberFilter($this->db, $this->field('age', 'int'));
        $sql    = $filter->between(10, 20, true);

        self::assertStringContainsString('>=', $sql);
        self::assertStringContainsString('<=', $sql);
        self::assertStringContainsString('10', $sql);
        self::assertStringContainsString('20', $sql);
    }

    public function testNumberBetweenExclusive(): void
    {
        $filter = new NumberFilter($this->db, $this->field('age', 'int'));
        $sql    = $filter->between(10, 20, false);

        self::assertStringContainsString('>', $sql);
        self::assertStringContainsString('<', $sql);
        self::assertStringNotContainsString('>=', $sql);
        self::assertStringNotContainsString('<=', $sql);
    }

    public function testNumberBetweenReturnsEmptyWhenFromEmpty(): void
    {
        $filter = new NumberFilter($this->db, $this->field('age', 'int'));

        self::assertSame('', $filter->between(null, 20));
    }

    public function testNumberBetweenReturnsEmptyWhenToEmpty(): void
    {
        $filter = new NumberFilter($this->db, $this->field('age', 'int'));

        self::assertSame('', $filter->between(10, null));
    }

    public function testNumberOutsideExclusive(): void
    {
        $filter = new NumberFilter($this->db, $this->field('age', 'int'));
        $sql    = $filter->outside(10, 20, false);

        self::assertStringContainsString('OR', $sql);
        self::assertStringContainsString('10', $sql);
        self::assertStringContainsString('20', $sql);
        self::assertStringNotContainsString('<=', $sql);
        self::assertStringNotContainsString('>=', $sql);
    }

    public function testNumberOutsideInclusive(): void
    {
        $filter = new NumberFilter($this->db, $this->field('age', 'int'));
        $sql    = $filter->outside(10, 20, true);

        self::assertStringContainsString('<=', $sql);
        self::assertStringContainsString('>=', $sql);
    }

    public function testNumberOutsideReturnsEmptyWhenBothEmpty(): void
    {
        $filter = new NumberFilter($this->db, $this->field('age', 'int'));

        self::assertSame('', $filter->outside(null, null));
    }

    public function testNumberInterval(): void
    {
        $filter = new NumberFilter($this->db, $this->field('score', 'int'));
        $sql    = $filter->interval(50, 10, true);

        // center=50, interval=10 → range [40, 60]
        self::assertStringContainsString('40', $sql);
        self::assertStringContainsString('60', $sql);
        self::assertStringContainsString('AND', $sql);
    }

    public function testNumberIntervalReturnsEmptyWhenValueEmpty(): void
    {
        $filter = new NumberFilter($this->db, $this->field('score', 'int'));

        self::assertSame('', $filter->interval(null, 10));
    }

    public function testNumberRangeBothBounds(): void
    {
        $filter = new NumberFilter($this->db, $this->field('price', 'int'));
        $sql    = $filter->range(5, 100, true);

        self::assertStringContainsString('>=', $sql);
        self::assertStringContainsString('<=', $sql);
    }

    public function testNumberRangeOnlyFrom(): void
    {
        $filter = new NumberFilter($this->db, $this->field('price', 'int'));
        $sql    = $filter->range(5, null, true);

        self::assertStringContainsString('5', $sql);
        self::assertStringNotContainsString('<', $sql);
    }

    public function testNumberRangeOnlyTo(): void
    {
        $filter = new NumberFilter($this->db, $this->field('price', 'int'));
        $sql    = $filter->range(null, 100, true);

        self::assertStringContainsString('100', $sql);
        self::assertStringNotContainsString('>', $sql);
    }

    public function testNumberRangeReturnsEmptyWhenBothEmpty(): void
    {
        $filter = new NumberFilter($this->db, $this->field('price', 'int'));

        self::assertSame('', $filter->range(null, null));
    }

    public function testNumberModulo(): void
    {
        $filter = new NumberFilter($this->db, $this->field('n', 'int'));
        $sql    = $filter->modulo(3, 7, true);

        self::assertStringContainsString('%', $sql);
        self::assertStringContainsString('= 0', $sql);
    }

    public function testNumberModuloReturnsEmptyWhenEitherValueEmpty(): void
    {
        $filter = new NumberFilter($this->db, $this->field('n', 'int'));

        self::assertSame('', $filter->modulo(null, 7));
        self::assertSame('', $filter->modulo(3, null));
    }

    public function testNumberPartialDelegatesToExact(): void
    {
        $filter = new NumberFilter($this->db, $this->field('n', 'int'));

        self::assertSame($filter->exact(42), $filter->partial(42));
    }

    public function testNumberSanitiseValueHandlesCommaDecimalSeparator(): void
    {
        $filter = new NumberFilter($this->db, $this->field('n', 'float'));
        $sql    = $filter->search('3,14', '=');

        // The sanitised value should use a dot, not a comma.
        self::assertStringContainsString('3.14', $sql);
        self::assertStringNotContainsString('3,14', $sql);
    }

    public function testNumberSanitiseValueArray(): void
    {
        $filter = new NumberFilter($this->db, $this->field('n', 'float'));
        $result = $filter->sanitiseValue(['1,5', '2,7']);

        self::assertSame(['1.5', '2.7'], $result);
    }

    public function testNumberDefaultSearchMethodIsExact(): void
    {
        $filter = new NumberFilter($this->db, $this->field('n', 'int'));

        self::assertSame('exact', $filter->getDefaultSearchMethod());
    }

    // =========================================================================
    // Boolean filter
    // =========================================================================

    public function testBooleanIsEmptyForNull(): void
    {
        $filter = new BooleanFilter($this->db, $this->field('enabled', 'tinyint'));

        self::assertTrue($filter->isEmpty(null));
    }

    public function testBooleanIsEmptyForEmptyString(): void
    {
        $filter = new BooleanFilter($this->db, $this->field('enabled', 'tinyint'));

        self::assertTrue($filter->isEmpty(''));
    }

    public function testBooleanIsNotEmptyForZeroString(): void
    {
        $filter = new BooleanFilter($this->db, $this->field('enabled', 'tinyint'));

        // Boolean overrides isEmpty — '0' must NOT be considered empty.
        self::assertFalse($filter->isEmpty('0'));
    }

    public function testBooleanIsNotEmptyForZeroInt(): void
    {
        $filter = new BooleanFilter($this->db, $this->field('enabled', 'tinyint'));

        self::assertFalse($filter->isEmpty(0));
    }

    public function testBooleanIsNotEmptyForOne(): void
    {
        $filter = new BooleanFilter($this->db, $this->field('enabled', 'tinyint'));

        self::assertFalse($filter->isEmpty(1));
    }

    public function testBooleanExactGeneratesCorrectSql(): void
    {
        $filter = new BooleanFilter($this->db, $this->field('enabled', 'tinyint'));
        $sql    = $filter->exact(1);

        self::assertSame("(`enabled` = '1')", $sql);
    }

    public function testBooleanExactForZero(): void
    {
        $filter = new BooleanFilter($this->db, $this->field('enabled', 'tinyint'));
        $sql    = $filter->exact(0);

        self::assertStringContainsString('`enabled`', $sql);
        self::assertStringContainsString("'0'", $sql);
    }

    // =========================================================================
    // Text filter
    // =========================================================================

    public function testTextDefaultSearchMethodIsPartial(): void
    {
        $filter = new TextFilter($this->db, $this->field('title', 'varchar'));

        self::assertSame('partial', $filter->getDefaultSearchMethod());
    }

    public function testTextPartialGeneratesLike(): void
    {
        $filter = new TextFilter($this->db, $this->field('title', 'varchar'));
        $sql    = $filter->partial('hello');

        self::assertStringContainsString('LIKE', $sql);
        self::assertStringContainsString('%hello%', $sql);
    }

    public function testTextPartialEmptyReturnsEmpty(): void
    {
        $filter = new TextFilter($this->db, $this->field('title', 'varchar'));

        self::assertSame('', $filter->partial(''));
    }

    public function testTextExactSingleValueGeneratesLike(): void
    {
        $filter = new TextFilter($this->db, $this->field('title', 'varchar'));
        $sql    = $filter->exact('world');

        self::assertStringContainsString('LIKE', $sql);
        self::assertStringContainsString("'world'", $sql);
        self::assertStringNotContainsString('%', $sql);
    }

    public function testTextExactArrayGeneratesIn(): void
    {
        $filter = new TextFilter($this->db, $this->field('status', 'varchar'));
        $sql    = $filter->exact(['a', 'b', 'c']);

        self::assertStringContainsString('IN', $sql);
        self::assertStringContainsString("'a'", $sql);
        self::assertStringContainsString("'c'", $sql);
    }

    public function testTextExactObjectCastToArray(): void
    {
        $filter = new TextFilter($this->db, $this->field('status', 'varchar'));
        $obj    = (object) ['x' => 'foo', 'y' => 'bar'];
        $sql    = $filter->exact($obj);

        self::assertStringContainsString('IN', $sql);
        self::assertStringContainsString("'foo'", $sql);
    }

    public function testTextExactEmptyReturnsEmpty(): void
    {
        $filter = new TextFilter($this->db, $this->field('title', 'varchar'));

        self::assertSame('', $filter->exact(''));
    }

    public function testTextBetweenAlwaysEmpty(): void
    {
        $filter = new TextFilter($this->db, $this->field('title', 'varchar'));

        self::assertSame('', $filter->between('a', 'z'));
    }

    public function testTextOutsideAlwaysEmpty(): void
    {
        $filter = new TextFilter($this->db, $this->field('title', 'varchar'));

        self::assertSame('', $filter->outside('a', 'z'));
    }

    public function testTextIntervalAlwaysEmpty(): void
    {
        $filter = new TextFilter($this->db, $this->field('title', 'varchar'));

        self::assertSame('', $filter->interval('value', 'interval'));
    }

    public function testTextRangeAlwaysEmpty(): void
    {
        $filter = new TextFilter($this->db, $this->field('title', 'varchar'));

        self::assertSame('', $filter->range('a', 'z'));
    }

    public function testTextModuloAlwaysEmpty(): void
    {
        $filter = new TextFilter($this->db, $this->field('title', 'varchar'));

        self::assertSame('', $filter->modulo('from', 'interval'));
    }

    // =========================================================================
    // Date filter
    // =========================================================================

    public function testDateDefaultSearchMethodIsExact(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));

        self::assertSame('exact', $filter->getDefaultSearchMethod());
    }

    public function testDateExactSingleValue(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $sql    = $filter->exact('2024-01-15');

        self::assertStringContainsString('LIKE', $sql);
        self::assertStringContainsString('2024-01-15', $sql);
    }

    public function testDatePartialLike(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $sql    = $filter->partial('2024');

        self::assertStringContainsString('LIKE', $sql);
        self::assertStringContainsString('%2024%', $sql);
    }

    public function testDateBetweenInclusive(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $sql    = $filter->between('2024-01-01', '2024-12-31', true);

        self::assertStringContainsString('>=', $sql);
        self::assertStringContainsString('<=', $sql);
        self::assertStringContainsString('2024-01-01', $sql);
        self::assertStringContainsString('2024-12-31', $sql);
    }

    public function testDateBetweenExclusive(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $sql    = $filter->between('2024-01-01', '2024-12-31', false);

        self::assertStringContainsString('>', $sql);
        self::assertStringContainsString('<', $sql);
        self::assertStringNotContainsString('>=', $sql);
        self::assertStringNotContainsString('<=', $sql);
    }

    public function testDateBetweenReturnsEmptyWhenFromEmpty(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));

        self::assertSame('', $filter->between('', '2024-12-31'));
    }

    public function testDateBetweenReturnsEmptyWhenToEmpty(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));

        self::assertSame('', $filter->between('2024-01-01', ''));
    }

    public function testDateOutsideInclusive(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $sql    = $filter->outside('2024-01-01', '2024-12-31', true);

        self::assertStringContainsString('<=', $sql);
        self::assertStringContainsString('>=', $sql);
    }

    public function testDateOutsideExclusive(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $sql    = $filter->outside('2024-01-01', '2024-12-31', false);

        self::assertStringContainsString('<', $sql);
        self::assertStringContainsString('>', $sql);
        self::assertStringNotContainsString('<=', $sql);
        self::assertStringNotContainsString('>=', $sql);
    }

    public function testDateOutsideReturnsEmptyWhenEitherEmpty(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));

        self::assertSame('', $filter->outside('', '2024-12-31'));
        self::assertSame('', $filter->outside('2024-01-01', ''));
    }

    public function testDateRangeBothBounds(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $sql    = $filter->range('2024-01-01', '2024-12-31', true);

        self::assertStringContainsString('>=', $sql);
        self::assertStringContainsString('<=', $sql);
        self::assertStringContainsString('AND', $sql);
    }

    public function testDateRangeOnlyFrom(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $sql    = $filter->range('2024-01-01', '', true);

        self::assertStringContainsString('2024-01-01', $sql);
        self::assertStringNotContainsString('<', $sql);
    }

    public function testDateRangeOnlyTo(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $sql    = $filter->range('', '2024-12-31', true);

        self::assertStringContainsString('2024-12-31', $sql);
        self::assertStringNotContainsString('>', $sql);
    }

    public function testDateRangeReturnsEmptyWhenBothEmpty(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));

        self::assertSame('', $filter->range('', ''));
    }

    public function testDateIntervalStringFormat(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $sql    = $filter->interval('2024-06-01', '+1 MONTH', true);

        self::assertStringContainsString('DATE_ADD', $sql);
        self::assertStringContainsString('INTERVAL', $sql);
        self::assertStringContainsString('MONTH', $sql);
    }

    public function testDateIntervalNegativeString(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $sql    = $filter->interval('2024-06-01', '-3 DAY', true);

        self::assertStringContainsString('DATE_SUB', $sql);
        self::assertStringContainsString('DAY', $sql);
    }

    public function testDateIntervalArrayFormat(): void
    {
        $filter   = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $interval = ['sign' => '+', 'value' => 2, 'unit' => 'WEEK'];
        $sql      = $filter->interval('2024-06-01', $interval, true);

        self::assertStringContainsString('DATE_ADD', $sql);
        self::assertStringContainsString('WEEK', $sql);
    }

    public function testDateIntervalObjectFormat(): void
    {
        $filter   = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $interval = (object) ['sign' => '-', 'value' => 1, 'unit' => 'YEAR'];
        $sql      = $filter->interval('2024-06-01', $interval, false);

        self::assertStringContainsString('DATE_SUB', $sql);
        self::assertStringContainsString('YEAR', $sql);
    }

    public function testDateIntervalReturnsEmptyWhenValueEmpty(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));

        self::assertSame('', $filter->interval('', '+1 MONTH'));
    }

    public function testDateIntervalReturnsEmptyWhenIntervalEmpty(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));

        self::assertSame('', $filter->interval('2024-01-01', ''));
    }

    public function testDateIntervalShortStringFallsBackToDefault(): void
    {
        // A string of ≤2 chars triggers the fallback (1 MONTH +)
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $sql    = $filter->interval('2024-01-01', 'X', true);

        // fallback is '+1 MONTH' → DATE_ADD … MONTH
        self::assertStringContainsString('DATE_ADD', $sql);
        self::assertStringContainsString('MONTH', $sql);
    }

    public function testDateIntervalMissingKeysReturnsEmpty(): void
    {
        $filter   = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $interval = ['sign' => '+'];   // missing 'value' and 'unit'
        $sql      = $filter->interval('2024-01-01', $interval);

        self::assertSame('', $sql);
    }

    // =========================================================================
    // Relation filter
    // =========================================================================

    public function testRelationGetFieldNameReturnsSubQueryString(): void
    {
        // Use a mock Query object whose __toString returns a known string.
        $query = $this->createMock(\Awf\Database\Query::class);
        $query->method('__toString')->willReturn('SELECT COUNT(*) FROM related WHERE fk=1');

        $filter = new RelationFilter($this->db, 'rel_name', $query);

        self::assertSame('(SELECT COUNT(*) FROM related WHERE fk=1)', $filter->getFieldName());
    }

    public function testRelationCallbackInvokesCallableWithSubQuery(): void
    {
        $query = $this->createMock(\Awf\Database\Query::class);
        $query->method('__toString')->willReturn('SELECT COUNT(*) FROM foo');

        $filter = new RelationFilter($this->db, 'rel', $query);

        $received = null;
        $filter->callback(static function ($q) use (&$received) {
            $received = $q;

            return 'dummy';
        });

        self::assertSame($query, $received);
    }

    public function testRelationExactProducesValidSql(): void
    {
        $query = $this->createMock(\Awf\Database\Query::class);
        $query->method('__toString')->willReturn('SELECT COUNT(*) FROM foo');

        $filter = new RelationFilter($this->db, 'rel', $query);
        $sql    = $filter->exact(3);

        self::assertStringContainsString('SELECT COUNT(*)', $sql);
        self::assertStringContainsString("'3'", $sql);
    }
}
