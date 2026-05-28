<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Database;

use Awf\Database\Query\Sqlite;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the SELECT / FROM / WHERE / JOIN query-builder methods on the
 * base Query class (tested via the concrete Sqlite subclass so we can
 * instantiate it without a real database driver).
 *
 * Methods under test: select(), from(), where(), join(), innerJoin(),
 * leftJoin(), rightJoin(), and the __toString() serialisation.
 */
class QuerySelectTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Collapse all whitespace sequences to a single space and trim. */
    private static function normalise(string $sql): string
    {
        return trim(preg_replace('/\s+/', ' ', $sql));
    }

    private function makeQuery(): Sqlite
    {
        return new Sqlite(null);
    }

    // -------------------------------------------------------------------------
    // select()
    // -------------------------------------------------------------------------

    public function testSelectWithStringSetsTypeAndClause(): void
    {
        $q = $this->makeQuery()->select('a.*');

        self::assertStringContainsString('SELECT', self::normalise((string) $q));
        self::assertStringContainsString('a.*', self::normalise((string) $q));
    }

    public function testSelectWithArrayIncludesAllColumns(): void
    {
        $q = $this->makeQuery()->select(['a.id', 'b.name', 'c.value']);
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('a.id', $sql);
        self::assertStringContainsString('b.name', $sql);
        self::assertStringContainsString('c.value', $sql);
    }

    public function testSelectCalledTwiceAccumulatesColumns(): void
    {
        $q = $this->makeQuery()->select('a.*')->select('b.id');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('a.*', $sql);
        self::assertStringContainsString('b.id', $sql);
    }

    public function testSelectSetsTypeToSelect(): void
    {
        $q = $this->makeQuery()->select('1');

        // __toString() only produces output for type=select
        self::assertNotEmpty(self::normalise((string) $q));
        self::assertStringContainsString('SELECT', self::normalise((string) $q));
    }

    public function testSelectWithEmptyColumnClearsSelectElement(): void
    {
        // Passing empty/null to select() is a documented edge case that
        // sets $this->select = null (see source).
        $q = $this->makeQuery()->select('a.*')->select('');
        $sql = self::normalise((string) $q);

        // After clearing, the select element is null; __toString produces
        // an empty string from a null cast.
        self::assertSame('', $sql);
    }

    public function testSelectDistinctProducesSELECTDISTINCT(): void
    {
        $q = $this->makeQuery()->selectDistinct('a.id');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('SELECT DISTINCT', $sql);
        self::assertStringContainsString('a.id', $sql);
    }

    // -------------------------------------------------------------------------
    // from()
    // -------------------------------------------------------------------------

    public function testFromAddsFromClause(): void
    {
        $q = $this->makeQuery()->select('*')->from('users');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('FROM', $sql);
        self::assertStringContainsString('users', $sql);
    }

    public function testFromAppearsAfterSelect(): void
    {
        $q = $this->makeQuery()->select('*')->from('posts');
        $sql = self::normalise((string) $q);

        // SELECT must appear before FROM
        self::assertLessThan(strpos($sql, 'FROM'), strpos($sql, 'SELECT'));
    }

    public function testFromCalledTwiceAccumulatesTables(): void
    {
        $q = $this->makeQuery()->select('*')->from('users')->from('orders');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('users', $sql);
        self::assertStringContainsString('orders', $sql);
    }

    public function testFromWithSubQueryAliasRequiresAlias(): void
    {
        $inner = $this->makeQuery()->select('id')->from('orders');

        $outer = $this->makeQuery();

        // Passing a Query instance without alias must throw
        $this->expectException(\RuntimeException::class);
        $outer->select('*')->from($inner);
    }

    public function testFromWithSubQueryAndAliasThrowsWithoutDriver(): void
    {
        // from() with a Query sub-query alias calls quoteName() internally,
        // which requires a database driver.  Without one a RuntimeException
        // must be thrown.
        $inner = $this->makeQuery()->select('id')->from('orders');
        $outer = $this->makeQuery()->select('*');

        $this->expectException(\RuntimeException::class);
        $outer->from($inner, 'sub');
    }

    public function testFromWithStringAndAliasThrowsWithoutDriver(): void
    {
        // from($table, $alias) calls quoteName($alias) which requires a driver.
        $q = $this->makeQuery()->select('*');

        $this->expectException(\RuntimeException::class);
        $q->from('orders', 'o');
    }

    // -------------------------------------------------------------------------
    // where()
    // -------------------------------------------------------------------------

    public function testWhereAddsWhereClause(): void
    {
        $q = $this->makeQuery()->select('*')->from('t')->where('id = 1');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('WHERE', $sql);
        self::assertStringContainsString('id = 1', $sql);
    }

    public function testWhereDefaultGlueIsAnd(): void
    {
        $q = $this->makeQuery()->select('*')->from('t')
            ->where('a = 1')
            ->where('b = 2');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('AND', $sql);
        self::assertStringContainsString('a = 1', $sql);
        self::assertStringContainsString('b = 2', $sql);
    }

    public function testWhereWithArrayOfConditions(): void
    {
        $q = $this->makeQuery()->select('*')->from('t')
            ->where(['x = 1', 'y = 2', 'z = 3']);
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('x = 1', $sql);
        self::assertStringContainsString('y = 2', $sql);
        self::assertStringContainsString('z = 3', $sql);
    }

    public function testWhereWithOrGlue(): void
    {
        $q = $this->makeQuery()->select('*')->from('t')
            ->where(['a = 1', 'b = 2'], 'OR');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('OR', $sql);
    }

    public function testWhereGlueIsFixedOnFirstCall(): void
    {
        // The glue is set only on the FIRST where() call; subsequent calls
        // just append, so the glue from the first call stays in effect.
        $q = $this->makeQuery()->select('*')->from('t')
            ->where('a = 1', 'AND')
            ->where('b = 2');   // no explicit glue; uses whatever was baked in
        $sql = self::normalise((string) $q);

        // Both conditions must be present
        self::assertStringContainsString('a = 1', $sql);
        self::assertStringContainsString('b = 2', $sql);
    }

    public function testClearWhereRemovesConditions(): void
    {
        $q = $this->makeQuery()->select('*')->from('t')->where('id = 1');
        $q->clear('where');
        $sql = self::normalise((string) $q);

        self::assertStringNotContainsString('WHERE', $sql);
        self::assertStringNotContainsString('id = 1', $sql);
    }

    public function testWhereAppearsAfterFrom(): void
    {
        $q = $this->makeQuery()->select('*')->from('t')->where('id = 1');
        $sql = self::normalise((string) $q);

        self::assertLessThan(strpos($sql, 'WHERE'), strpos($sql, 'FROM'));
    }

    // -------------------------------------------------------------------------
    // join() / innerJoin() / leftJoin() / rightJoin()
    // -------------------------------------------------------------------------

    public function testJoinGeneric(): void
    {
        $q = $this->makeQuery()->select('*')->from('a')
            ->join('INNER', 'b ON b.id = a.id');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('INNER JOIN', $sql);
        self::assertStringContainsString('b ON b.id = a.id', $sql);
    }

    public function testInnerJoin(): void
    {
        $q = $this->makeQuery()->select('*')->from('a')
            ->innerJoin('b ON b.id = a.id');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('INNER JOIN', $sql);
        self::assertStringContainsString('b ON b.id = a.id', $sql);
    }

    public function testLeftJoin(): void
    {
        $q = $this->makeQuery()->select('*')->from('a')
            ->leftJoin('c ON c.id = a.id');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('LEFT JOIN', $sql);
        self::assertStringContainsString('c ON c.id = a.id', $sql);
    }

    public function testRightJoin(): void
    {
        $q = $this->makeQuery()->select('*')->from('a')
            ->rightJoin('d ON d.id = a.id');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('RIGHT JOIN', $sql);
        self::assertStringContainsString('d ON d.id = a.id', $sql);
    }

    public function testMultipleJoinsAreAllPresent(): void
    {
        $q = $this->makeQuery()->select('*')->from('a')
            ->innerJoin('b ON b.id = a.id')
            ->leftJoin('c ON c.id = a.id')
            ->rightJoin('d ON d.id = a.id');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('INNER JOIN', $sql);
        self::assertStringContainsString('LEFT JOIN', $sql);
        self::assertStringContainsString('RIGHT JOIN', $sql);
    }

    public function testJoinAppearsAfterFrom(): void
    {
        $q = $this->makeQuery()->select('*')->from('a')
            ->innerJoin('b ON b.id = a.id');
        $sql = self::normalise((string) $q);

        self::assertLessThan(strpos($sql, 'INNER JOIN'), strpos($sql, 'FROM'));
    }

    public function testJoinAppearsBeforeWhere(): void
    {
        $q = $this->makeQuery()->select('*')->from('a')
            ->innerJoin('b ON b.id = a.id')
            ->where('a.id = 1');
        $sql = self::normalise((string) $q);

        self::assertLessThan(strpos($sql, 'WHERE'), strpos($sql, 'INNER JOIN'));
    }

    public function testClearJoinRemovesAllJoins(): void
    {
        $q = $this->makeQuery()->select('*')->from('a')
            ->innerJoin('b ON b.id = a.id')
            ->leftJoin('c ON c.id = a.id');
        $q->clear('join');
        $sql = self::normalise((string) $q);

        self::assertStringNotContainsString('JOIN', $sql);
    }

    // -------------------------------------------------------------------------
    // __toString() — complete query strings
    // -------------------------------------------------------------------------

    public static function completeQueryProvider(): array
    {
        return [
            'simple SELECT *' => [
                static function (Sqlite $q): Sqlite {
                    return $q->select('*')->from('users');
                },
                ['SELECT', '*', 'FROM', 'users'],
                [],
            ],
            'SELECT with WHERE' => [
                static function (Sqlite $q): Sqlite {
                    return $q->select('id, name')->from('users')->where('active = 1');
                },
                ['SELECT', 'id, name', 'FROM', 'users', 'WHERE', 'active = 1'],
                [],
            ],
            'SELECT with multiple WHERE AND' => [
                static function (Sqlite $q): Sqlite {
                    return $q->select('*')->from('orders')
                        ->where('status = 1')
                        ->where('total > 0');
                },
                ['SELECT', 'FROM', 'orders', 'WHERE', 'status = 1', 'AND', 'total > 0'],
                [],
            ],
            'SELECT with INNER JOIN' => [
                static function (Sqlite $q): Sqlite {
                    return $q->select('a.id, b.name')->from('a')
                        ->innerJoin('b ON b.id = a.id');
                },
                ['SELECT', 'FROM', 'a', 'INNER JOIN', 'b ON b.id = a.id'],
                [],
            ],
            'SELECT with LEFT JOIN and WHERE' => [
                static function (Sqlite $q): Sqlite {
                    return $q->select('a.*')->from('a')
                        ->leftJoin('b ON b.a_id = a.id')
                        ->where('a.deleted = 0');
                },
                ['SELECT', 'FROM', 'a', 'LEFT JOIN', 'b ON b.a_id = a.id', 'WHERE', 'a.deleted = 0'],
                [],
            ],
        ];
    }

    #[DataProvider('completeQueryProvider')]
    public function testCompleteQueryContainsExpectedParts(
        \Closure $builder,
        array $mustContain,
        array $mustNotContain
    ): void {
        $q   = $this->makeQuery();
        $sql = self::normalise((string) $builder($q));

        foreach ($mustContain as $fragment) {
            self::assertStringContainsString($fragment, $sql, "Expected SQL to contain: $fragment");
        }
        foreach ($mustNotContain as $fragment) {
            self::assertStringNotContainsString($fragment, $sql, "Expected SQL NOT to contain: $fragment");
        }
    }

    // -------------------------------------------------------------------------
    // clear() — full reset
    // -------------------------------------------------------------------------

    public function testClearAllResetsEntireQuery(): void
    {
        $q = $this->makeQuery()->select('*')->from('t')->where('id = 1')
            ->innerJoin('x ON x.id = t.id');
        $q->clear();
        $sql = self::normalise((string) $q);

        self::assertSame('', $sql);
    }

    public function testClearSelectOnlyRemovesSelectClause(): void
    {
        $q = $this->makeQuery()->select('id')->from('t');
        $q->clear('select');
        $sql = self::normalise((string) $q);

        // After clearing select, type is also cleared, so __toString returns ''
        self::assertSame('', $sql);
    }

    // -------------------------------------------------------------------------
    // setQuery() — raw SQL passthrough
    // -------------------------------------------------------------------------

    public function testSetQueryReturnsRawSql(): void
    {
        $raw = 'SELECT id FROM users WHERE active = 1';
        $q   = $this->makeQuery()->setQuery($raw);

        self::assertSame($raw, (string) $q);
    }

    public function testSetQueryOverridesBuiltQuery(): void
    {
        $raw = 'SELECT 1';
        $q   = $this->makeQuery()->select('id')->from('users')->setQuery($raw);

        self::assertSame($raw, (string) $q);
    }

    // -------------------------------------------------------------------------
    // __clone() — deep copy
    // -------------------------------------------------------------------------

    public function testCloneIsIndependentOfOriginal(): void
    {
        $original = $this->makeQuery()->select('*')->from('t');
        $clone    = clone $original;

        $clone->where('id = 99');
        $originalSql = self::normalise((string) $original);
        $cloneSql    = self::normalise((string) $clone);

        self::assertStringNotContainsString('WHERE', $originalSql);
        self::assertStringContainsString('WHERE', $cloneSql);
    }

    // -------------------------------------------------------------------------
    // Methods that require a Driver object — verify they throw without one
    // -------------------------------------------------------------------------

    public function testQuoteThrowsWithoutDriver(): void
    {
        $q = $this->makeQuery();

        $this->expectException(\RuntimeException::class);
        $q->quote('value');
    }

    public function testQuoteNameThrowsWithoutDriver(): void
    {
        $q = $this->makeQuery();

        $this->expectException(\RuntimeException::class);
        $q->quoteName('column_name');
    }

    public function testEscapeThrowsWithoutDriver(): void
    {
        $q = $this->makeQuery();

        $this->expectException(\RuntimeException::class);
        $q->escape('some text');
    }

    public function testNullDateThrowsWithoutDriver(): void
    {
        $q = $this->makeQuery();

        $this->expectException(\RuntimeException::class);
        $q->nullDate();
    }

    public function testDateFormatThrowsWithoutDriver(): void
    {
        $q = $this->makeQuery();

        $this->expectException(\RuntimeException::class);
        $q->dateFormat();
    }
}
