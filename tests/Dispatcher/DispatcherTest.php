<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Dispatcher;

use Awf\Application\Application;
use Awf\Dispatcher\Dispatcher;
use Awf\Input\Input;
use Awf\Tests\Helpers\AwfTestCase;
use Fakeapp\Controller\Jasager;
use Awf\Tests\Helpers\ReflectionHelper;

class DispatcherTest extends AwfTestCase
{
	protected function tearDown()
	{
		parent::tearDown();

		Jasager::resetResults();
	}

	public function testGetDispatcherWithoutContainer()
	{
		$dispatcher = new Dispatcher();

		$expected = Application::getInstance()->getContainer();
		$container = ReflectionHelper::getValue($dispatcher, 'container');

		$this->assertEquals($expected, $container);
	}

	public function testGetDispatcherWithContainer()
	{
		$newContainer = clone static::$container;
		$newContainer['marker'] = 'newContainer';

		$dispatcher = new Dispatcher($newContainer);

		$container = ReflectionHelper::getValue($dispatcher, 'container');

		$this->assertEquals($newContainer, $container);
		$this->assertEquals($newContainer['marker'], $container['marker']);
	}

	public function testNormalRequest()
	{
		$container = clone static::$container;
		$container->input->setData(array(
			'view'		=> 'jasager',
			'task'		=> 'yessir',
			'layout'	=> 'yesman',
		));

		$dispatcher = new Dispatcher($container);

		$this->assertEquals('jasager', ReflectionHelper::getValue($dispatcher, 'view'));
		$this->assertEquals('yesman', ReflectionHelper::getValue($dispatcher, 'layout'));

		$dispatcher->dispatch();
	}

	public function testDefaultView()
	{
		$container = clone static::$container;
		$container->input->setData(array('task'		=> 'yessir',));

		$dispatcher = $container->dispatcher;

		\Fakeapp\Dispatcher::$onBeforeDispatchResult = true;
		\Fakeapp\Dispatcher::$onAfterDispatchResult  = true;

		$dispatcher->dispatch();

		$this->assertEquals('jasager', ReflectionHelper::getValue($dispatcher, 'view'));
	}

	public function testOnBeforeDispatchExceptionResultsIn403()
	{
		$container = clone static::$container;
		$container->input->setData(array('task'		=> 'yessir',));

		$dispatcher = $container->dispatcher;

		\Fakeapp\Dispatcher::$onBeforeDispatchResult = new \Exception('Foobar', 123);
		\Fakeapp\Dispatcher::$onAfterDispatchResult = true;

		$this->setExpectedException('\\Exception', 'AWF_APPLICATION_ERROR_ACCESS_FORBIDDEN', 403);

		$dispatcher->dispatch();
	}

	public function testOnBeforeDispatchFalseResultsIn403()
	{
		$container = clone static::$container;
		$container->input->setData(array('task'		=> 'yessir',));

		$dispatcher = $container->dispatcher;

		\Fakeapp\Dispatcher::$onBeforeDispatchResult = false;
		\Fakeapp\Dispatcher::$onAfterDispatchResult = true;

		$this->setExpectedException('\\Exception', 'AWF_APPLICATION_ERROR_ACCESS_FORBIDDEN', 403);

		$dispatcher->dispatch();
	}

	public function testOnBeforeDispatchExceptionResultsInJsonString()
	{
		$container = clone static::$container;
		$container->input->setData(array(
			'task'		=> 'yessir',
			'format'	=> 'json'
		));

		$dispatcher = $container->dispatcher;

		\Fakeapp\Dispatcher::$onBeforeDispatchResult = new \Exception('Foobar', 123);
		\Fakeapp\Dispatcher::$onAfterDispatchResult = true;

		$container->application->myCloseCounter = 0;

		// We need an exception to be thrown...
		$this->setExpectedException('\\Exception', 'AWF_APPLICATION_ERROR_ACCESS_FORBIDDEN', 403);
		// ...and a JSON string to be output
		$this->expectOutputString('{"code":"403","error":"Foobar"}');

		$dispatcher->dispatch();

		// We also need to be sure that the dispatcher closed the application after dumping the JSON string
		$this->assertEquals(1, $container->application->myCloseCounter);
	}

	public function testControllerFalseResultsIn403()
	{
		$container = clone static::$container;
		$container->input->setData(array('task'		=> 'yessir',));

		Jasager::setUpResult(null, false, null);

		$dispatcher = $container->dispatcher;

		\Fakeapp\Dispatcher::$onBeforeDispatchResult = true;
		\Fakeapp\Dispatcher::$onAfterDispatchResult = true;

		$this->setExpectedException('\\Exception', 'AWF_APPLICATION_ERROR_ACCESS_FORBIDDEN', 403);

		$dispatcher->dispatch();
	}

	public function testControllerExceptionBubblesUp()
	{
		$container = clone static::$container;
		$container->input->setData(array('task'		=> 'yessir',));

		Jasager::setUpResult(null, null, new \Exception('Foobar', 123));

		$dispatcher = $container->dispatcher;

		\Fakeapp\Dispatcher::$onBeforeDispatchResult = true;
		\Fakeapp\Dispatcher::$onAfterDispatchResult = true;

		$this->setExpectedException('\\Exception', 'Foobar', 123);

		$dispatcher->dispatch();
	}

	public function testOnAfterDispatchFalseResultsIn403()
	{
		$container = clone static::$container;
		$container->input->setData(array('task'		=> 'yessir',));

		Jasager::setUpResult(null, null, null);

		$dispatcher = $container->dispatcher;

		\Fakeapp\Dispatcher::$onAfterDispatchResult = false;
		\Fakeapp\Dispatcher::$onBeforeDispatchResult = true;

		$this->setExpectedException('\\Exception', 'AWF_APPLICATION_ERROR_ACCESS_FORBIDDEN', 403);

		$dispatcher->dispatch();
	}


	public function testOnAfterDispatchExceptionBubblesUp()
	{
		$container = clone static::$container;
		$container->input->setData(array('task'		=> 'yessir',));

		Jasager::setUpResult(null, null, null);

		$dispatcher = $container->dispatcher;

		\Fakeapp\Dispatcher::$onAfterDispatchResult = new \Exception('Foobar', 123);
		\Fakeapp\Dispatcher::$onBeforeDispatchResult = true;

		$this->setExpectedException('\\Exception', 'Foobar', 123);

		$dispatcher->dispatch();
	}
}
