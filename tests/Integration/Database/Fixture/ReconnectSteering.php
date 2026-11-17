<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Database\Fixture;

/**
 * Lets a test steer a driver's connection-loss detection and reconnection.
 *
 * A real server only reaches the reconnect branch of execute() when the connection really drops. This trait lets a
 * test take that branch on demand: connected() can report the connection as lost, and connect() can fail.
 */
trait ReconnectSteering
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
