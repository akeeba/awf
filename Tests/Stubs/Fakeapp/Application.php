<?php
/**
 * Created by PhpStorm.
 * User: Nicholas
 * Date: 6/6/2014
 * Time: 4:23 μμ
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