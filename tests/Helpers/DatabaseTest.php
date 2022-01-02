<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Helpers;

use Awf\Application\Application;
use Awf\Database\Driver;
use Awf\Tests\Stubs\Fakeapp\Container as FakeContainer;
use Awf\Tests\Stubs\Utils\InsertOperation;
use Awf\Tests\Stubs\Utils\TruncateOperation;
use Awf\Uri\Uri;

abstract class DatabaseTest extends \PHPUnit_Extensions_Database_TestCase
{
	/** @var FakeContainer A container suitable for unit testing */
	public static $container = null;

	/**
	 * @var    Driver  The active database driver being used for the tests.
	 * @since  1.0
	 */
	protected static $driver;

	/** @var array If not empty, only tests inside this array would be executed, skipping the rest */
	private $whiteListTests = array();
	/** @var array Tests that should be skipped */
	private $blackLisTests  = array();

	/**
	 * This method is called before the first test of this test class is run.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public static function setUpBeforeClass()
	{
		// We always want the default database test case to use an SQLite memory database.
		$options = array(
			'driver' => 'sqlite',
			'database' => ':memory:',
			'prefix' => 'awf_'
		);

		try
		{
			// Attempt to instantiate the driver.
			self::$driver = Driver::getInstance($options);

			// Create a new PDO instance for an SQLite memory database and load the test schema into it.
			$pdo = new \PDO('sqlite::memory:');

			$pdo->exec(file_get_contents(__DIR__ . '/../Stubs/schema/ddl.sql'));

			// Set the PDO instance to the driver using reflection.
			TestHelper::setValue(self::$driver, 'connection', $pdo);
		}
		catch (\RuntimeException $e)
		{
			self::$driver = null;
		}

		// If for some reason an exception object was returned set our database object to null.
		if (self::$driver instanceof \Exception)
		{
			self::$driver = null;
		}
	}

	/**
	 * This method is called after the last test of this test class is run.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public static function tearDownAfterClass()
	{
		self::$driver = null;
	}

	/**
	 * Assigns mock callbacks to methods.
	 *
	 * @param   object  $mockObject  The mock object that the callbacks are being assigned to.
	 * @param   array   $array       An array of methods names to mock with callbacks.
	 *
	 * @return  void
	 *
	 * @note    This method assumes that the mock callback is named {mock}{method name}.
	 * @since   1.0
	 */
	public function assignMockCallbacks($mockObject, $array)
	{
		foreach ($array as $index => $method)
		{
			if (is_array($method))
			{
				$methodName = $index;
				$callback = $method;
			}
			else
			{
				$methodName = $method;
				$callback = array(get_called_class(), 'mock' . $method);
			}

			$mockObject->expects($this->any())
				->method($methodName)
				->will($this->returnCallback($callback));
		}
	}

	/**
	 * Assigns mock values to methods.
	 *
	 * @param   object  $mockObject  The mock object.
	 * @param   array   $array       An associative array of methods to mock with return values:<br />
	 *                               string (method name) => mixed (return value)
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function assignMockReturns($mockObject, $array)
	{
		foreach ($array as $method => $return)
		{
			$mockObject->expects($this->any())
				->method($method)
				->will($this->returnValue($return));
		}
	}

	/**
	 * Returns the default database connection for running the tests.
	 *
	 * @return  \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
	 *
	 * @since   1.0
	 */
	protected function getConnection()
	{
		if (!is_null(self::$driver))
		{
			return $this->createDefaultDBConnection(self::$driver->getConnection(), ':memory:');
		}
		else
		{
			return null;
		}
	}

	/**
	 * Gets the data set to be loaded into the database during setup
	 *
	 * @return  \PHPUnit_Extensions_Database_DataSet_XmlDataSet
	 *
	 * @since   1.0
	 */
	protected function getDataSet()
	{
		return new \PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
	}

	/**
	 * Returns the database operation executed in test setup.
	 *
	 * @return  \PHPUnit_Extensions_Database_Operation_Composite
	 *
	 * @since   1.0
	 */
	protected function getSetUpOperation()
	{
		// At the moment we can safely TRUNCATE tables, since we're not using InnoDB tables nor foreign keys
        // However if we ever need them, we can use our InsertOperation and TruncateOperation to suppress foreign keys
		return new \PHPUnit_Extensions_Database_Operation_Composite(
			array(
				\PHPUnit_Extensions_Database_Operation_Factory::TRUNCATE(),
				\PHPUnit_Extensions_Database_Operation_Factory::INSERT()
			)
		);
	}

	/**
	 * Sets up the fixture.
	 *
	 * This method is called before a test is executed.
	 *
	 * @param bool $resetContainer  Should I reset the Container?
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function setUp($resetContainer = true)
	{
		$class       = get_class($this);
		$parts       = explode('\\', $class);
		$currentTest = array_pop($parts);

		// Do I have to skip any tests? This is our latest resort when we have entangled tests: Test A is failing when the
		// whole suite is executed in a precise order, however we don't know WHICH tests is corrupting the environment.
		// We can't exclude any test since we would have a whole different suite, so the only solution is to SKIP them
		if($this->whiteListTests && !in_array($currentTest, $this->whiteListTests))
		{
			$this->markTestSkipped('Skipped due whitelist settings');
		}

		if(in_array($currentTest, $this->blackLisTests))
		{
			$this->markTestSkipped('Skipped due blacklist settings');
		}

		// Am I asked to reset the Application Container?
		if($resetContainer)
		{
			ReflectionHelper::setValue('\\Awf\\Application\\Application', 'instances', array());
			static::$container = new FakeContainer();

			Application::getInstance('Fakeapp', static::$container);
		}

        // Always reset the URI instances
        Uri::reset();

		if (empty(static::$driver))
		{
			$this->markTestSkipped('There is no database driver.');
		}

		parent::setUp();
	}
}
