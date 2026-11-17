<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Mvc\HyphenatedPrefix;

use Awf\Database\Driver;
use Awf\Database\Driver\Pdomysql;
use Awf\Mvc\DataModel;
use Awf\Mvc\TreeModel;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Pdomysql::class)]
#[CoversClass(DataModel::class)]
#[CoversClass(TreeModel::class)]
final class PdomysqlHyphenatedPrefixTest extends MysqlHyphenatedPrefixTestCase
{
    protected function connectDriver(): Driver
    {
        if (!Pdomysql::isSupported()) {
            $this->markTestSkipped('The pdo_mysql extension is not available.');
        }

        $conn = $this->mysqlConnection();

        return new Pdomysql([
            'driver'   => 'pdomysql',
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
