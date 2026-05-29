<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Database;

use Awf\Database\Driver\Mysqli;
use Awf\Tests\Integration\AbstractIntegrationTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

/**
 * Real-server integration tests for the MySQLi database driver.
 *
 * All tests are SKIPPED unless the following environment variables are set:
 *
 *   AWF_TEST_MYSQL_DSN   PDO DSN, e.g. "mysql:host=127.0.0.1;port=3306;dbname=awf_test"
 *   AWF_TEST_MYSQL_USER  Database username
 *   AWF_TEST_MYSQL_PASS  Database password (may be empty)
 *
 * Every test creates and destroys its own temporary tables so the suite is
 * fully idempotent and can run against a shared database without side-effects.
 */
#[CoversClass(Mysqli::class)]
final class MysqliDriverTest extends AbstractIntegrationTestCase
{
    /** Temporary table name (unique per run to tolerate aborted runs). */
    private const TMP_TABLE = 'awf_mysqli_integration_test';

    private ?Mysqli $db = null;

    // -------------------------------------------------------------------------
    // Infrastructure
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        if (!Mysqli::isSupported()) {
            $this->markTestSkipped('The mysqli extension is not available.');
        }

        $conn = $this->requireMysql();
        $this->db = $this->buildDriver($conn);

        // Make absolutely sure there is no leftover table from a previous failed run.
        $this->dropTmpTable();

        $this->db->setQuery(
            'CREATE TABLE ' . $this->db->quoteName(self::TMP_TABLE) . ' (
                id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
                label VARCHAR(191) NOT NULL,
                score DOUBLE       NOT NULL DEFAULT 0,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        $this->db->execute();
    }

    protected function tearDown(): void
    {
        $this->dropTmpTable();
        $this->db?->disconnect();
        $this->db = null;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a Mysqli driver from an associative array returned by requireMysql().
     *
     * The DSN format is "mysql:host=...;port=...;dbname=..." — we parse the
     * individual pieces so we can pass them to the native Mysqli driver which
     * does not accept PDO DSNs.
     *
     * @param array{dsn: string, user: string, pass: string} $conn
     */
    private function buildDriver(array $conn): Mysqli
    {
        $dsn  = $conn['dsn'];
        $user = $conn['user'];
        $pass = $conn['pass'];

        // Strip leading "mysql:" scheme if present.
        $dsn = preg_replace('/^mysql:/i', '', $dsn);

        $host   = '127.0.0.1';
        $port   = 3306;
        $dbname = '';

        foreach (explode(';', $dsn) as $segment) {
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

        return new Mysqli([
            'driver'   => 'mysqli',
            'host'     => $host . ':' . $port,
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
            $this->db?->dropTable(self::TMP_TABLE, true);
        } catch (\Throwable) {
            // Ignore — table might not exist yet.
        }
    }

    private function insertRow(string $label, float $score = 0.0): int
    {
        $this->db->setQuery(
            'INSERT INTO ' . $this->db->quoteName(self::TMP_TABLE)
            . ' (label, score) VALUES ('
            . $this->db->quote($label) . ', '
            . (float) $score
            . ')'
        );
        $this->db->execute();

        return (int) $this->db->insertid();
    }

    // -------------------------------------------------------------------------
    // Lifecycle / connection
    // -------------------------------------------------------------------------

    public function testConnectAndIsConnected(): void
    {
        $this->db->connect();

        self::assertTrue($this->db->connected(), 'Driver must report connected() = true after connect().');
    }

    public function testDisconnectMakesConnectedReturnFalse(): void
    {
        $this->db->connect();
        self::assertTrue($this->db->connected());

        $this->db->disconnect();

        self::assertFalse($this->db->connected(), 'Driver must report connected() = false after disconnect().');
    }

    public function testDriverNameIsMysqli(): void
    {
        self::assertSame('mysqli', $this->db->name);
    }

    public function testDbTechIsMySQL(): void
    {
        self::assertSame('mysql', Mysqli::$dbtech);
    }

    public function testIsSupportedReturnsTrue(): void
    {
        self::assertTrue(Mysqli::isSupported());
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

    public function testGetTableColumnsTypeOnlyStripsColumnWidths(): void
    {
        $columns = $this->db->getTableColumns(self::TMP_TABLE, true);

        // The type-only mode must strip numeric width specs such as "int(10)".
        foreach ($columns as $type) {
            self::assertDoesNotMatchRegularExpression('/\(\d+\)/', $type, 'Column type must not contain width specifier.');
        }
    }

    public function testGetTableColumnsFullReturnsObjects(): void
    {
        $columns = $this->db->getTableColumns(self::TMP_TABLE, false);

        self::assertNotEmpty($columns);
        foreach ($columns as $col) {
            self::assertIsObject($col);
        }
    }

    public function testGetTableKeysReturnsArrayWithPrimaryKey(): void
    {
        $keys = $this->db->getTableKeys(self::TMP_TABLE);

        self::assertIsArray($keys);
        self::assertNotEmpty($keys);

        $keyNames = array_map(fn($k) => $k->Key_name, $keys);
        self::assertContains('PRIMARY', $keyNames);
    }

    public function testGetTableCreateReturnsDdlStatement(): void
    {
        $result = $this->db->getTableCreate([self::TMP_TABLE]);

        self::assertIsArray($result);
        self::assertArrayHasKey(self::TMP_TABLE, $result);
        self::assertStringContainsStringIgnoringCase('CREATE TABLE', $result[self::TMP_TABLE]);
    }

    public function testGetCollationReturnsNonEmptyString(): void
    {
        $collation = $this->db->getCollation();

        // The temp table uses utf8mb4; collation must be a non-empty string.
        self::assertNotEmpty($collation);
        self::assertIsString($collation);
    }

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    public function testInsertAndLoadResult(): void
    {
        $id = $this->insertRow('hello', 1.5);

        self::assertGreaterThan(0, $id, 'insertid() must return a positive integer after INSERT.');

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
        self::assertSame('7.77', (string) $obj->score);
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

    public function testGetNumRowsAfterSelect(): void
    {
        $this->insertRow('n1');
        $this->insertRow('n2');
        $this->insertRow('n3');

        $this->db->setQuery(
            'SELECT id FROM ' . $this->db->quoteName(self::TMP_TABLE)
        );
        $this->db->execute();

        self::assertSame(3, $this->db->getNumRows());
    }

    // -------------------------------------------------------------------------
    // Transactions — commit
    // -------------------------------------------------------------------------

    public function testTransactionCommitPersistsData(): void
    {
        $this->db->transactionStart();
        $id = $this->insertRow('committed', 1.0);
        $this->db->transactionCommit();

        // Verify by querying outside any transaction.
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

        // The inserted row must not exist after rollback.
        $this->db->setQuery(
            'SELECT COUNT(*) FROM ' . $this->db->quoteName(self::TMP_TABLE)
            . ' WHERE id = ' . (int) $id
        );
        $count = (int) $this->db->loadResult();

        self::assertSame(0, $count, 'Rolled-back row must not be present in the table.');
    }

    // -------------------------------------------------------------------------
    // dropTable()
    // -------------------------------------------------------------------------

    public function testDropTableRemovesTable(): void
    {
        // The table was created in setUp(); verify it exists first.
        self::assertContains(self::TMP_TABLE, $this->db->getTableList());

        $result = $this->db->dropTable(self::TMP_TABLE, true);

        self::assertNotContains(self::TMP_TABLE, $this->db->getTableList());
        self::assertInstanceOf(Mysqli::class, $result);
    }

    public function testDropTableWithIfExistsTrueOnMissingTableDoesNotThrow(): void
    {
        $this->db->dropTable(self::TMP_TABLE, true); // Drop existing table.

        // Dropping the (now non-existent) table again with IF EXISTS must not throw.
        $result = $this->db->dropTable(self::TMP_TABLE, true);

        self::assertInstanceOf(Mysqli::class, $result);
    }

    public function testDropTableWithIfExistsFalseOnMissingTableThrows(): void
    {
        $this->db->dropTable(self::TMP_TABLE, true); // Remove the table first.

        $this->expectException(RuntimeException::class);

        // Without IF EXISTS the driver must throw when the table is gone.
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
            $this->db->dropTable($newName, true);
        }
    }

    public function testRenameTableMakesNewNameAppear(): void
    {
        $newName = self::TMP_TABLE . '_renamed';
        $this->db->renameTable(self::TMP_TABLE, $newName);

        try {
            self::assertContains($newName, $this->db->getTableList());
        } finally {
            $this->db->dropTable($newName, true);
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
            $this->db->dropTable($newName, true);
        }
    }

    public function testRenameTableReturnsSelf(): void
    {
        $newName = self::TMP_TABLE . '_renamed';
        $result  = $this->db->renameTable(self::TMP_TABLE, $newName);

        try {
            self::assertInstanceOf(Mysqli::class, $result);
            self::assertSame($this->db, $result);
        } finally {
            $this->db->dropTable($newName, true);
        }
    }

    // -------------------------------------------------------------------------
    // Prefix replacement
    // -------------------------------------------------------------------------

    public function testReplacePrefixSubstitutesPlaceholder(): void
    {
        $sql = $this->db->replacePrefix('SELECT * FROM #__mytable');

        // The prefix set in buildDriver() is 'tst_'.
        self::assertStringContainsString('tst_mytable', $sql);
        self::assertStringNotContainsString('#__', $sql);
    }

    public function testReplacePrefixIgnoresLiteralsInsideSingleQuotes(): void
    {
        $sql = $this->db->replacePrefix("SELECT '#__literal' FROM #__mytable");

        // The placeholder inside quotes must NOT be replaced.
        self::assertStringContainsString("'#__literal'", $sql);
        // The placeholder outside quotes must be replaced.
        self::assertStringContainsString('tst_mytable', $sql);
    }

    // -------------------------------------------------------------------------
    // escape() / quoting
    // -------------------------------------------------------------------------

    public function testEscapeStringEscapesSingleQuotes(): void
    {
        $result = $this->db->escape("it's");

        self::assertStringContainsString("\\'", $result);
    }

    public function testEscapeNullReturnsNullLiteral(): void
    {
        $result = $this->db->escape(null);

        self::assertSame('NULL', $result);
    }

    public function testEscapeExtraFlagEscapesWildcards(): void
    {
        $result = $this->db->escape('50%_off', true);

        self::assertStringContainsString('\\%', $result);
        self::assertStringContainsString('\\_', $result);
    }

    public function testQuoteWrapsStringInSingleQuotes(): void
    {
        $result = $this->db->quote('hello');

        self::assertSame("'hello'", $result);
    }

    public function testQuoteNameWrapsInBackticks(): void
    {
        $result = $this->db->quoteName('my_table');

        self::assertSame('`my_table`', $result);
    }

    // -------------------------------------------------------------------------
    // UTF-8 / UTF8MB4 handling
    // -------------------------------------------------------------------------

    public function testSupportsUtf8mb4ReturnsBoolean(): void
    {
        $result = $this->db->supportsUtf8mb4();

        self::assertIsBool($result);
    }

    public function testSetUTFReturnsTrueWhenConnectionIsOpen(): void
    {
        $this->db->connect();
        $result = $this->db->setUTF();

        self::assertTrue($result, 'setUTF() must return true when the connection is open.');
    }

    public function testInsertAndRetrieveUtf8mb4String(): void
    {
        // Insert a string containing 4-byte UTF-8 characters (emoji).
        $emoji = 'Hello 🌍 World 🎉';

        $this->db->setQuery(
            'INSERT INTO ' . $this->db->quoteName(self::TMP_TABLE)
            . ' (label, score) VALUES ('
            . $this->db->quote($emoji)
            . ', 0)'
        );
        $this->db->execute();

        $this->db->setQuery(
            'SELECT label FROM ' . $this->db->quoteName(self::TMP_TABLE)
            . ' LIMIT 1'
        );
        $retrieved = $this->db->loadResult();

        if ($this->db->supportsUtf8mb4()) {
            self::assertSame($emoji, $retrieved, 'Emoji string must survive a round-trip through a utf8mb4-capable server.');
        } else {
            // On servers without utf8mb4 the column may truncate or mangle the emoji;
            // we simply assert the query did not throw.
            self::assertIsString($retrieved);
        }
    }

    // -------------------------------------------------------------------------
    // Table lock / unlock
    // -------------------------------------------------------------------------

    public function testLockAndUnlockTableDoNotThrow(): void
    {
        $this->db->lockTable(self::TMP_TABLE);
        $this->db->unlockTables();

        // No exception means the test passed.
        self::assertTrue(true);
    }

    public function testLockTableReturnsSelf(): void
    {
        $result = $this->db->lockTable(self::TMP_TABLE);

        $this->db->unlockTables(); // Clean up the lock.

        self::assertInstanceOf(Mysqli::class, $result);
        self::assertSame($this->db, $result);
    }

    public function testUnlockTablesReturnsSelf(): void
    {
        $this->db->lockTable(self::TMP_TABLE);
        $result = $this->db->unlockTables();

        self::assertInstanceOf(Mysqli::class, $result);
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
            if (stripos($entry, 'INSERT') !== false) {
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
}
