<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Database\Query;

use Awf\Database\Driver\None as NoneDriver;
use Awf\Database\Query\Mysqli;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Database\Query\Mysqli — driver-specific behaviour.
 *
 * Covers:
 *  - processLimit()  – LIMIT/OFFSET rendering (MySQL syntax: LIMIT offset, limit)
 *  - setLimit()      – stores values as integers and chains
 *  - concatenate()   – CONCAT() without separator, CONCAT_WS() with separator
 *  - __toString()    – LIMIT clause is appended via processLimit when type is set
 *  - quote()         – throws without a real driver (inherited behaviour)
 *  - quoteName()     – throws without a real driver (inherited behaviour)
 */
class MysqliQueryTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeQuery(): Mysqli
    {
        return new Mysqli(null);
    }

    /**
     * Return a Mysqli query object backed by a minimal concrete driver that
     * provides quote() and quoteName() so the separator-based concatenate()
     * variant can be exercised.
     */
    private function makeQueryWithDriver(): Mysqli
    {
        $driver = new class extends NoneDriver {
            public function __construct()
            {
                // Bypass the parent constructor (which requires $options).
                $this->nameQuote   = '`';
                $this->nullDate    = '0000-00-00 00:00:00';
                $this->tablePrefix = '';
            }

            public function escape($text, $extra = false): string
            {
                return str_replace("'", "''", (string) $text);
            }

            public function quote($text, $escape = true): string
            {
                if ($escape) {
                    $text = $this->escape((string) $text);
                }

                return "'" . $text . "'";
            }

            public function quoteName($name, $as = null): string
            {
                return '`' . $name . '`';
            }

            // Satisfy remaining abstract stubs from NoneDriver.
            public function connect(): bool { return true; }
            public function disconnect(): void {}
            public function getVersion(): string { return '0'; }
            public function isConnected(): bool { return false; }
        };

        return new Mysqli($driver);
    }

    // =========================================================================
    // processLimit
    // =========================================================================

    public static function processLimitProvider(): array
    {
        return [
            'no limit no offset'        => ['SELECT 1', 0,  0,  'SELECT 1'],
            'limit only'                => ['SELECT 1', 10, 0,  'SELECT 1 LIMIT 0, 10'],
            'offset only'               => ['SELECT 1', 0,  5,  'SELECT 1 LIMIT 5, 0'],
            'limit and offset'          => ['SELECT 1', 20, 10, 'SELECT 1 LIMIT 10, 20'],
            'limit 1 offset 0'          => ['SELECT 1', 1,  0,  'SELECT 1 LIMIT 0, 1'],
            'large values'              => ['SELECT *', 1000, 500, 'SELECT * LIMIT 500, 1000'],
            'empty base query with limit' => ['', 5, 2, ' LIMIT 2, 5'],
        ];
    }

    #[DataProvider('processLimitProvider')]
    public function testProcessLimit(string $base, int $limit, int $offset, string $expected): void
    {
        $q      = $this->makeQuery();
        $result = $q->processLimit($base, $limit, $offset);

        self::assertSame($expected, $result);
    }

    public function testProcessLimitDefaultOffsetIsZero(): void
    {
        $q      = $this->makeQuery();
        // The $offset parameter defaults to 0; only $limit > 0 triggers the clause.
        $result = $q->processLimit('SELECT 1', 5);

        self::assertSame('SELECT 1 LIMIT 0, 5', $result);
    }

    public function testProcessLimitAppendsToComplexQuery(): void
    {
        $q      = $this->makeQuery();
        $base   = 'SELECT * FROM foo WHERE id > 0 ORDER BY id';
        $result = $q->processLimit($base, 25, 50);

        self::assertSame('SELECT * FROM foo WHERE id > 0 ORDER BY id LIMIT 50, 25', $result);
    }

    public function testProcessLimitMysqlFormatIsOffsetCommaLimit(): void
    {
        // MySQL LIMIT syntax is: LIMIT <offset>, <limit> — not LIMIT <limit> OFFSET <offset>.
        $q      = $this->makeQuery();
        $result = $q->processLimit('Q', 10, 3);

        // The order must be: offset=3 first, limit=10 second.
        self::assertSame('Q LIMIT 3, 10', $result);
    }

    // =========================================================================
    // setLimit / integration with __toString
    // =========================================================================

    public function testSetLimitReturnsSelf(): void
    {
        $q = $this->makeQuery();

        self::assertSame($q, $q->setLimit(10, 0));
    }

    public function testSetLimitStoresValues(): void
    {
        $q = $this->makeQuery();
        $q->setLimit(20, 5);

        // Verify via processLimit round-trip that the stored values are correct.
        $result = $q->processLimit('Q', 20, 5);
        self::assertSame('Q LIMIT 5, 20', $result);
    }

    public function testSetLimitCastsToInt(): void
    {
        $q = $this->makeQuery();
        // setLimit() casts to int; we verify the cast does not corrupt values.
        $q->setLimit(10, 3);

        self::assertSame('Q LIMIT 3, 10', $q->processLimit('Q', 10, 3));
    }

    public function testSetLimitZeroZeroProducesNoLimitClause(): void
    {
        $q = $this->makeQuery();
        $q->select('*')->from('foo')->setLimit(0, 0);

        $sql = (string) $q;

        self::assertStringNotContainsString('LIMIT', $sql);
    }

    /**
     * When setLimit() is called alongside a SELECT query, __toString() must
     * delegate to processLimit and append the MySQL LIMIT clause.
     */
    public function testSetLimitIntegratesWithToString(): void
    {
        $q = $this->makeQuery();
        $q->select('*')->from('foo')->setLimit(15, 30);

        $sql = (string) $q;

        // MySQL syntax: LIMIT <offset>, <limit>
        self::assertStringContainsString('LIMIT 30, 15', $sql);
    }

    public function testSetLimitOffsetOnlyAppendsClause(): void
    {
        $q = $this->makeQuery();
        $q->select('*')->from('foo')->setLimit(0, 5);

        $sql = (string) $q;

        self::assertStringContainsString('LIMIT 5, 0', $sql);
    }

    public function testSetLimitLimitOnlyAppendsClause(): void
    {
        $q = $this->makeQuery();
        $q->select('*')->from('foo')->setLimit(10, 0);

        $sql = (string) $q;

        self::assertStringContainsString('LIMIT 0, 10', $sql);
    }

    // =========================================================================
    // concatenate — without separator: CONCAT(a,b,...)
    // =========================================================================

    public function testConcatenateWithoutSeparatorMultipleValues(): void
    {
        $q      = $this->makeQuery();
        $result = $q->concatenate(['a', 'b', 'c']);

        self::assertSame('CONCAT(a,b,c)', $result);
    }

    public function testConcatenateWithoutSeparatorSingleValue(): void
    {
        $q      = $this->makeQuery();
        $result = $q->concatenate(['only']);

        self::assertSame('CONCAT(only)', $result);
    }

    public function testConcatenateWithoutSeparatorEmptyArray(): void
    {
        $q      = $this->makeQuery();
        $result = $q->concatenate([]);

        self::assertSame('CONCAT()', $result);
    }

    public function testConcatenateNullSeparatorBehavesLikeNoSeparator(): void
    {
        $q      = $this->makeQuery();
        // Passing null should follow the "no separator" branch (CONCAT).
        $result = $q->concatenate(['x', 'y'], null);

        self::assertSame('CONCAT(x,y)', $result);
    }

    public function testConcatenateWithoutSeparatorDoesNotRequireDriver(): void
    {
        // CONCAT() path must work without any database driver.
        $q = $this->makeQuery();

        $result = $q->concatenate(['col1', 'col2']);

        self::assertSame('CONCAT(col1,col2)', $result);
    }

    // =========================================================================
    // concatenate — with separator: CONCAT_WS(sep, a, b, ...)
    // =========================================================================

    public function testConcatenateWithSeparatorRequiresDriver(): void
    {
        // quote() is called internally for the separator; this throws without a driver.
        $q = $this->makeQuery();

        $this->expectException(\RuntimeException::class);

        $q->concatenate(['a', 'b'], ', ');
    }

    public function testConcatenateWithSeparatorUsesConcat_WS(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->concatenate(['a', 'b', 'c'], '-');

        // CONCAT_WS('<sep>', val1, val2, ...)
        self::assertSame("CONCAT_WS('-', a, b, c)", $result);
    }

    public function testConcatenateWithSeparatorSingleValue(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->concatenate(['col'], ',');

        self::assertSame("CONCAT_WS(',', col)", $result);
    }

    public function testConcatenateWithSeparatorEmptyArray(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->concatenate([], '-');

        self::assertSame("CONCAT_WS('-')", $result);
    }

    public function testConcatenateWithSeparatorQuotesSeparator(): void
    {
        // The separator string is passed through $this->quote(); verify it ends up quoted.
        $q      = $this->makeQueryWithDriver();
        $result = $q->concatenate(['f1', 'f2'], ' ');

        // The separator ' ' (space) must be single-quoted.
        self::assertStringContainsString("' '", $result);
        self::assertStringStartsWith('CONCAT_WS(', $result);
    }

    // =========================================================================
    // quote / quoteName without driver (inherited error conditions)
    // =========================================================================

    public function testQuoteThrowsWithoutDriver(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->makeQuery()->quote('value');
    }

    public function testQuoteNameThrowsWithoutDriver(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->makeQuery()->quoteName('column');
    }

    // =========================================================================
    // Name quoting (backtick) via quoteName with driver
    // =========================================================================

    public function testQuoteNameWithDriverReturnsBacktickWrapped(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->quoteName('my_table');

        self::assertSame('`my_table`', $result);
    }

    public function testQuoteWithDriverReturnsSingleQuoted(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->quote('hello');

        self::assertSame("'hello'", $result);
    }

    public function testQuoteWithDriverEscapesSingleQuotes(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->quote("it's");

        self::assertSame("'it''s'", $result);
    }

    // =========================================================================
    // Implements QueryLimitable interface
    // =========================================================================

    public function testImplementsQueryLimitable(): void
    {
        $q = $this->makeQuery();

        self::assertInstanceOf(\Awf\Database\QueryLimitable::class, $q);
    }
}
