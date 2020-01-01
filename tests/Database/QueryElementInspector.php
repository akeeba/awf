<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Database;

/**
 * Class to expose protected properties and methods in QueryElement for testing purposes.
 *
 * @since  1.0
 *
 * This class is adapted from Joomla! Framework
 */
class QueryElementInspector extends \Awf\Database\QueryElement
{
	/**
	 * Gets any property from the class.
	 *
	 * @param   string  $property  The name of the class property.
	 *
	 * @return  mixed   The value of the class property.
	 *
	 * @since   1.0
	 */
	public function __get($property)
	{
		return $this->$property;
	}

	/**
	 * Sets any property from the class.
	 *
	 * @param   string  $property  The name of the class property.
	 * @param   string  $value     The value of the class property.
	 *
	 * @return  mixed
	 *
	 * @since   1.0
	 */
	public function __set($property, $value)
	{
		return $this->$property = $value;
	}
}
