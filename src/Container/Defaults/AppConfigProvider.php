<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Container\Defaults;

use Awf\Application\Configuration as AppConfiguration;
use Awf\Container\Container;

/**
 * Application Configuration service provider
 *
 * @since   1.1.0
 */
class AppConfigProvider
{
	/**
	 * Returns the service.
	 *
	 * @param   Container  $c  The container calling us
	 *
	 * @return  AppConfiguration  The returned service object
	 * @since   1.1.0
	 */
	public function __invoke(Container $c): AppConfiguration
	{
		return new AppConfiguration($c);
	}

}