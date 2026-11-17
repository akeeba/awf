<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Mvc\HyphenatedPrefix;

use Awf\Database\Driver;
use Awf\Database\Driver\Postgresql;
use Awf\Mvc\DataModel;
use Awf\Mvc\TreeModel;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Native pgsql extension driver.
 */
#[CoversClass(Postgresql::class)]
#[CoversClass(DataModel::class)]
#[CoversClass(TreeModel::class)]
final class PostgresqlHyphenatedPrefixTest extends PostgresqlHyphenatedPrefixTestCase
{
    protected function connectDriver(): Driver
    {
        if (!Postgresql::isSupported()) {
            $this->markTestSkipped('The pgsql extension is not available.');
        }

        $conn = $this->pgConnection();

        return new Postgresql([
            'driver'   => 'postgresql',
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
