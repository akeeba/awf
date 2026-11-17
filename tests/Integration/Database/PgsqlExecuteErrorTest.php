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
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The errors the PDO PostgreSQL driver raises from execute(), against a real server.
 */
final class PgsqlExecuteErrorTest extends AbstractIntegrationTestCase
{
    protected function setUp(): void
    {
        if (!Pgsql::isSupported() || !in_array('pgsql', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The pdo_pgsql extension is not available.');
        }
    }

    private function options(): array
    {
        $conn    = $this->requirePostgresql();
        $options = [
            'driver' => 'pgsql', 'host' => '127.0.0.1', 'port' => 5432, 'user' => $conn['user'],
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

        return $options;
    }

    private function prepareTable(Pgsql $db): void
    {
        $db->setQuery('DROP TABLE IF EXISTS ' . $db->quoteName('#__exec_err'))->execute();
        $db->setQuery('CREATE TABLE ' . $db->quoteName('#__exec_err') . ' (id SERIAL PRIMARY KEY, label VARCHAR(20) UNIQUE)')->execute();
        $db->setQuery('INSERT INTO ' . $db->quoteName('#__exec_err') . " (label) VALUES ('a')")->execute();
    }

    private function dropTable(Pgsql $db): void
    {
        $db->setQuery('DROP TABLE IF EXISTS ' . $db->quoteName('#__exec_err'))->execute();
        $db->disconnect();
    }

    public static function failures(): array
    {
        return [
            'syntax error'     => ['SELEC 1', '42601'],
            'missing table'    => ['SELECT * FROM "awf_exec_missing"', '42P01'],
            'unique violation' => ["INSERT INTO \"awf_exec_err\" (label) VALUES ('a')", '23505'],
        ];
    }

    #[DataProvider('failures')]
    public function testAFailedQueryKeepsThePdoExceptionAndItsSqlstate(string $sql, string $sqlstate): void
    {
        $db = new Pgsql($this->options());

        try {
            $this->prepareTable($db);

            try {
                $db->setQuery($sql)->execute();
                self::fail('The query should have failed.');
            } catch (\RuntimeException $e) {
                self::assertInstanceOf(\PDOException::class, $e->getPrevious());
                self::assertSame($sqlstate, $e->getPrevious()->errorInfo[0]);
                self::assertIsInt($e->getCode());
                self::assertStringContainsString('ERROR:', $e->getMessage());
                self::assertStringEndsWith(' SQL=' . $sql, $e->getMessage());
            }
        } finally {
            $this->dropTable($db);
        }
    }

    public function testAnExceptionWhichIsNotAPdoExceptionPropagatesUntouched(): void
    {
        $db = new class($this->options()) extends Pgsql {
            public function swapConnection($connection): void
            {
                $this->connection = $connection;
            }
        };

        try {
            $db->setQuery('SELECT 77');
            $db->swapConnection(new class('sqlite::memory:') extends \PDO {
                #[\ReturnTypeWillChange]
                public function query($query, $fetchMode = null, ...$fetchModeArgs)
                {
                    if ($query === 'SELECT 77') {
                        throw new \LogicException('Not a database error');
                    }

                    return parent::query($query);
                }
            });

            try {
                $db->execute();
                self::fail('The query should have failed.');
            } catch (\Throwable $e) {
                self::assertInstanceOf(\LogicException::class, $e);
                self::assertSame('Not a database error', $e->getMessage());
            }
        } finally {
            $db->disconnect();
        }
    }

    public function testInSilentModeTheErrorComesFromTheConnection(): void
    {
        $db = new class($this->options()) extends Pgsql {
            public function connect()
            {
                parent::connect();

                $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
            }
        };

        try {
            $db->setQuery('SELEC 1')->execute();
            self::fail('The query should have failed.');
        } catch (\RuntimeException $e) {
            self::assertIsInt($e->getCode());
            self::assertStringContainsString('syntax error', $e->getMessage());
            self::assertStringEndsWith(' SQL=SELEC 1', $e->getMessage());
        } finally {
            $db->disconnect();
        }
    }
}
