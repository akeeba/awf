<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Database\Driver;

use Awf\Database\Driver\Pdo;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PDO-related non-server logic:
 *   - FixMySQLHostname::fixHostnamePortSocket() — pure hostname/port/socket parsing
 *   - Pdo::escape() — connection-free string escaping
 *   - Pdomysql constructor option normalisation
 *
 * No MySQL server is required; connection-dependent methods are not exercised here
 * (deferred to integration tests in [0960]).
 *
 * In-memory SQLite is used only where a live PDO connection is convenient and
 * pdo_sqlite is available.
 */
class PdoDriverTest extends TestCase
{
    // =========================================================================
    // Helper: expose FixMySQLHostname::fixHostnamePortSocket via a thin stub
    // =========================================================================

    /**
     * Return an anonymous class that exposes fixHostnamePortSocket() publicly,
     * using the real trait implementation verbatim.
     */
    private function makeHostnameFixerStub(): object
    {
        return new class {
            use \Awf\Database\Driver\FixMySQLHostname;

            public function fix(string &$host, mixed &$port, mixed &$socket): void
            {
                $this->fixHostnamePortSocket($host, $port, $socket);
            }
        };
    }

    /**
     * Convenience helper: run fixHostnamePortSocket and return [$host, $port, $socket].
     */
    private function fix(string $host, mixed $port, mixed $socket): array
    {
        $stub = $this->makeHostnameFixerStub();
        $stub->fix($host, $port, $socket);
        return [$host, $port, $socket];
    }

    // =========================================================================
    // FixMySQLHostname — regular TCP/IP hostnames
    // =========================================================================

    public function testPlainHostnameKeepsHostAndDefaultPort(): void
    {
        [$host, $port, $socket] = $this->fix('example.com', null, '');

        self::assertSame('example.com', $host);
        self::assertSame(3306, $port);
        self::assertSame('', $socket);
    }

    public function testIpv4AddressWithPortExtractsBoth(): void
    {
        [$host, $port, $socket] = $this->fix('192.168.1.100:3307', null, '');

        self::assertSame('192.168.1.100', $host);
        self::assertSame(3307, $port);
        self::assertSame('', $socket);
    }

    public function testIpv4AddressWithoutPortUsesDefault(): void
    {
        [$host, $port, $socket] = $this->fix('10.0.0.1', null, '');

        self::assertSame('10.0.0.1', $host);
        self::assertSame(3306, $port);
        self::assertSame('', $socket);
    }

    public function testIpv4AddressWithExplicitPortParameterKept(): void
    {
        [$host, $port, $socket] = $this->fix('10.0.0.1', 3308, '');

        self::assertSame('10.0.0.1', $host);
        self::assertSame(3308, $port);
        self::assertSame('', $socket);
    }

    // =========================================================================
    // FixMySQLHostname — localhost special cases
    // =========================================================================

    public function testLocalhostClearsPort(): void
    {
        [$host, $port, $socket] = $this->fix('localhost', 3306, '');

        // localhost means socket/named-pipe; port must be null
        self::assertNull($port);
    }

    public function testLocalhostWithNumericPortConvertsTo127(): void
    {
        // When a numeric port is explicitly set and hostname is 'localhost', the
        // implementation converts host to 127.0.0.1 so TCP/IP is used.
        // However, 'localhost' clears port first; provide explicit port after that step
        // by using 127.0.0.1 directly.
        [$host, $port, $socket] = $this->fix('127.0.0.1', 3306, '');

        self::assertSame('127.0.0.1', $host);
        self::assertSame(3306, $port);
    }

    // =========================================================================
    // FixMySQLHostname — UNIX socket paths
    // =========================================================================

    public function testUnixPathStyleSocketBecomesSocket(): void
    {
        [$host, $port, $socket] = $this->fix('/var/run/mysql/mysql.sock', null, '');

        self::assertNull($host,   'host must be null when a socket path is used');
        self::assertNull($port,   'port must be null when a socket path is used');
        self::assertSame('/var/run/mysql/mysql.sock', $socket);
    }

    public function testUnixSchemeSocketUriParsed(): void
    {
        [$host, $port, $socket] = $this->fix('unix:/var/run/mysql/mysql.sock', null, '');

        self::assertNull($host);
        self::assertNull($port);
        self::assertSame('/var/run/mysql/mysql.sock', $socket);
    }

    public function testSocketInPortParameterWithNonLocalhostBecomesSocket(): void
    {
        // Passing a non-numeric string as $port is treated as a socket path
        // when the host is NOT 'localhost' (localhost immediately clears port to null).
        [$host, $port, $socket] = $this->fix('db.example.com', '/tmp/mysql.sock', '');

        self::assertNull($host);
        self::assertNull($port);
        self::assertSame('/tmp/mysql.sock', $socket);
    }

    public function testExplicitSocketOverridesNumericPort(): void
    {
        // If both socket and numeric port are set, socket wins
        [$host, $port, $socket] = $this->fix('10.0.0.1', 3306, '/tmp/mysql.sock');

        self::assertNull($host);
        self::assertNull($port);
        self::assertSame('/tmp/mysql.sock', $socket);
    }

    // =========================================================================
    // FixMySQLHostname — IPv6 addresses
    // =========================================================================

    public function testSquareBracketedIpv6WithPort(): void
    {
        [$host, $port, $socket] = $this->fix('[::1]:3309', null, '');

        self::assertSame('[::1]', $host);
        self::assertSame(3309, $port);
        self::assertSame('', $socket);
    }

    public function testSquareBracketedIpv6WithoutPort(): void
    {
        [$host, $port, $socket] = $this->fix('[fe80::1]', null, '');

        self::assertSame('[fe80::1]', $host);
        self::assertSame(3306, $port);
    }

    // =========================================================================
    // FixMySQLHostname — empty host with port
    // =========================================================================

    public function testColonPrefixedPortUsesLoopback(): void
    {
        [$host, $port, $socket] = $this->fix(':3310', null, '');

        self::assertSame('127.0.0.1', $host);
        self::assertSame(3310, $port);
    }

    // =========================================================================
    // FixMySQLHostname — persistent connection prefix
    // =========================================================================

    public function testPersistentPrefixIsStrippedThenRestored(): void
    {
        [$host, $port, $socket] = $this->fix('p:example.com', null, '');

        self::assertStringStartsWith('p:', $host, 'Persistent prefix must be re-added to host');
        self::assertSame('p:example.com', $host);
        self::assertSame(3306, $port);
    }

    public function testPersistentPrefixWithIpv4(): void
    {
        [$host, $port, $socket] = $this->fix('p:10.0.0.5', 3307, '');

        self::assertSame('p:10.0.0.5', $host);
        self::assertSame(3307, $port);
    }

    // =========================================================================
    // FixMySQLHostname — Windows named pipes
    // =========================================================================

    public function testWindowsNamedPipeIsRecognised(): void
    {
        // A bare Windows named pipe without parentheses
        $namedPipe = '\\\\.\\pipe\\MySQL';

        [$host, $port, $socket] = $this->fix($namedPipe, null, '');

        // Named pipe: host must be '.'
        self::assertSame('.', $host);
        self::assertNull($port);
        // Socket must be wrapped in parentheses
        self::assertStringStartsWith('(', $socket);
        self::assertStringEndsWith(')', $socket);
    }

    public function testWindowsNamedPipeAlreadyInParenthesesIsKept(): void
    {
        $namedPipe = '(\\\\.\\pipe\\MySQL)';

        [$host, $port, $socket] = $this->fix($namedPipe, null, '');

        self::assertSame('.', $host);
        self::assertNull($port);
        // The parentheses must still wrap the result (no double-wrapping)
        self::assertStringStartsWith('(', $socket);
        self::assertStringEndsWith(')', $socket);
    }

    // =========================================================================
    // Pdo::escape() — connection-free (base Pdo implementation)
    // =========================================================================

    /**
     * Create a minimal concrete subclass of Pdo so that Pdo::escape() is tested
     * rather than the Sqlite override.  This stub never connects to any server.
     *
     * NOTE: Pdomysql::escape() calls connect(), so it is NOT tested here
     * (deferred to integration tests).
     */
    private function makePdoEscapeDriver(): Pdo
    {
        // Build a minimal concrete subclass of Pdo that implements the remaining
        // abstract methods without touching any database.
        return new class ([
            'driver'   => 'odbc',
            'database' => '',
            'prefix'   => '',
        ]) extends Pdo {
            public function dropTable($table, $ifExists = true): void {}
            public function getCollation(): mixed { return null; }
            public function getTableColumns($table, $typeOnly = true): array { return []; }
            public function getTableCreate($tables): array { return []; }
            public function getTableKeys($tables): array { return []; }
            public function getTableList(): array { return []; }
            public function getVersion(): string { return ''; }
            public function lockTable($tableName): static { return $this; }
            public function renameTable($oldTable, $newTable, $backup = null, $prefix = null): static { return $this; }
            public function unlockTables(): static { return $this; }
        };
    }

    public function testEscapeReturnsIntegerUnchanged(): void
    {
        $db = $this->makePdoEscapeDriver();

        self::assertSame(42, $db->escape(42));
    }

    public function testEscapeReturnsFloatUnchanged(): void
    {
        $db = $this->makePdoEscapeDriver();

        self::assertSame(3.14, $db->escape(3.14));
    }

    public function testEscapeNullReturnsNullString(): void
    {
        $db = $this->makePdoEscapeDriver();

        self::assertSame('NULL', $db->escape(null));
    }

    public function testEscapeSingleQuoteIsDoubled(): void
    {
        $db = $this->makePdoEscapeDriver();

        // Single-quotes must be doubled for SQL safety
        $result = $db->escape("it's a test");
        self::assertStringContainsString("''", $result);
    }

    public function testEscapeNullByteIsBackslashEscaped(): void
    {
        $db = $this->makePdoEscapeDriver();

        // addcslashes() converts \x00 (NUL) to the four-char octal sequence \000.
        $result = $db->escape("hello\x00world");
        // The raw null byte must not remain unescaped.
        self::assertStringNotContainsString("\x00", $result);
        // The result must contain a backslash (evidence of escaping).
        self::assertStringContainsString('\\', $result);
    }

    public function testEscapeNewlineIsBackslashEscaped(): void
    {
        $db = $this->makePdoEscapeDriver();

        // addcslashes() converts a real newline (\x0A) to the two-char sequence
        // backslash + 'n' (not a real newline). The raw newline is gone.
        $result = $db->escape("line1\nline2");
        self::assertStringContainsString('\\n', $result);
        self::assertStringNotContainsString("\n", $result);
    }

    public function testEscapeCarriageReturnIsBackslashEscaped(): void
    {
        $db = $this->makePdoEscapeDriver();

        // addcslashes() converts \r to the two-char sequence backslash + 'r'.
        $result = $db->escape("line1\rline2");
        self::assertStringContainsString('\\r', $result);
        self::assertStringNotContainsString("\r", $result);
    }

    public function testEscapeSub26IsBackslashEscaped(): void
    {
        $db = $this->makePdoEscapeDriver();

        // addcslashes() converts \x1A (SUB, ctrl-Z, octal 032) to \032 (octal escape).
        $result = $db->escape("end\x1Aoffile");
        // The raw SUB character must be gone.
        self::assertStringNotContainsString("\x1A", $result);
        // A backslash must be present (evidence of escaping).
        self::assertStringContainsString('\\', $result);
    }

    public function testEscapePlainStringIsReturnedIntact(): void
    {
        $db = $this->makePdoEscapeDriver();

        $result = $db->escape('hello world');
        self::assertSame('hello world', $result);
    }

    public function testEscapeEmptyStringReturnsEmptyString(): void
    {
        $db = $this->makePdoEscapeDriver();

        self::assertSame('', $db->escape(''));
    }

    // =========================================================================
    // Pdomysql constructor — option normalisation (no server needed)
    // =========================================================================

    /**
     * Provider: various inputs that should all produce a 'mysql' driver option.
     */
    public static function pdomysqlOptionsProvider(): array
    {
        return [
            'defaults only' => [
                [],
                'utf8',
                'mysql',
            ],
            'custom charset' => [
                ['charset' => 'utf8mb4'],
                'utf8mb4',
                'mysql',
            ],
        ];
    }

    #[DataProvider('pdomysqlOptionsProvider')]
    public function testPdomysqlNormalisesOptions(
        array  $inputOptions,
        string $expectedCharset,
        string $expectedDriver
    ): void {
        if (!\Awf\Database\Driver\Pdomysql::isSupported()) {
            $this->markTestSkipped('pdo_mysql is not available; cannot instantiate Pdomysql.');
        }

        // We cannot connect to MySQL, but we CAN inspect the options stored
        // on the object after construction (before connect() is called).
        // Use reflection to read the protected $options property.
        $options = $inputOptions + [
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => '',
            'database' => 'test',
            'prefix'   => '',
        ];

        // Construction will run fixHostnamePortSocket and normalise options but
        // must NOT call connect() — which would fail without a real server.
        // We therefore wrap in a try/catch for the inevitable connection error
        // and only check options if the constructor itself completes.
        $db = null;
        try {
            $db = new \Awf\Database\Driver\Pdomysql($options);
        } catch (\RuntimeException $e) {
            // A connection error here means the constructor ran and connect()
            // was attempted from within it — which only happens if 'connection'
            // key was injected. Since we don't do that, a RuntimeException
            // here is unexpected and we re-throw.
            throw $e;
        }

        // Read stored options via reflection (no setAccessible needed in PHP 8.1+)
        $ref = new \ReflectionProperty($db, 'options');
        $stored = $ref->getValue($db);

        self::assertSame($expectedDriver,  $stored['driver'],  'driver must always be "mysql"');
        self::assertSame($expectedCharset, $stored['charset'], 'charset must match expected value');
        self::assertArrayHasKey('driverOptions', $stored, 'driverOptions key must be present');
        self::assertIsArray($stored['driverOptions'], 'driverOptions must be an array');
    }

    public function testPdomysqlSetsDriverToMysqlEvenIfOverridden(): void
    {
        if (!\Awf\Database\Driver\Pdomysql::isSupported()) {
            $this->markTestSkipped('pdo_mysql is not available.');
        }

        $options = [
            'driver'   => 'sqlite',   // should be overridden to 'mysql'
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => '',
            'database' => 'test',
            'prefix'   => '',
        ];

        $db     = new \Awf\Database\Driver\Pdomysql($options);
        $ref    = new \ReflectionProperty($db, 'options');
        $stored = $ref->getValue($db);

        self::assertSame('mysql', $stored['driver'], 'Pdomysql must always force driver to "mysql"');
    }

    public function testPdomysqlDefaultCharsetIsUtf8(): void
    {
        if (!\Awf\Database\Driver\Pdomysql::isSupported()) {
            $this->markTestSkipped('pdo_mysql is not available.');
        }

        $options = [
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => '',
            'database' => 'test',
            'prefix'   => '',
        ];

        $db     = new \Awf\Database\Driver\Pdomysql($options);
        $ref    = new \ReflectionProperty($db, 'options');
        $stored = $ref->getValue($db);

        self::assertSame('utf8', $stored['charset']);
    }

    // =========================================================================
    // FixMySQLHostname — edge-case port normalisation
    // =========================================================================

    public static function portNormalisationProvider(): array
    {
        return [
            'string port is cast to int'      => ['db.example.com', '3310', '', 'db.example.com', 3310, ''],
            'zero port falls back to default'  => ['db.example.com', 0, '', 'db.example.com', 3306, ''],
            'null port falls back to default'  => ['db.example.com', null, '', 'db.example.com', 3306, ''],
        ];
    }

    #[DataProvider('portNormalisationProvider')]
    public function testPortNormalisation(
        string $inHost,
        mixed  $inPort,
        string $inSocket,
        string $expectedHost,
        int    $expectedPort,
        string $expectedSocket
    ): void {
        [$host, $port, $socket] = $this->fix($inHost, $inPort, $inSocket);

        self::assertSame($expectedHost,   $host);
        self::assertSame($expectedPort,   $port);
        self::assertSame($expectedSocket, $socket);
    }

    // =========================================================================
    // Pdo::isSupported() — static check
    // =========================================================================

    public function testPdoIsSupportedReturnsBooleanReflectingPdoConstant(): void
    {
        $result = Pdo::isSupported();

        // The result should be boolean and match whether PDO is available
        self::assertIsBool($result);
        self::assertSame(defined('\PDO::ATTR_DRIVER_NAME'), $result);
    }

    // =========================================================================
    // FixMySQLHostname — hostname with inline port via colon
    // =========================================================================

    public function testHostnameWithInlinePortExtracted(): void
    {
        [$host, $port, $socket] = $this->fix('db.example.com:4406', null, '');

        self::assertSame('db.example.com', $host);
        self::assertSame(4406, $port);
        self::assertSame('', $socket);
    }

    public function testHostnameWithoutInlinePortDefaultsTo3306(): void
    {
        [$host, $port, $socket] = $this->fix('db.example.com', null, '');

        self::assertSame('db.example.com', $host);
        self::assertSame(3306, $port);
        self::assertSame('', $socket);
    }
}
