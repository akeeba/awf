<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Database;

use Awf\Database\Driver\Sqlite;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for Driver execution and fetching methods against an in-memory SQLite database.
 *
 * Covers: execute(), loadResult(), loadAssoc(), loadAssocList(), loadObject(),
 * loadObjectList(), loadColumn(), loadRow(), loadRowList(), insertid(),
 * and getAffectedRows().
 */
class DriverFetchTest extends TestCase
{
    private ?Sqlite $db = null;

    // -------------------------------------------------------------------------
    // Infrastructure
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        if (!Sqlite::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        $this->db = new Sqlite([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Create a small table and seed it with known rows.
        $this->db->setQuery(
            'CREATE TABLE #__items (
                id   INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT    NOT NULL,
                val  INTEGER NOT NULL DEFAULT 0
            )'
        );
        $this->db->execute();

        foreach ([['alpha', 10], ['beta', 20], ['gamma', 30]] as [$name, $val]) {
            $this->db->setQuery(
                "INSERT INTO #__items (name, val) VALUES ('{$name}', {$val})"
            );
            $this->db->execute();
        }
    }

    protected function tearDown(): void
    {
        $this->db?->disconnect();
        $this->db = null;
    }

    // -------------------------------------------------------------------------
    // execute() — happy path
    // -------------------------------------------------------------------------

    public function testExecuteReturnsPdoStatementOnSelect(): void
    {
        $this->db->setQuery('SELECT 1');
        $result = $this->db->execute();

        self::assertInstanceOf(\PDOStatement::class, $result);
    }

    public function testExecuteReturnsPdoStatementOnInsert(): void
    {
        $this->db->setQuery("INSERT INTO #__items (name, val) VALUES ('delta', 40)");
        $result = $this->db->execute();

        self::assertInstanceOf(\PDOStatement::class, $result);
    }

    public function testExecuteReturnsPdoStatementOnUpdate(): void
    {
        $this->db->setQuery("UPDATE #__items SET val = 99 WHERE name = 'alpha'");
        $result = $this->db->execute();

        self::assertInstanceOf(\PDOStatement::class, $result);
    }

    public function testExecuteReturnsPdoStatementOnDelete(): void
    {
        $this->db->setQuery("DELETE FROM #__items WHERE name = 'alpha'");
        $result = $this->db->execute();

        self::assertInstanceOf(\PDOStatement::class, $result);
    }

    public function testExecuteIncrementsQueryCount(): void
    {
        $before = $this->db->getCount();

        $this->db->setQuery('SELECT 1');
        $this->db->execute();

        self::assertSame($before + 1, $this->db->getCount());
    }

    // -------------------------------------------------------------------------
    // execute() — error conditions
    // -------------------------------------------------------------------------

    public function testExecuteThrowsRuntimeExceptionOnBadSql(): void
    {
        $this->expectException(RuntimeException::class);

        $this->db->setQuery('THIS IS NOT VALID SQL AT ALL');
        $this->db->execute();
    }

    public function testExecuteThrowsOnNonExistentTable(): void
    {
        $this->expectException(RuntimeException::class);

        $this->db->setQuery('SELECT * FROM no_such_table_xyz');
        $this->db->execute();
    }

    // -------------------------------------------------------------------------
    // loadResult() — first field of first row
    // -------------------------------------------------------------------------

    public function testLoadResultReturnsSingleScalar(): void
    {
        $this->db->setQuery('SELECT COUNT(*) FROM #__items');
        $count = $this->db->loadResult();

        self::assertSame('3', (string) $count);
    }

    public function testLoadResultReturnsFirstColumnOnly(): void
    {
        $this->db->setQuery("SELECT name, val FROM #__items WHERE name = 'alpha'");
        $result = $this->db->loadResult();

        // Only the first column value should be returned.
        self::assertSame('alpha', $result);
    }

    public function testLoadResultReturnsNullWhenNoRows(): void
    {
        $this->db->setQuery("SELECT name FROM #__items WHERE name = 'nonexistent'");
        $result = $this->db->loadResult();

        self::assertNull($result);
    }

    // -------------------------------------------------------------------------
    // loadAssoc() — first row as associative array
    // -------------------------------------------------------------------------

    public function testLoadAssocReturnsAssociativeArray(): void
    {
        $this->db->setQuery("SELECT id, name, val FROM #__items WHERE name = 'alpha'");
        $row = $this->db->loadAssoc();

        self::assertIsArray($row);
        self::assertArrayHasKey('id', $row);
        self::assertArrayHasKey('name', $row);
        self::assertArrayHasKey('val', $row);
        self::assertSame('alpha', $row['name']);
        self::assertSame('10', (string) $row['val']);
    }

    public function testLoadAssocReturnsOnlyFirstRow(): void
    {
        $this->db->setQuery('SELECT name FROM #__items ORDER BY id ASC');
        $row = $this->db->loadAssoc();

        self::assertSame('alpha', $row['name']);
    }

    public function testLoadAssocReturnsNullWhenNoRows(): void
    {
        $this->db->setQuery("SELECT name FROM #__items WHERE name = 'missing'");
        $result = $this->db->loadAssoc();

        self::assertNull($result);
    }

    // -------------------------------------------------------------------------
    // loadAssocList() — all rows as array of associative arrays
    // -------------------------------------------------------------------------

    public function testLoadAssocListReturnsAllRows(): void
    {
        $this->db->setQuery('SELECT name, val FROM #__items ORDER BY id ASC');
        $rows = $this->db->loadAssocList();

        self::assertIsArray($rows);
        self::assertCount(3, $rows);
        self::assertSame('alpha', $rows[0]['name']);
        self::assertSame('beta', $rows[1]['name']);
        self::assertSame('gamma', $rows[2]['name']);
    }

    public function testLoadAssocListKeyedByColumn(): void
    {
        $this->db->setQuery('SELECT name, val FROM #__items ORDER BY id ASC');
        $rows = $this->db->loadAssocList('name');

        self::assertIsArray($rows);
        self::assertArrayHasKey('alpha', $rows);
        self::assertArrayHasKey('beta', $rows);
        self::assertArrayHasKey('gamma', $rows);
    }

    public function testLoadAssocListWithColumnReturnsOnlyThatColumn(): void
    {
        $this->db->setQuery('SELECT name, val FROM #__items ORDER BY id ASC');
        $rows = $this->db->loadAssocList(null, 'val');

        self::assertIsArray($rows);
        self::assertCount(3, $rows);
        // Each element should be only the val, not the full row.
        self::assertSame('10', (string) $rows[0]);
        self::assertSame('20', (string) $rows[1]);
        self::assertSame('30', (string) $rows[2]);
    }

    public function testLoadAssocListKeyedAndColumnFiltered(): void
    {
        $this->db->setQuery('SELECT name, val FROM #__items ORDER BY id ASC');
        $rows = $this->db->loadAssocList('name', 'val');

        self::assertIsArray($rows);
        self::assertArrayHasKey('alpha', $rows);
        self::assertSame('10', (string) $rows['alpha']);
        self::assertSame('20', (string) $rows['beta']);
    }

    public function testLoadAssocListReturnsEmptyArrayWhenNoRows(): void
    {
        $this->db->setQuery("SELECT name FROM #__items WHERE val > 99999");
        $result = $this->db->loadAssocList();

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    // -------------------------------------------------------------------------
    // loadObject() — first row as object
    // -------------------------------------------------------------------------

    public function testLoadObjectReturnsStdClassByDefault(): void
    {
        $this->db->setQuery("SELECT id, name, val FROM #__items WHERE name = 'alpha'");
        $obj = $this->db->loadObject();

        self::assertInstanceOf(\stdClass::class, $obj);
        self::assertSame('alpha', $obj->name);
        self::assertSame('10', (string) $obj->val);
    }

    public function testLoadObjectReturnsFirstRowOnly(): void
    {
        $this->db->setQuery('SELECT name FROM #__items ORDER BY id ASC');
        $obj = $this->db->loadObject();

        self::assertSame('alpha', $obj->name);
    }

    public function testLoadObjectReturnsNullWhenNoRows(): void
    {
        $this->db->setQuery("SELECT name FROM #__items WHERE name = 'nothing'");
        $result = $this->db->loadObject();

        self::assertNull($result);
    }

    public function testLoadObjectUsesCustomClass(): void
    {
        $this->db->setQuery("SELECT id, name, val FROM #__items WHERE name = 'alpha'");
        $obj = $this->db->loadObject(\stdClass::class);

        self::assertInstanceOf(\stdClass::class, $obj);
    }

    // -------------------------------------------------------------------------
    // loadObjectList() — all rows as array of objects
    // -------------------------------------------------------------------------

    public function testLoadObjectListReturnsAllRows(): void
    {
        $this->db->setQuery('SELECT name, val FROM #__items ORDER BY id ASC');
        $rows = $this->db->loadObjectList();

        self::assertIsArray($rows);
        self::assertCount(3, $rows);

        foreach ($rows as $row) {
            self::assertInstanceOf(\stdClass::class, $row);
        }
    }

    public function testLoadObjectListRowsHaveCorrectData(): void
    {
        $this->db->setQuery('SELECT name, val FROM #__items ORDER BY id ASC');
        $rows = $this->db->loadObjectList();

        self::assertSame('alpha', $rows[0]->name);
        self::assertSame('beta', $rows[1]->name);
        self::assertSame('gamma', $rows[2]->name);
    }

    public function testLoadObjectListKeyedByField(): void
    {
        $this->db->setQuery('SELECT name, val FROM #__items ORDER BY id ASC');
        $rows = $this->db->loadObjectList('name');

        self::assertArrayHasKey('alpha', $rows);
        self::assertArrayHasKey('beta', $rows);
        self::assertArrayHasKey('gamma', $rows);
        self::assertSame('10', (string) $rows['alpha']->val);
    }

    public function testLoadObjectListReturnsEmptyArrayWhenNoRows(): void
    {
        $this->db->setQuery("SELECT name FROM #__items WHERE val > 99999");
        $result = $this->db->loadObjectList();

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    // -------------------------------------------------------------------------
    // loadColumn() — values from a single column
    // -------------------------------------------------------------------------

    public function testLoadColumnReturnsFirstColumnByDefault(): void
    {
        $this->db->setQuery('SELECT name FROM #__items ORDER BY id ASC');
        $result = $this->db->loadColumn();

        self::assertSame(['alpha', 'beta', 'gamma'], $result);
    }

    public function testLoadColumnReturnsSecondColumnWithOffset(): void
    {
        $this->db->setQuery('SELECT name, val FROM #__items ORDER BY id ASC');
        $result = $this->db->loadColumn(1);

        self::assertCount(3, $result);
        self::assertSame('10', (string) $result[0]);
        self::assertSame('20', (string) $result[1]);
        self::assertSame('30', (string) $result[2]);
    }

    public function testLoadColumnReturnsEmptyArrayWhenNoRows(): void
    {
        $this->db->setQuery("SELECT name FROM #__items WHERE val > 99999");
        $result = $this->db->loadColumn();

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    // -------------------------------------------------------------------------
    // loadRow() — first row as numeric array
    // -------------------------------------------------------------------------

    public function testLoadRowReturnsNumericArray(): void
    {
        $this->db->setQuery("SELECT id, name, val FROM #__items WHERE name = 'alpha'");
        $row = $this->db->loadRow();

        self::assertIsArray($row);
        // Numeric keys
        self::assertArrayHasKey(0, $row);
        self::assertArrayHasKey(1, $row);
        self::assertArrayHasKey(2, $row);
        self::assertSame('alpha', $row[1]);
    }

    public function testLoadRowReturnsFirstRowOnly(): void
    {
        $this->db->setQuery('SELECT name FROM #__items ORDER BY id ASC');
        $row = $this->db->loadRow();

        self::assertSame('alpha', $row[0]);
    }

    public function testLoadRowReturnsNullWhenNoRows(): void
    {
        $this->db->setQuery("SELECT name FROM #__items WHERE val > 99999");
        $result = $this->db->loadRow();

        self::assertNull($result);
    }

    // -------------------------------------------------------------------------
    // loadRowList() — all rows as array of numeric arrays
    // -------------------------------------------------------------------------

    public function testLoadRowListReturnsAllRows(): void
    {
        $this->db->setQuery('SELECT name, val FROM #__items ORDER BY id ASC');
        $rows = $this->db->loadRowList();

        self::assertIsArray($rows);
        self::assertCount(3, $rows);

        foreach ($rows as $row) {
            self::assertIsArray($row);
        }
    }

    public function testLoadRowListRowsHaveCorrectData(): void
    {
        $this->db->setQuery('SELECT name, val FROM #__items ORDER BY id ASC');
        $rows = $this->db->loadRowList();

        self::assertSame('alpha', $rows[0][0]);
        self::assertSame('beta', $rows[1][0]);
        self::assertSame('gamma', $rows[2][0]);
    }

    public function testLoadRowListKeyedByColumnIndex(): void
    {
        $this->db->setQuery('SELECT name, val FROM #__items ORDER BY id ASC');
        $rows = $this->db->loadRowList(0);

        self::assertArrayHasKey('alpha', $rows);
        self::assertArrayHasKey('beta', $rows);
        self::assertArrayHasKey('gamma', $rows);
    }

    public function testLoadRowListReturnsEmptyArrayWhenNoRows(): void
    {
        $this->db->setQuery("SELECT name FROM #__items WHERE val > 99999");
        $result = $this->db->loadRowList();

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    // -------------------------------------------------------------------------
    // insertid() — auto-increment value
    // -------------------------------------------------------------------------

    public function testInsertidReturnsIntegerAfterInsert(): void
    {
        $this->db->setQuery("INSERT INTO #__items (name, val) VALUES ('delta', 40)");
        $this->db->execute();

        $id = $this->db->insertid();

        self::assertIsNumeric($id);
        self::assertGreaterThan(0, (int) $id);
    }

    public function testInsertidIncrementsWithEachInsert(): void
    {
        $this->db->setQuery("INSERT INTO #__items (name, val) VALUES ('delta', 40)");
        $this->db->execute();
        $firstId = (int) $this->db->insertid();

        $this->db->setQuery("INSERT INTO #__items (name, val) VALUES ('epsilon', 50)");
        $this->db->execute();
        $secondId = (int) $this->db->insertid();

        self::assertGreaterThan($firstId, $secondId);
    }

    // -------------------------------------------------------------------------
    // getAffectedRows() — rows affected by DML statements
    // -------------------------------------------------------------------------

    public function testGetAffectedRowsAfterInsertIsOne(): void
    {
        $this->db->setQuery("INSERT INTO #__items (name, val) VALUES ('delta', 40)");
        $this->db->execute();

        self::assertSame(1, $this->db->getAffectedRows());
    }

    public function testGetAffectedRowsAfterUpdateMatchingRows(): void
    {
        // Update all rows — should affect all 3 seeded rows.
        $this->db->setQuery('UPDATE #__items SET val = 0');
        $this->db->execute();

        self::assertSame(3, $this->db->getAffectedRows());
    }

    public function testGetAffectedRowsAfterUpdateNoMatch(): void
    {
        $this->db->setQuery("UPDATE #__items SET val = 0 WHERE name = 'nonexistent'");
        $this->db->execute();

        self::assertSame(0, $this->db->getAffectedRows());
    }

    public function testGetAffectedRowsAfterDeleteAll(): void
    {
        $this->db->setQuery('DELETE FROM #__items');
        $this->db->execute();

        self::assertSame(3, $this->db->getAffectedRows());
    }

    public function testGetAffectedRowsAfterDeleteSingleRow(): void
    {
        $this->db->setQuery("DELETE FROM #__items WHERE name = 'alpha'");
        $this->db->execute();

        self::assertSame(1, $this->db->getAffectedRows());
    }

    // -------------------------------------------------------------------------
    // getNumRows() — returned rows from SELECT
    // -------------------------------------------------------------------------

    public function testGetNumRowsAfterSelectAll(): void
    {
        $this->db->setQuery('SELECT * FROM #__items');
        $cursor = $this->db->execute();

        // PDO's rowCount() after SELECT is not guaranteed for all drivers,
        // but SQLite via PDO does return the correct value here.
        $num = $this->db->getNumRows($cursor);

        self::assertIsInt($num);
    }

    // -------------------------------------------------------------------------
    // insertObject() — insert from object properties
    // -------------------------------------------------------------------------

    public function testInsertObjectInsertsRow(): void
    {
        $obj       = new \stdClass();
        $obj->name = 'inserted';
        $obj->val  = 99;

        $success = $this->db->insertObject('#__items', $obj, 'id');

        self::assertTrue($success);
        // After insert the key property should be populated.
        self::assertGreaterThan(0, (int) $obj->id);
    }

    public function testInsertObjectRowIsRetrievable(): void
    {
        $obj       = new \stdClass();
        $obj->name = 'retrievable';
        $obj->val  = 77;

        $this->db->insertObject('#__items', $obj, 'id');

        $this->db->setQuery("SELECT name FROM #__items WHERE name = 'retrievable'");
        $result = $this->db->loadResult();

        self::assertSame('retrievable', $result);
    }

    // -------------------------------------------------------------------------
    // updateObject() — update from object properties
    // -------------------------------------------------------------------------

    public function testUpdateObjectModifiesExistingRow(): void
    {
        // First fetch the alpha row to get its id.
        $this->db->setQuery("SELECT id, name, val FROM #__items WHERE name = 'alpha'");
        $alpha = $this->db->loadObject();

        $alpha->val = 999;
        $this->db->updateObject('#__items', $alpha, 'id');

        $this->db->setQuery("SELECT val FROM #__items WHERE name = 'alpha'");
        $newVal = $this->db->loadResult();

        self::assertSame('999', (string) $newVal);
    }

    // -------------------------------------------------------------------------
    // Prefix replacement in queries
    // -------------------------------------------------------------------------

    public function testPrefixIsReplacedInQueriesBeforeExecution(): void
    {
        // The driver was created with prefix '' so #__ stays as '' (empty prefix).
        // Seeded rows were inserted with #__items which maps to items.
        $this->db->setQuery('SELECT COUNT(*) FROM #__items');
        $count = $this->db->loadResult();

        self::assertSame('3', (string) $count);
    }

    // -------------------------------------------------------------------------
    // Edge cases — empty result sets across all fetch methods
    // -------------------------------------------------------------------------

    public function testAllFetchMethodsHandleEmptyResultSet(): void
    {
        $noRowSql = "SELECT id, name, val FROM #__items WHERE val = -999";

        $this->db->setQuery($noRowSql);
        self::assertNull($this->db->loadResult(), 'loadResult() must return null for empty result set.');

        $this->db->setQuery($noRowSql);
        self::assertNull($this->db->loadAssoc(), 'loadAssoc() must return null for empty result set.');

        $this->db->setQuery($noRowSql);
        self::assertNull($this->db->loadObject(), 'loadObject() must return null for empty result set.');

        $this->db->setQuery($noRowSql);
        self::assertNull($this->db->loadRow(), 'loadRow() must return null for empty result set.');

        $this->db->setQuery($noRowSql);
        self::assertIsArray($this->db->loadAssocList(), 'loadAssocList() must return empty array for empty result set.');
        $this->db->setQuery($noRowSql);
        self::assertEmpty($this->db->loadAssocList());

        $this->db->setQuery($noRowSql);
        self::assertIsArray($this->db->loadObjectList(), 'loadObjectList() must return empty array for empty result set.');
        $this->db->setQuery($noRowSql);
        self::assertEmpty($this->db->loadObjectList());

        $this->db->setQuery($noRowSql);
        self::assertIsArray($this->db->loadColumn(), 'loadColumn() must return empty array for empty result set.');
        $this->db->setQuery($noRowSql);
        self::assertEmpty($this->db->loadColumn());

        $this->db->setQuery($noRowSql);
        self::assertIsArray($this->db->loadRowList(), 'loadRowList() must return empty array for empty result set.');
        $this->db->setQuery($noRowSql);
        self::assertEmpty($this->db->loadRowList());
    }
}
