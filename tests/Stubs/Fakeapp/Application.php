<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Fakeapp;


class Application extends \Awf\Application\Application
{
	public $myExitCode = 0;
	public $myCloseCounter = 0;

	public function initialise()
	{

	}

	public function close($code = 0)
	{
		$this->myCloseCounter++;
		$this->myExitCode = $code;
	}
}
