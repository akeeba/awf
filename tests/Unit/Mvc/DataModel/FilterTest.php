<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc\DataModel;

use Awf\Database\Driver\Mysqli as MysqliDriver;
use Awf\Database\Driver\Postgresql as PostgresqlDriver;
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
    // AbstractFilter::search — operator allow-list (SQL injection hardening)
    // =========================================================================

    public static function allowedOperatorProvider(): array
    {
        // NOTE: '!=' is intentionally excluded here. Any operator with a leading '!'
        // is stripped to a "NOT " prefix *before* normalisation (pre-existing
        // behaviour, preserved by this fix) — see
        // testSearchNegationPrefixStillWorksAfterNormalisation() for that case.
        return [
            '='        => ['='],
            '<>'       => ['<>'],
            '>'        => ['>'],
            '>='       => ['>='],
            '<'        => ['<'],
            '<='       => ['<='],
            'LIKE'     => ['LIKE'],
            'NOT LIKE' => ['NOT LIKE'],
        ];
    }

    #[DataProvider('allowedOperatorProvider')]
    public function testSearchAllowedOperatorSurvivesVerbatim(string $operator): void
    {
        $filter = new TextFilter($this->db, $this->field('description', 'varchar'));
        $sql    = $filter->search('x', $operator);

        // LIKE / NOT LIKE append the SQLite-flavoured ESCAPE clause (a single backslash,
        // since SQLite does not double backslashes inside string literals — see
        // Awf\Database\Driver\Sqlite::getLikeEscapeSql()).
        $escapeSuffix = ($operator === 'LIKE' || $operator === 'NOT LIKE') ? " ESCAPE '\\'" : '';

        self::assertSame("(`description` " . $operator . " 'x'" . $escapeSuffix . ")", $sql);
    }

    public static function hostileOperatorProvider(): array
    {
        return [
            'sleep injection'        => ["= 'x') OR (SELECT 1 FROM (SELECT SLEEP(5))a) -- "],
            'union injection'        => ["\") UNION SELECT password FROM ak_users -- "],
            'or 1'                   => ['= 1 OR 1'],
            'empty string'           => [''],
            'null'                   => [null],
            'array'                  => [['=', 'OR 1=1']],
        ];
    }

    #[DataProvider('hostileOperatorProvider')]
    public function testSearchHostileOperatorCollapsesToEquals(mixed $operator): void
    {
        $filter = new TextFilter($this->db, $this->field('description', 'varchar'));
        $sql    = $filter->search('x', $operator);

        self::assertSame("(`description` = 'x')", $sql);
    }

    public function testSearchNegationPrefixStillWorksAfterNormalisation(): void
    {
        // A leading '!' is stripped to a "NOT " prefix, then the remainder ('=') is
        // normalised. This is the pre-existing behaviour of '!='; it must survive
        // the operator allow-list unchanged.
        $filter = new NumberFilter($this->db, $this->field('n', 'int'));
        $sql    = $filter->search(5, '!=');

        self::assertSame("NOT (`n` = '5')", $sql);
    }

    public function testSearchLowercaseOperatorIsUppercased(): void
    {
        $filter = new TextFilter($this->db, $this->field('description', 'varchar'));
        $sql    = $filter->search('x', 'like');

        self::assertSame("(`description` LIKE 'x' ESCAPE '\\')", $sql);
    }

    public static function getSearchMethodsProvider(): array
    {
        return [
            'Text'    => [TextFilter::class, 'title', 'varchar'],
            'Number'  => [NumberFilter::class, 'n', 'int'],
            'Date'    => [DateFilter::class, 'created_at', 'datetime'],
            'Boolean' => [BooleanFilter::class, 'enabled', 'tinyint'],
        ];
    }

    #[DataProvider('getSearchMethodsProvider')]
    public function testGetSearchMethodsReturnsExactExplicitList(string $filterClass, string $name, string $type): void
    {
        $filter = new $filterClass($this->db, $this->field($name, $type));

        self::assertSame(
            ['exact', 'partial', 'between', 'outside', 'interval', 'search'],
            $filter->getSearchMethods()
        );
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

    // =========================================================================
    // Number filter — range()/modulo() cast+sanitise (SQL injection hardening)
    //
    // range()/modulo() are not reachable from request state (getSearchMethods()
    // does not list them — see AbstractFilter), but they remain a live hazard for
    // any direct $model->where('field', 'range'/'modulo', ...) call.
    // =========================================================================

    public static function hostileNumberBoundProvider(): array
    {
        return [
            'or injection'      => ['1 OR 1=1'],
            'stacked query'     => ['1); DROP TABLE x; --'],
        ];
    }

    #[DataProvider('hostileNumberBoundProvider')]
    public function testNumberRangeHostileFromBoundIsNeutralised(string $hostile): void
    {
        $filter = new NumberFilter($this->db, $this->field('price', 'int'));
        $sql    = $filter->range($hostile, 100, true);

        // (float) cast takes only the leading numeric portion; the hostile suffix never
        // reaches the query. Bounds are not quoted here — same as the pre-existing
        // between()/outside()/interval() output — because sanitiseValue() guarantees a
        // float-safe numeric string.
        self::assertSame('((`price` >= 1) AND (`price` <= 100))', $sql);
    }

    #[DataProvider('hostileNumberBoundProvider')]
    public function testNumberRangeHostileToBoundIsNeutralised(string $hostile): void
    {
        $filter = new NumberFilter($this->db, $this->field('price', 'int'));
        $sql    = $filter->range(5, $hostile, true);

        self::assertSame('((`price` >= 5) AND (`price` <= 1))', $sql);
    }

    /**
     * Pins the truthiness semantics of range(): a bound of int 0 is falsy and is
     * therefore NOT emitted, even though it is not "empty" for isEmpty() purposes
     * (isEmpty() only treats string "0" as non-empty; int 0 is empty via empty()).
     * This test exists to make that decision visible per the plan's requirement,
     * and to catch any future change that casts-then-tests truthiness (which would
     * change nothing here since isEmpty(0) is already true — see the next test for
     * the case that actually distinguishes cast-before vs cast-after truthiness).
     */
    public function testNumberRangeZeroBoundIsEmptyAndProducesNoClause(): void
    {
        $filter = new NumberFilter($this->db, $this->field('price', 'int'));

        self::assertSame('', $filter->range(0, 0, true));
    }

    /**
     * A string "0.0" bound is not "empty" for isEmpty() (only the literal string "0" is
     * special-cased), so range() proceeds — but the *truthiness* test in range() must
     * still be evaluated on the original value, not a float-cast copy, or a bound this
     * test doesn't cover could silently vanish. This pins that only the untouched
     * original value's truthiness decides whether a bound is emitted.
     */
    public function testNumberRangeStringZeroPointZeroBoundTruthiness(): void
    {
        $filter = new NumberFilter($this->db, $this->field('price', 'int'));
        // "0.0" is truthy as a PHP string (non-empty, non-"0"), so the bound IS emitted,
        // even though it sanitises down to the numeric string "0".
        $sql = $filter->range('0.0', null, true);

        self::assertSame('((`price` >= 0))', $sql);
    }

    #[DataProvider('hostileNumberBoundProvider')]
    public function testNumberModuloHostileValueIsNeutralised(string $hostile): void
    {
        $filter = new NumberFilter($this->db, $this->field('n', 'int'));
        $sql    = $filter->modulo($hostile, 7, true);

        self::assertSame('(`n` >= 1 AND (`n` - 1) % 7 = 0)', $sql);
    }

    #[DataProvider('hostileNumberBoundProvider')]
    public function testNumberModuloHostileIntervalIsNeutralised(string $hostile): void
    {
        $filter = new NumberFilter($this->db, $this->field('n', 'int'));
        $sql    = $filter->modulo(3, $hostile, true);

        self::assertSame('(`n` >= 3 AND (`n` - 3) % 1 = 0)', $sql);
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

    // =========================================================================
    // Driver::getLikeEscapeSql() — LIKE ESCAPE clause per driver (SQL injection hardening)
    //
    // The string-literal syntax for a backslash differs per driver: MySQL requires it
    // doubled (a lone backslash is itself an escape character inside a MySQL string
    // literal), SQLite and PostgreSQL do not. Instantiating a live connection isn't
    // needed for this — getLikeEscapeSql() reads no connection state — so these tests
    // use ReflectionClass::newInstanceWithoutConstructor() to call it on an
    // unconnected driver instance, plus a reflection check of the declaring class to
    // pin which classes inherit the Driver default vs. override it.
    // =========================================================================

    public function testGetLikeEscapeSqlDriverDefaultIsDoubledBackslash(): void
    {
        $driver = (new \ReflectionClass(MysqliDriver::class))->newInstanceWithoutConstructor();

        // MySQL: the escape backslash must be doubled inside the string literal.
        self::assertSame(" ESCAPE '\\\\'", $driver->getLikeEscapeSql());
    }

    public function testGetLikeEscapeSqlSqliteOverrideIsSingleBackslash(): void
    {
        $driver = (new \ReflectionClass(SqliteDriver::class))->newInstanceWithoutConstructor();

        // SQLite does not process backslash escapes in string literals.
        self::assertSame(" ESCAPE '\\'", $driver->getLikeEscapeSql());
    }

    public function testGetLikeEscapeSqlPostgresqlOverrideIsSingleBackslash(): void
    {
        $driver = (new \ReflectionClass(PostgresqlDriver::class))->newInstanceWithoutConstructor();

        // standard_conforming_strings is on by default since PostgreSQL 9.1.
        self::assertSame(" ESCAPE '\\'", $driver->getLikeEscapeSql());
    }

    public function testGetLikeEscapeSqlIsNotOverriddenOnPdo(): void
    {
        // Pdomysql extends Pdo and Sqlite extends Pdo, and those two need *different*
        // clauses. Pdo itself must not declare getLikeEscapeSql() so that Pdomysql
        // inherits the MySQL-flavoured Driver default while Sqlite's own override wins
        // for the Sqlite branch.
        self::assertFalse(
            (new \ReflectionClass(\Awf\Database\Driver\Pdo::class))->hasMethod('getLikeEscapeSql')
            && (new \ReflectionMethod(\Awf\Database\Driver\Pdo::class, 'getLikeEscapeSql'))->getDeclaringClass()->getName() === \Awf\Database\Driver\Pdo::class
        );
    }

    public function testGetLikeEscapeSqlPdomysqlInheritsDriverDefault(): void
    {
        $driver = (new \ReflectionClass(\Awf\Database\Driver\Pdomysql::class))->newInstanceWithoutConstructor();

        self::assertSame(" ESCAPE '\\\\'", $driver->getLikeEscapeSql());
        self::assertSame(
            \Awf\Database\Driver::class,
            (new \ReflectionMethod($driver, 'getLikeEscapeSql'))->getDeclaringClass()->getName()
        );
    }

    // =========================================================================
    // Text filter — LIKE wildcard escaping (SQL injection hardening)
    //
    // Awf\Database\Driver\Sqlite::escape() now honours $extra (Gap A1 — it used to be a
    // documented no-op: "Unused optional parameter to provide extra escaping"), so it
    // could be used here too. These tests still use a small local double that reproduces
    // the MySQL-flavoured driver contract (doubled-backslash ESCAPE clause), specifically
    // to pin the MySQL-style byte-for-byte output (distinct from SQLite's single-backslash
    // form, covered separately via the real SqliteDriver — see the end-to-end SQLite tests
    // in BehaviourFiltersTest and the getLikeEscapeSql() tests above):
    // escape($text, $extra) applies quote/backslash escaping always, and %/_ escaping only
    // when $extra is true; quote($text, $escape) re-escapes only when $escape is true.
    // =========================================================================

    /**
     * A minimal DB double reproducing the escape()/quote() contract of the real
     * Mysqli/Pdomysql drivers (unlike the SqliteDriver used elsewhere in this file, whose
     * escape() ignores the $extra flag entirely).
     */
    private function mysqlStyleDb(): object
    {
        return new class {
            public function escape($text, $extra = false): string
            {
                $text = addslashes((string) $text);

                return $extra ? addcslashes($text, '%_') : $text;
            }

            public function quote($text, $escape = true): string
            {
                return "'" . ($escape ? $this->escape($text) : $text) . "'";
            }

            public function qn($name): string
            {
                return '`' . $name . '`';
            }

            public function getLikeEscapeSql(): string
            {
                // Mirrors Awf\Database\Driver::getLikeEscapeSql()'s default (MySQL-style: the
                // escape backslash is doubled because a lone backslash is itself an escape
                // character inside a MySQL string literal).
                return " ESCAPE '\\\\'";
            }
        };
    }

    public function testTextPartialEscapesPercentAndUnderscoreWildcards(): void
    {
        $filter = new TextFilter($this->mysqlStyleDb(), $this->field('password', 'varchar'));
        $sql    = $filter->partial('50%_foo');

        self::assertSame("(`password` LIKE '%50\\%\\_foo%' ESCAPE '\\\\')", $sql);
    }

    /**
     * Reproduces the confirmed PoC payload for case E: without escaping, a bcrypt hash
     * fragment ending in '%' would widen the match into a row-presence oracle.
     */
    public function testTextPartialEscapesTrailingPercentInBcryptLikePayload(): void
    {
        $filter = new TextFilter($this->mysqlStyleDb(), $this->field('password', 'varchar'));
        $sql    = $filter->partial('$2y$10$a%');

        self::assertSame("(`password` LIKE '%\$2y\$10\$a\\%%' ESCAPE '\\\\')", $sql);
        self::assertStringNotContainsString("a%%", $sql);
    }

    public function testTextExactEscapesPercentAndUnderscoreWildcards(): void
    {
        $filter = new TextFilter($this->mysqlStyleDb(), $this->field('title', 'varchar'));
        $sql    = $filter->exact('50%_foo');

        self::assertSame("(`title` LIKE '50\\%\\_foo' ESCAPE '\\\\')", $sql);
    }

    /**
     * A value containing a single quote and a backslash must be escaped exactly once.
     * This is the assertion that catches a wrong quote() second argument: if quote()
     * were called with its default $escape=true, the already-escaped backslash/quote
     * from escape() would be escaped a second time here.
     */
    public function testTextPartialQuoteAndBackslashAreNotDoubleEscaped(): void
    {
        $filter = new TextFilter($this->mysqlStyleDb(), $this->field('title', 'varchar'));
        $sql    = $filter->partial("O'Brien\\test");

        self::assertSame("(`title` LIKE '%O\\'Brien\\\\test%' ESCAPE '\\\\')", $sql);
    }

    public function testTextExactQuoteAndBackslashAreNotDoubleEscaped(): void
    {
        $filter = new TextFilter($this->mysqlStyleDb(), $this->field('title', 'varchar'));
        $sql    = $filter->exact("O'Brien\\test");

        self::assertSame("(`title` LIKE 'O\\'Brien\\\\test' ESCAPE '\\\\')", $sql);
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
    // AbstractFilter::search — LIKE wildcard escaping (SQL injection hardening)
    //
    // search() is a third LIKE emitter besides Text::partial()/exact(): the operator
    // allow-list in AbstractFilter deliberately permits 'LIKE'/'NOT LIKE', so a caller
    // reaching search() with one of those operators (e.g. via a filter descriptor of
    // ['method' => 'search', 'operator' => 'LIKE', ...]) can turn a value containing '%'
    // or '_' into a pattern probe unless those wildcards are escaped. As with the Text
    // filter tests above, the in-memory SqliteDriver's escape() ignores $extra, so these
    // use the mysqlStyleDb() double defined earlier in this file.
    // =========================================================================

    public function testSearchWithLikeOperatorEscapesWildcard(): void
    {
        $filter = new TextFilter($this->mysqlStyleDb(), $this->field('password', 'varchar'));
        $sql    = $filter->search('$2y$10$a%', 'LIKE');

        self::assertSame("(`password` LIKE '\$2y\$10\$a\\%' ESCAPE '\\\\')", $sql);
        self::assertStringNotContainsString("a%'", $sql);
    }

    public function testSearchWithNotLikeOperatorEscapesWildcard(): void
    {
        $filter = new TextFilter($this->mysqlStyleDb(), $this->field('password', 'varchar'));
        $sql    = $filter->search('$2y$10$a%', 'NOT LIKE');

        self::assertSame("(`password` NOT LIKE '\$2y\$10\$a\\%' ESCAPE '\\\\')", $sql);
        self::assertStringNotContainsString("a%'", $sql);
    }

    /**
     * A value containing a single quote and a backslash must be escaped exactly once.
     * This is the assertion that catches a wrong quote() second argument: if quote()
     * were called with its default $escape=true, the already-escaped backslash/quote
     * from escape() would be escaped a second time here.
     */
    public function testSearchWithLikeOperatorDoesNotDoubleEscapeQuoteAndBackslash(): void
    {
        $filter = new TextFilter($this->mysqlStyleDb(), $this->field('title', 'varchar'));
        $sql    = $filter->search("O'Brien\\test", 'LIKE');

        self::assertSame("(`title` LIKE 'O\\'Brien\\\\test' ESCAPE '\\\\')", $sql);
    }

    /**
     * Non-pattern operators must be unaffected by the wildcard escaping: '%' in an
     * equality-style comparison is a literal character, not a LIKE wildcard, and must
     * survive verbatim. A regression here would silently corrupt every equality filter
     * on a value containing a percent sign.
     */
    public function testSearchWithEqualsOperatorLeavesPercentUnescaped(): void
    {
        $filter = new TextFilter($this->mysqlStyleDb(), $this->field('discount', 'varchar'));
        $sql    = $filter->search('50%', '=');

        self::assertSame("(`discount` = '50%')", $sql);
    }

    public function testSearchWithNegatedLikeOperatorEscapesWildcard(): void
    {
        $filter = new TextFilter($this->mysqlStyleDb(), $this->field('password', 'varchar'));
        $sql    = $filter->search('$2y$10$a%', '!LIKE');

        self::assertSame("NOT (`password` LIKE '\$2y\$10\$a\\%' ESCAPE '\\\\')", $sql);
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
    // Date filter — interval unit whitelist (SQL injection hardening)
    // =========================================================================

    public static function allowedIntervalUnitProvider(): array
    {
        return [
            'MICROSECOND' => ['MICROSECOND'],
            'SECOND'      => ['SECOND'],
            'MINUTE'      => ['MINUTE'],
            'HOUR'        => ['HOUR'],
            'DAY'         => ['DAY'],
            'WEEK'        => ['WEEK'],
            'MONTH'       => ['MONTH'],
            'QUARTER'     => ['QUARTER'],
            'YEAR'        => ['YEAR'],
        ];
    }

    #[DataProvider('allowedIntervalUnitProvider')]
    public function testDateIntervalAllowedUnitStringFormProducesExpectedSql(string $unit): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $sql    = $filter->interval('2024-06-01', '+1 ' . $unit, true);

        self::assertSame(
            "(`created_at` >= DATE_ADD('2024-06-01', INTERVAL 1 " . $unit . "))",
            $sql
        );
    }

    #[DataProvider('allowedIntervalUnitProvider')]
    public function testDateIntervalAllowedUnitArrayFormProducesExpectedSql(string $unit): void
    {
        $filter   = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $interval = ['sign' => '+', 'value' => 1, 'unit' => $unit];
        $sql      = $filter->interval('2024-06-01', $interval, true);

        self::assertSame(
            "(`created_at` >= DATE_ADD('2024-06-01', INTERVAL 1 " . $unit . "))",
            $sql
        );
    }

    public static function hostileIntervalUnitProvider(): array
    {
        return [
            'operator injection'  => ['MONTH))OR(1=1)--'],
            'stacked query'       => ['MONTH; DROP TABLE x'],
            'empty string'        => [''],
        ];
    }

    #[DataProvider('hostileIntervalUnitProvider')]
    public function testDateIntervalHostileUnitStringFormReturnsEmpty(string $unit): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $sql    = $filter->interval('2024-06-01', '+1 ' . $unit, true);

        self::assertSame('', $sql);
    }

    #[DataProvider('hostileIntervalUnitProvider')]
    public function testDateIntervalHostileUnitArrayFormReturnsEmpty(string $unit): void
    {
        $filter   = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $interval = ['sign' => '+', 'value' => 1, 'unit' => $unit];
        $sql      = $filter->interval('2024-06-01', $interval, true);

        self::assertSame('', $sql);
    }

    public function testDateIntervalLowercaseValidUnitIsAcceptedAndNormalised(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $sql    = $filter->interval('2024-06-01', '+1 month', true);

        self::assertSame(
            "(`created_at` >= DATE_ADD('2024-06-01', INTERVAL 1 MONTH))",
            $sql
        );
    }

    public function testDateIntervalNonNumericValueIsCastToIntegerStringForm(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        // "+1 OR 1=1 MONTH" — the numeric-ish leading token is what gets cast; the interval
        // string only has two whitespace-delimited tokens by construction of getInterval(),
        // so we exercise the cast via a value that isn't purely numeric.
        $sql = $filter->interval('2024-06-01', '+abc MONTH', true);

        // (int) "abc" === 0
        self::assertSame(
            "(`created_at` >= DATE_ADD('2024-06-01', INTERVAL 0 MONTH))",
            $sql
        );
    }

    public function testDateIntervalNonNumericValueIsCastToIntegerArrayForm(): void
    {
        $filter   = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $interval = ['sign' => '+', 'value' => '1 OR 1=1', 'unit' => 'MONTH'];
        $sql      = $filter->interval('2024-06-01', $interval, true);

        // (int) "1 OR 1=1" === 1 — the hostile suffix is dropped, not injected.
        self::assertSame(
            "(`created_at` >= DATE_ADD('2024-06-01', INTERVAL 1 MONTH))",
            $sql
        );
        self::assertStringNotContainsString('OR 1=1', $sql);
    }

    public static function nonMinusSignProvider(): array
    {
        return [
            'plus'        => ['+'],
            'empty'       => [''],
            'garbage'     => ['x'],
            'plus word'   => ['plus'],
        ];
    }

    #[DataProvider('nonMinusSignProvider')]
    public function testDateIntervalNonMinusSignProducesDateAdd(string $sign): void
    {
        $filter   = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $interval = ['sign' => $sign, 'value' => 1, 'unit' => 'MONTH'];
        $sql      = $filter->interval('2024-06-01', $interval, true);

        self::assertStringContainsString('DATE_ADD', $sql);
        self::assertStringNotContainsString('DATE_SUB', $sql);
    }

    public function testDateIntervalMinusSignProducesDateSub(): void
    {
        $filter   = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $interval = ['sign' => '-', 'value' => 1, 'unit' => 'MONTH'];
        $sql      = $filter->interval('2024-06-01', $interval, true);

        self::assertStringContainsString('DATE_SUB', $sql);
    }

    // =========================================================================
    // Date filter — interval() anchored to the given value (Gap B: previously compared
    // the column to a function of itself, discarding $value entirely)
    // =========================================================================

    /**
     * Pins the full anchored SQL for a '-' sign: DATE_SUB(<quoted anchor>, INTERVAL ...).
     */
    public function testDateIntervalMinusSignProducesAnchoredDateSub(): void
    {
        $filter   = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $interval = ['sign' => '-', 'value' => 2, 'unit' => 'MONTH'];
        $sql      = $filter->interval('2026-01-01', $interval, true);

        self::assertSame(
            "(`created_at` >= DATE_SUB('2026-01-01', INTERVAL 2 MONTH))",
            $sql
        );
    }

    /**
     * $include = false must produce a strict '>' comparison, not '>='; the anchor value
     * must still appear inside the DATE_ADD/DATE_SUB call, not the column self-reference.
     */
    public function testDateIntervalIncludeFalseProducesStrictGreaterThan(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $sql    = $filter->interval('2024-06-01', '+1 MONTH', false);

        self::assertSame(
            "(`created_at` > DATE_ADD('2024-06-01', INTERVAL 1 MONTH))",
            $sql
        );
    }

    /**
     * String-form interval, anchored SQL sanity check (representative subset: a single
     * unit exercised end to end with a non-default anchor date).
     */
    public function testDateIntervalStringFormProducesAnchoredSql(): void
    {
        $filter = new DateFilter($this->db, $this->field('created_at', 'datetime'));
        $sql    = $filter->interval('2026-03-15', '+1 WEEK', true);

        self::assertSame(
            "(`created_at` >= DATE_ADD('2026-03-15', INTERVAL 1 WEEK))",
            $sql
        );
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
