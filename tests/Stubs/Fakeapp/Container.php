<?php
/**
 * Created by PhpStorm.
 * User: Nicholas
 * Date: 6/6/2014
 * Time: 4:11 μμ
 */

namespace Awf\Tests\Stubs\Fakeapp;

class Container extends \Awf\Container\Container
{
	public function __construct(array $values = array())
	{
		$basePath = realpath(__DIR__ . '/../../..') . '/tests';

		$defaults = array(
			'application_name'     => 'Fakeapp',
			'session_segment_name' => 'fakeapp',
			'basePath'             => $basePath . '/Stubs/Fakeapp',
			'languagePath'         => $basePath . '/Stubs/Fakeapp/language',
			'filesystemBase'       => $basePath . '/Stubs/Fakeapp',
			'templatePath'         => $basePath . '/Stubs/Fakeapp/template',
			'sqlPath'              => $basePath . '/Stubs/schema'
		);

		$values = array_merge($defaults, $values);

		parent::__construct($values);

		$this->appConfig->set('live_site', 'http://www.example.com');
	}
} 