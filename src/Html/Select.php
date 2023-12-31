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
 * @method static setOptionSettings(array $options)
 * @method static getOptionSettings()
 * @method static booleanList(string $name, array $attribs = [], ?string $selected = null, string $yes = 'AWF_YES', string $no = 'AWF_NO',$id = false)
 * @method static genericList(array $data, string $name, ?array $attribs = null, string $optKey = 'value', string $optText = 'text',?string $selected = null, $idTag = false, bool $translate = false)
 * @method static suggestionList(array $data, string $optKey = 'value', string $optText = 'text', $idTag = '', bool $translate = false)
 * @method static groupedList(array $data, string $name, array $options = [])
 * @method static integerList(int $start, int $end, int $inc, string $name, ?array $attribs = null, ?string $selected = null,string $format = '')
 * @method static option(string $value, string $text = '', $optKey = 'value', string $optText = 'text', bool $disable = false)
 * @method static options(array $arr, $optKey = 'value', string $optText = 'text', ?string $selected = null, bool $translate = false)
 * @method static radioList(array $data, string $name, array $attribs = null, string $optKey = 'value', string $optText = 'text',?string $selected = null, $idTag = false, bool $translate = false)
 */
abstract class Select
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
			case 'setoptionsettings':
			case 'getoptionsettings':
			case 'booleanlist':
			case 'genericlist':
			case 'suggestionlist':
			case 'groupedlist':
			case 'integerlist':
			case 'option':
			case 'options':
			case 'radiolist':
				trigger_error(
					sprintf('Calling %s is deprecated. Use the container\'s html service instead.', __METHOD__),
					E_USER_DEPRECATED
				);

				return Application::getInstance()->getContainer()->html->get('select.' . $name, ...$arguments);
		}

		throw new \LogicException(
			sprintf('The method %s::%s does not exist.', __CLASS__, $name),
			500
		);
	}

}