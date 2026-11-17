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
 * The PDO MySQL driver with PDO::ATTR_EMULATE_PREPARES off, so the server validates the SQL when it is prepared.
 *
 * Pdomysql::connect() forces emulated prepares on every time it is called, so only a subclass gets to change it.
 */
final class NativePreparesPdomysql extends Pdomysql
{
	public function connect()
	{
		parent::connect();

		$this->connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
	}
}
