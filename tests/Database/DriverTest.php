<?php
/**
 * @package        awf
 * @subpackage     tests.date.date
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 *
 * This class is adapted from Joomla! Framework
 */

namespace Awf\Tests\Database;

use Awf\Database\Driver;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Helpers\TestHelper;
use Awf\Tests\Helpers\DatabaseTest;
use Awf\Tests\Stubs\Pimple\NonInvokable;

require_once __DIR__ . '/../Stubs/database/NosqlDriver.php';

/**
 * Class DriverTest
 *
 * @package Awf\Tests\Tests
 *
 * @coversDefaultClass \Awf\Database\Driver
 */
class DriverTest extends DatabaseTest
{
	/**
	 * @var    Driver
	 * @since  1.0
	 */
	protected $instance;

	/**
	 * A store to track if logging is working.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $logs;

	public function test__callQuote()
	{
		$this->assertThat(
			$this->instance->q('foo'),
			$this->equalTo($this->instance->quote('foo')),
			'Tests the q alias of quote.'
		);
	}

	public function test__callQuoteName()
	{
		$this->assertThat(
			$this->instance->qn('foo'),
			$this->equalTo($this->instance->quoteName('foo')),
			'Tests the qn alias of quoteName.'
		);
	}

	public function test__callUnknown()
	{
		$this->assertThat(
			$this->instance->foo(),
			$this->isNull(),
			'Tests for an unknown method.'
		);
	}

	public function test__construct()
	{
		$options = array(
			'database' => 'mightymouse',
			'prefix' => 'kot_',
		);

		$dummy = new Driver\Nosql($options);

		$actualOptions = ReflectionHelper::getValue($dummy, 'options');

		$this->assertArrayHasKey('database', $actualOptions);
		$this->assertEquals('mightymouse', $actualOptions['database']);

		$this->assertArrayHasKey('prefix', $actualOptions);
		$this->assertEquals('kot_', $actualOptions['prefix']);
	}

	public function testGetInstance()
	{
		$optionsOne = array(
			'driver' => 'nosql',
			'database' => 'mightymouse',
			'prefix' => 'kot_',
		);

		$optionsTwo = array(
			'driver' => 'nosql',
			'database' => 'dangermouse',
			'prefix' => 'dng_',
		);

		$driverOne = Driver::getInstance($optionsOne);
		$driverTwo = Driver::getInstance($optionsTwo);

		$this->assertInstanceOf('\\Awf\\Database\\Driver\\Nosql', $driverOne);
		$this->assertInstanceOf('\\Awf\\Database\\Driver\\Nosql', $driverTwo);

		$this->assertNotEquals($driverOne, $driverTwo);

		$actualOptions = ReflectionHelper::getValue($driverOne, 'options');
		$this->assertEquals('mightymouse', $actualOptions['database']);

		$actualOptions = ReflectionHelper::getValue($driverTwo, 'options');
		$this->assertEquals('dangermouse', $actualOptions['database']);
	}

	public function testGetConnection()
	{
		$this->assertNull($this->instance->getConnection());
	}

	public function testGetConnectors()
	{
		$this->assertContains(
			'Sqlite',
			$this->instance->getConnectors(),
			'The getConnectors method should return an array with Sqlite as an available option.'
		);
	}

	public function testGetCount()
	{
		$this->assertEquals(0, $this->instance->getCount());
	}

	public function testGetDatabase()
	{
		$this->assertEquals('mightymouse', TestHelper::invoke($this->instance, 'getDatabase'));
	}

	public function testGetDateFormat()
	{
		$this->assertThat(
			$this->instance->getDateFormat(),
			$this->equalTo('Y-m-d H:i:s')
		);
	}

	public function testSplitSql()
	{
		$this->assertThat(
			$this->instance->splitSql('SELECT * FROM #__foo;SELECT * FROM #__bar;'),
			$this->equalTo(
				array(
					'SELECT * FROM #__foo;',
					'SELECT * FROM #__bar;'
				)
			),
			'splitSql method should split a string of multiple queries into an array.'
		);
	}

	public function testGetPrefix()
	{
		$this->assertThat(
			$this->instance->getPrefix(),
			$this->equalTo('kot_')
		);
	}

	public function testGetNullDate()
	{
		$this->assertThat(
			$this->instance->getNullDate(),
			$this->equalTo('1BC')
		);
	}

	public function testGetMinimum()
	{
		$this->assertThat(
			$this->instance->getMinimum(),
			$this->equalTo('12.1'),
			'getMinimum should return a string with the minimum supported database version number'
		);
	}

	public function testIsMinimumVersion()
	{
		$this->assertThat(
			$this->instance->isMinimumVersion(),
			$this->isTrue(),
			'isMinimumVersion should return a boolean true if the database version is supported by the driver'
		);
	}

	public function testSetDebug()
	{
		$this->assertThat(
			$this->instance->setDebug(true),
			$this->isType('boolean'),
			'setDebug should return a boolean value containing the previous debug state.'
		);
	}

	public function testSetQuery()
	{
		$this->assertThat(
			$this->instance->setQuery('SELECT * FROM #__dbtest'),
			$this->isInstanceOf('Awf\Database\Driver'),
			'setQuery method should return an instance of Awf\Database\Driver.'
		);
	}

	public function testReplacePrefix()
	{
		$this->assertThat(
			$this->instance->replacePrefix('SELECT * FROM #__dbtest'),
			$this->equalTo('SELECT * FROM kot_dbtest'),
			'replacePrefix method should return the query string with the #__ prefix replaced by the actual table prefix.'
		);
	}

	public function testQuote()
	{
		$this->assertThat(
			$this->instance->quote('test', false),
			$this->equalTo("'test'"),
			'Tests the without escaping.'
		);

		$this->assertThat(
			$this->instance->quote('test'),
			$this->equalTo("'-test-'"),
			'Tests the with escaping (default).'
		);

		$this->assertEquals(
			array("'-test1-'", "'-test2-'"),
			$this->instance->quote(array('test1', 'test2')),
			'Check that the array is quoted.'
		);
	}

	public function testQuoteBooleanTrue()
	{
		$this->assertThat(
			$this->instance->quote(true),
			$this->equalTo("'-1-'"),
			'Tests handling of boolean true with escaping (default).'
		);
	}

	public function testQuoteBooleanFalse()
	{
		$this->assertThat(
			$this->instance->quote(false),
			$this->equalTo("'--'"),
			'Tests handling of boolean false with escaping (default).'
		);
	}

	public function testQuoteNull()
	{
		$this->assertThat(
			$this->instance->quote(null),
			$this->equalTo("'--'"),
			'Tests handling of null with escaping (default).'
		);
	}

	public function testQuoteInteger()
	{
		$this->assertThat(
			$this->instance->quote(42),
			$this->equalTo("'-42-'"),
			'Tests handling of integer with escaping (default).'
		);
	}

	public function testQuoteFloat()
	{
		$this->assertThat(
			$this->instance->quote(3.14),
			$this->equalTo("'-3.14-'"),
			'Tests handling of float with escaping (default).'
		);
	}

	public function testQuoteName()
	{
		$this->assertThat(
			$this->instance->quoteName('test'),
			$this->equalTo('[test]'),
			'Tests the left-right quotes on a string.'
		);

		$this->assertThat(
			$this->instance->quoteName('a.test'),
			$this->equalTo('[a].[test]'),
			'Tests the left-right quotes on a dotted string.'
		);

		$this->assertThat(
			$this->instance->quoteName(array('a', 'test')),
			$this->equalTo(array('[a]', '[test]')),
			'Tests the left-right quotes on an array.'
		);

		$this->assertThat(
			$this->instance->quoteName(array('a.b', 'test.quote')),
			$this->equalTo(array('[a].[b]', '[test].[quote]')),
			'Tests the left-right quotes on an array.'
		);

		$this->assertThat(
			$this->instance->quoteName(array('a.b', 'test.quote'), array(null, 'alias')),
			$this->equalTo(array('[a].[b]', '[test].[quote] AS [alias]')),
			'Tests the left-right quotes on an array.'
		);

		$this->assertThat(
			$this->instance->quoteName(array('a.b', 'test.quote'), array('alias1', 'alias2')),
			$this->equalTo(array('[a].[b] AS [alias1]', '[test].[quote] AS [alias2]')),
			'Tests the left-right quotes on an array.'
		);

		$this->assertThat(
			$this->instance->quoteName((object) array('a', 'test')),
			$this->equalTo(array('[a]', '[test]')),
			'Tests the left-right quotes on an object.'
		);

// 		TestHelper::setValue($this->db, 'nameQuote', '/');

		$refl = new \ReflectionClass($this->instance);
		$property = $refl->getProperty('nameQuote');
		$property->setAccessible(true);
		$property->setValue($this->instance, '/');

		$this->assertThat(
			$this->instance->quoteName('test'),
			$this->equalTo('/test/'),
			'Tests the uni-quotes on a string.'
		);
	}

	public function testTruncateTable()
	{
		$this->assertNull(
			$this->instance->truncateTable('#__dbtest'),
			'truncateTable should not return anything if successful.'
		);
	}

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 *
	 * @return void
	 */
	protected function setUp()
	{
		$this->instance = Driver::getInstance(
			array(
				'driver' => 'nosql',
				'database' => 'mightymouse',
				'prefix' => 'kot_',
			)
		);
	}

	/**
	 * Tears down the fixture.
	 *
	 * This method is called after a test is executed.
	 *
	 * @return void
	 */
	protected function tearDown()
	{
		// We need this to be empty.
	}
}
