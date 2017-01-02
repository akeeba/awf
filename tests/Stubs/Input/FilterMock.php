<?php
/**
 * @package        awf
 * @subpackage     tests.stubs
 * @copyright      2014-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Input;

/**
 * FilterMock test class.
 *
 * @since  1.0
 */
class FilterMock
{
	public $calls = array();

	/**
	 * Test __call
	 *
	 * @param   string  $name       @todo
	 * @param   mixed   $arguments  @todo
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

		return $arguments[0];
	}
}