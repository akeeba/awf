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
use Awf\Tests\Integration\Database\Fixture\ReconnectingMysqli;
use Awf\Tests\Integration\Database\Fixture\ReconnectingPdomysql;
use Awf\Tests\Integration\Database\Fixture\ReconnectingPgsql;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;

/**
 * A failed reconnect, or a failed retried query, must not stop a driver from reconnecting later.
 *
 * The PDO PostgreSQL, PDO MySQL and mysqli drivers each carry their own reconnect logic in execute().
 */
final class ReconnectTest extends AbstractIntegrationTestCase
{
    protected function tearDown(): void
    {
        // ReconnectingMysqli switches the process-wide mysqli report mode off. Restore PHP's default.
        if (extension_loaded('mysqli')) {
            mysqli_report(PHP_VERSION_ID >= 80100 ? MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT : MYSQLI_REPORT_OFF);
        }
    }

    public static function drivers(): array
    {
        return [
            'pgsql'    => ['pgsql'],
            'pdomysql' => ['pdomysql'],
            'mysqli'   => ['mysqli'],
        ];
    }

    private function build(string $family): Driver
    {
        $options = ['prefix' => 'awf_', 'select' => true];

        if ($family === 'pgsql') {
            if (!extension_loaded('pdo_pgsql')) {
                $this->markTestSkipped('The pdo_pgsql extension is not available.');
            }

            $opts = $options + $this->parse($this->requirePostgresql(), 5432);

            return new ReconnectingPgsql($opts + ['driver' => 'pgsql']);
        }

        if ($family === 'pdomysql' && !extension_loaded('pdo_mysql')) {
            $this->markTestSkipped('The pdo_mysql extension is not available.');
        }

        if ($family === 'mysqli' && !extension_loaded('mysqli')) {
            $this->markTestSkipped('The mysqli extension is not available.');
        }

        $opts = $options + $this->parse($this->requireMysql(), 3306);

        if ($family === 'mysqli') {
            $opts['host']   = $opts['host'] . ':' . $opts['port'];
            $opts['driver'] = 'mysqli';

            return new ReconnectingMysqli($opts);
        }

        $opts['driver'] = 'pdomysql';

        return new ReconnectingPdomysql($opts);
    }

    private function parse(array $conn, int $defaultPort): array
    {
        $ret = [
            'host'     => '127.0.0.1',
            'port'     => $defaultPort,
            'user'     => $conn['user'],
            'password' => $conn['pass'],
            'database' => '',
        ];

        $dsn = (string) preg_replace('/^[a-z]+:/i', '', $conn['dsn']);

        foreach (preg_split('/[;\s]+/', $dsn, -1, PREG_SPLIT_NO_EMPTY) as $segment) {
            [$key, $val] = explode('=', $segment, 2) + ['', ''];

            if ($key === 'host') {
                $ret['host'] = $val;
            } elseif ($key === 'port') {
                $ret['port'] = (int) $val;
            } elseif ($key === 'dbname') {
                $ret['database'] = $val;
            }
        }

        return $ret;
    }

    private function createProbe(Driver $db): void
    {
        // A temporary table only exists in the session that created it: creating it twice fails, unless a
        // reconnection opened a new session in between.
        $db->setQuery('CREATE TEMPORARY TABLE awf_reconnect_probe (id INT)');
        $db->execute();
    }

    /**
     * Run the probe query on a connection that is lost.
     *
     * The PDO driver prepares the statement in setQuery(), on the old connection, and execute() retries that same
     * statement after reconnecting, so the retry itself fails there. That is a separate defect; what these tests
     * check is that execute() still reconnects.
     */
    private function createProbeAfterReconnect(Driver $db, string $family): void
    {
        try {
            $this->createProbe($db);
        } catch (RuntimeException $e) {
            if ($family !== 'pdomysql') {
                throw $e;
            }
        }
    }

    #[DataProvider('drivers')]
    public function testFailedReconnectDoesNotDisableLaterReconnects(string $family): void
    {
        $db = $this->build($family);

        try {
            $this->createProbe($db);

            // The connection is lost, and so is the server.
            $db->reportDisconnected = true;
            $db->failConnect        = true;

            try {
                $this->createProbe($db);
                self::fail('execute() must throw when it cannot reconnect.');
            } catch (RuntimeException) {
                // Expected.
            }

            // The server is back. The next query opens a new session.
            $db->reportDisconnected = false;
            $db->failConnect        = false;
            $this->createProbe($db);

            // The connection is lost again. execute() must reconnect and run the query again in a new session.
            $db->reportDisconnected = true;
            $this->createProbeAfterReconnect($db, $family);

            self::assertSame(3, $db->connectCount, 'execute() must reconnect after an earlier reconnection failed.');
        } finally {
            $db->disconnect();
        }
    }

    #[DataProvider('drivers')]
    public function testFailedRetryDoesNotDisableLaterReconnects(string $family): void
    {
        $db = $this->build($family);

        try {
            // The connection is lost, and the query still fails after reconnecting.
            $db->reportDisconnected = true;

            try {
                $db->setQuery('SELECT * FROM awf_reconnect_no_such_table');
                $db->execute();
                self::fail('execute() must throw when the query fails after reconnecting.');
            } catch (RuntimeException) {
                // Expected.
            }

            $db->reportDisconnected = false;
            $this->createProbe($db);

            // The connection is lost again. execute() must reconnect and run the query again in a new session.
            $db->reportDisconnected = true;
            $this->createProbeAfterReconnect($db, $family);

            self::assertSame(3, $db->connectCount, 'execute() must reconnect after an earlier retried query failed.');
        } finally {
            $db->disconnect();
        }
    }
}
