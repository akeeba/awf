<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Tests\Awf\Input;

use Awf\Input\Input;
use Tests\Helpers\ReflectionHelper;
use Tests\Stubs\Input\FilterMock;

/**
 * Test class for Input.
 *
 * @since  1.0
 */
class InputTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * The test class.
	 *
	 * @var    Input
	 * @since  1.0
	 */
	private $instance;

	/**
	 * Test the Awf\Input\Input::__construct method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Input::__construct
	 * @since   1.0
	 */
	public function testMagicConstruct()
	{
		$_REQUEST['AwfIAmSet'] = 'foobar';

		// Create an input object with default request
		$input = new Input(null, array('filter' => new FilterMock()));

		// Assert that the global $_REQUEST data is assigned to the input class
		$this->assertEquals(
			'foobar',
			$input->get('AwfIAmSet'),
			'Line: ' . __LINE__ . '.'
		);

		// Create an input object with custom data
		$otherData = array(
			'AwfOtherStuff' => 'baz',
		);

		$input = new Input($otherData, array('filter' => new FilterMock()));

		// Assert that the custom data is assigned to the input class
		$this->assertEquals(
			'baz',
			$input->get('AwfOtherStuff'),
			'Line: ' . __LINE__ . '.'
		);

		// Assert that the global $_REQUEST data is not assigned to the input class
		$this->assertNull(
			$input->get('AwfIAmSet'),
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * Test the Awf\Input\Input::__call method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Input::__call
	 * @since   1.0
	 */
	public function testMagicCall()
	{
		$customData = array(
			'mycmd' 	=> 'foo_bar',
			'mypath'	=> '/usr/local/bin/',
		);

		$input = new Input($customData, array('filter' => new FilterMock()));

		// Make sure getCmd calls get() with 'cmd' filter type
		$this->assertEquals(
			$input->get('mycmd', null, 'cmd'),
			$input->getCmd('mycmd', null),
			'Line: ' . __LINE__ . '.'
		);

		// Make sure getPath calls get() with 'path' filter type
		$this->assertEquals(
			$input->get('mypath', null, 'path'),
			$input->getPath('mypath', null),
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * Test the Awf\Input\Input::__get method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Input::__get
	 * @since   1.0
	 */
	public function testMagicGet()
	{
		// Test super globals
		$_POST['foo'] = 'bar';

		// Test the get method.
		$this->assertThat(
			$this->instance->post->get('foo'),
			$this->equalTo('bar'),
			'Line: ' . __LINE__ . '.'
		);

		// Test the set method.
		$this->instance->post->set('foo', 'notbar');
		$this->assertThat(
			$this->instance->post->get('foo'),
			$this->equalTo('notbar'),
			'Line: ' . __LINE__ . '.'
		);

		$_GET['foo'] = 'bar';

		// Test the get method.
		$this->assertThat(
			$this->instance->get->get('foo'),
			$this->equalTo('bar')
		);

		// Test the set method.
		$this->instance->get->set('foo', 'notbar');
		$this->assertThat(
			$this->instance->get->get('foo'),
			$this->equalTo('notbar')
		);

		// Test input class Cli
		$this->instance->cli->set('foo', 'bar');
		$this->assertThat(
			$this->instance->cli->get('foo'),
			$this->equalTo('bar')
		);
	}

	/**
	 * Test the Awf\Input\Input::count method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Input::count
	 * @since   1.0
	 */
	public function testCount()
	{
		$this->assertEquals(
			count($_REQUEST),
			count($this->instance)
		);

		$this->assertEquals(
			count($_POST),
			count($this->instance->post)
		);

		$this->assertEquals(
			count($_GET),
			count($this->instance->get)
		);
	}

	/**
	 * Test the Awf\Input\Input::get method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Input::get
	 * @since   1.0
	 */
	public function testGet()
	{
		$_REQUEST['foo'] = 'bar';

		$instance = new Input;

		// Test the get method.
		$this->assertThat(
			$instance->get('foo'),
			$this->equalTo('bar'),
			'Line: ' . __LINE__ . '.'
		);

		$_GET['foo'] = 'bar2';

		// Test the get method.
		$this->assertThat(
			$instance->get->get('foo'),
			$this->equalTo('bar2'),
			'Checks first use of new super-global.'
		);

		// Test the get method.
		$this->assertThat(
			$instance->get('default_value', 'default'),
			$this->equalTo('default'),
			'Line: ' . __LINE__ . '.'
		);

		$_REQUEST['empty'] = '';

		// Test the get method
		$this->assertThat(
			$instance->get('empty', 'default'),
			$this->equalTo('')
		);
	}

	/**
	 * Test the Awf\Input\Input::def method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Input::def
	 * @since   1.0
	 */
	public function testDef()
	{
		$_REQUEST['foo'] = 'bar';

		$this->instance->def('foo', 'nope');

		$this->assertThat(
			$_REQUEST['foo'],
			$this->equalTo('bar'),
			'Line: ' . __LINE__ . '.'
		);

		$this->instance->def('Awf', 'under test');

		$this->assertArrayHasKey('Awf', $_REQUEST, 'Checks super-global was modified.');
	}

	/**
	 * Test the Awf\Input\Input::set method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Input::set
	 * @since   1.0
	 */
	public function testSet()
	{
		$_REQUEST['foo'] = 'bar2';
		$this->instance->set('foo', 'bar');

		$this->assertThat(
			$_REQUEST['foo'],
			$this->equalTo('bar'),
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * Test the Awf\Input\Input::get method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Input::get
	 * @since   1.0
	 */
	public function testGetArray()
	{
		$filterMock = new \Tests\Stubs\Input\FilterMock;

		$array = array(
			'var1' => 'value1',
			'var2' => 34,
			'var3' => array('test')
		);

		$input = new Input(
			$array,
			array('filter' => $filterMock)
		);

		$this->assertThat(
			$input->getArray(
				array('var1' => 'filter1', 'var2' => 'filter2', 'var3' => 'filter3')
			),
			$this->equalTo(array('var1' => 'value1', 'var2' => 34, 'var3' => array('test'))),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertThat(
			$filterMock->calls['clean'][0],
			$this->equalTo(array('value1', 'filter1')),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertThat(
			$filterMock->calls['clean'][1],
			$this->equalTo(array(34, 'filter2')),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertThat(
			$filterMock->calls['clean'][2],
			$this->equalTo(array(array('test'), 'filter3')),
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * Test the Awf\Input\Input::get method using a nested data set.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Input::get
	 * @since   1.0
	 */
	public function testGetArrayNested()
	{
		$filterMock = new \Tests\Stubs\Input\FilterMock;

		$array = array(
			'var2' => 34,
			'var3' => array('var2' => 'test'),
			'var4' => array('var1' => array('var2' => 'test'))
		);
		$input = new Input(
			$array,
			array('filter' => $filterMock)
		);

		$this->assertThat(
			$input->getArray(
				array('var2' => 'filter2', 'var3' => array('var2' => 'filter3'))
			),
			$this->equalTo(array('var2' => 34, 'var3' => array('var2' => 'test'))),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertThat(
			$input->getArray(
				array('var4' => array('var1' => array('var2' => 'filter1')))
			),
			$this->equalTo(array('var4' => array('var1' => array('var2' => 'test')))),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertThat(
			$filterMock->calls['clean'][0],
			$this->equalTo(array(34, 'filter2')),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertThat(
			$filterMock->calls['clean'][1],
			$this->equalTo(array(array('var2' => 'test'), 'array')),
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * Test the Awf\Input\Input::getArray method without specified variables.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Input::getArray
	 * @since   1.0
	 */
	public function testGetArrayWithoutSpecifiedVariables()
	{
		$array = array(
			'var2' => 34,
			'var3' => array('var2' => 'test'),
			'var4' => array('var1' => array('var2' => 'test')),
			'var5' => array('foo' => array()),
			'var6' => array('bar' => null),
			'var7' => null
		);

		$input = new Input($array);

		$this->assertEquals(
			$input->getData(),
			$array,
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * Test the Awf\Input\Input::getMethod method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Input::getMethod
	 * @backupGlobals 1
	 * @since   1.0
	 */
	public function testGetMethod()
	{
		$_SERVER['REQUEST_METHOD'] = 'FOO';

		$input = new Input(null, array('filter' => new FilterMock()));

		$this->assertEquals(
			'FOO',
			$input->getMethod(),
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * Test the Awf\Input\Input::loadAllInputs method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Input::loadAllInputs
	 * @backupGlobals 1
	 * @since   1.0
	 */
	public function testLoadAllInputs()
	{
		$input = new Input(null, array('filter' => new FilterMock()));

		$GLOBALS['_TEST'] = array(
			'foo'	=> 'bar',
			'baz'	=> 'zaz',
		);
		ReflectionHelper::invoke($input, 'loadAllInputs');

		unset($GLOBALS['_TEST']);

		$this->assertEquals(
			'bar',
			$input->test->getCmd('foo', 'bar'),
			'Line: ' . __LINE__ . '.'
		);
	}


	/**
	 * Test the Awf\Input\Input::serialize method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Input::serialize
	 * @since   1.0
	 */
	public function testSerialize()
	{
		// Load the inputs so that the static $loaded is set to true.
		ReflectionHelper::invoke($this->instance, 'loadAllInputs');

		// Adjust the values so they are easier to handle.
		ReflectionHelper::setValue($this->instance, 'inputs', array('server' => 'remove', 'env' => 'remove', 'request' => 'keep'));
		ReflectionHelper::setValue($this->instance, 'options', 'options');
		ReflectionHelper::setValue($this->instance, 'data', 'data');

		$this->assertThat(
			$this->instance->serialize(),
			$this->equalTo('a:3:{i:0;s:7:"options";i:1;s:4:"data";i:2;a:1:{s:7:"request";s:4:"keep";}}'),
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * Test the Awf\Input\Input::unserialize method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Input::unserialize
	 * @since   1.0
	 */
	public function testUnserialize()
	{
		// Load the inputs so that the static $loaded is set to true.
		ReflectionHelper::invoke($this->instance, 'loadAllInputs');

		// Adjust the values so they are easier to handle.
		ReflectionHelper::setValue($this->instance, 'inputs', array('server' => 'remove', 'env' => 'remove', 'request' => 'keep'));
		ReflectionHelper::setValue($this->instance, 'options', 'options');
		ReflectionHelper::setValue($this->instance, 'data', 'data');

		$serialised = $this->instance->serialize();

		$newInput = new Input();
		$newInput->unserialize($serialised);

		$this->assertEquals(
			'data',
			ReflectionHelper::getValue($newInput, 'data'),
			'Line: ' . __LINE__ . '.'
		);
	}

	/*
	 * Protected methods.
	 */
	/**
	 * Setup for testing.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function setUp()
	{
		parent::setUp();

		$array = null;
		$this->instance = new Input($array, array('filter' => new \Tests\Stubs\Input\FilterMock));
	}
}
	