<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Database;

use Awf\Database\Driver\None as NoneDriver;
use Awf\Database\Query\Sqlite;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Database\Query — clauses, quoting & formatting.
 *
 * Methods under test:
 *   order(), group(), having(), union(), unionDistinct(),
 *   q()/quote(), qn()/quoteName(), e()/escape(),
 *   format(), currentTimestamp(), nullDate(), dateFormat(),
 *   year(), month(), day(), hour(), minute(), second(),
 *   charLength(), length(), concatenate(), castAsChar()
 */
class QueryClausesTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Collapse all whitespace sequences to a single space and trim. */
    private static function normalise(string $sql): string
    {
        return trim(preg_replace('/\s+/', ' ', $sql));
    }

    /** Build a query with NO driver attached (tests that throw without a driver). */
    private function makeQuery(): Sqlite
    {
        return new Sqlite(null);
    }

    /**
     * Build a concrete Driver subclass (Driver\None) whose nameQuote is set to
     * the backtick character (mimicking SQLite / MySQL conventions) and whose
     * escape() method doubles single-quotes, then return a Sqlite query bound
     * to that driver.
     */
    private function makeQueryWithDriver(): Sqlite
    {
        // Anonymous class extends the concrete NoneDriver (which already
        // implements all abstract methods) and customises the properties we
        // care about for quoting tests.
        $driver = new class extends NoneDriver {
            public function __construct()
            {
                // Bypass the parent constructor which would require $options.
                $this->nameQuote = '`';
                $this->nullDate  = '0000-00-00 00:00:00';
                $this->tablePrefix = 'test_';
            }

            public function escape($text, $extra = false): string
            {
                // Basic single-quote doubling — enough for quoting tests.
                return str_replace("'", "''", (string) $text);
            }
        };

        return new Sqlite($driver);
    }

    // =========================================================================
    // order()
    // =========================================================================

    public function testOrderSingleColumn(): void
    {
        $q   = $this->makeQuery()->select('*')->from('t')->order('id');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('ORDER BY', $sql);
        self::assertStringContainsString('id', $sql);
    }

    public function testOrderMultipleColumnsCumulate(): void
    {
        $q   = $this->makeQuery()->select('*')->from('t')
            ->order('id')
            ->order('name');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('id', $sql);
        self::assertStringContainsString('name', $sql);
    }

    public function testOrderWithArray(): void
    {
        $q   = $this->makeQuery()->select('*')->from('t')
            ->order(['id DESC', 'name ASC']);
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('id DESC', $sql);
        self::assertStringContainsString('name ASC', $sql);
    }

    public function testOrderAppearsAfterGroupAndHaving(): void
    {
        $q   = $this->makeQuery()
            ->select('*')
            ->from('t')
            ->group('category')
            ->having('COUNT(*) > 1')
            ->order('id');
        $sql = self::normalise((string) $q);

        $posGroup  = strpos($sql, 'GROUP BY');
        $posHaving = strpos($sql, 'HAVING');
        $posOrder  = strpos($sql, 'ORDER BY');

        self::assertNotFalse($posGroup);
        self::assertNotFalse($posHaving);
        self::assertNotFalse($posOrder);
        self::assertLessThan($posOrder, $posHaving);
        self::assertLessThan($posHaving, $posGroup);
    }

    public function testClearOrderRemovesOrderClause(): void
    {
        $q = $this->makeQuery()->select('*')->from('t')->order('id');
        $q->clear('order');
        $sql = self::normalise((string) $q);

        self::assertStringNotContainsString('ORDER BY', $sql);
    }

    // =========================================================================
    // group()
    // =========================================================================

    public function testGroupSingleColumn(): void
    {
        $q   = $this->makeQuery()->select('category, COUNT(*)')->from('t')->group('category');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('GROUP BY', $sql);
        self::assertStringContainsString('category', $sql);
    }

    public function testGroupWithMultipleColumnsCumulate(): void
    {
        $q   = $this->makeQuery()->select('*')->from('t')
            ->group('a')
            ->group('b');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('a', $sql);
        self::assertStringContainsString('b', $sql);
    }

    public function testGroupWithArray(): void
    {
        $q   = $this->makeQuery()->select('*')->from('t')
            ->group(['x', 'y', 'z']);
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('x', $sql);
        self::assertStringContainsString('y', $sql);
        self::assertStringContainsString('z', $sql);
    }

    public function testGroupAppearsAfterWhere(): void
    {
        $q   = $this->makeQuery()->select('*')->from('t')
            ->where('active = 1')
            ->group('category');
        $sql = self::normalise((string) $q);

        self::assertLessThan(strpos($sql, 'GROUP BY'), strpos($sql, 'WHERE'));
    }

    public function testClearGroupRemovesGroupClause(): void
    {
        $q = $this->makeQuery()->select('*')->from('t')->group('category');
        $q->clear('group');
        $sql = self::normalise((string) $q);

        self::assertStringNotContainsString('GROUP BY', $sql);
    }

    // =========================================================================
    // having()
    // =========================================================================

    public function testHavingSingleCondition(): void
    {
        $q   = $this->makeQuery()->select('*')->from('t')
            ->group('category')
            ->having('COUNT(*) > 5');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('HAVING', $sql);
        self::assertStringContainsString('COUNT(*) > 5', $sql);
    }

    public function testHavingDefaultGlueIsAnd(): void
    {
        $q   = $this->makeQuery()->select('*')->from('t')
            ->group('category')
            ->having('COUNT(*) > 5')
            ->having('SUM(value) < 100');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('AND', $sql);
    }

    public function testHavingWithOrGlue(): void
    {
        $q   = $this->makeQuery()->select('*')->from('t')
            ->group('category')
            ->having(['COUNT(*) > 5', 'SUM(x) > 1'], 'OR');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('OR', $sql);
    }

    public function testHavingAppearsAfterGroupBy(): void
    {
        $q   = $this->makeQuery()->select('*')->from('t')
            ->group('category')
            ->having('COUNT(*) > 1');
        $sql = self::normalise((string) $q);

        self::assertLessThan(strpos($sql, 'HAVING'), strpos($sql, 'GROUP BY'));
    }

    public function testClearHavingRemovesHavingClause(): void
    {
        $q = $this->makeQuery()->select('*')->from('t')
            ->group('category')
            ->having('COUNT(*) > 1');
        $q->clear('having');
        $sql = self::normalise((string) $q);

        self::assertStringNotContainsString('HAVING', $sql);
    }

    // =========================================================================
    // union() / unionDistinct()
    // =========================================================================

    public function testUnionProducesUnionKeyword(): void
    {
        $q = $this->makeQuery()
            ->select('id')->from('a')
            ->union('SELECT id FROM b');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('UNION', $sql);
        self::assertStringContainsString('SELECT id FROM b', $sql);
    }

    public function testUnionDistinctProducesUnionDistinct(): void
    {
        $q = $this->makeQuery()
            ->select('id')->from('a')
            ->unionDistinct('SELECT id FROM b');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('UNION DISTINCT', $sql);
    }

    public function testUnionClearsOrderByClause(): void
    {
        // Per MySQL spec, ORDER BY is not valid before UNION; the framework
        // clears it automatically.
        $q = $this->makeQuery()
            ->select('id')->from('a')
            ->order('id')
            ->union('SELECT id FROM b');
        $sql = self::normalise((string) $q);

        self::assertStringNotContainsString('ORDER BY', $sql);
    }

    public function testMultipleUnionsAllPresent(): void
    {
        $q = $this->makeQuery()
            ->select('id')->from('a')
            ->union('SELECT id FROM b')
            ->union('SELECT id FROM c');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('SELECT id FROM b', $sql);
        self::assertStringContainsString('SELECT id FROM c', $sql);
    }

    public function testClearUnionRemovesUnionClause(): void
    {
        $q = $this->makeQuery()
            ->select('id')->from('a')
            ->union('SELECT id FROM b');
        $q->clear('union');
        $sql = self::normalise((string) $q);

        self::assertStringNotContainsString('UNION', $sql);
    }

    // =========================================================================
    // quote() / q()  — requires driver
    // =========================================================================

    public function testQuoteWrapsStringInSingleQuotes(): void
    {
        $q = $this->makeQueryWithDriver();

        self::assertSame("'hello'", $q->quote('hello'));
    }

    public function testQuoteEscapesSingleQuoteByDefault(): void
    {
        $q = $this->makeQueryWithDriver();

        self::assertSame("'it''s'", $q->quote("it's"));
    }

    public function testQuoteWithEscapeFalseDoesNotEscape(): void
    {
        $q = $this->makeQueryWithDriver();

        self::assertSame("'it's'", $q->quote("it's", false));
    }

    public function testQuoteWithArray(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->quote(['foo', 'bar']);

        self::assertIsArray($result);
        self::assertSame("'foo'", $result[0]);
        self::assertSame("'bar'", $result[1]);
    }

    public function testQAliasForQuote(): void
    {
        $q = $this->makeQueryWithDriver();

        self::assertSame($q->quote('test'), $q->q('test'));
    }

    public function testQuoteThrowsWithoutDriver(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->makeQuery()->quote('value');
    }

    // =========================================================================
    // quoteName() / qn()  — requires driver
    // =========================================================================

    public function testQuoteNameWrapsInBackticks(): void
    {
        $q = $this->makeQueryWithDriver();

        self::assertSame('`column`', $q->quoteName('column'));
    }

    public function testQuoteNameWithDotNotation(): void
    {
        $q = $this->makeQueryWithDriver();

        self::assertSame('`table`.`column`', $q->quoteName('table.column'));
    }

    public function testQuoteNameWithAlias(): void
    {
        $q = $this->makeQueryWithDriver();

        self::assertSame('`column` AS `alias`', $q->quoteName('column', 'alias'));
    }

    public function testQuoteNameWithArray(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->quoteName(['foo', 'bar']);

        self::assertIsArray($result);
        self::assertSame('`foo`', $result[0]);
        self::assertSame('`bar`', $result[1]);
    }

    public function testQnAliasForQuoteName(): void
    {
        $q = $this->makeQueryWithDriver();

        self::assertSame($q->quoteName('col'), $q->qn('col'));
    }

    public function testQuoteNameThrowsWithoutDriver(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->makeQuery()->quoteName('col');
    }

    // =========================================================================
    // escape() / e()  — requires driver
    // =========================================================================

    public function testEscapeDelegate(): void
    {
        $q = $this->makeQueryWithDriver();

        // Our fake driver doubles single-quotes.
        self::assertSame("it''s", $q->escape("it's"));
    }

    public function testEscapeNoSpecialChars(): void
    {
        $q = $this->makeQueryWithDriver();

        self::assertSame('hello world', $q->escape('hello world'));
    }

    public function testEAliasForEscape(): void
    {
        $q = $this->makeQueryWithDriver();

        self::assertSame($q->escape("it's"), $q->e("it's"));
    }

    public function testEscapeThrowsWithoutDriver(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->makeQuery()->escape('text');
    }

    // =========================================================================
    // nullDate() / dateFormat()  — requires driver
    // =========================================================================

    public function testNullDateQuotedReturnsQuotedZeroDate(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->nullDate(true);

        // The result must be a SQL-quoted zero date string.
        self::assertStringContainsString('0000-00-00 00:00:00', $result);
        self::assertStringStartsWith("'", $result);
        self::assertStringEndsWith("'", $result);
    }

    public function testNullDateUnquotedReturnsBareZeroDate(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->nullDate(false);

        self::assertSame('0000-00-00 00:00:00', $result);
    }

    public function testNullDateThrowsWithoutDriver(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->makeQuery()->nullDate();
    }

    public function testDateFormatReturnsFormatString(): void
    {
        $q      = $this->makeQueryWithDriver();
        $format = $q->dateFormat();

        self::assertIsString($format);
        self::assertNotEmpty($format);
    }

    public function testDateFormatThrowsWithoutDriver(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->makeQuery()->dateFormat();
    }

    // =========================================================================
    // currentTimestamp()
    // =========================================================================

    public function testCurrentTimestampReturnsNonEmptyString(): void
    {
        $result = $this->makeQuery()->currentTimestamp();

        self::assertIsString($result);
        self::assertNotEmpty($result);
    }

    // =========================================================================
    // Date extractors: year(), month(), day(), hour(), minute(), second()
    // =========================================================================

    public static function dateExtractorProvider(): array
    {
        return [
            'year'   => ['year',   'YEAR'],
            'month'  => ['month',  'MONTH'],
            'day'    => ['day',    'DAY'],
            'hour'   => ['hour',   'HOUR'],
            'minute' => ['minute', 'MINUTE'],
            'second' => ['second', 'SECOND'],
        ];
    }

    #[DataProvider('dateExtractorProvider')]
    public function testDateExtractorWrapsFieldInFunction(string $method, string $expected): void
    {
        $q      = $this->makeQuery();
        $result = $q->$method('`date_col`');

        self::assertStringContainsString($expected . '(', $result);
        self::assertStringContainsString('`date_col`', $result);
    }

    // =========================================================================
    // charLength() / length() / castAsChar() / concatenate()
    // =========================================================================

    public function testCharLengthProducesCorrectExpression(): void
    {
        $q      = $this->makeQuery();
        $result = $q->charLength('`name`');

        // SQLite subclass uses lowercase length()
        self::assertStringContainsString('`name`', $result);
        self::assertStringContainsString('(', $result);
    }

    public function testCharLengthWithOperatorAndCondition(): void
    {
        $q      = $this->makeQuery();
        $result = $q->charLength('`name`', '>', '5');

        self::assertStringContainsString('> 5', $result);
    }

    public function testLengthProducesLengthCall(): void
    {
        $q      = $this->makeQuery();
        $result = $q->length('`bio`');

        self::assertStringContainsString('LENGTH(', $result);
        self::assertStringContainsString('`bio`', $result);
    }

    public function testCastAsCharReturnsValueUnchanged(): void
    {
        // The base implementation is a pass-through.
        $q      = $this->makeQuery();
        $result = $q->castAsChar('`col`');

        self::assertSame('`col`', $result);
    }

    public function testConcatenateWithoutSeparator(): void
    {
        $q      = $this->makeQuery();
        $result = $q->concatenate(['a', 'b', 'c']);

        // Sqlite subclass uses the || operator.
        self::assertStringContainsString('||', $result);
    }

    public function testConcatenateWithSeparatorRequiresDriver(): void
    {
        // The separator is quoted, which needs a driver.
        $q = $this->makeQuery();

        $this->expectException(\RuntimeException::class);
        $q->concatenate(['a', 'b'], '-');
    }

    public function testConcatenateWithSeparatorWithDriver(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->concatenate(['a', 'b'], '-');

        self::assertStringContainsString('||', $result);
        self::assertStringContainsString("'-'", $result);
    }

    // =========================================================================
    // format()
    // =========================================================================

    public function testFormatPercentLiteralEscapes(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('100%%');

        self::assertSame('100%', $result);
    }

    public function testFormatTypeA(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%a', '42');

        self::assertSame('42', (string) $result);
    }

    public function testFormatTypeN(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%n', 'column');

        self::assertSame('`column`', $result);
    }

    public function testFormatTypeQ(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%q', 'value');

        self::assertSame("'value'", $result);
    }

    public function testFormatTypeQUppercaseNoEscape(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%Q', "it's");

        // %Q passes false to quote() — no escaping
        self::assertSame("'it's'", $result);
    }

    public function testFormatTypeR(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%r', 'raw_value');

        self::assertSame('raw_value', $result);
    }

    public function testFormatTypeE(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%e', "it's");

        // %e calls escape() — doubles the single quote.
        self::assertSame("it''s", $result);
    }

    public function testFormatTypeTInvariant(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%t');

        self::assertSame($q->currentTimestamp(), $result);
    }

    public function testFormatTypeZInvariant(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%z');

        self::assertSame($q->nullDate(false), $result);
    }

    public function testFormatTypeZUppercaseInvariant(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%Z');

        self::assertSame($q->nullDate(true), $result);
    }

    public function testFormatDateExtractorY(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%y', '2024-01-15');

        // %y → year(quote(value))
        self::assertStringContainsString('YEAR(', $result);
        self::assertStringContainsString("'2024-01-15'", $result);
    }

    public function testFormatDateExtractorYUppercase(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%Y', 'date_col');

        // %Y → year(quoteName(value))
        self::assertStringContainsString('YEAR(', $result);
        self::assertStringContainsString('`date_col`', $result);
    }

    public function testFormatDateExtractorM(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%m', '2024-01-15');

        self::assertStringContainsString('MONTH(', $result);
    }

    public function testFormatDateExtractorD(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%d', '2024-01-15');

        self::assertStringContainsString('DAY(', $result);
    }

    public function testFormatDateExtractorH(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%h', '12:00:00');

        self::assertStringContainsString('HOUR(', $result);
    }

    public function testFormatDateExtractorI(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%i', '12:30:00');

        self::assertStringContainsString('MINUTE(', $result);
    }

    public function testFormatDateExtractorS(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%s', '12:30:45');

        self::assertStringContainsString('SECOND(', $result);
    }

    public function testFormatPositionalArguments(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('SELECT %1$n FROM %2$n', 'col', 'tbl');

        self::assertSame('SELECT `col` FROM `tbl`', $result);
    }

    public function testFormatMixedTypesInOneString(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%n = %q', 'status', 'active');

        self::assertSame('`status` = \'active\'', $result);
    }

    public function testFormatMissingArgumentProducesQuotedEmptyString(): void
    {
        // When the argument index does not exist the framework substitutes '' as
        // the replacement value; for %n this then passes '' to quoteName(), which
        // wraps it in backticks → '``'.
        $q      = $this->makeQueryWithDriver();
        $result = $q->format('%n');

        // The result must be the quoted form of an empty identifier.
        self::assertSame('``', $result);
    }
}
