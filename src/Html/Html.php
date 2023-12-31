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
 * @method static string link($url, $text, $attribs = null)
 * @method static string iframe($url, $name, $attribs = null, $noFrames = '')
 * @method static string date($input = 'now', $format = null, $tz = true)
 * @method static string image($file, $alt, $attribs = null, $relative = false)
 * @method static string tooltip(string $tooltip, $title = '', string $image = 'images/tooltip.png', string $text = '', string $href = '', string $alt = 'Tooltip', string $class = 'hasTooltip')
 * @method static string tooltipText($title = '', $content = '', $translate = 1, $escape = 1)
 * @method static string calendar($value, $name, $id, $format = 'yyyy-mm-dd', $attribs = null)
 * @method static void setFormatOptions($options)
 */
abstract class Html
{
	/**
	 * Function caller. Call me like Html::_('HtmlClass.function', $param1, $param2)
	 *
	 * @param   string  $key  The name of helper method to load, (prefix).(class).function
	 *                        prefix and class are optional and can be used to load custom
	 *                        html helpers.
	 *
	 * @deprecated 2.0 Use the Container's html service instead.
	 * @return  mixed
	 *
	 * @throws  \InvalidArgumentException
	 */
	public static function _($key)
	{
		trigger_error(
			sprintf('Calling %s is deprecated. Use the container\'s html service instead.', __METHOD__),
			E_USER_DEPRECATED
		);

		[$prefix, $file, $func] = static::extract($key);

		$className = $prefix . ucfirst($file);

		if (!class_exists($className))
		{
			$className = __NAMESPACE__ . '\\' . $prefix . '\\' . ucfirst($file);
		}

		if (!class_exists($className))
		{
			throw new \InvalidArgumentException(sprintf('%s %s not found.', $prefix, $file), 500);
		}

		$toCall = [$className, $func];

		if (!is_callable($toCall))
		{
			throw new \InvalidArgumentException(sprintf('%s::%s not found.', $className, $func), 500);
		}

		$args = func_get_args();

		// Remove function name from arguments
		array_shift($args);

		// PHP 5.3 workaround
		$temp = [];

		foreach ($args as &$arg)
		{
			$temp[] = &$arg;
		}

		return call_user_func_array($toCall, $temp);
	}

	/**
	 * Method to extract a key
	 *
	 * @param   string  $key  The name of helper method to load, (prefix).(class).function
	 *                        prefix and class are optional and can be used to load custom html helpers.
	 *
	 * @deprecated 2.0
	 * @return  array  Contains lowercase key, prefix, file, function.
	 *
	 */
	protected static function extract($key)
	{
		$key = preg_replace('#[^A-Z0-9_\.]#i', '', $key);

		// Check to see whether we need to load a helper file
		$parts = explode('.', $key);

		$prefix = count($parts) === 3 ? array_shift($parts) : '\\Awf\\Html\\';
		$file   = count($parts) === 2 ? array_shift($parts) : '';
		$func   = array_shift($parts);

		return array($prefix, $file, $func);
	}

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
			case 'setformatoptions':
				trigger_error(
					sprintf('Calling %s is deprecated. Use the container\'s html service instead.', __METHOD__),
					E_USER_DEPRECATED
				);

				Application::getInstance()->getContainer()->html->setFormatOptions(...$arguments);

				return;

			case 'link':
			case 'iframe':
			case 'date':
			case 'image':
			case 'tooltip':
			case 'tooltiptext':
			case 'calendar':
				trigger_error(
					sprintf('Calling %s is deprecated. Use the container\'s html service instead.', __METHOD__),
					E_USER_DEPRECATED
				);

				return Application::getInstance()->getContainer()->html->get('basic.' . $name, ...$arguments);
		}

		throw new \LogicException(
			sprintf('The method %s::%s does not exist.', __CLASS__, $name),
			500
		);
	}


}