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
		$defaults = array(
			'application_name'     => 'Fakeapp',
			'session_segment_name' => 'fakeapp',
			'basePath'             => realpath(__DIR__),
			'languagePath'         => realpath(__DIR__ . '/../../data/lang'),
			'filesystemBase'       => realpath(__DIR__),
			'templatePath'			=> realpath(__DIR__ . '/template')
		);

		$values = array_merge($defaults, $values);

		parent::__construct($values);

		$this->appConfig->set('live_site', 'http://www.example.com');
	}
} 