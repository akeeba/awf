<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Database;

use Awf\Database\Driver\Sqlite;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Driver utility and metadata methods against an in-memory SQLite database.
 *
 * Covers: quote(), quoteName(), escape(), getTableList(), getTableColumns(),
 * getTableKeys(), transactionStart/Commit/Rollback, replacePrefix(), getPrefix().
 */
class DriverUtilityTest extends TestCase
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
            'prefix'   => 'tst_',
        ]);

        // Create a test table with various column types and a primary key.
        $this->db->setQuery(
            'CREATE TABLE tst_things (
                id    INTEGER PRIMARY KEY AUTOINCREMENT,
                label TEXT    NOT NULL,
                score REAL    NOT NULL DEFAULT 0.0,
                notes TEXT    NULL
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
    // quote() — string quoting
    // -------------------------------------------------------------------------

    public static function quoteStringProvider(): array
    {
        return [
            'plain string'               => ['hello',    "'hello'"],
            'empty string'               => ['',         "''"],
            'string with single quote'   => ["it's",     "'it''s'"],
            'numeric string'             => ['42',       "'42'"],
        ];
    }

    #[DataProvider('quoteStringProvider')]
    public function testQuoteScalarString(string $input, string $expected): void
    {
        self::assertSame($expected, $this->db->quote($input));
    }

    public function testQuoteWithEscapeDisabledDoesNotEscapeContents(): void
    {
        // With $escape = false the content is wrapped in quotes as-is.
        $result = $this->db->quote("it's alive", false);

        self::assertSame("'it's alive'", $result);
    }

    public function testQuoteArrayWrapsEachElement(): void
    {
        $input  = ['foo', 'bar', "baz's"];
        $result = $this->db->quote($input);

        self::assertIsArray($result);
        self::assertCount(3, $result);
        self::assertSame("'foo'", $result[0]);
        self::assertSame("'bar'", $result[1]);
        self::assertSame("'baz''s'", $result[2]);
    }

    public function testQuoteArrayPreservesKeys(): void
    {
        $input  = ['a' => 'one', 'b' => 'two'];
        $result = $this->db->quote($input);

        self::assertArrayHasKey('a', $result);
        self::assertArrayHasKey('b', $result);
    }

    // -------------------------------------------------------------------------
    // quoteName() — identifier quoting (SQLite uses backticks)
    // -------------------------------------------------------------------------

    public static function quoteNameProvider(): array
    {
        return [
            'simple name'            => ['foo',          '`foo`'],
            'name with space'        => ['my col',       '`my col`'],
            'name with backtick'     => ['my`col',       '`my``col`'],
            'dot notation'           => ['t.col',        '`t`.`col`'],
            'three-part dot'         => ['a.b.c',        '`a`.`b`.`c`'],
        ];
    }

    #[DataProvider('quoteNameProvider')]
    public function testQuoteNameSingleString(string $input, string $expected): void
    {
        self::assertSame($expected, $this->db->quoteName($input));
    }

    public function testQuoteNameWithAsClause(): void
    {
        $result = $this->db->quoteName('my_table', 'mt');

        self::assertSame('`my_table` AS `mt`', $result);
    }

    public function testQuoteNameArrayWithoutAs(): void
    {
        $result = $this->db->quoteName(['foo', 'bar']);

        self::assertIsArray($result);
        self::assertCount(2, $result);
        self::assertSame('`foo`', $result[0]);
        self::assertSame('`bar`', $result[1]);
    }

    public function testQuoteNameArrayWithMatchingAsArray(): void
    {
        $result = $this->db->quoteName(['col1', 'col2'], ['c1', 'c2']);

        self::assertIsArray($result);
        self::assertSame('`col1` AS `c1`', $result[0]);
        self::assertSame('`col2` AS `c2`', $result[1]);
    }

    public function testQuoteNameMagicAliasQn(): void
    {
        self::assertSame($this->db->quoteName('foo'), $this->db->qn('foo'));
    }

    public function testQuoteNameMagicAliasNq(): void
    {
        self::assertSame($this->db->quoteName('bar'), $this->db->nq('bar'));
    }

    // -------------------------------------------------------------------------
    // escape() — string escaping for SQLite
    // -------------------------------------------------------------------------

    public function testEscapeReturnsStringUnchangedWhenSafe(): void
    {
        $result = $this->db->escape('hello world');

        self::assertIsString($result);
        self::assertStringContainsString('hello world', $result);
    }

    public function testEscapeSingleQuoteBecomesTwoSingleQuotes(): void
    {
        // SQLite3::escapeString doubles single-quote characters.
        $result = $this->db->escape("O'Brien");

        self::assertStringContainsString("O''Brien", $result);
    }

    public function testEscapePassesThroughIntegers(): void
    {
        // The SQLite driver returns int/float unchanged.
        $result = $this->db->escape(42);

        self::assertSame(42, $result);
    }

    public function testEscapePassesThroughFloats(): void
    {
        $result = $this->db->escape(3.14);

        self::assertSame(3.14, $result);
    }

    public function testEscapeNullReturnsNullLiteral(): void
    {
        $result = $this->db->escape(null);

        self::assertSame('NULL', $result);
    }

    // -------------------------------------------------------------------------
    // getPrefix() / replacePrefix()
    // -------------------------------------------------------------------------

    public function testGetPrefixReturnsConfiguredPrefix(): void
    {
        self::assertSame('tst_', $this->db->getPrefix());
    }

    public function testGetPrefixReturnsEmptyStringWhenNoneSet(): void
    {
        $db = new Sqlite([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        self::assertSame('', $db->getPrefix());
    }

    public function testReplacePrefixSubstitutesPlaceholder(): void
    {
        $sql    = 'SELECT * FROM #__things WHERE id = 1';
        $result = $this->db->replacePrefix($sql);

        self::assertStringContainsString('tst_things', $result);
        self::assertStringNotContainsString('#__', $result);
    }

    public function testReplacePrefixLeavesStringLiteralsUntouched(): void
    {
        // The placeholder inside a quoted string must NOT be replaced.
        $sql    = "SELECT '#__things' AS alias FROM #__things";
        $result = $this->db->replacePrefix($sql);

        self::assertStringContainsString("'#__things'", $result);
        self::assertStringContainsString('tst_things', $result);
    }

    public function testReplacePrefixLeavesDoubleQuotedLiteralsUntouched(): void
    {
        // Same protection for double-quoted strings.
        $sql    = 'SELECT "#__col" FROM #__things';
        $result = $this->db->replacePrefix($sql);

        self::assertStringContainsString('"#__col"', $result);
        self::assertStringContainsString('tst_things', $result);
    }

    public function testReplacePrefixWithCustomPrefixArgument(): void
    {
        // The second argument overrides the placeholder token.
        $db     = new Sqlite(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'x_']);
        $sql    = 'SELECT * FROM @@myTable';
        $result = $db->replacePrefix($sql, '@@');

        self::assertStringContainsString('x_myTable', $result);
        self::assertStringNotContainsString('@@', $result);
    }

    public function testReplacePrefixSqlWithNoPlaceholderIsUnchanged(): void
    {
        $sql    = 'SELECT 1 + 1';
        $result = $this->db->replacePrefix($sql);

        self::assertSame('SELECT 1 + 1', $result);
    }

    public function testReplacePrefixMultipleOccurrences(): void
    {
        $sql    = 'SELECT * FROM #__a JOIN #__b ON #__a.id = #__b.a_id';
        $result = $this->db->replacePrefix($sql);

        self::assertStringNotContainsString('#__', $result);
        self::assertSame(
            'SELECT * FROM tst_a JOIN tst_b ON tst_a.id = tst_b.a_id',
            $result
        );
    }

    // -------------------------------------------------------------------------
    // getTableList()
    // -------------------------------------------------------------------------

    public function testGetTableListIncludesCreatedTable(): void
    {
        $tables = $this->db->getTableList();

        self::assertIsArray($tables);
        self::assertContains('tst_things', $tables);
    }

    public function testGetTableListReturnsOnlyTableTypeEntries(): void
    {
        // getTableList() only returns rows with type='table' from sqlite_master.
        // This means user-created tables (and SQLite internal tables like sqlite_sequence
        // that are also of type 'table') appear, while VIEWs and triggers do not.
        $this->db->setQuery('CREATE VIEW tst_view AS SELECT id FROM tst_things');
        $this->db->execute();

        $tables = $this->db->getTableList();

        foreach ($tables as $entry) {
            // None of the entries should be the view we just created.
            self::assertNotSame('tst_view', $entry);
        }
    }

    public function testGetTableListReturnsEmptyArrayWhenNoTables(): void
    {
        $db = new Sqlite([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $tables = $db->getTableList();

        self::assertIsArray($tables);
        self::assertEmpty($tables);
    }

    public function testGetTableListIncludesMultipleTables(): void
    {
        // Add a second table.
        $this->db->setQuery('CREATE TABLE tst_other (id INTEGER PRIMARY KEY)');
        $this->db->execute();

        $tables = $this->db->getTableList();

        self::assertContains('tst_things', $tables);
        self::assertContains('tst_other', $tables);
    }

    public function testGetTableListIsSorted(): void
    {
        $this->db->setQuery('CREATE TABLE tst_aardvark (id INTEGER PRIMARY KEY)');
        $this->db->execute();

        $this->db->setQuery('CREATE TABLE tst_zebra (id INTEGER PRIMARY KEY)');
        $this->db->execute();

        $tables = $this->db->getTableList();

        // Tables should be in ascending alphabetical order.
        $sorted = $tables;
        sort($sorted);
        self::assertSame($sorted, $tables);
    }

    // -------------------------------------------------------------------------
    // getTableColumns()
    // -------------------------------------------------------------------------

    public function testGetTableColumnsTypeOnlyReturnsNameToTypeMap(): void
    {
        // SQLite driver returns column names in the same case as defined in CREATE TABLE.
        $columns = $this->db->getTableColumns('tst_things', true);

        self::assertIsArray($columns);
        self::assertArrayHasKey('id', $columns);
        self::assertArrayHasKey('label', $columns);
        self::assertArrayHasKey('score', $columns);
        self::assertArrayHasKey('notes', $columns);
    }

    public function testGetTableColumnsTypeOnlyValuesAreStrings(): void
    {
        $columns = $this->db->getTableColumns('tst_things', true);

        foreach ($columns as $type) {
            self::assertIsString($type);
        }
    }

    public function testGetTableColumnsTypeOnlyContainsCorrectTypes(): void
    {
        $columns = $this->db->getTableColumns('tst_things', true);

        // The types returned are what was declared in the CREATE TABLE statement.
        self::assertStringContainsStringIgnoringCase('INTEGER', $columns['id']);
        self::assertStringContainsStringIgnoringCase('TEXT', $columns['label']);
        self::assertStringContainsStringIgnoringCase('REAL', $columns['score']);
    }

    public function testGetTableColumnsFullInfoReturnsObjects(): void
    {
        $columns = $this->db->getTableColumns('tst_things', false);

        self::assertIsArray($columns);

        foreach ($columns as $col) {
            self::assertIsObject($col);
        }
    }

    public function testGetTableColumnsFullInfoHasExpectedFields(): void
    {
        $columns = $this->db->getTableColumns('tst_things', false);

        // Column map keys use the same case as in CREATE TABLE.
        self::assertArrayHasKey('id', $columns);
        $idCol = $columns['id'];

        // Each column object must have these MySQL-compatible fields.
        self::assertObjectHasProperty('Field',   $idCol);
        self::assertObjectHasProperty('Type',    $idCol);
        self::assertObjectHasProperty('Null',    $idCol);
        self::assertObjectHasProperty('Default', $idCol);
        self::assertObjectHasProperty('Key',     $idCol);
    }

    public function testGetTableColumnsPrimaryKeyMarked(): void
    {
        $columns = $this->db->getTableColumns('tst_things', false);

        self::assertSame('PRI', $columns['id']->Key);
    }

    public function testGetTableColumnsNullableColumnReflected(): void
    {
        $columns = $this->db->getTableColumns('tst_things', false);

        // notes is declared NULL so Null field should be 'YES'.
        self::assertSame('YES', $columns['notes']->Null);
    }

    public function testGetTableColumnsNotNullColumnReflected(): void
    {
        $columns = $this->db->getTableColumns('tst_things', false);

        // label is declared NOT NULL.
        self::assertSame('NO', $columns['label']->Null);
    }

    // -------------------------------------------------------------------------
    // getTableKeys()
    // -------------------------------------------------------------------------

    public function testGetTableKeysReturnsPrimaryKeyColumn(): void
    {
        // getTableKeys() keys use the same case as column names in CREATE TABLE.
        $keys = $this->db->getTableKeys('tst_things');

        self::assertIsArray($keys);
        self::assertNotEmpty($keys);
        self::assertArrayHasKey('id', $keys);
    }

    public function testGetTableKeysOnlyReturnsPrimaryKeyColumns(): void
    {
        $keys = $this->db->getTableKeys('tst_things');

        // Only the PK column (id) must be in the result; label/score/notes must not.
        self::assertArrayNotHasKey('label', $keys);
        self::assertArrayNotHasKey('score', $keys);
        self::assertArrayNotHasKey('notes', $keys);
    }

    public function testGetTableKeysReturnsEmptyArrayForTableWithNoPrimaryKey(): void
    {
        // Create a heap table with no explicit PK.
        $this->db->setQuery('CREATE TABLE tst_nopk (val TEXT)');
        $this->db->execute();

        $keys = $this->db->getTableKeys('tst_nopk');

        self::assertIsArray($keys);
        self::assertEmpty($keys);
    }

    public function testGetTableKeyValuesAreObjects(): void
    {
        $keys = $this->db->getTableKeys('tst_things');

        foreach ($keys as $key) {
            self::assertIsObject($key);
        }
    }

    // -------------------------------------------------------------------------
    // transactionStart() / transactionCommit()
    // -------------------------------------------------------------------------

    public function testTransactionCommitPersistsData(): void
    {
        $this->db->transactionStart();
        $this->db->setQuery("INSERT INTO tst_things (label, score) VALUES ('committed', 1.0)");
        $this->db->execute();
        $this->db->transactionCommit();

        // Row must be visible after commit.
        $this->db->setQuery("SELECT COUNT(*) FROM tst_things WHERE label = 'committed'");
        $count = $this->db->loadResult();

        self::assertSame('1', (string) $count);
    }

    public function testTransactionRollbackDiscardsData(): void
    {
        $this->db->transactionStart();
        $this->db->setQuery("INSERT INTO tst_things (label, score) VALUES ('rolled_back', 2.0)");
        $this->db->execute();
        $this->db->transactionRollback();

        // Row must NOT be visible after rollback.
        $this->db->setQuery("SELECT COUNT(*) FROM tst_things WHERE label = 'rolled_back'");
        $count = $this->db->loadResult();

        self::assertSame('0', (string) $count);
    }

    public function testTransactionStartCommitIsIdempotent(): void
    {
        // Two sequential transactions must not break the driver.
        $this->db->transactionStart();
        $this->db->setQuery("INSERT INTO tst_things (label, score) VALUES ('tx1', 1.0)");
        $this->db->execute();
        $this->db->transactionCommit();

        $this->db->transactionStart();
        $this->db->setQuery("INSERT INTO tst_things (label, score) VALUES ('tx2', 2.0)");
        $this->db->execute();
        $this->db->transactionCommit();

        $this->db->setQuery("SELECT COUNT(*) FROM tst_things WHERE label IN ('tx1','tx2')");
        $count = $this->db->loadResult();

        self::assertSame('2', (string) $count);
    }

    // -------------------------------------------------------------------------
    // Nested transactions (savepoints) — SQLite-specific
    // -------------------------------------------------------------------------

    public function testNestedTransactionSavepointRollbackAffectsOnlyInnerTransaction(): void
    {
        // Outer transaction starts.
        $this->db->transactionStart();
        $this->db->setQuery("INSERT INTO tst_things (label, score) VALUES ('outer', 10.0)");
        $this->db->execute();

        // Inner transaction (savepoint).
        $this->db->transactionStart(true);
        $this->db->setQuery("INSERT INTO tst_things (label, score) VALUES ('inner', 20.0)");
        $this->db->execute();

        // Roll back only the inner (savepoint).
        $this->db->transactionRollback(true);

        // Commit outer.
        $this->db->transactionCommit();

        $this->db->setQuery("SELECT COUNT(*) FROM tst_things WHERE label = 'outer'");
        self::assertSame('1', (string) $this->db->loadResult(), 'Outer row must be committed.');

        $this->db->setQuery("SELECT COUNT(*) FROM tst_things WHERE label = 'inner'");
        self::assertSame('0', (string) $this->db->loadResult(), 'Inner row must have been rolled back.');
    }

    public function testNestedTransactionSavepointCommitPersistsBothLevels(): void
    {
        // Outer transaction starts.
        $this->db->transactionStart();
        $this->db->setQuery("INSERT INTO tst_things (label, score) VALUES ('lvl1', 1.0)");
        $this->db->execute();

        // Inner transaction (savepoint).
        $this->db->transactionStart(true);
        $this->db->setQuery("INSERT INTO tst_things (label, score) VALUES ('lvl2', 2.0)");
        $this->db->execute();

        // Commit inner (releases savepoint).
        $this->db->transactionCommit(true);

        // Commit outer.
        $this->db->transactionCommit();

        $this->db->setQuery("SELECT COUNT(*) FROM tst_things WHERE label IN ('lvl1','lvl2')");
        self::assertSame('2', (string) $this->db->loadResult(), 'Both rows must be committed.');
    }

    // -------------------------------------------------------------------------
    // Real-query integration: prefix + table list + columns + keys round-trip
    // -------------------------------------------------------------------------

    public function testRoundTripPrefixedTableIsListedAndInspectable(): void
    {
        // Create a prefixed table via replacePrefix workflow.
        $sql = $this->db->replacePrefix(
            'CREATE TABLE #__widgets (
                id    INTEGER PRIMARY KEY,
                name  TEXT NOT NULL
            )'
        );
        $this->db->setQuery($sql);
        $this->db->execute();

        // Verify it appears in getTableList().
        $tables = $this->db->getTableList();
        self::assertContains('tst_widgets', $tables, 'Prefixed table must appear in getTableList().');

        // Verify columns can be introspected (keys use original CREATE TABLE case).
        $cols = $this->db->getTableColumns('tst_widgets', true);
        self::assertArrayHasKey('id', $cols);
        self::assertArrayHasKey('name', $cols);

        // Verify primary key is detected.
        $keys = $this->db->getTableKeys('tst_widgets');
        self::assertArrayHasKey('id', $keys);
    }
}
