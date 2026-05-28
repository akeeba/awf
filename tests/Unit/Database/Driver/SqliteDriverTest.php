<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Database\Driver;

use Awf\Database\Driver\Sqlite;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for Sqlite-driver-specific overrides:
 * dropTable(), renameTable(), getTableCreate(), escape() specifics,
 * getCollation(), select(), setUTF(), lockTable(), unlockTables().
 *
 * All tests use an in-memory SQLite database; skipped gracefully when
 * pdo_sqlite is unavailable.
 */
class SqliteDriverTest extends TestCase
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

        // Create a baseline table used by several tests.
        $this->db->setQuery(
            'CREATE TABLE test_things (
                id    INTEGER PRIMARY KEY AUTOINCREMENT,
                label TEXT    NOT NULL,
                score REAL    NOT NULL DEFAULT 0.0
            )'
        );
        $this->db->execute();
    }

    protected function tearDown(): void
    {
        $this->db?->disconnect();
        $this->db = null;
    }

    // -------------------------------------------------------------------------
    // dropTable() — happy path
    // -------------------------------------------------------------------------

    public function testDropTableRemovesExistingTable(): void
    {
        // Confirm the table exists before dropping.
        $before = $this->db->getTableList();
        self::assertContains('test_things', $before, 'Baseline table must exist before drop.');

        $result = $this->db->dropTable('test_things');

        // Table must no longer appear in the list.
        $after = $this->db->getTableList();
        self::assertNotContains('test_things', $after, 'Table must be absent after dropTable().');

        // Method must return $this for chaining.
        self::assertSame($this->db, $result);
    }

    public function testDropTableWithIfExistsTrueOnMissingTableDoesNotThrow(): void
    {
        // Dropping a non-existent table with IF EXISTS must silently succeed.
        $result = $this->db->dropTable('nonexistent_table_xyz', true);

        self::assertSame($this->db, $result);
    }

    public function testDropTableWithIfExistsFalseOnMissingTableThrows(): void
    {
        // Dropping without IF EXISTS must throw when the table does not exist.
        $this->expectException(RuntimeException::class);

        $this->db->dropTable('nonexistent_table_xyz', false);
    }

    public function testDropTableDefaultsToIfExistsTrue(): void
    {
        // The default for $ifExists is true, so a missing table must not throw.
        // (This exercises the parameter default rather than a specific $ifExists = true call.)
        $result = $this->db->dropTable('still_nonexistent_table');

        self::assertSame($this->db, $result);
    }

    public function testDropTableGeneratesIfExistsKeywordInSql(): void
    {
        // Enable debug logging so we can inspect the SQL that was executed.
        $this->db->setDebug(true);

        $this->db->dropTable('test_things', true);

        $this->db->setDebug(false);
        $log = $this->db->getLog();

        self::assertNotEmpty($log, 'Debug log must contain the DROP TABLE statement.');
        self::assertStringContainsStringIgnoringCase('IF EXISTS', $log[0]);
    }

    public function testDropTableWithoutIfExistsOmitsKeywordInSql(): void
    {
        $this->db->setDebug(true);

        $this->db->dropTable('test_things', false);

        $this->db->setDebug(false);
        $log = $this->db->getLog();

        self::assertNotEmpty($log);
        self::assertStringNotContainsStringIgnoringCase('IF EXISTS', $log[0]);
    }

    public function testDropTableReturnsSelf(): void
    {
        $result = $this->db->dropTable('test_things', true);

        self::assertInstanceOf(Sqlite::class, $result);
        self::assertSame($this->db, $result);
    }

    // -------------------------------------------------------------------------
    // renameTable() — happy path
    // -------------------------------------------------------------------------

    public function testRenameTableMakesOldTableDisappear(): void
    {
        $this->db->renameTable('test_things', 'test_things_renamed');

        $tables = $this->db->getTableList();
        self::assertNotContains('test_things', $tables, 'Original table must not be listed after rename.');
    }

    public function testRenameTableMakesNewTableAppear(): void
    {
        $this->db->renameTable('test_things', 'test_things_renamed');

        $tables = $this->db->getTableList();
        self::assertContains('test_things_renamed', $tables, 'New table name must appear after rename.');
    }

    public function testRenameTableReturnsSelf(): void
    {
        $result = $this->db->renameTable('test_things', 'test_things_renamed');

        self::assertInstanceOf(Sqlite::class, $result);
        self::assertSame($this->db, $result);
    }

    public function testRenameTablePreservesRowData(): void
    {
        $this->db->setQuery("INSERT INTO test_things (label, score) VALUES ('hello', 1.5)");
        $this->db->execute();

        $this->db->renameTable('test_things', 'test_things_renamed');

        $this->db->setQuery("SELECT label FROM test_things_renamed WHERE id = 1");
        $label = $this->db->loadResult();

        self::assertSame('hello', $label, 'Row data must survive a table rename.');
    }

    public function testRenameTableIgnoresBackupAndPrefixArguments(): void
    {
        // The SQLite driver ignores $backup and $prefix — they are MySQL-only.
        // Passing them must not throw or change behaviour.
        $result = $this->db->renameTable('test_things', 'test_things_np', 'backup_tbl', 'pfx_');

        $tables = $this->db->getTableList();
        self::assertContains('test_things_np', $tables);
        self::assertSame($this->db, $result);
    }

    // -------------------------------------------------------------------------
    // getTableCreate()
    // -------------------------------------------------------------------------

    public function testGetTableCreateReturnsSingleItemArray(): void
    {
        $result = $this->db->getTableCreate('test_things');

        self::assertIsArray($result);
        self::assertCount(1, $result);
    }

    public function testGetTableCreateContainsRequestedTableName(): void
    {
        $result = $this->db->getTableCreate('test_things');

        // The driver casts the argument to an array and returns it as-is (SQLite does not
        // natively expose CREATE TABLE strings the way MySQL does).
        self::assertContains('test_things', $result);
    }

    public function testGetTableCreateWithArrayInputReturnsEachTableName(): void
    {
        // Create a second table so we can pass two names.
        $this->db->setQuery('CREATE TABLE test_other (id INTEGER PRIMARY KEY)');
        $this->db->execute();

        $result = $this->db->getTableCreate(['test_things', 'test_other']);

        self::assertIsArray($result);
        self::assertCount(2, $result);
        self::assertContains('test_things', $result);
        self::assertContains('test_other', $result);
    }

    public function testGetTableCreateWithEmptyArrayReturnsEmptyArray(): void
    {
        $result = $this->db->getTableCreate([]);

        self::assertIsArray($result);
        self::assertEmpty($result);
    }

    // -------------------------------------------------------------------------
    // escape() — SQLite-specific overrides
    // -------------------------------------------------------------------------

    public static function escapeStringProvider(): array
    {
        return [
            'plain ASCII string'         => ['hello world', 'hello world'],
            'string with single quote'   => ["O'Brien",     "O''Brien"],
            'empty string'               => ['',            ''],
            'multiple single quotes'     => ["it's it's",   "it''s it''s"],
            'backslash (no special meaning in SQLite)' => ['foo\\bar', 'foo\\bar'],
        ];
    }

    #[DataProvider('escapeStringProvider')]
    public function testEscapeString(string $input, string $expected): void
    {
        $result = $this->db->escape($input);

        self::assertSame($expected, $result);
    }

    public function testEscapeIntegerReturnsIntegerUnchanged(): void
    {
        $result = $this->db->escape(42);

        self::assertSame(42, $result);
    }

    public function testEscapeZeroReturnsZeroUnchanged(): void
    {
        $result = $this->db->escape(0);

        self::assertSame(0, $result);
    }

    public function testEscapeNegativeIntegerReturnsUnchanged(): void
    {
        $result = $this->db->escape(-7);

        self::assertSame(-7, $result);
    }

    public function testEscapeFloatReturnsFloatUnchanged(): void
    {
        $result = $this->db->escape(3.14);

        self::assertSame(3.14, $result);
    }

    public function testEscapeNullReturnsNullLiteralString(): void
    {
        $result = $this->db->escape(null);

        self::assertSame('NULL', $result);
    }

    public function testEscapeExtraParameterHasNoEffect(): void
    {
        // The SQLite driver documents $extra as unused.
        $withExtra    = $this->db->escape("O'Brien", true);
        $withoutExtra = $this->db->escape("O'Brien", false);

        self::assertSame($withExtra, $withoutExtra);
    }

    // -------------------------------------------------------------------------
    // getCollation()
    // -------------------------------------------------------------------------

    public function testGetCollationReturnsNullWhenNoCharsetConfigured(): void
    {
        // With no charset option the driver's $charset is null, so getCollation() returns null.
        $collation = $this->db->getCollation();

        self::assertNull($collation);
    }

    public function testGetCollationReturnsCharsetWhenConfigured(): void
    {
        $db = new Sqlite([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
            'charset'  => 'utf8',
        ]);

        // When charset is explicitly set in options it must be returned by getCollation().
        self::assertSame('utf8', $db->getCollation());
    }

    // -------------------------------------------------------------------------
    // select()
    // -------------------------------------------------------------------------

    public function testSelectReturnsTrueForAnyDatabaseName(): void
    {
        // SQLite's select() is a no-op that always returns true.
        $result = $this->db->select('any_database_name');

        self::assertTrue($result);
    }

    public function testSelectAcceptsEmptyString(): void
    {
        $result = $this->db->select('');

        self::assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // setUTF()
    // -------------------------------------------------------------------------

    public function testSetUTFReturnsFalse(): void
    {
        // SQLite does not support runtime charset changes; must always return false.
        $result = $this->db->setUTF();

        self::assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // lockTable() / unlockTables()
    // -------------------------------------------------------------------------

    public function testLockTableReturnsSelf(): void
    {
        $result = $this->db->lockTable('test_things');

        self::assertSame($this->db, $result);
    }

    public function testUnlockTablesReturnsSelf(): void
    {
        $result = $this->db->unlockTables();

        self::assertSame($this->db, $result);
    }

    public function testLockAndUnlockTableDoNotThrow(): void
    {
        // SQLite does not support advisory locks; these must be silently ignored.
        $this->db->lockTable('test_things');
        $this->db->unlockTables();

        // If we reach here no exception was thrown — test passes.
        self::assertTrue(true);
    }

    // -------------------------------------------------------------------------
    // isSupported() — static capability check
    // -------------------------------------------------------------------------

    public function testIsSupportedReturnsBool(): void
    {
        self::assertIsBool(Sqlite::isSupported());
    }

    public function testIsSupportedReturnsTrueInCurrentEnvironment(): void
    {
        // The test suite was already guarded at setUp(), so isSupported() must be true here.
        self::assertTrue(Sqlite::isSupported());
    }

    // -------------------------------------------------------------------------
    // Driver metadata
    // -------------------------------------------------------------------------

    public function testDriverNameIsSqlite(): void
    {
        self::assertSame('sqlite', $this->db->name);
    }

    public function testDbTechIsSqlite(): void
    {
        self::assertSame('sqlite', Sqlite::$dbtech);
    }

    // -------------------------------------------------------------------------
    // disconnect() / __destruct() — resource cleanup
    // -------------------------------------------------------------------------

    public function testDisconnectNullifiesConnection(): void
    {
        $this->db->connect();
        self::assertNotNull($this->db->getConnection());

        $this->db->disconnect();

        self::assertNull($this->db->getConnection());
    }

    public function testDisconnectIsIdempotent(): void
    {
        $this->db->connect();
        $this->db->disconnect();
        $this->db->disconnect(); // Second call must not throw.

        self::assertNull($this->db->getConnection());
    }

    // -------------------------------------------------------------------------
    // Round-trip: dropTable then recreate
    // -------------------------------------------------------------------------

    public function testDropAndRecreateTable(): void
    {
        $this->db->dropTable('test_things', true);

        // Re-create the same table.
        $this->db->setQuery(
            'CREATE TABLE test_things (
                id    INTEGER PRIMARY KEY AUTOINCREMENT,
                label TEXT    NOT NULL
            )'
        );
        $this->db->execute();

        $tables = $this->db->getTableList();
        self::assertContains('test_things', $tables, 'Table must exist after being recreated.');
    }

    // -------------------------------------------------------------------------
    // Round-trip: renameTable then drop
    // -------------------------------------------------------------------------

    public function testRenameAndDropTable(): void
    {
        $this->db->renameTable('test_things', 'test_things_final');
        $this->db->dropTable('test_things_final', true);

        $tables = $this->db->getTableList();
        self::assertNotContains('test_things_final', $tables, 'Table must be gone after rename + drop.');
    }
}
