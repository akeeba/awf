<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Database\Fixture;

use Awf\Database\Driver\Mysqli;

/**
 * The mysqli driver whose connection-loss detection and reconnection can be steered by a test.
 */
final class ReconnectingMysqli extends Mysqli
{
	use ReconnectSteering;

	/**
	 * Make failed queries return false instead of throwing, which is the only way execute() reaches its reconnect
	 * branch: since PHP 8.1 mysqli throws mysqli_sql_exception by default, before that code runs. The report mode is
	 * process-wide, so tests must restore it (see ReconnectTest::tearDown()).
	 */
	protected function afterConnect(): void
	{
		mysqli_report(MYSQLI_REPORT_OFF);
	}
}
