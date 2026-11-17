<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Mvc\DataModel\Filter;


class Boolean extends Number
{
	/**
	 * Is it a null or otherwise empty value?
	 *
	 * @param   mixed  $value  The value to test for emptiness
	 *
	 * @return  bool
	 */
	public function isEmpty($value)
	{
		// An empty array is a blank too, like for every other filter: it would otherwise produce "IN ()", a syntax error.
		return is_null($value) || ($value === '') || ($value === []);
	}
} 
