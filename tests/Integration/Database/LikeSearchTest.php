<?php

/**
 * @package   awf
 * @copyright Copyright (c) 2024-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Database;

use Awf\Database\Driver;
use Awf\Database\Driver\Mysqli;
use Awf\Database\Driver\Pgsql;
use Awf\Database\Driver\Postgresql;
use Awf\Mvc\DataModel\Filter\Text;
use Awf\Tests\Integration\AbstractIntegrationTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * LIKE searches built by the Text filter, executed on a real server, asserting the rows returned.
 *
 * Both PostgreSQL drivers switch standard_conforming_strings off on connect, which changes how the LIKE escape
 * character and the escaped wildcards must be written.
 */
#[CoversClass(Postgresql::class)]
#[CoversClass(Pgsql::class)]
#[CoversClass(Mysqli::class)]
#[CoversClass(Text::class)]
final class LikeSearchTest extends AbstractIntegrationTestCase
{
    private const TABLE = 'awf_like_search_test';

    private const ROWS = [
        '100% done',
        '100 xx',
        '100%',
        'a_b',
        'axb',
        'back\\slash',
        'lone\\',
        'c:\\%temp%',
        "it's",
        "it's here",
        'plain',
    ];

    private ?Driver $db = null;

    public static function driverProvider(): array
    {
        return [
            'native PostgreSQL' => ['postgresql'],
            'PDO PostgreSQL'    => ['pgsql'],
            'mysqli'            => ['mysqli'],
        ];
    }

    protected function tearDown(): void
    {
        try {
            if ($this->db !== null) {
                $this->db->setQuery('DROP TABLE IF EXISTS ' . $this->db->quoteName(self::TABLE))->execute();
                $this->db->disconnect();
            }
        } catch (\Throwable $e) {
            // Ignore.
        }

        $this->db = null;
    }

    #[DataProvider('driverProvider')]
    public function testPartialTreatsWildcardsAndBackslashesLiterally(string $driver): void
    {
        $this->connect($driver);

        self::assertSame(['100% done', '100%'], $this->partial('100%'));
        self::assertSame(['a_b'], $this->partial('a_b'));

        // MySQL's escape($text, true) is not changed by this fix, and it escapes a backslash needle into a pattern
        // that then reads as an escaped percent sign. Only the PostgreSQL drivers are held to this.
        if ($driver !== 'mysqli') {
            self::assertSame(['back\\slash', 'lone\\', 'c:\\%temp%'], $this->partial('\\'));
            self::assertSame(['c:\\%temp%'], $this->partial('\\%'));
        }

        self::assertSame(["it's", "it's here"], $this->partial("it's"));
        self::assertSame(['plain'], $this->partial('lai'));
    }

    #[DataProvider('driverProvider')]
    public function testExactTreatsWildcardsAndBackslashesLiterally(string $driver): void
    {
        $this->connect($driver);

        self::assertSame(['100%'], $this->rows($this->filter()->exact('100%')));
        self::assertSame(['a_b'], $this->rows($this->filter()->exact('a_b')));
        self::assertSame(['lone\\'], $this->rows($this->filter()->exact('lone\\')));
        self::assertSame(["it's"], $this->rows($this->filter()->exact("it's")));
        self::assertSame(['c:\\%temp%'], $this->rows($this->filter()->exact('c:\\%temp%')));
    }

    #[DataProvider('driverProvider')]
    public function testLikeAndNotLikeOperators(string $driver): void
    {
        $this->connect($driver);

        self::assertSame(['100%'], $this->rows($this->filter()->search('100%', 'LIKE')));
        self::assertSame(['a_b'], $this->rows($this->filter()->search('a_b', 'LIKE')));
        self::assertSame(['lone\\'], $this->rows($this->filter()->search('lone\\', 'LIKE')));
        self::assertSame(["it's"], $this->rows($this->filter()->search("it's", 'LIKE')));

        $notLike = $this->rows($this->filter()->search('100%', 'NOT LIKE'));
        self::assertNotContains('100%', $notLike);
        self::assertCount(count(self::ROWS) - 1, $notLike);

        $notLike = $this->rows($this->filter()->search('a_b', 'NOT LIKE'));
        self::assertNotContains('a_b', $notLike);
        self::assertContains('axb', $notLike);
    }

    private function filter(): Text
    {
        return new Text($this->db, (object) ['name' => 'label', 'type' => 'varchar(191)']);
    }

    /**
     * @return string[]
     */
    private function partial(string $needle): array
    {
        return $this->rows($this->filter()->partial($needle));
    }

    /**
     * @return string[]  The labels matching the WHERE condition, in insertion order.
     */
    private function rows(string $where): array
    {
        self::assertNotSame('', $where);

        $this->db->setQuery(
            'SELECT ' . $this->db->quoteName('label') . ' FROM ' . $this->db->quoteName(self::TABLE)
            . ' WHERE ' . $where . ' ORDER BY ' . $this->db->quoteName('id')
        );

        return array_map('strval', $this->db->loadColumn());
    }

    private function connect(string $driver): void
    {
        if ($driver === 'mysqli') {
            if (!Mysqli::isSupported()) {
                $this->markTestSkipped('The mysqli extension is not available.');
            }

            $conn     = $this->requireMysql();
            $p        = self::parseDsn($conn['dsn'], 3306);
            $this->db = new Mysqli([
                'driver'   => 'mysqli',
                'host'     => $p['host'] . ':' . $p['port'],
                'user'     => $conn['user'],
                'password' => $conn['pass'],
                'database' => $p['dbname'],
                'prefix'   => 'tst_',
                'select'   => true,
            ]);
            $idType   = 'INT NOT NULL AUTO_INCREMENT';
        } else {
            $class = $driver === 'pgsql' ? Pgsql::class : Postgresql::class;

            if (!$class::isSupported()) {
                $this->markTestSkipped('The PostgreSQL extension for this driver is not available.');
            }

            $conn     = $this->requirePostgresql();
            $p        = self::parseDsn($conn['dsn'], 5432);
            $this->db = new $class([
                'driver'   => $driver,
                'host'     => $p['host'],
                'port'     => $p['port'],
                'user'     => $conn['user'],
                'password' => $conn['pass'],
                'database' => $p['dbname'],
                'prefix'   => 'tst_',
                'select'   => true,
            ]);
            $idType   = 'SERIAL';
        }

        $table = $this->db->quoteName(self::TABLE);
        $this->db->setQuery('DROP TABLE IF EXISTS ' . $table)->execute();
        $this->db->setQuery(
            'CREATE TABLE ' . $table . ' (' . $this->db->quoteName('id') . ' ' . $idType . ', '
            . $this->db->quoteName('label') . ' VARCHAR(191) NOT NULL, PRIMARY KEY (' . $this->db->quoteName('id') . '))'
        )->execute();

        foreach (self::ROWS as $label) {
            $this->db->setQuery(
                'INSERT INTO ' . $table . ' (' . $this->db->quoteName('label') . ') VALUES (' . $this->db->quote($label) . ')'
            )->execute();
        }
    }

    /**
     * @return array{host: string, port: int, dbname: string}
     */
    private static function parseDsn(string $dsn, int $defaultPort): array
    {
        $ret = ['host' => '127.0.0.1', 'port' => $defaultPort, 'dbname' => ''];
        $dsn = preg_replace('/^[a-z]+:/i', '', $dsn);

        foreach (preg_split('/[;\s]+/', $dsn, -1, PREG_SPLIT_NO_EMPTY) as $segment) {
            [$key, $val] = array_map('trim', explode('=', $segment, 2)) + ['', ''];

            if ($key === 'host') {
                $ret['host'] = $val;
            } elseif ($key === 'port') {
                $ret['port'] = (int) $val;
            } elseif ($key === 'dbname' || $key === 'database') {
                $ret['dbname'] = $val;
            }
        }

        return $ret;
    }
}
