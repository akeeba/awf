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
 * @method static setJavascriptPrefix(string $prefix)
 * @method static sort(string $title, string $order, string $direction = 'asc', ?string $selected = '', string $task = null,string $new_direction = 'asc', string $tip = '', string $orderingJs = '')
 * @method static checkAll(string $name = 'checkall-toggle', string $tip = 'AWF_COMMON_LBL_CHECK_ALL', string $action = '')
 * @method static id(int $rowNum, int $recId, bool $checkedOut = false, string $name = 'cid', string $checkedJs = '',string $altLabel = '')
 */
abstract class Grid
{
	/**
	 * @var string
	 * @deprecated 2.0 Use $container->html->run('grid.setJavascriptPrefix', $yourPrefix) instead
	 */
	public static $javascriptPrefix = '';

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
			case 'setjavascriptprefix':
			case 'sort':
			case 'checkall':
			case 'id':
				trigger_error(
					sprintf('Calling %s is deprecated. Use the container\'s html service instead.', __METHOD__),
					E_USER_DEPRECATED
				);

				return Application::getInstance()->getContainer()->html->get('grid.' . $name, ...$arguments);
		}

		throw new \LogicException(
			sprintf('The method %s::%s does not exist.', __CLASS__, $name),
			500
		);
	}

}