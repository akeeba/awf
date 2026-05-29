<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Database\Query;

use Awf\Database\Driver\None as NoneDriver;
use Awf\Database\Query\Sqlite;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Database\Query\Sqlite — driver-specific behaviour.
 *
 * Covers:
 *  - processLimit()   – LIMIT/OFFSET rendering
 *  - setLimit()       – stores values as integers and chains
 *  - charLength()     – delegates to length()
 *  - concatenate()    – with and without separator
 *  - bind()/getBounded() – parameter-binding bookkeeping
 *  - clear()          – clears bounded array alongside base state
 *  - quote() error    – throws without a real driver (inherited behaviour)
 */
class SqliteQueryTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeQuery(): Sqlite
    {
        return new Sqlite(null);
    }

    /**
     * Return a Sqlite query object backed by a minimal concrete driver that
     * provides quote() so concatenate()-with-separator can be exercised.
     */
    private function makeQueryWithDriver(): Sqlite
    {
        $driver = new class extends NoneDriver {
            public function __construct()
            {
                // Bypass the parent constructor (which requires $options).
                $this->nameQuote  = '`';
                $this->nullDate   = '0000-00-00 00:00:00';
                $this->tablePrefix = '';
            }

            public function escape($text, $extra = false): string
            {
                return str_replace("'", "''", (string) $text);
            }

            // quote() wraps in single-quotes after escaping.
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

        return new Sqlite($driver);
    }

    // =========================================================================
    // processLimit
    // =========================================================================

    public static function processLimitProvider(): array
    {
        return [
            'no limit no offset'     => ['SELECT 1', 0,  0,  'SELECT 1'],
            'limit only'             => ['SELECT 1', 10, 0,  'SELECT 1 LIMIT 0, 10'],
            'offset only'            => ['SELECT 1', 0,  5,  'SELECT 1 LIMIT 5, 0'],
            'limit and offset'       => ['SELECT 1', 20, 10, 'SELECT 1 LIMIT 10, 20'],
            'limit 1 offset 0'       => ['SELECT 1', 1,  0,  'SELECT 1 LIMIT 0, 1'],
            'large values'           => ['SELECT *', 1000, 500, 'SELECT * LIMIT 500, 1000'],
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

    public function testProcessLimitAppendsToExistingWhereClause(): void
    {
        $q      = $this->makeQuery();
        $base   = 'SELECT * FROM foo WHERE id > 0';
        $result = $q->processLimit($base, 25, 50);

        self::assertSame('SELECT * FROM foo WHERE id > 0 LIMIT 50, 25', $result);
    }

    // =========================================================================
    // setLimit / integration with __toString
    // =========================================================================

    public function testSetLimitReturnsSelf(): void
    {
        $q = $this->makeQuery();

        self::assertSame($q, $q->setLimit(10, 0));
    }

    public function testSetLimitStoresIntegerValues(): void
    {
        $q = $this->makeQuery();
        $q->setLimit(10, 5);

        // Access via processLimit round-trip
        $result = $q->processLimit('Q', 10, 5);
        self::assertSame('Q LIMIT 5, 10', $result);
    }

    public function testSetLimitCastsStringsToInt(): void
    {
        $q = $this->makeQuery();
        // setLimit casts to int; passing numeric strings is fine
        $q->setLimit(10, 3);

        self::assertSame('Q LIMIT 3, 10', $q->processLimit('Q', 10, 3));
    }

    /**
     * When setLimit() is called and a SELECT is built, __toString() should
     * append the LIMIT clause via processLimit.
     */
    public function testSetLimitIntegratesWithToString(): void
    {
        $q = $this->makeQuery();
        $q->select('*')->from('foo')->setLimit(15, 30);

        $sql = (string) $q;

        self::assertStringContainsString('LIMIT 30, 15', $sql);
    }

    public function testSetLimitZeroZeroProducesNoLimitClause(): void
    {
        $q = $this->makeQuery();
        $q->select('*')->from('foo')->setLimit(0, 0);

        $sql = (string) $q;

        self::assertStringNotContainsString('LIMIT', $sql);
    }

    // =========================================================================
    // charLength
    // =========================================================================

    public function testCharLengthBasic(): void
    {
        $q      = $this->makeQuery();
        $result = $q->charLength('field');

        self::assertSame('length(field)', $result);
    }

    public function testCharLengthWithOperatorAndCondition(): void
    {
        $q      = $this->makeQuery();
        $result = $q->charLength('a.name', '>', '0');

        self::assertSame('length(a.name) > 0', $result);
    }

    public function testCharLengthWithoutConditionIgnoresOperator(): void
    {
        // When $condition is null (not provided), operator is also ignored.
        $q      = $this->makeQuery();
        $result = $q->charLength('col', '=');

        self::assertSame('length(col)', $result);
    }

    public static function charLengthOperatorProvider(): array
    {
        return [
            'greater-than'  => ['>', '5',  'length(x) > 5'],
            'less-than'     => ['<', '10', 'length(x) < 10'],
            'equals'        => ['=', '0',  'length(x) = 0'],
            'not-equals'    => ['<>', '3', 'length(x) <> 3'],
        ];
    }

    #[DataProvider('charLengthOperatorProvider')]
    public function testCharLengthWithVariousOperators(string $op, string $cond, string $expected): void
    {
        $q = $this->makeQuery();

        self::assertSame($expected, $q->charLength('x', $op, $cond));
    }

    // =========================================================================
    // concatenate
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

    public function testConcatenateWithSeparatorRequiresDriver(): void
    {
        // quote() is called internally for the separator — this throws without a driver.
        $q = $this->makeQuery();

        $this->expectException(\RuntimeException::class);

        $q->concatenate(['a', 'b'], ', ');
    }

    public function testConcatenateWithSeparatorAndDriver(): void
    {
        $q      = $this->makeQueryWithDriver();
        $result = $q->concatenate(['a', 'b', 'c'], '-');

        // The separator is quoted with single-quotes and joined with ' || '.
        self::assertSame("a || '-' || b || '-' || c", $result);
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
        // Passing null as separator should follow the "no separator" branch.
        $result = $q->concatenate(['x', 'y'], null);

        self::assertSame('x || y', $result);
    }

    // =========================================================================
    // bind / getBounded
    // =========================================================================

    public function testBindStoresKeyValuePair(): void
    {
        $q     = $this->makeQuery();
        $value = 'hello';
        $q->bind(':name', $value);

        $bounded = $q->getBounded();

        self::assertArrayHasKey(':name', $bounded);
        self::assertSame('hello', $bounded[':name']->value);
    }

    public function testBindReturnsSelf(): void
    {
        $q     = $this->makeQuery();
        $value = 1;

        self::assertSame($q, $q->bind(':x', $value));
    }

    public function testBindWithEmptyKeyResetsBounded(): void
    {
        $q     = $this->makeQuery();
        $value = 'v';
        $q->bind(':k', $value);

        // Calling bind with an empty key should wipe the array.
        $q->bind(null);

        self::assertSame([], $q->getBounded());
    }

    public function testBindWithNullValueRemovesKey(): void
    {
        $q      = $this->makeQuery();
        $value  = 'original';
        $null   = null;

        $q->bind(':k', $value);
        $q->bind(':k', $null);

        $bounded = $q->getBounded();
        self::assertArrayNotHasKey(':k', $bounded);
    }

    public function testBindMultipleKeys(): void
    {
        $q  = $this->makeQuery();
        $v1 = 'foo';
        $v2 = 'bar';

        $q->bind(':a', $v1)->bind(':b', $v2);

        $bounded = $q->getBounded();

        self::assertArrayHasKey(':a', $bounded);
        self::assertArrayHasKey(':b', $bounded);
        self::assertSame('foo', $bounded[':a']->value);
        self::assertSame('bar', $bounded[':b']->value);
    }

    public function testGetBoundedWithKeyReturnsSingleEntry(): void
    {
        $q     = $this->makeQuery();
        $value = 42;
        $q->bind(':id', $value);

        $entry = $q->getBounded(':id');

        self::assertIsObject($entry);
        self::assertSame(42, $entry->value);
    }

    public function testGetBoundedWithNonExistentKeyReturnsNull(): void
    {
        $q      = $this->makeQuery();
        $result = $q->getBounded(':missing');

        // The method returns by reference with no explicit return for missing
        // keys — the caller receives null.
        self::assertNull($result);
    }

    // =========================================================================
    // clear
    // =========================================================================

    public function testClearWithNullResetsBoundedArray(): void
    {
        $q     = $this->makeQuery();
        $value = 'x';
        $q->bind(':k', $value);

        $q->clear();

        self::assertSame([], $q->getBounded());
    }

    public function testClearSelectClausePreservesBounded(): void
    {
        $q     = $this->makeQuery();
        $value = 'preserved';
        $q->bind(':p', $value);

        // Clearing a specific clause other than null should NOT reset bounded.
        $q->select('*')->from('foo')->clear('select');

        $bounded = $q->getBounded();
        self::assertArrayHasKey(':p', $bounded);
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
}
