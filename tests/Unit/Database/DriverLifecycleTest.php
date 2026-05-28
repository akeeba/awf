<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Database;

use Awf\Database\Driver;
use Awf\Database\Driver\Sqlite;
use Awf\Database\Query\Sqlite as SqliteQuery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for Driver::getInstance() factory selection, connect()/connected()/disconnect(),
 * setQuery()/getQuery(), and option handling, exercised against an in-memory SQLite database.
 */
class DriverLifecycleTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a fresh in-memory SQLite driver instance.
     * Skips the test gracefully if the pdo_sqlite extension is unavailable.
     */
    private function makeSqliteDriver(array $extraOptions = []): Sqlite
    {
        if (!Sqlite::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        $options = array_merge([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ], $extraOptions);

        return new Sqlite($options);
    }

    // -------------------------------------------------------------------------
    // fromOptions() factory — happy path
    // -------------------------------------------------------------------------

    public function testFromOptionsReturnsSqliteInstance(): void
    {
        if (!Sqlite::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        $db = Driver::fromOptions([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        self::assertInstanceOf(Sqlite::class, $db);
    }

    public function testFromOptionsPreservesDatabase(): void
    {
        $db = $this->makeSqliteDriver();

        self::assertSame(':memory:', $db->getDatabase());
    }

    public function testFromOptionsPreservesPrefix(): void
    {
        $db = $this->makeSqliteDriver(['prefix' => 'test_']);

        self::assertSame('test_', $db->getPrefix());
    }

    // -------------------------------------------------------------------------
    // fromOptions() factory — error conditions
    // -------------------------------------------------------------------------

    public function testFromOptionsThrowsForUnknownDriver(): void
    {
        $this->expectException(RuntimeException::class);

        Driver::fromOptions(['driver' => 'nonexistent_driver_xyz']);
    }

    // -------------------------------------------------------------------------
    // getInstance() — deprecated factory, still must work
    // -------------------------------------------------------------------------

    public function testGetInstanceReturnsSqliteInstance(): void
    {
        if (!Sqlite::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        $db = @Driver::getInstance([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        self::assertInstanceOf(Sqlite::class, $db);
    }

    public function testGetInstanceIsCachedBySameOptions(): void
    {
        if (!Sqlite::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        $options = [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => 'cache_test_',
        ];

        $db1 = @Driver::getInstance($options);
        $db2 = @Driver::getInstance($options);

        self::assertSame($db1, $db2, 'getInstance() must return the same object for identical options.');
    }

    public function testGetInstanceEmitsDeprecatedNotice(): void
    {
        if (!Sqlite::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        $noticed = false;

        set_error_handler(function (int $errno, string $errstr) use (&$noticed): bool {
            if ($errno === E_USER_DEPRECATED) {
                $noticed = true;
            }

            return true;
        });

        Driver::getInstance([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => 'deprecated_test_',
        ]);

        restore_error_handler();

        self::assertTrue($noticed, 'getInstance() must trigger an E_USER_DEPRECATED notice.');
    }

    // -------------------------------------------------------------------------
    // connect() / connected() / disconnect()
    // -------------------------------------------------------------------------

    public function testConnectEstablishesConnection(): void
    {
        $db = $this->makeSqliteDriver();

        // connect() is lazy; calling connected() will internally connect.
        $db->connect();

        self::assertNotNull($db->getConnection(), 'Connection resource must not be null after connect().');
    }

    public function testConnectedReturnsTrueAfterConnect(): void
    {
        $db = $this->makeSqliteDriver();

        $db->connect();

        self::assertTrue($db->connected());
    }

    public function testConnectionIsNullAfterDisconnect(): void
    {
        $db = $this->makeSqliteDriver();

        $db->connect();

        self::assertNotNull($db->getConnection(), 'Connection must be non-null after connect().');

        $db->disconnect();

        // After disconnect the connection resource is released.
        // Note: calling connected() again on SQLite would trigger an automatic reconnect
        // because the PDO driver's connect() is re-entrant. We verify the raw connection
        // property is gone instead.
        self::assertNull($db->getConnection(), 'Connection must be null after disconnect().');
    }

    public function testDisconnectNullifiesConnectionProperty(): void
    {
        $db = $this->makeSqliteDriver();

        $db->connect();

        self::assertNotNull($db->getConnection());

        $db->disconnect();

        self::assertNull($db->getConnection());
    }

    public function testConnectIsIdempotent(): void
    {
        $db = $this->makeSqliteDriver();

        $db->connect();
        $firstConnection = $db->getConnection();

        // Calling connect() again when already connected must be a no-op.
        $db->connect();

        self::assertSame($firstConnection, $db->getConnection());
    }

    // -------------------------------------------------------------------------
    // setQuery() / getQuery()
    // -------------------------------------------------------------------------

    public function testSetQueryWithStringStoresQuery(): void
    {
        $db = $this->makeSqliteDriver();

        $db->setQuery('SELECT 1');

        // Before execution a string query is wrapped in a Query object by the PDO driver.
        // getQuery(false) must not return null.
        self::assertNotNull($db->getQuery(false));
    }

    public function testSetQueryReturnsSelf(): void
    {
        $db = $this->makeSqliteDriver();

        $result = $db->setQuery('SELECT 1');

        self::assertSame($db, $result);
    }

    public function testGetQueryNewReturnsSqliteQueryObject(): void
    {
        $db = $this->makeSqliteDriver();

        $q = $db->getQuery(true);

        self::assertInstanceOf(SqliteQuery::class, $q);
    }

    public function testGetQueryNewReturnsFreshInstanceEachTime(): void
    {
        $db = $this->makeSqliteDriver();

        $q1 = $db->getQuery(true);
        $q2 = $db->getQuery(true);

        self::assertNotSame($q1, $q2);
    }

    public function testGetQueryFalseReturnsCurrentQuery(): void
    {
        $db  = $this->makeSqliteDriver();
        $q   = $db->getQuery(true)->select('1');

        $db->setQuery($q);

        self::assertSame($q, $db->getQuery(false));
    }

    // -------------------------------------------------------------------------
    // Option handling
    // -------------------------------------------------------------------------

    public function testGetDatabaseReturnsConfiguredDatabase(): void
    {
        $db = $this->makeSqliteDriver(['database' => ':memory:']);

        self::assertSame(':memory:', $db->getDatabase());
    }

    public function testGetPrefixReturnsConfiguredPrefix(): void
    {
        $db = $this->makeSqliteDriver(['prefix' => 'abc_']);

        self::assertSame('abc_', $db->getPrefix());
    }

    public function testGetPrefixDefaultsToEmpty(): void
    {
        $db = $this->makeSqliteDriver(['prefix' => '']);

        self::assertSame('', $db->getPrefix());
    }

    // -------------------------------------------------------------------------
    // getConnectors()
    // -------------------------------------------------------------------------

    public function testGetConnectorsReturnsSqliteWhenSupported(): void
    {
        if (!Sqlite::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        $connectors = Driver::getConnectors();

        // The returned list must contain a connector whose name (case-insensitive) starts with 'Sqlite'.
        $found = array_filter(
            $connectors,
            static fn(string $c): bool => stripos($c, 'Sqlite') !== false
        );

        self::assertNotEmpty($found, 'getConnectors() must include the Sqlite connector when pdo_sqlite is available.');
    }

    public function testGetConnectorsFiltersByTech(): void
    {
        if (!Sqlite::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        $connectors = Driver::getConnectors('sqlite');

        foreach ($connectors as $connector) {
            // All returned items must be for the requested technology.
            self::assertStringContainsStringIgnoringCase('sqlite', $connector);
        }
    }

    // -------------------------------------------------------------------------
    // setConnection() / getConnection()
    // -------------------------------------------------------------------------

    public function testSetConnectionReplacesConnection(): void
    {
        $db = $this->makeSqliteDriver();

        $db->connect();

        $fakePdo = new \PDO('sqlite::memory:');
        $db->setConnection($fakePdo);

        self::assertSame($fakePdo, $db->getConnection());
    }

    // -------------------------------------------------------------------------
    // getCount() — number of executed statements
    // -------------------------------------------------------------------------

    public function testGetCountIncreasesAfterExecute(): void
    {
        $db = $this->makeSqliteDriver();

        $initialCount = $db->getCount();

        $db->setQuery('SELECT 1');
        $db->execute();

        self::assertGreaterThan($initialCount, $db->getCount());
    }

    // -------------------------------------------------------------------------
    // setDebug() / getLog()
    // -------------------------------------------------------------------------

    public function testSetDebugReturnsPreviousLevel(): void
    {
        $db = $this->makeSqliteDriver();

        $old = $db->setDebug(true);

        self::assertFalse($old);

        $db->setDebug(false);
    }

    public function testQueryIsLoggedWhenDebugEnabled(): void
    {
        $db = $this->makeSqliteDriver();

        $db->setDebug(true);
        $db->setQuery('SELECT 42');
        $db->execute();
        $db->setDebug(false);

        $log = $db->getLog();

        self::assertNotEmpty($log, 'Log must not be empty when debug mode is on.');
        self::assertStringContainsString('SELECT 42', $log[0]);
    }

    public function testQueryIsNotLoggedWhenDebugDisabled(): void
    {
        $db = $this->makeSqliteDriver();

        $db->setDebug(false);
        $db->setQuery('SELECT 99');
        $db->execute();

        self::assertEmpty($db->getLog(), 'Log must be empty when debug mode is off.');
    }

    // -------------------------------------------------------------------------
    // getVersion()
    // -------------------------------------------------------------------------

    public function testGetVersionReturnsNonEmptyString(): void
    {
        $db = $this->makeSqliteDriver();

        $version = $db->getVersion();

        self::assertIsString($version);
        self::assertNotEmpty($version);
    }

    // -------------------------------------------------------------------------
    // quote() / quoteName()
    // -------------------------------------------------------------------------

    public static function quoteProvider(): array
    {
        return [
            'simple string'          => ["hello", "'hello'"],
            'string with apostrophe' => ["it's", "'it''s'"],
            'empty string'           => ['', "''"],
        ];
    }

    #[DataProvider('quoteProvider')]
    public function testQuoteWrapsInSingleQuotes(string $input, string $expected): void
    {
        $db = $this->makeSqliteDriver();

        self::assertSame($expected, $db->quote($input));
    }

    public function testQuoteArrayWrapsEachElement(): void
    {
        $db = $this->makeSqliteDriver();

        $result = $db->quote(['a', 'b', 'c']);

        self::assertIsArray($result);
        self::assertCount(3, $result);

        foreach ($result as $quoted) {
            self::assertMatchesRegularExpression("/^'.*'$/", $quoted);
        }
    }

    public function testQuoteNameBackticksIdentifier(): void
    {
        $db = $this->makeSqliteDriver();

        self::assertSame('`foo`', $db->quoteName('foo'));
    }

    public function testQuoteNameHandlesDotNotation(): void
    {
        $db = $this->makeSqliteDriver();

        self::assertSame('`table`.`column`', $db->quoteName('table.column'));
    }

    // -------------------------------------------------------------------------
    // replacePrefix()
    // -------------------------------------------------------------------------

    public function testReplacePrefixSubstitutesPlaceholder(): void
    {
        $db = $this->makeSqliteDriver(['prefix' => 'app_']);

        $sql = $db->replacePrefix('SELECT * FROM #__users');

        self::assertStringContainsString('app_users', $sql);
        self::assertStringNotContainsString('#__', $sql);
    }

    public function testReplacePrefixIgnoresPrefixInsideStringLiterals(): void
    {
        $db = $this->makeSqliteDriver(['prefix' => 'app_']);

        // The #__ inside a quoted string should NOT be replaced.
        $sql = $db->replacePrefix("SELECT '#__users' AS alias FROM #__accounts");

        self::assertStringContainsString("'#__users'", $sql);
        self::assertStringContainsString('app_accounts', $sql);
    }

    // -------------------------------------------------------------------------
    // splitSql()
    // -------------------------------------------------------------------------

    public static function splitSqlProvider(): array
    {
        return [
            'single statement no semicolon' => [
                'SELECT 1',
                ['SELECT 1'],
            ],
            'two statements' => [
                'SELECT 1; SELECT 2',
                ['SELECT 1;', ' SELECT 2'],
            ],
            'semicolon inside quoted string not split' => [
                "SELECT 'a;b' FROM t",
                ["SELECT 'a;b' FROM t"],
            ],
        ];
    }

    #[DataProvider('splitSqlProvider')]
    public function testSplitSql(string $input, array $expected): void
    {
        $result = Driver::splitSql($input);

        self::assertSame($expected, $result);
    }

    // -------------------------------------------------------------------------
    // Magic aliases q() / qn()
    // -------------------------------------------------------------------------

    public function testMagicQAliasCallsQuote(): void
    {
        $db = $this->makeSqliteDriver();

        self::assertSame($db->quote('hello'), $db->q('hello'));
    }

    public function testMagicQnAliasCallsQuoteName(): void
    {
        $db = $this->makeSqliteDriver();

        self::assertSame($db->quoteName('foo'), $db->qn('foo'));
    }

    public function testMagicNqAliasCallsQuoteName(): void
    {
        $db = $this->makeSqliteDriver();

        self::assertSame($db->quoteName('bar'), $db->nq('bar'));
    }

    // -------------------------------------------------------------------------
    // getDateFormat() / getNullDate()
    // -------------------------------------------------------------------------

    public function testGetDateFormatReturnsExpectedPattern(): void
    {
        $db = $this->makeSqliteDriver();

        self::assertSame('Y-m-d H:i:s', $db->getDateFormat());
    }

    public function testGetNullDateIsString(): void
    {
        $db = $this->makeSqliteDriver();

        self::assertIsString($db->getNullDate());
    }

    // -------------------------------------------------------------------------
    // isMinimumVersion()
    // -------------------------------------------------------------------------

    public function testIsMinimumVersionReturnsBool(): void
    {
        $db = $this->makeSqliteDriver();

        self::assertIsBool($db->isMinimumVersion());
    }

    // -------------------------------------------------------------------------
    // hasUTFSupport()
    // -------------------------------------------------------------------------

    public function testHasUTFSupportReturnsBool(): void
    {
        $db = $this->makeSqliteDriver();

        self::assertIsBool($db->hasUTFSupport());
    }

    // -------------------------------------------------------------------------
    // Basic round-trip: create table, insert, query
    // -------------------------------------------------------------------------

    public function testBasicRoundTripInsertAndSelect(): void
    {
        $db = $this->makeSqliteDriver();

        // Create a table.
        $db->setQuery('CREATE TABLE test_rt (id INTEGER PRIMARY KEY, val TEXT)');
        $db->execute();

        // Insert a row.
        $db->setQuery("INSERT INTO test_rt (val) VALUES ('hello')");
        $db->execute();

        // Select it back.
        $db->setQuery('SELECT val FROM test_rt WHERE id = 1');
        $result = $db->loadResult();

        self::assertSame('hello', $result);
    }

    public function testLoadAssocReturnsAssociativeRow(): void
    {
        $db = $this->makeSqliteDriver();

        $db->setQuery('CREATE TABLE test_la (id INTEGER PRIMARY KEY, name TEXT)');
        $db->execute();

        $db->setQuery("INSERT INTO test_la (name) VALUES ('world')");
        $db->execute();

        $db->setQuery('SELECT id, name FROM test_la LIMIT 1');
        $row = $db->loadAssoc();

        self::assertIsArray($row);
        self::assertArrayHasKey('name', $row);
        self::assertSame('world', $row['name']);
    }

    public function testLoadObjectReturnsStdClass(): void
    {
        $db = $this->makeSqliteDriver();

        $db->setQuery('CREATE TABLE test_lo (id INTEGER PRIMARY KEY, name TEXT)');
        $db->execute();

        $db->setQuery("INSERT INTO test_lo (name) VALUES ('object')");
        $db->execute();

        $db->setQuery('SELECT id, name FROM test_lo LIMIT 1');
        $obj = $db->loadObject();

        self::assertIsObject($obj);
        self::assertSame('object', $obj->name);
    }

    public function testLoadColumnReturnsIndexedArray(): void
    {
        $db = $this->makeSqliteDriver();

        $db->setQuery('CREATE TABLE test_lc (id INTEGER PRIMARY KEY, val TEXT)');
        $db->execute();

        $db->setQuery("INSERT INTO test_lc (val) VALUES ('alpha')");
        $db->execute();

        $db->setQuery("INSERT INTO test_lc (val) VALUES ('beta')");
        $db->execute();

        $db->setQuery('SELECT val FROM test_lc ORDER BY id');
        $list = $db->loadColumn();

        self::assertSame(['alpha', 'beta'], $list);
    }
}
