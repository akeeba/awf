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
 * The PDO MySQL driver whose connection-loss detection and reconnection can be steered by a test.
 */
final class ReconnectingPdomysql extends Pdomysql
{
	use ReconnectSteering;

	/**
	 * Make failed queries return false instead of throwing, which is the only way execute() reaches its reconnect
	 * branch: with PDO::ERRMODE_EXCEPTION the PDOException escapes before that code runs.
	 */
	protected function afterConnect(): void
	{
		$this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
	}
}
