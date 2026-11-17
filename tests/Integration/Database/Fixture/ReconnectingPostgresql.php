<?php

/**
 * @package   awf
 * @copyright Copyright (c) 2024-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Database\Fixture;

use Awf\Database\Driver\Postgresql;

/**
 * Native PostgreSQL driver whose connection-loss detection and reconnection can be steered by a test.
 *
 * pg_ping() quietly resets a dropped connection, so on a real server execute() only enters its reconnect branch when
 * that reset fails as well. This double lets a test take that branch on demand.
 */
final class ReconnectingPostgresql extends Postgresql
{
	/** When true, connected() reports the connection as lost. */
	public bool $reportDisconnected = false;

	/** When true, connect() fails as if the server could not be reached. */
	public bool $failConnect = false;

	/** How many times connect() opened a new connection. */
	public int $connectCount = 0;

	public function connect()
	{
		if ($this->connection)
		{
			return;
		}

		if ($this->failConnect)
		{
			throw new \RuntimeException('Simulated connection failure.');
		}

		parent::connect();

		$this->connectCount++;
	}

	public function connected()
	{
		return $this->reportDisconnected ? false : parent::connected();
	}
}
