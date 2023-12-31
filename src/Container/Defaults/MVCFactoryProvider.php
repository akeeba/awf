<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Container\Defaults;

use Awf\Container\Container;
use Awf\Mvc\Factory as MVCFactory;

/**
 * MVC Factory service provider
 *
 * @since   1.1.0
 */
class MVCFactoryProvider
{
	/**
	 * Returns the service.
	 *
	 * @param   Container  $c  The container calling us
	 *
	 * @return  MVCFactory  The returned service object
	 * @since   1.1.0
	 */
	public function __invoke(Container $c): MVCFactory
	{
		return new MVCFactory($c);
	}

}