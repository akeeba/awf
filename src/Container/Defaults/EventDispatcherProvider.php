<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Container\Defaults;

use Awf\Container\Container;
use Awf\Event\Dispatcher as EventDispatcher;

/**
 * Event Dispatcher service provider
 *
 * @since   1.1.0
 */
class EventDispatcherProvider
{
	/**
	 * Returns the service.
	 *
	 * @param   Container  $c  The container calling us
	 *
	 * @return  EventDispatcher  The returned service object
	 * @since   1.1.0
	 */
	public function __invoke(Container $c): EventDispatcher
	{
		return new EventDispatcher($c);
	}

}