<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Database;

use Awf\Database\Query\Sqlite;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the INSERT / UPDATE / DELETE / REPLACE / clear() query-builder
 * methods on the base Query class, exercised via the concrete Sqlite subclass
 * so no real database driver is needed.
 *
 * Methods under test:
 *   insert(), replace(), update(), delete(),
 *   columns(), values(), set(), clear()
 */
class QueryDmlTest extends TestCase
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

    // =========================================================================
    // INSERT
    // =========================================================================

    public function testInsertProducesInsertInto(): void
    {
        $q   = $this->makeQuery()->insert('users');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('INSERT INTO', $sql);
        self::assertStringContainsString('users', $sql);
    }

    public function testInsertSetsTypeToInsert(): void
    {
        $q = $this->makeQuery()->insert('users');

        self::assertSame('insert', $q->type);
    }

    public function testInsertWithSetMethodProducesSET(): void
    {
        $q   = $this->makeQuery()->insert('users')->set('name = \'Alice\'');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('INSERT INTO', $sql);
        self::assertStringContainsString('SET', $sql);
        self::assertStringContainsString("name = 'Alice'", $sql);
    }

    public function testInsertWithColumnsAndValues(): void
    {
        $q   = $this->makeQuery()
            ->insert('users')
            ->columns('id, name')
            ->values('1, \'Alice\'');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('INSERT INTO', $sql);
        self::assertStringContainsString('VALUES', $sql);
        self::assertStringContainsString('id, name', $sql);
        self::assertStringContainsString("1, 'Alice'", $sql);
    }

    public function testInsertWithColumnsAsArray(): void
    {
        $q   = $this->makeQuery()
            ->insert('users')
            ->columns(['id', 'name', 'email'])
            ->values('1, \'Alice\', \'a@b.com\'');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('id', $sql);
        self::assertStringContainsString('name', $sql);
        self::assertStringContainsString('email', $sql);
    }

    public function testInsertWithMultipleValueRows(): void
    {
        $q = $this->makeQuery()
            ->insert('users')
            ->columns('id, name')
            ->values('1, \'Alice\'')
            ->values('2, \'Bob\'');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString("1, 'Alice'", $sql);
        self::assertStringContainsString("2, 'Bob'", $sql);
        self::assertStringContainsString('VALUES', $sql);
    }

    public function testInsertWithValuesAsArray(): void
    {
        $q = $this->makeQuery()
            ->insert('users')
            ->columns('id, name')
            ->values(["1, 'Alice'", "2, 'Bob'"]);
        $sql = self::normalise((string) $q);

        self::assertStringContainsString("1, 'Alice'", $sql);
        self::assertStringContainsString("2, 'Bob'", $sql);
    }

    public function testInsertWithoutColumnsOnlyEmitsValues(): void
    {
        $q   = $this->makeQuery()
            ->insert('users')
            ->values('1, \'Alice\'');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('INSERT INTO', $sql);
        self::assertStringContainsString('VALUES', $sql);
    }

    public function testInsertColumnsCalledTwiceAccumulates(): void
    {
        $q = $this->makeQuery()
            ->insert('users')
            ->columns('id')
            ->columns('name')
            ->values('1, \'Alice\'');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('id', $sql);
        self::assertStringContainsString('name', $sql);
    }

    public function testInsertSetsAutoIncrementField(): void
    {
        $q = $this->makeQuery()->insert('users', 'id');

        self::assertSame('id', $q->autoIncrementField);
    }

    public function testInsertAutoIncrementDefaultFalse(): void
    {
        $q = $this->makeQuery()->insert('users');

        self::assertFalse($q->autoIncrementField);
    }

    // =========================================================================
    // REPLACE
    // =========================================================================

    public function testReplaceProducesReplaceInto(): void
    {
        $q   = $this->makeQuery()->replace('users');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('REPLACE INTO', $sql);
        self::assertStringContainsString('users', $sql);
    }

    public function testReplaceSetsTypeToReplace(): void
    {
        $q = $this->makeQuery()->replace('users');

        self::assertSame('replace', $q->type);
    }

    public function testReplaceWithColumnsAndValues(): void
    {
        $q   = $this->makeQuery()
            ->replace('users')
            ->columns('id, name')
            ->values('1, \'Alice\'');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('REPLACE INTO', $sql);
        self::assertStringContainsString('VALUES', $sql);
        self::assertStringContainsString('id, name', $sql);
    }

    public function testReplaceWithSetMethod(): void
    {
        $q   = $this->makeQuery()->replace('users')->set('id = 1');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('REPLACE INTO', $sql);
        self::assertStringContainsString('SET', $sql);
        self::assertStringContainsString('id = 1', $sql);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function testUpdateProducesUpdateKeyword(): void
    {
        $q   = $this->makeQuery()->update('users')->set('name = \'Bob\'');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('UPDATE', $sql);
        self::assertStringContainsString('users', $sql);
    }

    public function testUpdateSetsTypeToUpdate(): void
    {
        $q = $this->makeQuery()->update('users');

        self::assertSame('update', $q->type);
    }

    public function testUpdateWithSetProducesSET(): void
    {
        $q   = $this->makeQuery()
            ->update('users')
            ->set('name = \'Bob\'');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('SET', $sql);
        self::assertStringContainsString("name = 'Bob'", $sql);
    }

    public function testUpdateSetOrderIsUpdateThenSet(): void
    {
        $q   = $this->makeQuery()
            ->update('users')
            ->set('name = \'Bob\'');
        $sql = self::normalise((string) $q);

        self::assertLessThan(strpos($sql, 'SET'), strpos($sql, 'UPDATE'));
    }

    public function testUpdateWithMultipleSetCalls(): void
    {
        $q   = $this->makeQuery()
            ->update('users')
            ->set('name = \'Bob\'')
            ->set('age = 30');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString("name = 'Bob'", $sql);
        self::assertStringContainsString('age = 30', $sql);
    }

    public function testUpdateWithSetAsArray(): void
    {
        $q   = $this->makeQuery()
            ->update('users')
            ->set(['name = \'Bob\'', 'age = 30']);
        $sql = self::normalise((string) $q);

        self::assertStringContainsString("name = 'Bob'", $sql);
        self::assertStringContainsString('age = 30', $sql);
    }

    public function testUpdateWithWhere(): void
    {
        $q   = $this->makeQuery()
            ->update('users')
            ->set('name = \'Bob\'')
            ->where('id = 1');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('WHERE', $sql);
        self::assertStringContainsString('id = 1', $sql);
        // SET must appear before WHERE
        self::assertLessThan(strpos($sql, 'WHERE'), strpos($sql, 'SET'));
    }

    public function testUpdateWithJoin(): void
    {
        $q   = $this->makeQuery()
            ->update('users')
            ->join('INNER', 'orders ON orders.user_id = users.id')
            ->set('users.name = \'Bob\'');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('INNER JOIN', $sql);
        self::assertStringContainsString('orders ON orders.user_id = users.id', $sql);
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    public function testDeleteProducesDeleteKeyword(): void
    {
        $q   = $this->makeQuery()->delete('users');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('DELETE', $sql);
        self::assertStringContainsString('FROM', $sql);
        self::assertStringContainsString('users', $sql);
    }

    public function testDeleteSetsTypeToDelete(): void
    {
        $q = $this->makeQuery()->delete('users');

        self::assertSame('delete', $q->type);
    }

    public function testDeleteWithoutTableCreatesDeleteFromlessSql(): void
    {
        // delete() without a $table param omits the FROM clause
        $q   = $this->makeQuery()->delete();
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('DELETE', $sql);
        self::assertStringNotContainsString('FROM', $sql);
    }

    public function testDeleteFromAppearsAfterDelete(): void
    {
        $q   = $this->makeQuery()->delete('users');
        $sql = self::normalise((string) $q);

        self::assertLessThan(strpos($sql, 'FROM'), strpos($sql, 'DELETE'));
    }

    public function testDeleteWithWhere(): void
    {
        $q   = $this->makeQuery()
            ->delete('users')
            ->where('id = 5');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('WHERE', $sql);
        self::assertStringContainsString('id = 5', $sql);
        // FROM must appear before WHERE
        self::assertLessThan(strpos($sql, 'WHERE'), strpos($sql, 'FROM'));
    }

    public function testDeleteWithMultipleWhereConditions(): void
    {
        $q   = $this->makeQuery()
            ->delete('users')
            ->where('id = 5')
            ->where('active = 0');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('id = 5', $sql);
        self::assertStringContainsString('active = 0', $sql);
    }

    public function testDeleteWithJoin(): void
    {
        $q   = $this->makeQuery()
            ->delete('users')
            ->innerJoin('orders ON orders.user_id = users.id')
            ->where('orders.total = 0');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('INNER JOIN', $sql);
    }

    // =========================================================================
    // clear() — individual clause clearing
    // =========================================================================

    public function testClearInsertResetsInsertClause(): void
    {
        $q = $this->makeQuery()->insert('users');
        $q->clear('insert');

        self::assertNull($q->insert);
        self::assertNull($q->type);
        self::assertFalse((bool) $q->autoIncrementField);
    }

    public function testClearUpdateResetsUpdateClause(): void
    {
        $q = $this->makeQuery()->update('users');
        $q->clear('update');

        self::assertNull($q->update);
        self::assertNull($q->type);
    }

    public function testClearDeleteResetsDeleteClause(): void
    {
        $q = $this->makeQuery()->delete('users');
        $q->clear('delete');

        self::assertNull($q->delete);
        self::assertNull($q->type);
    }

    public function testClearSetResetsSetClause(): void
    {
        $q = $this->makeQuery()->update('users')->set('name = \'Bob\'');
        $q->clear('set');

        self::assertNull($q->set);
        // type is NOT reset when clearing only 'set'
        self::assertSame('update', $q->type);
    }

    public function testClearColumnsResetsColumnsClause(): void
    {
        $q = $this->makeQuery()->insert('users')->columns('id, name');
        $q->clear('columns');

        self::assertNull($q->columns);
    }

    public function testClearValuesResetsValuesClause(): void
    {
        $q = $this->makeQuery()->insert('users')->values('1, \'Alice\'');
        $q->clear('values');

        self::assertNull($q->values);
    }

    public function testClearNullResetsEntireQuery(): void
    {
        $q = $this->makeQuery()
            ->insert('users')
            ->columns('id, name')
            ->values('1, \'Alice\'');

        $q->clear();

        self::assertNull($q->type);
        self::assertNull($q->insert);
        self::assertNull($q->columns);
        self::assertNull($q->values);
        self::assertNull($q->set);
        self::assertNull($q->where);
        self::assertNull($q->autoIncrementField);
    }

    public function testClearUnknownClauseResetsEntireQuery(): void
    {
        $q = $this->makeQuery()->update('users')->set('x = 1');

        // Any unrecognised clause name falls through to the default branch
        $q->clear('nonexistent_clause');

        self::assertNull($q->type);
        self::assertNull($q->update);
        self::assertNull($q->set);
    }

    public function testClearDeleteFromQueryAllowsReuse(): void
    {
        $q = $this->makeQuery()
            ->delete('users')
            ->where('id = 1');

        $q->clear();

        $q->insert('orders')->columns('id')->values('99');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('INSERT INTO', $sql);
        self::assertStringNotContainsString('DELETE', $sql);
    }

    // =========================================================================
    // Chaining smoke-tests
    // =========================================================================

    public function testMethodChainingReturnsQueryInstance(): void
    {
        $q = $this->makeQuery();

        self::assertInstanceOf(Sqlite::class, $q->insert('users'));
        self::assertInstanceOf(Sqlite::class, $q->columns('id'));
        self::assertInstanceOf(Sqlite::class, $q->values('1'));

        $q2 = $this->makeQuery();
        self::assertInstanceOf(Sqlite::class, $q2->update('users'));
        self::assertInstanceOf(Sqlite::class, $q2->set('x = 1'));

        $q3 = $this->makeQuery();
        self::assertInstanceOf(Sqlite::class, $q3->delete('users'));
    }

    // =========================================================================
    // Edge-cases and interactions
    // =========================================================================

    public function testInsertTypeIsReplacedBySubsequentInsertCall(): void
    {
        $q = $this->makeQuery()->insert('users');
        $q->insert('orders');

        self::assertSame('insert', $q->type);
        $sql = self::normalise((string) $q);
        // The second insert() call re-creates the element; only 'orders' remains
        self::assertStringContainsString('orders', $sql);
    }

    public function testUpdateWithoutSetProducesIncompleteButNonCrashingSql(): void
    {
        // No SET clause; __toString should not throw, even if the SQL is invalid
        $q = $this->makeQuery()->update('users');

        // Should not throw:
        $sql = self::normalise((string) $q);
        self::assertStringContainsString('UPDATE', $sql);
    }

    public function testDeleteWithExplicitFromAndSeparateFrom(): void
    {
        // delete() with a table sets from() internally; calling from() again
        // should accumulate a second table in the FROM clause.
        $q   = $this->makeQuery()->delete('users')->from('orders');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('users', $sql);
        self::assertStringContainsString('orders', $sql);
    }

    public function testReplaceColumnsAccumulateAcrossCalls(): void
    {
        $q = $this->makeQuery()
            ->replace('items')
            ->columns('id')
            ->columns(['name', 'qty'])
            ->values('1, \'Widget\', 10');
        $sql = self::normalise((string) $q);

        self::assertStringContainsString('id', $sql);
        self::assertStringContainsString('name', $sql);
        self::assertStringContainsString('qty', $sql);
    }

    public function testClearReplaceResetsReplaceClause(): void
    {
        $q = $this->makeQuery()->replace('items');
        $q->clear('replace');

        self::assertNull($q->replace);
        self::assertNull($q->type);
        self::assertFalse((bool) $q->autoIncrementField);
    }

    /** Clearing a single clause does not disturb unrelated clauses. */
    public function testClearColumnsDoesNotClearValues(): void
    {
        $q = $this->makeQuery()
            ->insert('users')
            ->columns('id, name')
            ->values('1, \'Alice\'');

        $q->clear('columns');

        self::assertNull($q->columns);
        self::assertNotNull($q->values);
    }

    public function testClearValuesDoesNotClearColumns(): void
    {
        $q = $this->makeQuery()
            ->insert('users')
            ->columns('id, name')
            ->values('1, \'Alice\'');

        $q->clear('values');

        self::assertNull($q->values);
        self::assertNotNull($q->columns);
    }
}
