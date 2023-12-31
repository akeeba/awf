<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Container\Defaults;

use Awf\Container\Container;
use Awf\Dispatcher\Dispatcher as AppDispatcher;

/**
 * Application Dispatcher service provider
 *
 * @since   1.1.0
 */
class DispatcherProvider
{
	/**
	 * Returns the service.
	 *
	 * @param   Container  $c  The container calling us
	 *
	 * @return  AppDispatcher  The returned service object
	 * @since   1.1.0
	 */
	public function __invoke(Container $c): AppDispatcher
	{
		foreach (
			[
				$c->applicationNamespace . '\\Dispatcher',
				'\\' . ucfirst($c->application_name) . '\Dispatcher',
				AppDispatcher::class,
			] as $class
		)
		{
			if (!class_exists($class))
			{
				continue;
			}

			return new $class($c);
		}
	}

}