<?php
/**
 * @package    awf
 * @copyright  Copyright (c)2014-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU GPL version 3 or later
 */

namespace Awf\Tests\Helpers;

use Awf\Tests\Stubs\Fakeapp\Container as FakeContainer;

/**
 * Base class for tests requiring a container and/or an application to be set up
 *
 * @package Awf\Tests\Helpers
 */
abstract class ApplicationTestCase extends \PHPUnit_Framework_TestCase
{
	/** @var FakeContainer A container suitable for unit testing */
	public static $container = null;

	public function __construct($name = null, array $data = array(), $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		// We can't use setUpBeforeClass or setUp because PHPUnit will not run these methods before
		// getting the data from the data provider of each test :(

		ReflectionHelper::setValue('\\Awf\\Application\\Application', 'instances', array());

		// Convince the autoloader about our default app and its container
		static::$container = new FakeContainer();
		\Awf\Application\Application::getInstance('Fakeapp', static::$container);
	}
}
