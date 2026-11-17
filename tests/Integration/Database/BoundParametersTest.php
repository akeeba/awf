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
use Awf\Database\Driver\Pgsql;
use Awf\Database\Driver\Sqlite;
use Awf\Database\Query\Mysqli as MysqliQuery;
use Awf\Database\Query\Postgresql as PostgresqlQuery;
use Awf\Tests\Integration\AbstractIntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Real-server tests for Query::bind() on the PDO drivers, and for the clear refusal of the drivers without it.
 *
 * The MySQL and PostgreSQL cases are skipped unless AWF_TEST_MYSQL_DSN / AWF_TEST_PG_DSN are set. SQLite runs in memory.
 */
final class BoundParametersTest extends AbstractIntegrationTestCase
{
    private function connect(string $family): Driver
    {
        if ($family === 'sqlite') {
            if (!Sqlite::isSupported()) {
                $this->markTestSkipped('The pdo_sqlite extension is not available.');
            }

            return new Sqlite(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        }

        if (!in_array($family === 'mysql' ? 'mysql' : 'pgsql', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The PDO driver is not available.');
        }

        $conn = $family === 'mysql' ? $this->requireMysql() : $this->requirePostgresql();
        $opts = [
            'host'     => '127.0.0.1',
            'port'     => 0,
            'user'     => $conn['user'],
            'password' => $conn['pass'],
            'database' => '',
            'prefix'   => 'awf_',
            'select'   => true,
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

        return $family === 'mysql' ? new Pdomysql($opts) : new Pgsql($opts);
    }

    public static function families(): array
    {
        return [
            'PDO MySQL'      => ['mysql'],
            'SQLite'         => ['sqlite'],
            'PDO PostgreSQL' => ['pgsql'],
        ];
    }

    private function makeTable(Driver $db): void
    {
        $db->setQuery('DROP TABLE IF EXISTS bindtest')->execute();
        $db->setQuery('CREATE TEMPORARY TABLE bindtest (id INTEGER, name VARCHAR(50), note VARCHAR(50))')->execute();
    }

    #[DataProvider('families')]
    public function testBindReturnsTheQueryForChaining(string $family): void
    {
        $db    = $this->connect($family);
        $value = 5;
        $query = $db->getQuery(true)->select('1');

        self::assertSame($query, $query->bind(':x', $value, \PDO::PARAM_INT));
    }

    #[DataProvider('families')]
    public function testBoundParameterIsSentToTheServer(string $family): void
    {
        $db    = $this->connect($family);
        $value = 5;
        $query = $db->getQuery(true)->select('1')->where('5 = :x');

        $query->bind(':x', $value, \PDO::PARAM_INT);
        $db->setQuery($query);

        self::assertSame(1, (int) $db->loadResult());
    }

    #[DataProvider('families')]
    public function testEveryParameterTypeRoundTrips(string $family): void
    {
        $db     = $this->connect($family);
        $string = "O'Reilly \\ \"quoted\" :notAParam";
        $int    = 42;
        $true   = true;
        $false  = false;
        $query  = $db->getQuery(true)
            ->select(':s AS s')
            ->select(':i AS i')
            ->select(':t AS t')
            ->select(':f AS f');

        $query->bind(':s', $string, \PDO::PARAM_STR);
        $query->bind(':i', $int, \PDO::PARAM_INT);
        $query->bind(':t', $true, \PDO::PARAM_BOOL);
        $query->bind(':f', $false, \PDO::PARAM_BOOL);

        $db->setQuery($query);
        $row = $db->loadObject();

        self::assertNotNull($row);
        self::assertSame($string, $row->s);
        self::assertSame(42, (int) $row->i);
        // PostgreSQL returns an untyped boolean parameter as 't' / 'f'.
        self::assertContains($row->t, ['1', 1, 't', true]);
        self::assertContains($row->f, ['0', 0, 'f', false, '']);
    }

    #[DataProvider('families')]
    public function testBoundParametersCombineWithLimitAndOffset(string $family): void
    {
        $db = $this->connect($family);
        $this->makeTable($db);

        foreach ([1, 2, 3, 4, 5] as $id) {
            $db->setQuery('INSERT INTO bindtest (id, name) VALUES (' . $id . ", 'row')")->execute();
        }

        $min   = 2;
        $name  = 'row';
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('bindtest'))
            ->where($db->quoteName('id') . ' >= :min')
            ->where($db->quoteName('name') . ' = :name')
            ->order($db->quoteName('id'));
        $query->bind(':min', $min, \PDO::PARAM_INT);
        $query->bind(':name', $name);

        $db->setQuery($query, 1, 2);

        self::assertSame(['3', '4'], array_map('strval', $db->loadColumn()));
    }

    #[DataProvider('families')]
    public function testInsertWithBoundValuesCanBeRepeated(string $family): void
    {
        $db = $this->connect($family);
        $this->makeTable($db);

        $id   = 0;
        $name = '';
        $note = "it's \\ fine";

        $insert = $db->getQuery(true)
            ->insert($db->quoteName('bindtest'))
            ->columns($db->quoteName(['id', 'name', 'note']))
            ->values(':id, :name, :note');
        $insert->bind(':id', $id, \PDO::PARAM_INT);
        $insert->bind(':name', $name);
        $insert->bind(':note', $note);

        foreach ([[1, 'first'], [2, 'second']] as [$id, $name]) {
            $db->setQuery($insert);
            $db->execute();
        }

        $db->setQuery('SELECT id, name, note FROM bindtest ORDER BY id');
        $rows = $db->loadObjectList();

        self::assertCount(2, $rows);
        self::assertSame('first', $rows[0]->name);
        self::assertSame('second', $rows[1]->name);
        self::assertSame($note, $rows[1]->note);
    }

    public function testBindCannotBeSilentlyIgnoredByTheMysqliQueryClass(): void
    {
        $query = new MysqliQuery();
        $value = 1;

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Bound parameters are not supported');

        $query->bind(':x', $value);
    }

    public function testGetBoundedCannotBeSilentlyIgnoredByTheMysqliQueryClass(): void
    {
        $this->expectException(\BadMethodCallException::class);

        (new MysqliQuery())->getBounded();
    }

    public function testBindCannotBeSilentlyIgnoredByTheNativePostgresqlQueryClass(): void
    {
        $query = new PostgresqlQuery();
        $value = 1;

        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Bound parameters are not supported');

        $query->bind(':x', $value);
    }
}
