<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Container\Defaults;

use Awf\Container\Container;
use Awf\Database\Driver;

/**
 * Database service provider
 *
 * @since   1.1.0
 */
class DatabaseProvider
{
	/**
	 * Returns the service.
	 *
	 * @param   Container  $c  The container calling us
	 *
	 * @return  Driver  The returned service object
	 * @since   1.1.0
	 */
	public function __invoke(Container $c): Driver
	{
		return Driver::fromContainer($c);
	}

}