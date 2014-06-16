<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Container;


use Awf\Container\Container;

/**
 * Class ContainerTest
 *
 * @package Container
 *
 * @coversDefaultClass \Awf\Container\Container
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
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
	 * @depends testForcedValues
	 *
	 * @param Container $c
	 */
	public function testGetApplication(Container $c)
	{
		$object = $c->application;

		$this->assertInstanceOf('\\Awf\\Application\\Application', $object);
	}

	/**
	 * @depends testForcedValues
	 *
	 * @param Container $c
	 */
	public function testGetAppConfig(Container $c)
	{
		$object = $c->appConfig;

		$this->assertInstanceOf('\\Awf\\Application\\Configuration', $object);
	}

	/**
	 * @depends testForcedValues
	 *
	 * @param Container $c
	 */
	public function testGetDb(Container $c)
	{
		$object = $c->db;

		$this->assertInstanceOf('\\Awf\\Database\\Driver', $object);
	}

	/**
	 * @depends testForcedValues
	 *
	 * @param Container $c
	 */
	public function testGetDispatcher(Container $c)
	{
		$object = $c->dispatcher;

		$this->assertInstanceOf('\\Awf\\Dispatcher\\Dispatcher', $object);
	}

	/**
	 * @depends testForcedValues
	 *
	 * @param Container $c
	 */
	public function testGetEventDispatcher(Container $c)
	{
		$object = $c->eventDispatcher;

		$this->assertInstanceOf('\\Awf\\Event\\Dispatcher', $object);
	}

	/**
	 * @depends testForcedValues
	 *
	 * @param Container $c
	 */
	public function testGetFilesystem(Container $c)
	{
		$object = $c->fileSystem;

		$this->assertInstanceOf('\\Awf\\Filesystem\\FilesystemInterface', $object);
	}

	/**
	 * @depends testForcedValues
	 *
	 * @param Container $c
	 */
	public function testGetInput(Container $c)
	{
		$object = $c->input;

		$this->assertInstanceOf('\\Awf\\Input\\Input', $object);
	}

	/**
	 * @depends testForcedValues
	 *
	 * @param Container $c
	 */
	public function testGetMailer(Container $c)
	{
		$object = $c->mailer;

		$this->assertInstanceOf('\\Awf\\Mailer\\Mailer', $object);
	}

	/**
	 * @depends testForcedValues
	 *
	 * @param Container $c
	 */
	public function testGetRouter(Container $c)
	{
		$object = $c->router;

		$this->assertInstanceOf('\\Awf\\Router\\Router', $object);
	}

	/**
	 * @depends testForcedValues
	 *
	 * @param Container $c
	 */
	public function testGetSession(Container $c)
	{
		$object = $c->session;

		$this->assertInstanceOf('\\Awf\\Session\\Manager', $object);
	}

	/**
	 * @depends testForcedValues
	 *
	 * @param Container $c
	 */
	public function testGetSegment(Container $c)
	{
		$object = $c->segment;

		$this->assertInstanceOf('\\Awf\\Session\\Segment', $object);
	}

	/**
	 * @depends testForcedValues
	 *
	 * @param Container $c
	 */
	public function testGetUserManager(Container $c)
	{
		$object = $c->userManager;

		$this->assertInstanceOf('\\Awf\\User\\ManagerInterface', $object);
	}

}
 