<?php
/**
 * @package        awf
 * @subpackage     tests.date.date
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Tests\Awf\Input;

/**
 * FilterInputMockTracker test class.
 *
 * @since  1.0
 */
class FilterInputMock
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