<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Container\Defaults;

use Awf\Container\Container;
use Awf\Date\Date;

/**
 * Date Factory service provider
 *
 * @since   1.1.0
 */
class DateFactoryProvider
{
	/**
	 * Returns the service.
	 *
	 * @param   Container  $c  The container calling us
	 *
	 * @return  callable  The returned service object
	 * @since   1.1.0
	 */
	public function __invoke(Container $c): callable
	{
		return $c->protect(
			function (string $date = 'now', $tz = null) use ($c): Date {
				return new Date($date, $tz, $c);
			}
		);
	}

}