<?php
/**
 * @package		awf
 * @copyright	2014-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Fakeapp\Controller;


use Awf\Mvc\Controller;

class Jasager extends Controller
{
	public static $myResult = array(
		'echo'		=> null,
		'return'	=> null,
		'exception'	=> null,
	);

	/**
	 * Resets the mocked results.
	 * We MUST call this method between tests, otherwise the previous state will corrupt other tests
	 */
	public static function resetResults()
	{
		static::$myResult = array(
			'echo'		=> null,
			'return'	=> null,
			'exception'	=> null,
		);
	}

	public static function setUpResult($echo = null, $return = null, $exception = null)
	{
		static::$myResult = array(
			'echo'		=> $echo,
			'return'	=> $return,
			'exception'	=> $exception
		);
	}

	public function yessir()
	{
		if (!is_null(static::$myResult['exception']))
		{
			throw static::$myResult['exception'];
		}

		if (static::$myResult['echo'])
		{
			echo static::$myResult['echo'];
		}

		return static::$myResult['return'];
	}

	public function redirect()
	{
		// Do not apply redirections.
	}
}