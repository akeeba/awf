<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Html;

use Awf\Application\Application;
use Awf\Exception\App;

/**
 * @deprecated 2.0 Use the container's html service instead
 *
 * @method static calendar()
 */
abstract class Behaviour
{
	/**
	 * Handle static method calls for backwards compatibility.
	 *
	 * @param   string  $name
	 * @param   array   $arguments
	 *
	 * @deprecated 2.0 Use the container's html service instead.
	 * @return mixed|void
	 * @throws App
	 *
	 */
	public static function __callStatic($name, $arguments)
	{
		switch (strtolower($name))
		{
			case 'calendar':
				trigger_error(
					sprintf('Calling %s is deprecated. Use the container\'s html service instead.', __METHOD__),
					E_USER_DEPRECATED
				);

				return Application::getInstance()->getContainer()->html->get('behaviour.' . $name, ...$arguments);
		}

		throw new \LogicException(
			sprintf('The method %s::%s does not exist.', __CLASS__, $name),
			500
		);
	}

}