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
}
