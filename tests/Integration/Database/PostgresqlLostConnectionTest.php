<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Database;

use Awf\Database\Driver\Postgresql;
use Awf\Tests\Integration\AbstractIntegrationTestCase;

/**
 * The native PostgreSQL driver against a backend the server really terminates (pg_terminate_backend).
 */
final class PostgresqlLostConnectionTest extends AbstractIntegrationTestCase
{
    protected function setUp(): void
    {
        if (!Postgresql::isSupported()) {
            $this->markTestSkipped('The pg_connect PHP extension is not available.');
        }
    }

    private function build(): Postgresql
    {
        $conn    = $this->requirePostgresql();
        $options = [
            'driver' => 'postgresql', 'host' => '127.0.0.1', 'port' => 5432, 'user' => $conn['user'],
            'password' => $conn['pass'], 'database' => '', 'prefix' => 'awf_', 'select' => true,
        ];

        foreach (preg_split('/[;\s]+/', (string) preg_replace('/^[a-z]+:/i', '', $conn['dsn']), -1, PREG_SPLIT_NO_EMPTY) as $segment) {
            [$key, $val] = explode('=', $segment, 2) + ['', ''];

            if ($key === 'host') {
                $options['host'] = $val;
            } elseif ($key === 'port') {
                $options['port'] = (int) $val;
            } elseif ($key === 'dbname') {
                $options['database'] = $val;
            }
        }

        return new Postgresql($options);
    }

    private function terminate(int $pid): void
    {
        $conn = $this->requirePostgresql();
        $pdo  = new \PDO($conn['dsn'], $conn['user'], $conn['pass'], [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('SELECT pg_terminate_backend(' . $pid . ')');
        $pdo = null;

        // Let the server finish tearing the connection down.
        usleep(300000);
    }

    private function pid(Postgresql $db): int
    {
        return (int) $db->setQuery('SELECT pg_backend_pid()')->loadResult();
    }

    public function testTheFirstQueryAfterALostConnectionReconnectsAndSucceeds(): void
    {
        $db = $this->build();

        try {
            $oldPid = $this->pid($db);
            $this->terminate($oldPid);

            $newPid = $this->pid($db);

            self::assertNotSame($oldPid, $newPid, 'The driver must have opened a new connection.');
            self::assertSame($newPid, $this->pid($db), 'Later queries use the new connection.');
        } finally {
            $db->disconnect();
        }
    }

    public function testAFailedStatementInsideATransactionDoesNotReconnect(): void
    {
        $db = $this->build();

        try {
            $pid = $this->pid($db);

            $db->transactionStart();

            try {
                $db->setQuery('SELECT * FROM "awf_no_such_table"')->execute();
                self::fail('The query should have failed.');
            } catch (\RuntimeException $e) {
                self::assertStringContainsString('does not exist', $e->getMessage());
            }

            // The connection and its (aborted) transaction are still there.
            try {
                $db->setQuery('SELECT 1')->execute();
                self::fail('The transaction should be aborted.');
            } catch (\RuntimeException $e) {
                self::assertStringContainsString('current transaction is aborted', $e->getMessage());
            }

            $db->transactionRollback();

            self::assertSame($pid, $this->pid($db), 'The driver must not have reconnected.');
        } finally {
            $db->disconnect();
        }
    }
}
