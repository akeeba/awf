<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Registry\Format;

use Awf\Registry\AbstractRegistryFormat;

/**
 * PHP class format handler for Registry
 *
 * This class is adapted from the Joomla! Framework
 */
class Php extends AbstractRegistryFormat
{
	/**
	 * Converts an object into a php class string.
	 * - NOTE: Only one depth level is supported.
	 *
	 * @param   object  $object  Data Source Object
	 * @param   array   $params  Parameters used by the formatter
	 *
	 * @return  string  Config class formatted string
	 */
	public function objectToString($object, $params = array())
	{
		// Build the object variables string
		$vars = '';

		foreach (get_object_vars($object) as $k => $v)
		{
			if (is_null($v))
			{
				$vars .= "\tpublic $" . $k . " = NULL;\n";
			}
			elseif (is_bool($v))
			{
				$vars .= "\tpublic $" . $k . " = " . ($v ? 'true' : 'false') . ";\n";
			}
			elseif (is_scalar($v))
			{
				$vars .= "\tpublic $" . $k . " = '" . addcslashes($v, '\\\'') . "';\n";
			}
			elseif (is_array($v) || is_object($v))
			{
				$vars .= "\tpublic $" . $k . " = " . $this->getArrayString((array) $v) . ";\n";
			}
		}

		$str = "<?php\nclass " . $params['class'] . " {\n";
		$str .= $vars;
		$str .= "}";

		// Use the closing tag if it not set to false in parameters.
		if (!isset($params['closingtag']) || $params['closingtag'] !== false)
		{
			$str .= "\n?>";
		}

		return $str;
	}

	/**
	 * Parse a PHP class formatted string and convert it into an object.
	 *
	 * @param   string  $data     PHP Class formatted string to convert.
	 * @param   array   $options  Options used by the formatter.
	 *
	 * @return  object   Data object.
	 */
	public function stringToObject($data, array $options = array())
	{
		throw new \BadMethodCallException('Do not use loadString() for PHP-based registry files; only use loadFile()');
	}

	/**
	 * Method to get an array as an exported string.
	 *
	 * @param   array  $a  The array to get as a string.
	 *
	 * @return  string
	 */
	protected function getArrayString($a)
	{
		$s = '[';
		$i = 0;

		foreach ($a as $k => $v)
		{
			$s .= ($i) ? ', ' : '';
			$s .= '"' . $k . '" => ';

			if (is_array($v) || is_object($v))
			{
				$s .= $this->getArrayString((array) $v);
			}
			elseif (is_null($v))
			{
				$s .= 'NULL';
			}
			elseif (is_bool($v))
			{
				$s .= $v ? 'true' : 'false';
			}
			else
			{
				$s .= '"' . addslashes($v) . '"';
			}

			$i++;
		}

		$s .= ']';

		return $s;
	}
}
