<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Database;

use Awf\Database\Driver\Pgsql;
use Awf\Tests\Integration\AbstractIntegrationTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

/**
 * Real-server integration tests for the PDO PostgreSQL database driver's connection handling.
 *
 * Skipped unless AWF_TEST_PG_DSN / AWF_TEST_PG_USER (and optionally AWF_TEST_PG_PASS) are set.
 */
#[CoversClass(Pgsql::class)]
final class PgsqlDriverTest extends AbstractIntegrationTestCase
{
    protected function setUp(): void
    {
        if (!Pgsql::isSupported() || !in_array('pgsql', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The pdo_pgsql extension is not available.');
        }
    }

    /**
     * Connection options for the test server, optionally without some keys.
     *
     * @param   array  $override  Keys to set, or null to remove the key.
     */
    private function options(array $override = []): array
    {
        $conn = $this->requirePostgresql();
        $dsn  = (string) preg_replace('/^[a-z]+:/i', '', $conn['dsn']);

        $options = [
            'driver'   => 'pgsql',
            'host'     => '127.0.0.1',
            'port'     => 5432,
            'user'     => $conn['user'],
            'password' => $conn['pass'],
            'database' => '',
            'prefix'   => 'awf_',
            'select'   => true,
        ];

        foreach (preg_split('/[;\s]+/', $dsn, -1, PREG_SPLIT_NO_EMPTY) as $segment) {
            [$key, $val] = explode('=', $segment, 2) + ['', ''];

            if ($key === 'host') {
                $options['host'] = $val;
            } elseif ($key === 'port') {
                $options['port'] = (int) $val;
            } elseif ($key === 'dbname') {
                $options['database'] = $val;
            }
        }

        foreach ($override as $key => $value) {
            if ($value === null) {
                unset($options[$key]);
            } else {
                $options[$key] = $value;
            }
        }

        return $options;
    }

    /**
     * Run the callback with a warning-capturing error handler.
     *
     * @return  array{0: mixed, 1: string[], 2: ?\Throwable}  Result, captured warnings/notices, thrown exception
     */
    private function captureWarnings(callable $callback): array
    {
        $warnings  = [];
        $result    = null;
        $exception = null;

        set_error_handler(
            static function (int $errno, string $errstr) use (&$warnings): bool {
                $warnings[] = $errstr;

                return true;
            },
            E_ALL
        );

        try {
            $result = $callback();
        } catch (\Throwable $e) {
            $exception = $e;
        } finally {
            restore_error_handler();
        }

        return [$result, $warnings, $exception];
    }

    public function testConnectWithoutSelectOptionRaisesNoWarning(): void
    {
        $options = $this->options(['select' => null]);

        [$db, $warnings, $exception] = $this->captureWarnings(
            static fn() => new Pgsql($options)
        );

        self::assertNull($exception);
        self::assertSame([], $warnings);
        self::assertTrue($db->connected());
    }

    public function testConnectWithoutPortOptionRaisesNoWarning(): void
    {
        $options = $this->options(['port' => null]);

        [, $warnings, $exception] = $this->captureWarnings(
            static fn() => new Pgsql($options)
        );

        self::assertSame([], $warnings);

        // The port falls back to 5432. Whether anything listens there depends on the machine: either the connection
        // succeeds or it fails with a RuntimeException. Only an undefined-key warning (or another error) is a bug.
        if ($exception !== null) {
            self::assertInstanceOf(RuntimeException::class, $exception);
        }
    }

    public function testConnectionFailureThrowsRuntimeExceptionWithPdoExceptionChained(): void
    {
        $options = $this->options(['password' => 'definitely-the-wrong-password']);

        try {
            new Pgsql($options);
            self::fail('No exception was thrown');
        } catch (RuntimeException $e) {
            self::assertStringStartsWith('Could not connect to PDO: ', $e->getMessage());
            self::assertSame(2, $e->getCode());
            self::assertInstanceOf(\PDOException::class, $e->getPrevious());
        }
    }

    public function testConnectionFailureOnUnusedPortThrowsRuntimeException(): void
    {
        $options = $this->options(['port' => 1]);

        try {
            new Pgsql($options);
            self::fail('No exception was thrown');
        } catch (RuntimeException $e) {
            self::assertStringStartsWith('Could not connect to PDO: ', $e->getMessage());
            self::assertSame(2, $e->getCode());
            self::assertInstanceOf(\PDOException::class, $e->getPrevious());
        }
    }

    public function testDriverFromOptionsReportsConnectionFailure(): void
    {
        $options = $this->options(['port' => 1]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to connect to the Database: Could not connect to PDO: ');

        \Awf\Database\Driver::fromOptions($options);
    }

    public function testQueryReconnectsLazilyAfterDisconnect(): void
    {
        $db = new Pgsql($this->options());

        $db->disconnect();
        self::assertFalse($db->connected());

        $db->setQuery('SELECT 1')->execute();

        self::assertTrue($db->connected());
    }

    /**
     * Run the callback against a fresh table, returning the driver to it; the table is dropped afterwards.
     */
    private function withTable(callable $callback): void
    {
        $db = new Pgsql($this->options());

        try {
            $db->setQuery('DROP TABLE IF EXISTS awf_pgsql_txn_test')->execute();
            $db->setQuery('CREATE TABLE awf_pgsql_txn_test (id INT PRIMARY KEY)')->execute();

            $callback($db);
        } finally {
            try {
                $db->setQuery('DROP TABLE IF EXISTS awf_pgsql_txn_test')->execute();
            } catch (\Throwable $e) {
                // The connection may be in a bad state; the table is dropped on the next run.
            }

            $db->disconnect();
        }
    }

    private function ids(Pgsql $db): array
    {
        return array_map(
            'intval',
            $db->setQuery('SELECT id FROM awf_pgsql_txn_test ORDER BY id')->loadColumn() ?: []
        );
    }

    public function testFailedStatementInTransactionDoesNotDropTheTransaction(): void
    {
        $this->withTable(function (Pgsql $db): void {
            $db->transactionStart();
            $db->setQuery('INSERT INTO awf_pgsql_txn_test (id) VALUES (10)')->execute();

            try {
                $db->setQuery('SELECT * FROM awf_pgsql_no_such_table')->execute();
                self::fail('The failing statement must throw.');
            } catch (RuntimeException $e) {
                self::assertStringContainsString('awf_pgsql_no_such_table', $e->getMessage());
            }

            // The transaction is aborted: this must fail, not run on a new connection.
            try {
                $db->setQuery('INSERT INTO awf_pgsql_txn_test (id) VALUES (11)')->execute();
                self::fail('A statement in an aborted transaction must throw.');
            } catch (RuntimeException $e) {
                self::assertStringContainsString('current transaction is aborted', $e->getMessage());
            }

            $db->transactionRollback();

            self::assertSame([], $this->ids($db));
        });
    }
}
