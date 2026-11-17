<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Database;

use Awf\Database\Driver;
use Awf\Database\Driver\Pdomysql;
use Awf\Database\Driver\Sqlite;
use Awf\Tests\Integration\AbstractIntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The offset and limit given to setQuery() on the drivers built on Pdo (PDO MySQL and SQLite).
 *
 * The limit must reach the statement the server runs exactly once, whatever kind of query it is given: a query object
 * which renders its own limit, a plain string, or a query object holding raw SQL, which cannot render one. It must
 * also appear exactly once in the debug log and in the error messages.
 */
final class PdoLimitTest extends AbstractIntegrationTestCase
{
    public static function drivers(): array
    {
        return ['pdomysql' => ['pdomysql'], 'sqlite' => ['sqlite']];
    }

    private function build(string $family): Driver
    {
        if ($family === 'sqlite') {
            if (!Sqlite::isSupported()) {
                $this->markTestSkipped('The pdo_sqlite extension is not available.');
            }

            $db = new Sqlite(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => 'awf_']);
            $pk = 'INTEGER';
        } else {
            if (!extension_loaded('pdo_mysql')) {
                $this->markTestSkipped('The pdo_mysql extension is not available.');
            }

            $conn = $this->requireMysql();
            $opts = [
                'driver' => 'pdomysql', 'prefix' => 'awf_', 'select' => true, 'host' => '127.0.0.1', 'port' => 3306,
                'user' => $conn['user'], 'password' => $conn['pass'], 'database' => '',
            ];

            foreach (preg_split('/[;\s]+/', (string) preg_replace('/^[a-z]+:/i', '', $conn['dsn']), -1, PREG_SPLIT_NO_EMPTY) as $segment) {
                [$key, $val] = explode('=', $segment, 2) + ['', ''];

                if ($key === 'host') {
                    $opts['host'] = $val;
                } elseif ($key === 'port') {
                    $opts['port'] = (int) $val;
                } elseif ($key === 'dbname') {
                    $opts['database'] = $val;
                }
            }

            $db = new Pdomysql($opts);
            $pk = 'INT AUTO_INCREMENT';
        }

        $db->setQuery('DROP TABLE IF EXISTS ' . $db->quoteName('#__paging_rows'))->execute();
        $db->setQuery('CREATE TABLE ' . $db->quoteName('#__paging_rows') . " (id $pk PRIMARY KEY, label VARCHAR(20))")->execute();
        $db->setQuery('INSERT INTO ' . $db->quoteName('#__paging_rows') . " (label) VALUES ('a'), ('b'), ('c')")->execute();

        return $db;
    }

    private function drop(Driver $db): void
    {
        $db->setQuery('DROP TABLE IF EXISTS ' . $db->quoteName('#__paging_rows'))->execute();
        $db->disconnect();
    }

    private function selectSql(Driver $db): string
    {
        return 'SELECT id FROM ' . $db->quoteName('#__paging_rows') . ' ORDER BY id';
    }

    /** @return array<string, array{0: callable(Driver): mixed}> */
    private function queryKinds(Driver $db): array
    {
        $sql = $this->selectSql($db);

        return [
            'query object' => fn () => $db->getQuery(true)->select('id')->from($db->quoteName('#__paging_rows'))->order('id'),
            'string'       => fn () => $sql,
            'raw SQL query object' => fn () => $db->getQuery(true)->setQuery($sql),
        ];
    }

    #[DataProvider('drivers')]
    public function testTheLimitReachesTheServerExactlyOnce(string $family): void
    {
        $db = $this->build($family);

        try {
            foreach ($this->queryKinds($db) as $kind => $make) {
                foreach ([[0, 2, [1, 2]], [1, 1, [2]], [2, 5, [3]], [0, 1, [1]]] as [$offset, $limit, $expected]) {
                    $ids = $db->setQuery($make(), $offset, $limit)->loadColumn();

                    self::assertSame($expected, array_map('intval', $ids), "$kind, offset $offset, limit $limit");
                }

                // Without an offset and limit there is no limit.
                self::assertCount(3, $db->setQuery($make())->loadColumn(), "$kind, no limit");
            }
        } finally {
            $this->drop($db);
        }
    }

    #[DataProvider('drivers')]
    public function testAQueryObjectWhichLimitedItselfIsNotLimitedTwice(string $family): void
    {
        $db = $this->build($family);

        try {
            $query = $db->getQuery(true)->select('id')->from($db->quoteName('#__paging_rows'))->order('id');
            $query->setLimit(2, 1);

            self::assertSame([2, 3], array_map('intval', $db->setQuery($query)->loadColumn()));
        } finally {
            $this->drop($db);
        }
    }

    #[DataProvider('drivers')]
    public function testTheLimitAppearsOnceInTheLogAndInErrorMessages(string $family): void
    {
        $db = $this->build($family);

        try {
            $db->setDebug(true);

            foreach ($this->queryKinds($db) as $kind => $make) {
                $db->setQuery($make(), 0, 1)->loadColumn();
                $log = $db->getLog();
                $sql = (string) end($log);

                self::assertSame(1, substr_count(strtoupper($sql), 'LIMIT'), "$kind, log: $sql");
            }

            $query = $db->getQuery(true)->select('*')->from($db->quoteName('#__paging_missing'));

            try {
                $db->setQuery($query, 0, 1)->loadObjectList();
                self::fail('The query should have failed.');
            } catch (\RuntimeException $e) {
                // SQLite fails while the statement is prepared, outside execute(): no " SQL=" tail, nothing to check.
                $position = strpos($e->getMessage(), ' SQL=');

                if ($position === false) {
                    self::assertSame('sqlite', $family, $e->getMessage());
                } else {
                    self::assertSame(1, substr_count(strtoupper(substr($e->getMessage(), $position)), 'LIMIT'), $e->getMessage());
                }
            }
        } finally {
            $this->drop($db);
        }
    }
}
