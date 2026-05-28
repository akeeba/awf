<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Database\Query;

use Awf\Database\Driver\None as NoneDriver;
use Awf\Database\Query\Pgsql;
use Awf\Database\Query\Postgresql;
use Awf\Database\QueryLimitable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Database\Query\Postgresql and Database\Query\Pgsql — driver-specific behaviour.
 *
 * Covers:
 *  - Both Postgresql and Pgsql classes (Pgsql extends Postgresql, same behaviour)
 *  - processLimit()   – PostgreSQL LIMIT/OFFSET rendering (separate clauses)
 *  - setLimit()       – stores values as integers and chains
 *  - limit() / offset() — QueryElement-based limit/offset on SELECT
 *  - returning()      – RETURNING clause on INSERT
 *  - concatenate()    – uses || operator (not CONCAT)
 *  - castAsChar()     – uses ::text cast
 *  - currentTimestamp() – returns NOW()
 *  - forUpdate() / forShare() / noWait() – locking clauses
 *  - clear()         – clears Postgres-specific fields
 *  - dateAdd()       – timestamp interval arithmetic
 *  - year/month/day/hour/minute/second extraction via EXTRACT
 *  - __toString()    – SELECT/UPDATE/INSERT serialisation
 *  - quoteName       – double-quote name quoting (PostgreSQL convention)
 *  - QueryLimitable interface compliance
 */
class PostgresqlQueryTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeQuery(): Postgresql
    {
        return new Postgresql(null);
    }

    private function makePgsqlQuery(): Pgsql
    {
        return new Pgsql(null);
    }

    /**
     * Return a Postgresql query object backed by a minimal concrete driver that
     * provides quote() / quoteName() so separator-based concatenate() can be tested.
     */
    private function makeQueryWithDriver(): Postgresql
    {
        $driver = new class extends NoneDriver {
            public function __construct()
            {
                // Bypass the parent constructor (which requires $options).
                $this->nameQuote   = '"';
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
                $quoted = '"' . $name . '"';

                if ($as !== null) {
                    $quoted .= ' AS "' . $as . '"';
                }

                return $quoted;
            }

            // Satisfy remaining abstract stubs from NoneDriver.
            public function connect(): bool { return true; }
            public function disconnect(): void {}
            public function getVersion(): string { return '0'; }
            public function isConnected(): bool { return false; }
        };

        return new Postgresql($driver);
    }

    /** Collapse all whitespace sequences to a single space and trim. */
    private static function normalise(string $sql): string
    {
        return trim(preg_replace('/\s+/', ' ', $sql));
    }

    // =========================================================================
    // QueryLimitable interface
    // =========================================================================

    public function testPostgresqlImplementsQueryLimitable(): void
    {
        self::assertInstanceOf(QueryLimitable::class, $this->makeQuery());
    }

    public function testPgsqlImplementsQueryLimitable(): void
    {
        self::assertInstanceOf(QueryLimitable::class, $this->makePgsqlQuery());
    }

    public function testPgsqlExtendsPostgresql(): void
    {
        self::assertInstanceOf(Postgresql::class, $this->makePgsqlQuery());
    }

    // =========================================================================
    // processLimit — PostgreSQL uses separate LIMIT and OFFSET clauses
    // =========================================================================

    public static function processLimitProvider(): array
    {
        return [
            'no limit no offset'    => ['SELECT 1', 0,  0,  'SELECT 1'],
            'limit only'            => ['SELECT 1', 10, 0,  'SELECT 1 LIMIT 10'],
            'offset only'           => ['SELECT 1', 0,  5,  'SELECT 1 OFFSET 5'],
            'limit and offset'      => ['SELECT 1', 20, 10, 'SELECT 1 LIMIT 20 OFFSET 10'],
            'limit 1 offset 0'      => ['SELECT 1', 1,  0,  'SELECT 1 LIMIT 1'],
            'large values'          => ['SELECT *', 1000, 500, 'SELECT * LIMIT 1000 OFFSET 500'],
            'zero limit with offset'=> ['SELECT *', 0, 25, 'SELECT * OFFSET 25'],
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
        // Default $offset = 0; only limit clause appears.
        $result = $q->processLimit('SELECT 1', 5);

        self::assertSame('SELECT 1 LIMIT 5', $result);
    }

    public function testProcessLimitAppendsToExistingQuery(): void
    {
        $q      = $this->makeQuery();
        $base   = 'SELECT * FROM foo WHERE id > 0 ORDER BY id';
        $result = $q->processLimit($base, 25, 50);

        self::assertSame('SELECT * FROM foo WHERE id > 0 ORDER BY id LIMIT 25 OFFSET 50', $result);
    }

    public function testProcessLimitUsesStandaloneLimitAndOffset(): void
    {
        // PostgreSQL syntax is LIMIT <n> OFFSET <m>, unlike MySQL LIMIT <m>, <n>.
        $q      = $this->makeQuery();
        $result = $q->processLimit('Q', 10, 3);

        // Must NOT produce MySQL-style "LIMIT 3, 10".
        self::assertStringNotContainsString('LIMIT 3, 10', $result);
        self::assertStringContainsString('LIMIT 10', $result);
        self::assertStringContainsString('OFFSET 3', $result);
    }

    // =========================================================================
    // processLimit — same behaviour on Pgsql subclass
    // =========================================================================

    public function testPgsqlProcessLimitMatchesPostgresql(): void
    {
        $pg   = $this->makeQuery();
        $pgsql = $this->makePgsqlQuery();

        self::assertSame(
            $pg->processLimit('SELECT 1', 10, 5),
            $pgsql->processLimit('SELECT 1', 10, 5)
        );
    }

    // =========================================================================
    // setLimit
    // =========================================================================

    public function testSetLimitReturnsSelf(): void
    {
        $q = $this->makeQuery();

        self::assertSame($q, $q->setLimit(10, 0));
    }

    public function testSetLimitStoresValuesAsIntegers(): void
    {
        $q = $this->makeQuery();
        $q->setLimit(10, 5);

        // Verify via processLimit round-trip.
        $result = $q->processLimit('Q', 10, 5);
        self::assertSame('Q LIMIT 10 OFFSET 5', $result);
    }

    public function testSetLimitZeroZeroProducesNoLimitClause(): void
    {
        $q = $this->makeQuery();
        $q->select('*')->from('foo')->setLimit(0, 0);

        $sql = (string) $q;

        self::assertStringNotContainsString('LIMIT', $sql);
        self::assertStringNotContainsString('OFFSET', $sql);
    }

    // =========================================================================
    // limit() / offset() methods — build QueryElement objects for SELECT
    // =========================================================================

    public function testLimitMethodReturnsSelf(): void
    {
        $q = $this->makeQuery();

        self::assertSame($q, $q->limit(10));
    }

    public function testOffsetMethodReturnsSelf(): void
    {
        $q = $this->makeQuery();

        self::assertSame($q, $q->offset(5));
    }

    public function testLimitAppearsInSelectToString(): void
    {
        $q   = $this->makeQuery();
        $sql = self::normalise((string) $q->select('*')->from('foo')->limit(10));

        self::assertStringContainsString('LIMIT 10', $sql);
    }

    public function testOffsetAppearsInSelectToString(): void
    {
        $q   = $this->makeQuery();
        $sql = self::normalise((string) $q->select('*')->from('foo')->offset(5));

        self::assertStringContainsString('OFFSET 5', $sql);
    }

    public function testLimitAndOffsetTogetherInSelectToString(): void
    {
        $q = $this->makeQuery();
        $q->select('*')->from('foo')->limit(20)->offset(10);
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('LIMIT 20', $sql);
        self::assertStringContainsString('OFFSET 10', $sql);
        // LIMIT must come before OFFSET.
        self::assertLessThan(strpos($sql, 'OFFSET'), strpos($sql, 'LIMIT'));
    }

    public function testLimitCalledTwiceIsIdempotent(): void
    {
        // The limit() method only creates the element if null; second call is ignored.
        $q = $this->makeQuery();
        $q->select('*')->from('foo')->limit(10)->limit(999);
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('LIMIT 10', $sql);
        self::assertStringNotContainsString('LIMIT 999', $sql);
    }

    // =========================================================================
    // returning() — RETURNING clause on INSERT
    // =========================================================================

    public function testReturningAppearsInInsertToString(): void
    {
        $q = $this->makeQuery();
        $q->insert('#__users')
          ->columns(['username', 'email'])
          ->values(["'alice'", "'alice@example.com'"])
          ->returning('id');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('RETURNING', $sql);
        self::assertStringContainsString('id', $sql);
    }

    public function testReturningNotPresentWithoutCall(): void
    {
        $q = $this->makeQuery();
        $q->insert('#__users')
          ->columns(['username'])
          ->values(["'alice'"]);
        $sql = self::normalise((string) $q);

        self::assertStringNotContainsString('RETURNING', $sql);
    }

    public function testReturningReturnsSelf(): void
    {
        $q = $this->makeQuery();

        self::assertSame($q, $q->returning('id'));
    }

    public function testReturningCalledTwiceIsIdempotent(): void
    {
        // returning() only sets the element when it's null; second call is ignored.
        $q = $this->makeQuery();
        $q->insert('#__users')
          ->columns(['username'])
          ->values(["'alice'"])
          ->returning('id')
          ->returning('uuid');
        $sql = self::normalise((string) $q);

        // 'id' is set first; 'uuid' must not appear.
        self::assertStringContainsString('id', $sql);
        self::assertStringNotContainsString('uuid', $sql);
    }

    // =========================================================================
    // concatenate() — uses || operator (PostgreSQL style)
    // =========================================================================

    public function testConcatenateWithoutSeparator(): void
    {
        $q      = $this->makeQuery();
        $result = $q->concatenate(['a', 'b', 'c']);

        self::assertSame('a || b || c', $result);
    }

    public function testConcatenateSingleValueWithoutSeparator(): void
    {
        $q      = $this->makeQuery();
        $result = $q->concatenate(['only']);

        self::assertSame('only', $result);
    }

    public function testConcatenateEmptyArrayWithoutSeparator(): void
    {
        $q      = $this->makeQuery();
        $result = $q->concatenate([]);

        self::assertSame('', $result);
    }

    public function testConcatenateNullSeparatorBehavesLikeNoSeparator(): void
    {
        $q      = $this->makeQuery();
        $result = $q->concatenate(['x', 'y'], null);

        self::assertSame('x || y', $result);
    }

    public function testConcatenateWithSeparatorRequiresDriver(): void
    {
        // The separator is passed through $this->quote(), which throws without a driver.
        $q = $this->makeQuery();

        $this->expectException(\RuntimeException::class);

        $q->concatenate(['a', 'b'], ', ');
    }

    public function testConcatenateWithSeparatorAndDriver(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->concatenate(['a', 'b', 'c'], '-');

        // PostgreSQL: a || '-' || b || '-' || c
        self::assertSame("a || '-' || b || '-' || c", $result);
    }

    public function testConcatenateWithSeparatorTwoValues(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->concatenate(['first', 'last'], ' ');

        self::assertStringContainsString('||', $result);
        self::assertStringContainsString("' '", $result);
    }

    public function testConcatenateUsesPipeOperatorNotConcat(): void
    {
        $q      = $this->makeQuery();
        $result = $q->concatenate(['col1', 'col2']);

        // Must NOT use MySQL-style CONCAT().
        self::assertStringNotContainsString('CONCAT', $result);
        self::assertStringContainsString('||', $result);
    }

    // =========================================================================
    // castAsChar()
    // =========================================================================

    public function testCastAsCharUsesPostgresqlCast(): void
    {
        $q      = $this->makeQuery();
        $result = $q->castAsChar('mycolumn');

        self::assertSame('mycolumn::text', $result);
    }

    public function testCastAsCharWithQuotedIdentifier(): void
    {
        $q      = $this->makeQuery();
        $result = $q->castAsChar('"my_column"');

        self::assertSame('"my_column"::text', $result);
    }

    // =========================================================================
    // currentTimestamp()
    // =========================================================================

    public function testCurrentTimestampReturnsNow(): void
    {
        $q = $this->makeQuery();

        self::assertSame('NOW()', $q->currentTimestamp());
    }

    // =========================================================================
    // Date extraction: year / month / day / hour / minute / second
    // =========================================================================

    public static function dateExtractProvider(): array
    {
        return [
            'year'   => ['year',   'EXTRACT (YEAR FROM col)'],
            'month'  => ['month',  'EXTRACT (MONTH FROM col)'],
            'day'    => ['day',    'EXTRACT (DAY FROM col)'],
            'hour'   => ['hour',   'EXTRACT (HOUR FROM col)'],
            'minute' => ['minute', 'EXTRACT (MINUTE FROM col)'],
            'second' => ['second', 'EXTRACT (SECOND FROM col)'],
        ];
    }

    #[DataProvider('dateExtractProvider')]
    public function testDateExtract(string $method, string $expected): void
    {
        $q = $this->makeQuery();

        self::assertSame($expected, $q->$method('col'));
    }

    // =========================================================================
    // dateAdd()
    // =========================================================================

    public function testDateAddPositiveInterval(): void
    {
        $q      = $this->makeQuery();
        $result = $q->dateAdd('2023-01-01', '7', 'day');

        self::assertSame("timestamp '2023-01-01' + interval '7 day'", $result);
    }

    public function testDateAddNegativeIntervalUsesSubtraction(): void
    {
        $q      = $this->makeQuery();
        $result = $q->dateAdd('2023-01-01', '-7', 'day');

        self::assertStringContainsString('-', $result);
        self::assertStringContainsString("timestamp '2023-01-01' - interval '7 day'", $result);
    }

    public function testDateAddNegativeIntervalStripsLeadingMinus(): void
    {
        $q      = $this->makeQuery();
        $result = $q->dateAdd('2023-01-01', '-30', 'minute');

        // The interval value must NOT have a leading minus sign.
        self::assertStringNotContainsString("'-30", $result);
        self::assertStringContainsString("interval '30 minute'", $result);
    }

    public function testDateAddWithMonthPart(): void
    {
        $q      = $this->makeQuery();
        $result = $q->dateAdd('2023-06-15', '3', 'month');

        self::assertSame("timestamp '2023-06-15' + interval '3 month'", $result);
    }

    // =========================================================================
    // forUpdate() / forShare() / noWait() — locking hints
    // =========================================================================

    public function testForUpdateReturnsSelf(): void
    {
        $q = $this->makeQuery();

        self::assertSame($q, $q->forUpdate('users'));
    }

    public function testForShareReturnsSelf(): void
    {
        $q = $this->makeQuery();

        self::assertSame($q, $q->forShare('users'));
    }

    public function testNoWaitReturnsSelf(): void
    {
        $q = $this->makeQuery();

        self::assertSame($q, $q->noWait());
    }

    public function testForUpdateAppearsInSelectToString(): void
    {
        $q = $this->makeQuery();
        $q->select('*')->from('users')->forUpdate('users');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('FOR UPDATE', $sql);
        self::assertStringContainsString('OF users', $sql);
    }

    public function testForShareAppearsInSelectToString(): void
    {
        $q = $this->makeQuery();
        $q->select('*')->from('orders')->forShare('orders');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('FOR SHARE', $sql);
        self::assertStringContainsString('OF orders', $sql);
    }

    public function testNoWaitAppearsInSelectToString(): void
    {
        $q = $this->makeQuery();
        $q->select('*')->from('users')->forUpdate('users')->noWait();
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('NOWAIT', $sql);
    }

    public function testForUpdateTakesPrecedenceOverForShare(): void
    {
        // When both forUpdate and forShare are set, forUpdate wins in __toString.
        $q = $this->makeQuery();
        $q->select('*')->from('t')->forUpdate('t')->forShare('t');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('FOR UPDATE', $sql);
        // FOR SHARE must not appear when FOR UPDATE is set.
        self::assertStringNotContainsString('FOR SHARE', $sql);
    }

    // =========================================================================
    // clear()
    // =========================================================================

    public function testClearLimitResetsLimit(): void
    {
        $q = $this->makeQuery();
        $q->select('*')->from('foo')->limit(10);
        $q->clear('limit');
        $sql = self::normalise((string) $q);

        self::assertStringNotContainsString('LIMIT', $sql);
    }

    public function testClearOffsetResetsOffset(): void
    {
        $q = $this->makeQuery();
        $q->select('*')->from('foo')->offset(5);
        $q->clear('offset');
        $sql = self::normalise((string) $q);

        self::assertStringNotContainsString('OFFSET', $sql);
    }

    public function testClearReturningResetsReturning(): void
    {
        $q = $this->makeQuery();
        $q->insert('#__users')
          ->columns(['username'])
          ->values(["'alice'"])
          ->returning('id');
        $q->clear('returning');
        $sql = self::normalise((string) $q);

        self::assertStringNotContainsString('RETURNING', $sql);
    }

    public function testClearForUpdateResetsForUpdate(): void
    {
        $q = $this->makeQuery();
        $q->select('*')->from('t')->forUpdate('t');
        $q->clear('forUpdate');
        $sql = self::normalise((string) $q);

        self::assertStringNotContainsString('FOR UPDATE', $sql);
    }

    public function testClearForShareResetsForShare(): void
    {
        $q = $this->makeQuery();
        $q->select('*')->from('t')->forShare('t');
        $q->clear('forShare');
        $sql = self::normalise((string) $q);

        self::assertStringNotContainsString('FOR SHARE', $sql);
    }

    public function testClearNoWaitResetsNoWait(): void
    {
        $q = $this->makeQuery();
        $q->select('*')->from('t')->forUpdate('t')->noWait();
        $q->clear('noWait');
        $sql = self::normalise((string) $q);

        self::assertStringNotContainsString('NOWAIT', $sql);
    }

    public function testClearNullResetsEntireQuery(): void
    {
        $q = $this->makeQuery();
        $q->select('*')->from('foo')->limit(10)->offset(5)->returning('id');
        $q->clear();

        // After full clear, type is reset — toString returns empty string.
        self::assertSame('', (string) $q);
    }

    public function testClearReturnsSelf(): void
    {
        $q = $this->makeQuery();

        self::assertSame($q, $q->clear('limit'));
    }

    // =========================================================================
    // __toString() — SELECT query
    // =========================================================================

    public function testSelectToStringBasic(): void
    {
        $q   = $this->makeQuery();
        $sql = self::normalise((string) $q->select('*')->from('users'));

        self::assertStringContainsString('SELECT', $sql);
        self::assertStringContainsString('*', $sql);
        self::assertStringContainsString('FROM', $sql);
        self::assertStringContainsString('users', $sql);
    }

    public function testSelectToStringWithWhere(): void
    {
        $q   = $this->makeQuery();
        $sql = self::normalise((string) $q->select('id')->from('users')->where('active = 1'));

        self::assertStringContainsString('WHERE', $sql);
        self::assertStringContainsString('active = 1', $sql);
    }

    public function testSelectToStringWithOrder(): void
    {
        $q   = $this->makeQuery();
        $sql = self::normalise((string) $q->select('id')->from('t')->order('id DESC'));

        self::assertStringContainsString('ORDER BY', $sql);
        self::assertStringContainsString('id DESC', $sql);
    }

    public function testSelectToStringWithGroup(): void
    {
        $q   = $this->makeQuery();
        $sql = self::normalise((string) $q->select('status, COUNT(*)')->from('t')->group('status'));

        self::assertStringContainsString('GROUP BY', $sql);
        self::assertStringContainsString('status', $sql);
    }

    public function testSelectToStringWithHaving(): void
    {
        $q   = $this->makeQuery();
        $sql = self::normalise((string) $q->select('status, COUNT(*)')->from('t')->group('status')->having('COUNT(*) > 1'));

        self::assertStringContainsString('HAVING', $sql);
        self::assertStringContainsString('COUNT(*) > 1', $sql);
    }

    // =========================================================================
    // __toString() — INSERT query
    // =========================================================================

    public function testInsertToStringBasic(): void
    {
        $q = $this->makeQuery();
        $q->insert('#__users')
          ->columns(['username', 'email'])
          ->values(["'alice'", "'alice@example.com'"]);
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('INSERT', $sql);
        self::assertStringContainsString('VALUES', $sql);
    }

    public function testInsertToStringWithReturning(): void
    {
        $q = $this->makeQuery();
        $q->insert('#__users')
          ->columns(['username'])
          ->values(["'alice'"])
          ->returning('id');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('RETURNING', $sql);
        self::assertStringContainsString('id', $sql);
        // RETURNING must come after VALUES.
        self::assertGreaterThan(strpos($sql, 'VALUES'), strpos($sql, 'RETURNING'));
    }

    // =========================================================================
    // __toString() — UPDATE query
    // =========================================================================

    public function testUpdateToStringBasic(): void
    {
        $q = $this->makeQuery();
        $q->update('#__users')->set(['name = \'bob\''])->where('id = 1');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('UPDATE', $sql);
        self::assertStringContainsString('SET', $sql);
        self::assertStringContainsString('WHERE', $sql);
    }

    // =========================================================================
    // quoteName — PostgreSQL uses double-quote identifier quoting
    // =========================================================================

    public function testQuoteNameThrowsWithoutDriver(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->makeQuery()->quoteName('column');
    }

    public function testQuoteNameWithDriverUsesDoubleQuotes(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->quoteName('my_table');

        self::assertSame('"my_table"', $result);
    }

    public function testQuoteNameWithAlias(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->quoteName('users', 'u');

        self::assertStringContainsString('"users"', $result);
        self::assertStringContainsString('"u"', $result);
    }

    // =========================================================================
    // quote / escape without driver (inherited error conditions)
    // =========================================================================

    public function testQuoteThrowsWithoutDriver(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->makeQuery()->quote('value');
    }

    // =========================================================================
    // Pgsql subclass — verify same behaviour as Postgresql
    // =========================================================================

    public function testPgsqlConcatenateWithoutSeparator(): void
    {
        $q      = $this->makePgsqlQuery();
        $result = $q->concatenate(['a', 'b']);

        self::assertSame('a || b', $result);
    }

    public function testPgsqlCastAsChar(): void
    {
        $q = $this->makePgsqlQuery();

        self::assertSame('col::text', $q->castAsChar('col'));
    }

    public function testPgsqlCurrentTimestamp(): void
    {
        $q = $this->makePgsqlQuery();

        self::assertSame('NOW()', $q->currentTimestamp());
    }

    public function testPgsqlProcessLimitStandaloneClauses(): void
    {
        $q = $this->makePgsqlQuery();

        self::assertSame('SELECT 1 LIMIT 5 OFFSET 10', $q->processLimit('SELECT 1', 5, 10));
    }

    public function testPgsqlReturningInInsert(): void
    {
        $q = $this->makePgsqlQuery();
        $q->insert('#__orders')
          ->columns(['amount'])
          ->values(['99'])
          ->returning('order_id');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('RETURNING', $sql);
        self::assertStringContainsString('order_id', $sql);
    }
}
