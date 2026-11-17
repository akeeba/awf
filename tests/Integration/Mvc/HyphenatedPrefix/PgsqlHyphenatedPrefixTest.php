<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Mvc\HyphenatedPrefix;

use Awf\Database\Driver;
use Awf\Database\Driver\Pgsql;
use Awf\Mvc\DataModel;
use Awf\Mvc\TreeModel;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * PDO PostgreSQL driver.
 *
 * The native pgsql extension driver (Driver\Postgresql) is not tested here: on PHP 8.1 and later its execute() expects
 * pg_connect() to return a resource, gets a PgSql\Connection object, and throws before running any query.
 */
#[CoversClass(Pgsql::class)]
#[CoversClass(DataModel::class)]
#[CoversClass(TreeModel::class)]
final class PgsqlHyphenatedPrefixTest extends PostgresqlHyphenatedPrefixTestCase
{
    protected function connectDriver(): Driver
    {
        if (!Pgsql::isSupported() || !in_array('pgsql', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The pdo_pgsql extension is not available.');
        }

        $conn = $this->pgConnection();

        return new Pgsql([
            'driver'   => 'pgsql',
            'host'     => $conn['host'],
            'port'     => $conn['port'],
            'user'     => $conn['user'],
            'password' => $conn['pass'],
            'database' => $conn['dbname'],
            'prefix'   => self::PREFIX,
            'select'   => true,
        ]);
    }
}
