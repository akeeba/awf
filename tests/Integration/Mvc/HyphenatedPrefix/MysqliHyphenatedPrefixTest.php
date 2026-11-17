<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Mvc\HyphenatedPrefix;

use Awf\Database\Driver;
use Awf\Database\Driver\Mysqli;
use Awf\Mvc\DataModel;
use Awf\Mvc\TreeModel;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Mysqli::class)]
#[CoversClass(DataModel::class)]
#[CoversClass(TreeModel::class)]
final class MysqliHyphenatedPrefixTest extends MysqlHyphenatedPrefixTestCase
{
    protected function connectDriver(): Driver
    {
        if (!Mysqli::isSupported()) {
            $this->markTestSkipped('The mysqli extension is not available.');
        }

        $conn = $this->mysqlConnection();

        return new Mysqli([
            'driver'   => 'mysqli',
            'host'     => $conn['host'] . ':' . $conn['port'],
            'user'     => $conn['user'],
            'password' => $conn['pass'],
            'database' => $conn['dbname'],
            'prefix'   => self::PREFIX,
            'select'   => true,
        ]);
    }
}
