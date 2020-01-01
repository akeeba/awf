<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Utils;


class MockDocument
{
	public $calls = array();

	/**
	 * Test __call
	 *
	 * @param   string  $name
	 * @param   mixed   $arguments
	 *
	 * @return void
	 */
	public function __call($name, $arguments)
	{
		if (!isset($this->calls[$name]))
		{
			$this->calls[$name] = array();
		}

		$this->calls[$name][] = $arguments;
	}
} 
