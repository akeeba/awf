<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Database;

use Awf\Database\Driver;
use Awf\Tests\Integration\AbstractIntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

/**
 * The MySQL drivers with their DEFAULT settings, against a connection the server really drops (KILL).
 *
 * Nothing here switches the mysqli report mode or the PDO error mode: the application gets what PHP gives it.
 */
final class LostConnectionTest extends AbstractIntegrationTestCase
{
    public static function mysqlDrivers(): array
    {
        return [
            'mysqli'   => ['mysqli'],
            'pdomysql' => ['pdomysql'],
        ];
    }

    private function build(string $family): Driver
    {
        if ($family === 'pdomysql' && !extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('The pdo_mysql extension is not available.');
        }

        if ($family === 'mysqli' && !extension_loaded('mysqli')) {
            $this->markTestSkipped('The mysqli extension is not available.');
        }

        $conn = $this->requireMysql();
        $opts = [
            'prefix'   => 'awf_',
            'select'   => true,
            'host'     => '127.0.0.1',
            'port'     => 3306,
            'user'     => $conn['user'],
            'password' => $conn['pass'],
            'database' => '',
            'driver'   => $family,
        ];

        $dsn = (string) preg_replace('/^[a-z]+:/i', '', $conn['dsn']);

        foreach (preg_split('/[;\s]+/', $dsn, -1, PREG_SPLIT_NO_EMPTY) as $segment) {
            [$key, $val] = explode('=', $segment, 2) + ['', ''];

            if ($key === 'host') {
                $opts['host'] = $val;
            } elseif ($key === 'port') {
                $opts['port'] = (int) $val;
            } elseif ($key === 'dbname') {
                $opts['database'] = $val;
            }
        }

        if ($family === 'mysqli') {
            $opts['host'] .= ':' . $opts['port'];

            return new \Awf\Database\Driver\Mysqli($opts);
        }

        return new \Awf\Database\Driver\Pdomysql($opts);
    }

    /** Kill a server-side connection from a second, independent connection. */
    private function kill(int $connectionId): void
    {
        $conn = $this->requireMysql();
        $pdo  = new \PDO($conn['dsn'], $conn['user'], $conn['pass'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('KILL ' . $connectionId);
        $pdo = null;

        // Let the server finish tearing the connection down.
        usleep(200000);
    }

    private function connectionId(Driver $db): int
    {
        $db->setQuery('SELECT CONNECTION_ID()');

        return (int) $db->loadResult();
    }

    #[DataProvider('mysqlDrivers')]
    public function testConnectedAfterDroppedConnectionReturnsFalseWithoutThrowing(string $family): void
    {
        $db = $this->build($family);

        try {
            $this->kill($this->connectionId($db));

            self::assertFalse($db->connected());
        } finally {
            $db->disconnect();
        }
    }

    #[DataProvider('mysqlDrivers')]
    public function testLostConnectionNeverLeaksNativeExceptionAndDriverRecovers(string $family): void
    {
        $db = $this->build($family);

        try {
            $oldId = $this->connectionId($db);
            $this->kill($oldId);

            try {
                $newId = $this->connectionId($db);
            } catch (RuntimeException $e) {
                // The PDO driver retries a statement prepared on the dead connection (item 35). A RuntimeException is
                // acceptable here; a raw PDOException or mysqli_sql_exception (both extend RuntimeException) is not.
                $this->assertNotNative($e);
                $newId = $this->connectionId($db);
            }

            self::assertNotSame($oldId, $newId, 'The driver must have opened a new connection.');
        } finally {
            $db->disconnect();
        }
    }

    public function testMysqliReconnectsTransparentlyAfterKill(): void
    {
        $db = $this->build('mysqli');

        try {
            $oldId = $this->connectionId($db);
            $this->kill($oldId);

            $newId = $this->connectionId($db);

            self::assertNotSame($oldId, $newId);
        } finally {
            $db->disconnect();
        }
    }

    #[DataProvider('mysqlDrivers')]
    public function testSyntaxErrorThrowsRuntimeExceptionWithDriverErrorNumber(string $family): void
    {
        $db = $this->build($family);

        try {
            $db->setQuery('SELEC 1');
            $db->execute();
            self::fail('A syntax error must throw.');
        } catch (RuntimeException $e) {
            self::assertSame(1064, $e->getCode());
            self::assertStringContainsString('You have an error in your SQL syntax', $e->getMessage());
            self::assertStringContainsString('SELEC 1', $e->getMessage());
            $this->assertNativeExceptionChained($family, $e);
        } finally {
            $db->disconnect();
        }
    }

    #[DataProvider('mysqlDrivers')]
    public function testMissingTableThrowsRuntimeExceptionWithDriverErrorNumber(string $family): void
    {
        $db = $this->build($family);

        try {
            $db->setQuery('SELECT * FROM awf_no_such_table_at_all');
            $db->execute();
            self::fail('A missing table must throw.');
        } catch (RuntimeException $e) {
            self::assertSame(1146, $e->getCode());
            self::assertStringContainsString("awf_no_such_table_at_all' doesn't exist", $e->getMessage());
            $this->assertNativeExceptionChained($family, $e);
        } finally {
            $db->disconnect();
        }
    }

    private function assertNotNative(\Throwable $e): void
    {
        self::assertNotInstanceOf(\PDOException::class, $e, 'A raw PDOException escaped the driver.');
        self::assertNotInstanceOf(\mysqli_sql_exception::class, $e, 'A raw mysqli_sql_exception escaped the driver.');
    }

    private function assertNativeExceptionChained(string $family, RuntimeException $e): void
    {
        $this->assertNotNative($e);

        if ($family === 'pdomysql') {
            self::assertInstanceOf(\PDOException::class, $e->getPrevious());

            return;
        }

        // PHP 7.4 - 8.0 return false and have nothing to chain; PHP 8.1+ throws.
        if (PHP_VERSION_ID >= 80100) {
            self::assertInstanceOf(\mysqli_sql_exception::class, $e->getPrevious());
        }
    }

    private function buildPgsql(): Driver
    {
        if (!extension_loaded('pdo_pgsql')) {
            $this->markTestSkipped('The pdo_pgsql extension is not available.');
        }

        $conn = $this->requirePostgresql();
        $opts = [
            'driver'   => 'pgsql',
            'prefix'   => 'awf_',
            'select'   => true,
            'host'     => '127.0.0.1',
            'port'     => 5432,
            'user'     => $conn['user'],
            'password' => $conn['pass'],
            'database' => '',
        ];

        $dsn = (string) preg_replace('/^[a-z]+:/i', '', $conn['dsn']);

        foreach (preg_split('/[;\s]+/', $dsn, -1, PREG_SPLIT_NO_EMPTY) as $segment) {
            [$key, $val] = explode('=', $segment, 2) + ['', ''];

            if ($key === 'host') {
                $opts['host'] = $val;
            } elseif ($key === 'port') {
                $opts['port'] = (int) $val;
            } elseif ($key === 'dbname') {
                $opts['database'] = $val;
            }
        }

        return new \Awf\Database\Driver\Pgsql($opts);
    }

    public function testPgsqlReconnectsTransparentlyAfterBackendIsTerminated(): void
    {
        $db = $this->buildPgsql();

        try {
            $db->setQuery('SELECT pg_backend_pid()');
            $oldPid = (int) $db->loadResult();

            $conn = $this->requirePostgresql();
            $pdo  = new \PDO($conn['dsn'], $conn['user'], $conn['pass'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            $pdo->query('SELECT pg_terminate_backend(' . $oldPid . ')');
            $pdo = null;
            usleep(200000);

            self::assertFalse($db->connected());

            $db->setQuery('SELECT pg_backend_pid()');
            $newPid = (int) $db->loadResult();

            self::assertNotSame($oldPid, $newPid);
        } finally {
            $db->disconnect();
        }
    }

    public function testPgsqlSqlErrorThrowsRuntimeExceptionWithServerMessage(): void
    {
        $db = $this->buildPgsql();

        try {
            $db->setQuery('SELECT * FROM awf_no_such_table_at_all');
            $db->execute();
            self::fail('A missing table must throw.');
        } catch (RuntimeException $e) {
            self::assertNotInstanceOf(\PDOException::class, $e);
            self::assertStringContainsString('awf_no_such_table_at_all', $e->getMessage());
            self::assertNotSame(0, $e->getCode());
        } finally {
            $db->disconnect();
        }
    }
}
