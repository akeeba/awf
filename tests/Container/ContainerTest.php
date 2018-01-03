<?php
/**
 * @package		awf
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Container;


use Awf\Container\Container;
use Awf\Tests\Helpers\AwfTestCase;

/**
 * Class ContainerTest
 *
 * @package Container
 *
 * @coversDefaultClass \Awf\Container\Container
 */
class ContainerTest extends AwfTestCase
{
	/**
	 * Make sure we can get a customised container
	 *
	 * @return Container
	 */
	public function testForcedValues()
	{
		$values = array(
			'application_name'		=> 'fakeapp',
			'session_segment_name'	=> 'fakeapp_segment',
			'basePath'				=> realpath(__DIR__ . '/../Stubs/Fakeapp'),
			'templatePath'			=> realpath(__DIR__ . '/../Stubs/Fakeapp/template'),
			'languagePath'			=> realpath(__DIR__ . '/../data/lang'),
			'temporaryPath'			=> realpath(__DIR__ . '/../Stubs/Fakeapp/tmp'),
			'filesystemBase'		=> realpath(__DIR__ . '/../Stubs/Fakeapp'),
			'sqlPath'				=> realpath(__DIR__ . '/../Stubs/Fakeapp/sql'),
		);

		if (!defined('APATH_BASE'))
		{
			define('APATH_BASE', realpath(__DIR__ . '/../Stubs/Fakeapp'));
		}

		$container = new Container($values);

		$this->assertInstanceOf('\\Awf\\Container\\Container', $container);

		foreach ($values as $k => $v)
		{
			$this->assertEquals($v, $container->$k);
			$this->assertEquals($v, $container[$k]);
		}

		return $container;
	}

	/**
	 * @group       Container
	 */
	public function testGetApplication()
	{
		$c = $this->getContainer();
		$object = $c->application;

		$this->assertInstanceOf('\\Awf\\Application\\Application', $object);
	}

	/**
	 * @group       Container
	 */
	public function testGetAppConfig()
	{
		$c = $this->getContainer();
		$object = $c->appConfig;

		$this->assertInstanceOf('\\Awf\\Application\\Configuration', $object);
	}

	/**
	 * @group       Container
	 */
	public function testGetDb()
	{
		$c = $this->getContainer();
		$object = $c->db;

		$this->assertInstanceOf('\\Awf\\Database\\Driver', $object);
	}

	/**
	 * @group       Container
	 */
	public function testGetDispatcher()
	{
		$c = $this->getContainer();
		$object = $c->dispatcher;

		$this->assertInstanceOf('\\Awf\\Dispatcher\\Dispatcher', $object);
	}

	/**
	 * @group       Container
	 */
	public function testGetEventDispatcher()
	{
		$c = $this->getContainer();
		$object = $c->eventDispatcher;

		$this->assertInstanceOf('\\Awf\\Event\\Dispatcher', $object);
	}

	/**
	 * @group       Container
	 */
	public function testGetFilesystem()
	{
		$c = $this->getContainer();
		$object = $c->fileSystem;

		$this->assertInstanceOf('\\Awf\\Filesystem\\FilesystemInterface', $object);
	}

	/**
	 * @group       Container
	 */
	public function testGetInput()
	{
		$c = $this->getContainer();
		$object = $c->input;

		$this->assertInstanceOf('\\Awf\\Input\\Input', $object);
	}

	/**
	 * @group       Container
	 */
	public function testGetMailer()
	{
		$c = $this->getContainer();
		$object = $c->mailer;

		$this->assertInstanceOf('\\Awf\\Mailer\\Mailer', $object);
	}

	/**
	 * @group       Container
	 */
	public function testGetRouter()
	{
		$c = $this->getContainer();
		$object = $c->router;

		$this->assertInstanceOf('\\Awf\\Router\\Router', $object);
	}

	/**
	 * @group       Container
	 */
	public function testGetSession()
	{
		$c = $this->getContainer();
		$object = $c->session;

		$this->assertInstanceOf('\\Awf\\Session\\Manager', $object);
	}

	/**
	 * @group       Container
	 */
	public function testGetSegment()
	{
		$c = $this->getContainer();
		$object = $c->segment;

		$this->assertInstanceOf('\\Awf\\Session\\Segment', $object);
	}

	/**
	 * @group       Container
	 */
	public function testGetUserManager()
	{
		$c = $this->getContainer();
		$object = $c->userManager;

		$this->assertInstanceOf('\\Awf\\User\\ManagerInterface', $object);
	}

	protected function getContainer()
	{
		$values = array(
			'application_name'		=> 'fakeapp',
			'session_segment_name'	=> 'fakeapp_segment',
			'basePath'				=> realpath(__DIR__ . '/../Stubs/Fakeapp'),
			'templatePath'			=> realpath(__DIR__ . '/../Stubs/Fakeapp/template'),
			'languagePath'			=> realpath(__DIR__ . '/../data/lang'),
			'temporaryPath'			=> realpath(__DIR__ . '/../Stubs/Fakeapp/tmp'),
			'filesystemBase'		=> realpath(__DIR__ . '/../Stubs/Fakeapp'),
			'sqlPath'				=> realpath(__DIR__ . '/../Stubs/Fakeapp/sql'),
		);

		if (!defined('APATH_BASE'))
		{
			define('APATH_BASE', realpath(__DIR__ . '/../Stubs/Fakeapp'));
		}

		$container = new Container($values);

		return $container;
	}
}
