<?php

/**
 * @package   awf
 * @copyright Copyright (c) 2024-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Database;

use Awf\Database\Driver\Postgresql;
use Awf\Tests\Integration\AbstractIntegrationTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

/**
 * Real-server integration tests for the native PostgreSQL database driver.
 *
 * All tests are SKIPPED unless the following environment variables are set:
 *
 *   AWF_TEST_PG_DSN   DSN in the form "host=127.0.0.1 port=5432 dbname=awf_test"
 *                     OR a PDO-style "pgsql:host=127.0.0.1;port=5432;dbname=awf_test"
 *                     (both formats are accepted and normalised internally).
 *   AWF_TEST_PG_USER  Database username
 *   AWF_TEST_PG_PASS  Database password (may be empty)
 *
 * The pg_connect PHP extension must also be available; otherwise every test is
 * skipped individually.
 *
 * Every test creates and destroys its own temporary table so the suite is
 * fully idempotent and can run against a shared database without side-effects.
 */
#[CoversClass(Postgresql::class)]
final class PostgresqlDriverTest extends AbstractIntegrationTestCase
{
    /** Temporary table name used throughout the suite. */
    private const TMP_TABLE = 'awf_postgresql_integration_test';

    private ?Postgresql $db = null;

    // -------------------------------------------------------------------------
    // Infrastructure
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        if (!Postgresql::isSupported()) {
            $this->markTestSkipped('The pg_connect PHP extension is not available.');
        }

        $conn     = $this->requirePostgresql();
        $this->db = $this->buildDriver($conn);

        // Ensure no stale table from a previous failed run exists.
        $this->dropTmpTable();

        $this->db->setQuery(
            'CREATE TABLE ' . $this->db->quoteName(self::TMP_TABLE) . ' (
                id    SERIAL       NOT NULL,
                label VARCHAR(191) NOT NULL,
                score DOUBLE PRECISION NOT NULL DEFAULT 0,
                PRIMARY KEY (id)
            )'
        );
        $this->db->execute();
    }

    protected function tearDown(): void
    {
        $this->dropTmpTable();

        try {
            $this->db?->disconnect();
        } catch (\Throwable) {
            // Ignore — connection may already be gone.
        }

        $this->db = null;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a Postgresql driver from the connection details returned by requirePostgresql().
     *
     * The env-var DSN may be in PDO form ("pgsql:host=...;port=...;dbname=...")
     * or in native libpq form ("host=... port=... dbname=...").  Both are
     * normalised to the individual host/port/dbname values expected by the
     * native pg_connect-based driver.
     *
     * @param array{dsn: string, user: string, pass: string} $conn
     */
    private function buildDriver(array $conn): Postgresql
    {
        $dsn  = $conn['dsn'];
        $user = $conn['user'];
        $pass = $conn['pass'];

        // Detect and strip the PDO "pgsql:" scheme prefix if present.
        $dsn = preg_replace('/^pgsql:/i', '', $dsn);

        // Determine the separator: PDO uses ";", libpq uses " ".
        $separator = str_contains($dsn, ';') ? ';' : ' ';

        $host   = '127.0.0.1';
        $port   = 5432;
        $dbname = '';

        foreach (explode($separator, $dsn) as $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            [$key, $val] = array_map('trim', explode('=', $segment, 2)) + ['', ''];

            switch (strtolower($key)) {
                case 'host':
                    $host = $val;
                    break;
                case 'port':
                    $port = (int) $val;
                    break;
                case 'dbname':
                case 'database':
                    $dbname = $val;
                    break;
            }
        }

        return new Postgresql([
            'driver'   => 'postgresql',
            'host'     => $host,
            'port'     => $port,
            'user'     => $user,
            'password' => $pass,
            'database' => $dbname,
            'prefix'   => 'tst_',
            'select'   => true,
        ]);
    }

    private function dropTmpTable(): void
    {
        try {
            $this->db?->setQuery('DROP TABLE IF EXISTS ' . $this->db->quoteName(self::TMP_TABLE));
            $this->db?->execute();
        } catch (\Throwable) {
            // Table may not exist — that is fine.
        }
    }

    /**
     * Insert a row and return the new auto-incremented id via RETURNING.
     */
    private function insertRow(string $label, float $score = 0.0): int
    {
        $this->db->setQuery(
            'INSERT INTO ' . $this->db->quoteName(self::TMP_TABLE)
            . ' (label, score) VALUES ('
            . $this->db->quote($label) . ', '
            . $score
            . ') RETURNING id'
        );
        $id = $this->db->loadResult();

        return (int) $id;
    }

    // -------------------------------------------------------------------------
    // Lifecycle / connection
    // -------------------------------------------------------------------------

    public function testConnectAndConnected(): void
    {
        $this->db->connect();

        self::assertTrue($this->db->connected(), 'connected() must return true after connect().');
    }

    public function testDisconnectMakesConnectedReturnFalse(): void
    {
        $this->db->connect();
        self::assertTrue($this->db->connected());

        $this->db->disconnect();

        self::assertFalse($this->db->connected(), 'connected() must return false after disconnect().');
    }

    public function testDriverNameIsPostgresql(): void
    {
        self::assertSame('postgresql', $this->db->name);
    }

    public function testDbTechIsPostgresql(): void
    {
        self::assertSame('postgresql', Postgresql::$dbtech);
    }

    public function testIsSupportedReturnsTrue(): void
    {
        self::assertTrue(Postgresql::isSupported());
    }

    public function testGetVersionReturnsNonEmptyString(): void
    {
        $version = $this->db->getVersion();

        self::assertIsString($version);
        self::assertNotEmpty($version);
    }

    // -------------------------------------------------------------------------
    // Table introspection
    // -------------------------------------------------------------------------

    public function testGetTableListContainsTmpTable(): void
    {
        $tables = $this->db->getTableList();

        self::assertIsArray($tables);
        self::assertContains(self::TMP_TABLE, $tables, 'Temp table must appear in getTableList().');
    }

    public function testGetTableColumnsTypeOnlyReturnsExpectedColumns(): void
    {
        $columns = $this->db->getTableColumns(self::TMP_TABLE, true);

        self::assertArrayHasKey('id', $columns);
        self::assertArrayHasKey('label', $columns);
        self::assertArrayHasKey('score', $columns);
    }

    public function testGetTableColumnsFullReturnsObjects(): void
    {
        $columns = $this->db->getTableColumns(self::TMP_TABLE, false);

        self::assertNotEmpty($columns);
        foreach ($columns as $col) {
            self::assertIsObject($col);
        }
    }

    public function testGetTableColumnsFullHasExpectedProperties(): void
    {
        $columns = $this->db->getTableColumns(self::TMP_TABLE, false);

        self::assertArrayHasKey('id', $columns);
        $idCol = $columns['id'];
        self::assertObjectHasProperty('column_name', $idCol);
        self::assertObjectHasProperty('type', $idCol);
    }

    public function testGetTableKeysReturnsArrayForExistingTable(): void
    {
        $keys = $this->db->getTableKeys(self::TMP_TABLE);

        self::assertIsArray($keys);
        self::assertNotEmpty($keys, 'getTableKeys() must return at least one index entry for a table with a primary key.');
    }

    public function testGetTableKeysReturnsFalseForNonExistentTable(): void
    {
        $result = $this->db->getTableKeys('this_table_does_not_exist_xyz');

        self::assertFalse($result, 'getTableKeys() must return false when the table does not exist.');
    }

    public function testGetTableCreateReturnsEmptyString(): void
    {
        // PostgreSQL does not support SHOW CREATE TABLE — the driver returns ''.
        $result = $this->db->getTableCreate([self::TMP_TABLE]);

        self::assertSame('', $result, 'getTableCreate() must return an empty string for PostgreSQL.');
    }

    public function testGetCollationReturnsNonEmptyString(): void
    {
        $collation = $this->db->getCollation();

        self::assertIsString($collation);
        self::assertNotEmpty($collation);
    }

    public function testGetTableSequencesReturnsArrayForTableWithSerial(): void
    {
        $sequences = $this->db->getTableSequences(self::TMP_TABLE);

        // SERIAL columns always generate a sequence; we must get at least one.
        self::assertIsArray($sequences);
        self::assertNotEmpty($sequences, 'A SERIAL column must produce at least one sequence.');
    }

    public function testGetTableSequencesReturnsFalseForNonExistentTable(): void
    {
        $result = $this->db->getTableSequences('nonexistent_table_xyz');

        self::assertFalse($result, 'getTableSequences() must return false when the table does not exist.');
    }

    public function testShowTablesAlsoContainsTmpTable(): void
    {
        $tables = $this->db->showTables();

        self::assertIsArray($tables);
        self::assertContains(self::TMP_TABLE, $tables);
    }

    // -------------------------------------------------------------------------
    // CRUD — INSERT / SELECT
    // -------------------------------------------------------------------------

    public function testInsertAndLoadResult(): void
    {
        $id = $this->insertRow('hello', 1.5);

        self::assertGreaterThan(0, $id, 'RETURNING id must yield a positive integer after INSERT.');

        $this->db->setQuery(
            'SELECT label FROM ' . $this->db->quoteName(self::TMP_TABLE)
            . ' WHERE id = ' . (int) $id
        );
        $label = $this->db->loadResult();

        self::assertSame('hello', $label);
    }

    public function testInsertMultipleRowsAndLoadColumn(): void
    {
        $this->insertRow('alpha', 1.0);
        $this->insertRow('beta', 2.0);
        $this->insertRow('gamma', 3.0);

        $this->db->setQuery(
            'SELECT label FROM ' . $this->db->quoteName(self::TMP_TABLE)
            . ' ORDER BY id ASC'
        );
        $labels = $this->db->loadColumn();

        self::assertSame(['alpha', 'beta', 'gamma'], $labels);
    }

    public function testLoadAssocReturnsSingleRow(): void
    {
        $this->insertRow('test-assoc', 9.9);

        $this->db->setQuery(
            'SELECT id, label, score FROM ' . $this->db->quoteName(self::TMP_TABLE)
            . ' LIMIT 1'
        );
        $row = $this->db->loadAssoc();

        self::assertIsArray($row);
        self::assertArrayHasKey('id', $row);
        self::assertArrayHasKey('label', $row);
        self::assertArrayHasKey('score', $row);
        self::assertSame('test-assoc', $row['label']);
    }

    public function testLoadAssocListReturnsAllRows(): void
    {
        $this->insertRow('r1');
        $this->insertRow('r2');

        $this->db->setQuery(
            'SELECT label FROM ' . $this->db->quoteName(self::TMP_TABLE)
            . ' ORDER BY id ASC'
        );
        $rows = $this->db->loadAssocList();

        self::assertCount(2, $rows);
        self::assertSame('r1', $rows[0]['label']);
        self::assertSame('r2', $rows[1]['label']);
    }

    public function testLoadObjectReturnsSingleObject(): void
    {
        $this->insertRow('obj-row', 7.77);

        $this->db->setQuery(
            'SELECT id, label, score FROM ' . $this->db->quoteName(self::TMP_TABLE)
            . ' LIMIT 1'
        );
        $obj = $this->db->loadObject();

        self::assertIsObject($obj);
        self::assertSame('obj-row', $obj->label);
    }

    public function testLoadObjectListReturnsAllRows(): void
    {
        $this->insertRow('o1');
        $this->insertRow('o2');

        $this->db->setQuery(
            'SELECT label FROM ' . $this->db->quoteName(self::TMP_TABLE)
            . ' ORDER BY id ASC'
        );
        $rows = $this->db->loadObjectList();

        self::assertCount(2, $rows);
        self::assertSame('o1', $rows[0]->label);
        self::assertSame('o2', $rows[1]->label);
    }

    public function testLoadRowReturnsIndexedArray(): void
    {
        $this->insertRow('row-test', 5.5);

        $this->db->setQuery(
            'SELECT id, label FROM ' . $this->db->quoteName(self::TMP_TABLE)
            . ' LIMIT 1'
        );
        $row = $this->db->loadRow();

        self::assertIsArray($row);
        self::assertArrayHasKey(0, $row); // id
        self::assertArrayHasKey(1, $row); // label
        self::assertSame('row-test', $row[1]);
    }

    public function testGetAffectedRowsAfterUpdate(): void
    {
        $this->insertRow('before', 0.0);
        $this->insertRow('before', 0.0);

        $this->db->setQuery(
            'UPDATE ' . $this->db->quoteName(self::TMP_TABLE)
            . " SET score = 99.9 WHERE label = 'before'"
        );
        $this->db->execute();

        self::assertSame(2, $this->db->getAffectedRows());
    }

    // -------------------------------------------------------------------------
    // Transactions — commit
    // -------------------------------------------------------------------------

    public function testTransactionCommitPersistsData(): void
    {
        $this->db->transactionStart();
        $id = $this->insertRow('committed', 1.0);
        $this->db->transactionCommit();

        $this->db->setQuery(
            'SELECT label FROM ' . $this->db->quoteName(self::TMP_TABLE)
            . ' WHERE id = ' . (int) $id
        );
        $label = $this->db->loadResult();

        self::assertSame('committed', $label, 'Committed row must be readable after commit.');
    }

    // -------------------------------------------------------------------------
    // Transactions — rollback
    // -------------------------------------------------------------------------

    public function testTransactionRollbackDiscardsData(): void
    {
        $this->db->transactionStart();
        $id = $this->insertRow('to-be-rolled-back', 2.0);
        $this->db->transactionRollback();

        $this->db->setQuery(
            'SELECT COUNT(*) FROM ' . $this->db->quoteName(self::TMP_TABLE)
            . ' WHERE id = ' . (int) $id
        );
        $count = (int) $this->db->loadResult();

        self::assertSame(0, $count, 'Rolled-back row must not be present in the table.');
    }

    // -------------------------------------------------------------------------
    // Savepoints
    // -------------------------------------------------------------------------

    public function testSavepointRollbackToSavepointDiscardsOnlySubsequentChanges(): void
    {
        $this->db->transactionStart();
        $id1 = $this->insertRow('before-savepoint', 1.0);
        $this->db->transactionSavepoint('sp1');
        $this->insertRow('after-savepoint', 2.0);
        $this->db->transactionRollback('sp1');
        $this->db->releaseTransactionSavepoint('sp1');
        $this->db->transactionCommit();

        // Row before the savepoint must exist.
        $this->db->setQuery(
            'SELECT COUNT(*) FROM ' . $this->db->quoteName(self::TMP_TABLE)
            . ' WHERE id = ' . (int) $id1
        );
        self::assertSame(1, (int) $this->db->loadResult(), 'Row before savepoint must be preserved.');

        // The second row (after savepoint) must have been rolled back.
        $this->db->setQuery(
            'SELECT COUNT(*) FROM ' . $this->db->quoteName(self::TMP_TABLE)
            . " WHERE label = 'after-savepoint'"
        );
        self::assertSame(0, (int) $this->db->loadResult(), 'Row after savepoint must be discarded.');
    }

    // -------------------------------------------------------------------------
    // dropTable()
    // -------------------------------------------------------------------------

    public function testDropTableWithIfExistsRemovesTable(): void
    {
        self::assertContains(self::TMP_TABLE, $this->db->getTableList());

        $this->db->dropTable(self::TMP_TABLE, true);

        self::assertNotContains(self::TMP_TABLE, $this->db->getTableList());
    }

    public function testDropTableWithIfExistsTrueOnMissingTableDoesNotThrow(): void
    {
        $this->db->dropTable(self::TMP_TABLE, true); // Drop existing table.

        // Dropping the (now non-existent) table with IF EXISTS must not throw.
        $this->db->dropTable(self::TMP_TABLE, true);

        // If we get here without an exception the test passes.
        self::assertTrue(true);
    }

    public function testDropTableWithIfExistsFalseOnMissingTableThrows(): void
    {
        $this->db->dropTable(self::TMP_TABLE, true); // Remove the table first.

        $this->expectException(RuntimeException::class);

        // Without IF EXISTS the driver must throw when the table is absent.
        $this->db->dropTable(self::TMP_TABLE, false);
    }

    // -------------------------------------------------------------------------
    // renameTable()
    // -------------------------------------------------------------------------

    public function testRenameTableMakesOldNameDisappear(): void
    {
        $newName = self::TMP_TABLE . '_renamed';
        $this->db->renameTable(self::TMP_TABLE, $newName);

        try {
            self::assertNotContains(self::TMP_TABLE, $this->db->getTableList());
        } finally {
            $this->db->setQuery('DROP TABLE IF EXISTS ' . $this->db->quoteName($newName));
            $this->db->execute();
        }
    }

    public function testRenameTableMakesNewNameAppear(): void
    {
        $newName = self::TMP_TABLE . '_renamed';
        $this->db->renameTable(self::TMP_TABLE, $newName);

        try {
            self::assertContains($newName, $this->db->getTableList());
        } finally {
            $this->db->setQuery('DROP TABLE IF EXISTS ' . $this->db->quoteName($newName));
            $this->db->execute();
        }
    }

    public function testRenameTablePreservesRowData(): void
    {
        $this->insertRow('preserve-me', 42.0);

        $newName = self::TMP_TABLE . '_renamed';
        $this->db->renameTable(self::TMP_TABLE, $newName);

        try {
            $this->db->setQuery(
                'SELECT label FROM ' . $this->db->quoteName($newName) . ' LIMIT 1'
            );
            $label = $this->db->loadResult();

            self::assertSame('preserve-me', $label, 'Row data must survive a table rename.');
        } finally {
            $this->db->setQuery('DROP TABLE IF EXISTS ' . $this->db->quoteName($newName));
            $this->db->execute();
        }
    }

    public function testRenameTableOnNonExistentTableThrows(): void
    {
        $this->expectException(RuntimeException::class);

        $this->db->renameTable('nonexistent_table_xyz', 'whatever_new_name');
    }

    // -------------------------------------------------------------------------
    // Prefix replacement
    // -------------------------------------------------------------------------

    public function testReplacePrefixSubstitutesPlaceholder(): void
    {
        $sql = $this->db->replacePrefix('SELECT * FROM #__mytable');

        // The prefix configured in buildDriver() is 'tst_'.
        self::assertStringContainsString('tst_mytable', $sql);
        self::assertStringNotContainsString('#__', $sql);
    }

    public function testReplacePrefixIgnoresLiteralsInsideSingleQuotes(): void
    {
        $sql = $this->db->replacePrefix("SELECT '#__literal' FROM #__mytable");

        // Placeholder inside quotes must NOT be replaced.
        self::assertStringContainsString("'#__literal'", $sql);
        // Placeholder outside quotes must be replaced.
        self::assertStringContainsString('tst_mytable', $sql);
    }

    // -------------------------------------------------------------------------
    // escape() / quoting
    // -------------------------------------------------------------------------

    public function testEscapeNullReturnsNullLiteral(): void
    {
        $result = $this->db->escape(null);

        self::assertSame('NULL', $result);
    }

    public function testEscapeExtraFlagEscapesWildcards(): void
    {
        $result = $this->db->escape('50%_off', true);

        self::assertStringContainsString('%', $result); // % character present
        self::assertStringContainsString('_', $result); // _ character present
        // Extra flag causes addcslashes with '%_', so backslash precedes them.
        self::assertMatchesRegularExpression('/\\\%/', $result);
        self::assertMatchesRegularExpression('/\\\\_/', $result);
    }

    public function testQuoteWrapsStringInSingleQuotes(): void
    {
        $result = $this->db->quote('hello');

        self::assertSame("'hello'", $result);
    }

    public function testQuoteNameWrapsInDoubleQuotes(): void
    {
        $result = $this->db->quoteName('my_table');

        // PostgreSQL uses double-quotes for identifiers.
        self::assertSame('"my_table"', $result);
    }

    // -------------------------------------------------------------------------
    // setUTF() / getRandom()
    // -------------------------------------------------------------------------

    public function testSetUtfReturnsZeroOnSuccess(): void
    {
        $this->db->connect();
        $result = $this->db->setUTF();

        // pg_set_client_encoding() returns 0 on success.
        self::assertSame(0, $result);
    }

    public function testGetRandomReturnsFloat(): void
    {
        $value = $this->db->getRandom();

        self::assertIsNumeric($value);
        self::assertGreaterThanOrEqual(0.0, (float) $value);
        self::assertLessThan(1.0, (float) $value);
    }

    // -------------------------------------------------------------------------
    // Table lock / unlock
    // -------------------------------------------------------------------------

    public function testLockTableStartsATransaction(): void
    {
        // lockTable() in PostgreSQL starts a transaction internally.
        $result = $this->db->lockTable(self::TMP_TABLE);

        // Commit the transaction opened by lockTable via unlockTables.
        $this->db->unlockTables();

        self::assertInstanceOf(Postgresql::class, $result);
        self::assertSame($this->db, $result);
    }

    public function testUnlockTablesReturnsSelf(): void
    {
        $this->db->lockTable(self::TMP_TABLE);
        $result = $this->db->unlockTables();

        self::assertInstanceOf(Postgresql::class, $result);
        self::assertSame($this->db, $result);
    }

    // -------------------------------------------------------------------------
    // Debug / query log
    // -------------------------------------------------------------------------

    public function testDebugModeLogsExecutedStatements(): void
    {
        $this->db->setDebug(true);

        $this->insertRow('log-me', 0.0);

        $this->db->setDebug(false);
        $log = $this->db->getLog();

        self::assertNotEmpty($log, 'Debug log must contain at least one statement.');

        $found = false;
        foreach ($log as $entry) {
            if (stripos((string) $entry, 'INSERT') !== false) {
                $found = true;
                break;
            }
        }
        self::assertTrue($found, 'Debug log must contain the INSERT statement.');
    }

    // -------------------------------------------------------------------------
    // Edge cases
    // -------------------------------------------------------------------------

    public function testExecuteInvalidSqlThrowsRuntimeException(): void
    {
        $this->expectException(RuntimeException::class);

        $this->db->setQuery('THIS IS NOT VALID SQL AT ALL');
        $this->db->execute();
    }

    public function testLoadResultOnEmptyResultSetReturnsNull(): void
    {
        $this->db->setQuery(
            'SELECT label FROM ' . $this->db->quoteName(self::TMP_TABLE)
            . ' WHERE 1 = 0'
        );
        $result = $this->db->loadResult();

        self::assertNull($result, 'loadResult() must return null when the result set is empty.');
    }

    public function testLoadAssocOnEmptyResultSetReturnsNull(): void
    {
        $this->db->setQuery(
            'SELECT * FROM ' . $this->db->quoteName(self::TMP_TABLE)
            . ' WHERE 1 = 0'
        );
        $result = $this->db->loadAssoc();

        self::assertNull($result, 'loadAssoc() must return null when the result set is empty.');
    }

    public function testInsertObjectPopulatesKeyProperty(): void
    {
        $obj        = new \stdClass();
        $obj->label = 'inserted-via-object';
        $obj->score = 3.14;

        $result = $this->db->insertObject(self::TMP_TABLE, $obj, 'id');

        self::assertTrue($result, 'insertObject() must return true on success.');
        self::assertGreaterThan(0, (int) $obj->id, 'insertObject() must populate the key property with the new id.');
    }
}
