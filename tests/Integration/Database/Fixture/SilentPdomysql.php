<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Database\Fixture;

use Awf\Database\Driver\Pdomysql;

/**
 * The PDO MySQL driver on a connection in PDO::ERRMODE_SILENT, where a failed query returns false.
 *
 * Pdomysql::connect() forces PDO::ERRMODE_EXCEPTION on every call, so only a subclass gets to change it afterwards.
 */
final class SilentPdomysql extends Pdomysql
{
	public function connect()
	{
		// Pdomysql::connect() forces the exception mode every time it is called, not just when it opens a connection.
		parent::connect();

		$this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
	}
}
