<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Event;


use Awf\Event\Dispatcher;
use Awf\Tests\Helpers\ApplicationTestCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Event\FirstObserver;
use Awf\Tests\Stubs\Event\SecondObserver;
use Awf\Tests\Stubs\Fakeapp\Container as FakeContainer;

/**
 * Class DispatcherTest
 *
 * @package Awf\Tests\Event
 *
 * @coversDefaultClass Awf\Event\Dispatcher
 */
class DispatcherTest extends ApplicationTestCase
{
	/** @var  Dispatcher */
	protected $object;

	/**
	 * @covers Awf\Event\Dispatcher::__construct
	 */
	public function testConstructor()
	{
		$myDispatcher = new Dispatcher(static::$container);

		$this->assertInstanceOf('\\Awf\\Event\\Dispatcher', $myDispatcher);

		$this->assertEquals(
			static::$container,
			ReflectionHelper::getValue($myDispatcher, 'container')
		);
	}

	/**
	 * @covers Awf\Event\Dispatcher::getContainer
	 */
	public function testGetContainer()
	{
		$actual = $this->object->getContainer();
		$this->assertEquals(static::$container, $actual);
	}

	/**
	 * @covers Awf\Event\Dispatcher::getInstance
	 */
	public function testGetInstance()
	{
		ReflectionHelper::setValue($this->object, 'instances', array());

		// Test that the correct object is returned by the first call to getInstance
		$myDispatcher = Dispatcher::getInstance(static::$container);
		$this->assertInstanceOf('\\Awf\\Event\\Dispatcher', $myDispatcher);
		$this->assertEquals(
			static::$container,
			ReflectionHelper::getValue($myDispatcher, 'container')
		);

		// Test that the correct object is returned by subsequent calls to getInstance
		$newDispatcher = Dispatcher::getInstance(static::$container);
		$this->assertInstanceOf('\\Awf\\Event\\Dispatcher', $newDispatcher);
		$this->assertEquals(
			static::$container,
			ReflectionHelper::getValue($newDispatcher, 'container')
		);
		$this->assertEquals(
			$myDispatcher,
			$newDispatcher
		);

		// Test that a different container results in a different dispatcher instance
		$foobarContainer = new FakeContainer(array(
			'application_name' => 'otherfoo'
		));
		$otherDispatcher = Dispatcher::getInstance($foobarContainer);
		$this->assertInstanceOf('\\Awf\\Event\\Dispatcher', $otherDispatcher);
		$this->assertNotEquals($myDispatcher, $otherDispatcher);
		$this->assertEquals(
			$foobarContainer,
			ReflectionHelper::getValue($otherDispatcher, 'container')
		);

		// Test that subsequent calls with the original container return the correct dispatcher
		$newDispatcher = Dispatcher::getInstance(static::$container);
		$this->assertInstanceOf('\\Awf\\Event\\Dispatcher', $newDispatcher);
		$this->assertEquals(
			static::$container,
			ReflectionHelper::getValue($newDispatcher, 'container')
		);
		$this->assertEquals(
			$myDispatcher,
			$newDispatcher
		);
	}

	/**
	 * @covers Awf\Event\Dispatcher::attach
	 */
	public function testAttach()
	{
		ReflectionHelper::setValue($this->object, 'observers', array());

		// Test that an observer is auto-attached to the observable dispatcher
		$observer1 = new FirstObserver($this->object);
		$observers = ReflectionHelper::getValue($this->object, 'observers');
		$this->assertCount(1, $observers);
		$this->assertEquals($observer1, $observers[get_class($observer1)]);

		// Test that another observer is auto-attached to the observable dispatcher
		$observer2 = new SecondObserver($this->object);
		$observers = ReflectionHelper::getValue($this->object, 'observers');
		$this->assertCount(2, $observers);
		$this->assertEquals($observer2, $observers[get_class($observer2)]);

		// Test that we cannot attach a new instance of the same observer class
		$observer1new = new FirstObserver($this->object);
		$observers = ReflectionHelper::getValue($this->object, 'observers');
		$this->assertCount(2, $observers);
		$this->assertNotEquals($observer1new, $observers[get_class($observer1)]);
	}

	/**
	 * @covers Awf\Event\Dispatcher::detach
	 */
	public function testDetach()
	{
		ReflectionHelper::setValue($this->object, 'observers', array());
		$observer1 = new FirstObserver($this->object);
		$observer2 = new SecondObserver($this->object);

		$observers = ReflectionHelper::getValue($this->object, 'observers');
		$this->assertCount(2, $observers);

		// Detaching an observer
		$this->object->detach($observer1);
		$observers = ReflectionHelper::getValue($this->object, 'observers');
		$this->assertCount(1, $observers);

		// Detaching the same observer
		$this->object->detach($observer1);
		$observers = ReflectionHelper::getValue($this->object, 'observers');
		$this->assertCount(1, $observers);

		// Detaching another observer
		$this->object->detach($observer2);
		$observers = ReflectionHelper::getValue($this->object, 'observers');
		$this->assertCount(0, $observers);
	}

	/**
	 * @covers Awf\Event\Dispatcher::hasObserver
	 * @covers Awf\Event\Dispatcher::hasObserverClass
	 */
	public function testHasObserver()
	{
		ReflectionHelper::setValue($this->object, 'observers', array());
		$observer1 = new FirstObserver($this->object);

		$otherDispatcher = new Dispatcher(static::$container);
		$observer2 = new SecondObserver($otherDispatcher);

		$actual = $this->object->hasObserver($observer1);
		$this->assertTrue($actual);

		$actual = $this->object->hasObserver($observer2);
		$this->assertFalse($actual);
	}

	/**
	 * @covers Awf\Event\Dispatcher::trigger
	 */
	public function testTrigger()
	{
		$observer1 = new FirstObserver($this->object);
		$observer2 = new SecondObserver($this->object);

		// Trigger a non-existent event
		$result = $this->object->trigger('notthere');
		$this->assertEquals(array(), $result);

		// Trigger a non-existent event with data
		$result = $this->object->trigger('notthere', array('whatever', 'nevermind'));
		$this->assertEquals(array(), $result);

		// Trigger an event with one observer responding to it
		$result = $this->object->trigger('onlySecond');
		$this->assertEquals(array('only second'), $result);

		// Trigger an event with two observers responding to it
		$result = $this->object->trigger('identifyYourself');
		$this->assertEquals(array('one', 'two'), $result);

		// Trigger an event with two observers responding to it, with parameters
		$result = $this->object->trigger('returnConditional', array('one'));
		$this->assertEquals(array(true, false), $result);

		// Trigger an event with two observers responding to it, with parameters
		$result = $this->object->trigger('returnConditional', array('two'));
		$this->assertEquals(array(false, true), $result);
	}

	/**
	 * @covers Awf\Event\Dispatcher::chainHandle
	 */
	public function testChainHandle()
	{
		$observer1 = new FirstObserver($this->object);
		$observer2 = new SecondObserver($this->object);

		// Trigger a non-existent event
		$result = $this->object->chainHandle('notthere');
		$this->assertNull($result);

		// Trigger a non-existent event with data
		$result = $this->object->chainHandle('notthere', array('whatever', 'nevermind'));
		$this->assertNull($result);

		// Trigger an event with one observer responding to it
		$result = $this->object->chainHandle('onlySecond');
		$this->assertEquals('only second', $result);

		// Trigger an event with two observers responding to it
		$result = $this->object->chainHandle('identifyYourself');
		$this->assertEquals('one', $result);

		// Trigger an event with two observers responding to it, with parameters
		$result = $this->object->chainHandle('returnConditional', array('one'));
		$this->assertEquals(true, $result);

		// Trigger an event with two observers responding to it, with parameters
		$result = $this->object->chainHandle('returnConditional', array('two'));
		$this->assertEquals(false, $result);

		// Trigger a real chain handler
		$result = $this->object->chainHandle('chain', array('one'));
		$this->assertEquals('one', $result);

		// Trigger a real chain handler
		$result = $this->object->chainHandle('chain', array('two'));
		$this->assertEquals('two', $result);
	}

	protected function setUp()
	{
		$this->object = new Dispatcher(static::$container);
		ReflectionHelper::setValue($this->object, 'instances', array(
			static::$container->application->getName() => $this->object
		));
	}

	protected function tearDown()
	{
		ReflectionHelper::setValue($this->object, 'instances', array());
	}


}
 