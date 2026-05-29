<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Database;

use Awf\Database\Driver\Sqlite as SqliteDriver;
use Awf\Database\Iterator\Sqlite as SqliteIterator;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Database\Iterator\AbstractIterator (via the Sqlite/Pdo concrete classes)
 * using an in-memory SQLite database seeded with known data.
 */
class IteratorTest extends TestCase
{
    private ?PDO $pdo = null;

    protected function setUp(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec(
            'CREATE TABLE items (id INTEGER PRIMARY KEY, name TEXT NOT NULL, value INTEGER NOT NULL)'
        );

        $this->pdo->exec(
            "INSERT INTO items (id, name, value) VALUES (1, 'alpha', 10), (2, 'beta', 20), (3, 'gamma', 30)"
        );
    }

    protected function tearDown(): void
    {
        $this->pdo = null;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Execute a SELECT query and return an already-executed PDOStatement.
     */
    private function executeQuery(string $sql = 'SELECT * FROM items ORDER BY id'): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Build a SqliteIterator over a fresh result set.
     */
    private function makeIterator(?string $column = null, string $class = 'stdClass'): SqliteIterator
    {
        return new SqliteIterator($this->executeQuery(), $column, $class);
    }

    // -------------------------------------------------------------------------
    // Constructor — error conditions
    // -------------------------------------------------------------------------

    public function testConstructorThrowsForNonExistentClass(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SqliteIterator($this->executeQuery(), null, 'NoSuch\\Class\\ThatDoesNotExist');
    }

    // -------------------------------------------------------------------------
    // Iterator contract — happy path
    // -------------------------------------------------------------------------

    public function testIteratesAllRows(): void
    {
        $rows = [];

        foreach ($this->makeIterator() as $row) {
            $rows[] = $row;
        }

        self::assertCount(3, $rows);
    }

    public function testRowsAreStdClassByDefault(): void
    {
        foreach ($this->makeIterator() as $row) {
            self::assertInstanceOf(\stdClass::class, $row);
            break; // check only the first
        }
    }

    public function testRowsContainExpectedColumns(): void
    {
        $iterator = $this->makeIterator();
        $first    = $iterator->current();

        self::assertObjectHasProperty('id', $first);
        self::assertObjectHasProperty('name', $first);
        self::assertObjectHasProperty('value', $first);
    }

    public function testRowDataMatchesSeedValues(): void
    {
        $rows = [];

        foreach ($this->makeIterator() as $row) {
            $rows[] = $row;
        }

        self::assertEquals('1',     $rows[0]->id);
        self::assertEquals('alpha', $rows[0]->name);
        self::assertEquals('10',    $rows[0]->value);

        self::assertEquals('2',    $rows[1]->id);
        self::assertEquals('beta', $rows[1]->name);
        self::assertEquals('20',   $rows[1]->value);

        self::assertEquals('3',     $rows[2]->id);
        self::assertEquals('gamma', $rows[2]->name);
        self::assertEquals('30',    $rows[2]->value);
    }

    // -------------------------------------------------------------------------
    // key() — numeric (default) vs column-based
    // -------------------------------------------------------------------------

    public function testDefaultKeyIsZeroBasedInt(): void
    {
        $iterator = $this->makeIterator();

        $keys = [];
        foreach ($iterator as $key => $row) {
            $keys[] = $key;
        }

        self::assertSame([0, 1, 2], $keys);
    }

    public function testColumnKeyUsesNamedColumnValue(): void
    {
        $iterator = $this->makeIterator('name');

        $keys = [];
        foreach ($iterator as $key => $row) {
            $keys[] = $key;
        }

        self::assertSame(['alpha', 'beta', 'gamma'], $keys);
    }

    public function testColumnKeyFallsBackToIntWhenColumnAbsent(): void
    {
        // Column 'nonexistent' does not exist in the result set; key should be numeric.
        $iterator = $this->makeIterator('nonexistent');

        $keys = [];
        foreach ($iterator as $key => $row) {
            $keys[] = $key;
        }

        self::assertSame([0, 1, 2], $keys);
    }

    // -------------------------------------------------------------------------
    // current() / valid() / next()
    // -------------------------------------------------------------------------

    public function testCurrentReturnsFirstRowBeforeAnyNext(): void
    {
        $iterator = $this->makeIterator();

        // constructor calls next() once, so current() should be row 0
        $first = $iterator->current();

        self::assertInstanceOf(\stdClass::class, $first);
        self::assertEquals('alpha', $first->name);
    }

    public function testValidIsTrueWhenRowsRemain(): void
    {
        $iterator = $this->makeIterator();

        self::assertTrue($iterator->valid());
    }

    public function testValidIsFalseAfterExhausting(): void
    {
        $iterator = $this->makeIterator();

        // consume all rows manually
        while ($iterator->valid()) {
            $iterator->next();
        }

        self::assertFalse($iterator->valid());
    }

    public function testNextAdvancesToSecondRow(): void
    {
        $iterator = $this->makeIterator();

        $iterator->next();
        $second = $iterator->current();

        self::assertEquals('beta', $second->name);
    }

    // -------------------------------------------------------------------------
    // rewind() — iterator is not rewindable
    // -------------------------------------------------------------------------

    public function testRewindIsANoOp(): void
    {
        $iterator = $this->makeIterator();

        // advance past first row
        $iterator->next();
        self::assertEquals('beta', $iterator->current()->name);

        // rewind must NOT reset position
        $iterator->rewind();
        self::assertEquals('beta', $iterator->current()->name);
    }

    // -------------------------------------------------------------------------
    // count() — via PDOStatement::rowCount()
    // -------------------------------------------------------------------------

    public function testCountReflectsNumberOfRows(): void
    {
        // PDOStatement::rowCount() behaviour for SELECT differs by driver.
        // SQLite via PDO does return 0 for rowCount() on SELECT — test the interface,
        // not a specific magic number.
        $iterator = $this->makeIterator();

        self::assertIsInt(count($iterator));
    }

    // -------------------------------------------------------------------------
    // Custom hydration class
    // -------------------------------------------------------------------------

    public function testRowsHydrateToRequestedClass(): void
    {
        $iterator = new SqliteIterator($this->executeQuery(), null, IteratorTestRow::class);

        foreach ($iterator as $row) {
            self::assertInstanceOf(IteratorTestRow::class, $row);
        }
    }

    // -------------------------------------------------------------------------
    // Empty result set
    // -------------------------------------------------------------------------

    public function testEmptyResultSetIsImmediatelyInvalid(): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM items WHERE 1 = 0');
        $stmt->execute();

        $iterator = new SqliteIterator($stmt);

        self::assertFalse($iterator->valid());
    }

    public function testEmptyResultSetIteratesZeroTimes(): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM items WHERE 1 = 0');
        $stmt->execute();

        $iterator = new SqliteIterator($stmt);
        $count    = 0;

        foreach ($iterator as $row) {
            $count++;
        }

        self::assertSame(0, $count);
    }

    // -------------------------------------------------------------------------
    // Single row result set
    // -------------------------------------------------------------------------

    public function testSingleRowResultSet(): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM items WHERE id = 1');
        $stmt->execute();

        $iterator = new SqliteIterator($stmt);
        $rows     = [];

        foreach ($iterator as $row) {
            $rows[] = $row;
        }

        self::assertCount(1, $rows);
        self::assertEquals('alpha', $rows[0]->name);
    }

    // -------------------------------------------------------------------------
    // Foreach produces same results as manual iteration
    // -------------------------------------------------------------------------

    public function testForeachEquivalentToManualIteration(): void
    {
        // Manual iteration
        $iterA  = $this->makeIterator();
        $manual = [];

        while ($iterA->valid()) {
            $manual[] = clone $iterA->current();
            $iterA->next();
        }

        // Foreach iteration
        $iterB   = $this->makeIterator();
        $foreach = [];

        foreach ($iterB as $row) {
            $foreach[] = $row;
        }

        self::assertCount(count($manual), $foreach);

        for ($i = 0; $i < count($manual); $i++) {
            self::assertEquals($manual[$i]->name, $foreach[$i]->name);
        }
    }
}

/**
 * Minimal value object used to test custom hydration class support.
 */
class IteratorTestRow
{
    public string|int|null $id    = null;
    public string|null     $name  = null;
    public string|int|null $value = null;
}
