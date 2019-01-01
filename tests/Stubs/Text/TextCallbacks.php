<?php
/**
 * @package    awf
 * @copyright  Copyright (c)2014-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Text;


class TextCallbacks
{
	public static $filename = null;

	/**
	 * This callback prefixes every string with "Foo "
	 *
	 * @param $filename
	 * @param $strings
	 *
	 * @return array
	 */
	public static function preprocess($filename, $strings)
	{
		self::$filename = $filename;

		$temp = array();

		foreach ($strings as $k => $v)
		{
			$temp[$k] = 'Foo ' . $v;
		}

		return $temp;
	}

	/**
	 * This callback does nothing at all
	 *
	 * @param $filename
	 * @param $strings
	 */
	public static function donada($filename, $strings)
	{
		self::$filename = $filename;
	}

	/**
	 * This callback blocks the loading of language files
	 *
	 * @param $filename
	 * @param $strings
	 *
	 * @return bool
	 */
	public static function block($filename, $strings)
	{
		self::$filename = $filename;
		return false;
	}
}
