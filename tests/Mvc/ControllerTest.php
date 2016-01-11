<?php
/**
 * @package        awf
 * @copyright      2014-2016 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Controller;

use Awf\Input\Input;
use Awf\Database\Driver;
use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\ClosureHelper;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\ControllerStub;
use Awf\Tests\Stubs\Mvc\ModelStub;
use Awf\Tests\Stubs\Mvc\ViewStub;

require_once 'ControllerDataprovider.php';

/**
 * @covers      Awf\Mvc\Controller::<protected>
 * @covers      Awf\Mvc\Controller::<private>
 * @package     Awf\Tests\Controller
 */
class ControllerTest extends AwfTestCase
{
    /**
     * @group           Controller
     * @group           ControllerGetInstance
     * @covers          Awf\Mvc\Controller::getInstance
     * @dataProvider    ControllerDataprovider::getTestgetInstance
     */
    public function testGetInstance($test, $check)
    {
        $msg       = 'Controller::getInstance %s - Case: '.$check['case'];
        $container = null;

        if($test['container'])
        {
            $container = new Container(array(
                'input' => new Input(array(
                    'view' => $test['view']
                ))
            ));
        }

        $result = ControllerStub::getInstance($test['appName'], $test['controller'], $container);

        $this->assertInstanceOf($check['result'], $result, sprintf($msg, 'Loaded the wrong controller'));
    }

    /**
     * @group           Controller
     * @group           ControllerConstruct
     * @covers          Awf\Mvc\Controller::__construct
     * @dataProvider    ControllerDataprovider::getTest__construct
     */
    public function test__construct($test, $check)
    {
        $containerSetup = array(
            'input' => new Input(
                array(
                    'layout' => $test['layout']
                )
            )
        );

        if($test['mvc'])
        {
            $containerSetup['mvc_config'] = $test['mvc'];
        }

        $msg        = 'Controller::__construct %s - Case: '.$check['case'];
        $container  = null;
        $counterApp = 0;

        if($test['container'])
        {
            $container = new Container($containerSetup);
        }
        else
        {
            $fakeapp    = new ClosureHelper(array(
                'getContainer' => function () use(&$counterApp, &$containerSetup){
                    $counterApp++;

                    return new Container($containerSetup);
                }
            ));

            // Let's save current app istances, I'll have to restore them later
            $oldinstances = ReflectionHelper::getValue('\\Awf\\Application\\Application', 'instances');
            ReflectionHelper::setValue('\\Awf\\Application\\Application', 'instances', array('tests' => $fakeapp));
        }

        // First of all let's get the mock of the object WITHOUT calling the constructor
        $controller = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\ControllerStub', array('registerDefaultTask', 'setModelName', 'setViewName'), array(), '', false);
        $controller->expects($this->once())->method('registerDefaultTask')->with($this->equalTo($check['defaultTask']));
        $controller->expects($check['viewName'] ? $this->once() : $this->never())->method('setViewName')->with($this->equalTo($check['viewName']));
        $controller->expects($check['modelName'] ? $this->once() : $this->never())->method('setModelName')->with($this->equalTo($check['modelName']));

        // Now I can explicitly call the constructor
        $controller->__construct($container);

        if(!$test['container'])
        {
            ReflectionHelper::setValue('\\Awf\\Application\\Application', 'instances', $oldinstances);
        }

        $layout  = ReflectionHelper::getValue($controller, 'layout');

        $this->assertEquals($check['layout'], $layout, sprintf($msg, 'Failed to set the layout'));
        $this->assertEquals($check['defView'], $controller->default_view, sprintf($msg, 'Failed to set the default view'));
        $this->assertEquals($check['counterApp'], $counterApp, sprintf($msg, 'Failed to correctly get the container from the Application'));
    }

    /**
     * @group           Controller
     * @group           ControllerConstruct
     * @covers          Awf\Mvc\Controller::__construct
     */
    public function test__constructTaskMap()
    {
        $container  = new Container();
        $controller = new ControllerStub($container);

        $tasks = ReflectionHelper::getValue($controller, 'taskMap');

        // Remove reference to __call magic method
        unset($tasks['__call']);

        $check = array(
            'onbeforedummy' => 'onBeforeDummy',
            'onafterdummy'  => 'onAfterDummy',
            'display'       => 'display',
            'main'          => 'main',
            '__default'     => 'main'
        );

        $this->assertEquals($check, $tasks, 'Controller::__construct failed to create the taskMap array');
    }

    /**
     * @group           Controller
     * @group           ControllerExecute
     * @covers          Awf\Mvc\Controller::execute
     * @dataProvider    ControllerDataprovider::getTestExecute
     */
    public function testExecute($test, $check)
    {
        $msg        = 'Controller::execute %s - Case: '.$check['case'];
        $before     = 0;
        $task       = 0;
        $after      = 0;
        $container  = new Container();
        $controller = new ControllerStub($container, array(
            'onBeforeDummy' => function() use (&$before, $test){
                $before++;
                return $test['mock']['before'];
            },
            'onAfterDummy' => function() use (&$after, $test){
                $after++;
                return $test['mock']['after'];
            },
            $test['task'] => function() use(&$task, $test){
                $task++;
                return $test['mock']['task'];
            }
        ));

        ReflectionHelper::setValue($controller, 'taskMap', $test['mock']['taskMap']);

        $result = $controller->execute($test['task']);

        $doTask = ReflectionHelper::getValue($controller, 'doTask');

        $this->assertEquals($check['doTask'], $doTask, sprintf($msg, 'Failed to set the $doTask property'));
        $this->assertEquals($check['before'], $before, sprintf($msg, 'Invoked the onBefore<task> method the wrong amount of times'));
        $this->assertEquals($check['task'], $task, sprintf($msg, 'Invoked the <task> method the wrong amount of times'));
        $this->assertEquals($check['after'], $after, sprintf($msg, 'Invoked the onAfter<task> method the wrong amount of times'));
        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong value'));
    }

    /**
     * @group           Controller
     * @group           ControllerExecute
     * @covers          Awf\Mvc\Controller::execute
     */
    public function testExecuteException()
    {
        $this->setExpectedException('Exception');

        $container  = new Container();
        $controller = new ControllerStub($container);

        ReflectionHelper::setValue($controller, 'taskMap', array());

        $controller->execute('foobar');
    }

    /**
     * @group           Controller
     * @group           ControllerDisplay
     * @covers          Awf\Mvc\Controller::display
     * @dataProvider    ControllerDataprovider::getTestDisplay
     */
    public function testDisplay($test, $check)
    {
        $msg = 'Controller::display %s - Case: '.$check['case'];

        $layoutCounter = 0;
        $layoutCheck   = null;
        $modelCounter  = 0;
        $container     = new Container();

        $view = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\ViewStub', array('setDefaultModel', 'setLayout', 'display'), array($container));
        $view->expects($this->any())->method('display')->willReturn(null);
        $view->expects($this->any())->method('setDefaultModel')->willReturnCallback(
            function($model) use (&$modelCounter){
                $modelCounter++;
            }
        );
        $view->expects($this->any())->method('setLayout')->willReturnCallback(
            function($layout) use (&$layoutCounter, &$layoutCheck){
                $layoutCounter++;
                $layoutCheck = $layout;
            }
        );

        $controller = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\ControllerStub', array('getView', 'getModel'), array($container));
        $controller->expects($this->any())->method('getModel')->willReturn($test['mock']['getModel']);
        $controller->expects($this->any())->method('getView')->willReturn($view);

        ReflectionHelper::setValue($controller, 'task'  , $test['mock']['task']);
        ReflectionHelper::setValue($controller, 'doTask', $test['mock']['doTask']);
        ReflectionHelper::setValue($controller, 'layout', $test['mock']['layout']);

        $controller->display();

        $this->assertEquals($check['modelCounter'], $modelCounter, sprintf($msg, 'Failed to set view default model the correct amount of times'));
        $this->assertEquals($check['layoutCounter'], $layoutCounter, sprintf($msg, 'Failed to set view layout the correct amount of times'));
        $this->assertEquals($check['layout'], $layoutCheck, sprintf($msg, 'Set the wrong view layout'));
        $this->assertEquals($check['task'], $view->task, sprintf($msg, 'Set the wrong view task'));
        $this->assertEquals($check['doTask'], $view->doTask, sprintf($msg, 'Set the wrong view doTask'));
    }

    /**
     * @group           Controller
     * @group           ControllerGetModel
     * @covers          Awf\Mvc\Controller::getModel
     * @dataProvider    ControllerDataprovider::getTestGetModel
     */
    public function testGetModel($test, $check)
    {
        $msg        = 'Controller::getModel %s - Case: '.$check['case'];
        $container  = new Container();
        $controller = new ControllerStub($container);

        ReflectionHelper::setValue($controller, 'modelName', $test['mock']['modelName']);
        ReflectionHelper::setValue($controller, 'view', $test['mock']['view']);
        ReflectionHelper::setValue($controller, 'modelInstances', $test['mock']['instances']);

        $result = $controller->getModel($test['name'], $test['config']);

        $config = $result->passedContainer['mvc_config'];

        $this->assertInstanceOf($check['result'], $result, sprintf($msg, 'Created the wrong model'));
        $this->assertEquals($check['config'], $config, sprintf($msg, 'Passed configuration was not considered'));
    }

    /**
     * @group           Controller
     * @group           ControllerGetView
     * @covers          Awf\Mvc\Controller::getView
     * @dataProvider    ControllerDataprovider::getTestGetView
     */
    public function testGetView($test, $check)
    {
        $msg        = 'Controller::getView %s - Case: '.$check['case'];
        $container  = new Container(array(
            'input' => new Input(array(
                'format' => $test['mock']['format']
            ))
        ));
        $controller = new ControllerStub($container);

        ReflectionHelper::setValue($controller, 'viewName', $test['mock']['viewName']);
        ReflectionHelper::setValue($controller, 'view', $test['mock']['view']);
        ReflectionHelper::setValue($controller, 'viewInstances', $test['mock']['instances']);

        $result = $controller->getView($test['name'], $test['config']);

        $config = $result->passedContainer['mvc_config'];

        $this->assertInstanceOf($check['result'], $result, sprintf($msg, 'Created the wrong view'));
        $this->assertEquals($check['config'], $config, sprintf($msg, 'Passed configuration was not considered'));
    }

    /**
     * @group           Controller
     * @group           ControllerSetViewName
     * @covers          Awf\Mvc\Controller::setViewName
     */
    public function testSetViewName()
    {
        $controller = new ControllerStub();
        $controller->setViewName('foobar');

        $value = ReflectionHelper::getValue($controller, 'viewName');

        $this->assertEquals('foobar', $value, 'Controller::setViewName failed to set the view name');
    }

    /**
     * @group           Controller
     * @group           ControllerSetModelName
     * @covers          Awf\Mvc\Controller::setModelName
     */
    public function testSetModelName()
    {
        $controller = new ControllerStub();
        $controller->setModelName('foobar');

        $value = ReflectionHelper::getValue($controller, 'modelName');

        $this->assertEquals('foobar', $value, 'Controller::setModelName failed to set the model name');
    }

    /**
     * @group           Controller
     * @group           ControllerSetModel
     * @covers          Awf\Mvc\Controller::setModel
     */
    public function testSetModel()
    {
        $model      = new ModelStub();
        $controller = new ControllerStub();
        $controller->setModel('foobar', $model);

        $models = ReflectionHelper::getValue($controller, 'modelInstances');

        $this->assertArrayHasKey('foobar', $models, 'Controller::setModel Failed to save the model');
        $this->assertSame($model, $models['foobar'], 'Controller::setModel Failed to store the same copy of the passed model');
    }

    /**
     * @group           Controller
     * @group           ControllerSetView
     * @covers          Awf\Mvc\Controller::setView
     */
    public function testSetView()
    {
        $view       = new ViewStub();
        $controller = new ControllerStub();
        $controller->setView('foobar', $view);

        $views = ReflectionHelper::getValue($controller, 'viewInstances');

        $this->assertArrayHasKey('foobar', $views, 'Controller::setView Failed to save the view');
        $this->assertSame($view, $views['foobar'], 'Controller::setView Failed to store the same copy of the passed view');
    }

    /**
     * @group           Controller
     * @group           ControllerGetTask
     * @covers          Awf\Mvc\Controller::getTask
     */
    public function testGetTask()
    {
        $controller = new ControllerStub();
        ReflectionHelper::setValue($controller, 'task', 'foobar');

        $task = $controller->getTask();

        $this->assertEquals('foobar', $task, 'Controller::getTask failed to return the current task');
    }

    /**
     * @group           Controller
     * @group           ControllerGetTasks
     * @covers          Awf\Mvc\Controller::getTasks
     */
    public function testGetTasks()
    {
        $controller = new ControllerStub();
        ReflectionHelper::setValue($controller, 'methods', array('foobar'));

        $tasks = $controller->getTasks();

        $this->assertEquals(array('foobar'), $tasks, 'Controller::getTasks failed to return the internal tasks');
    }

    /**
     * @group           Controller
     * @group           ControllerRedirect
     * @covers          Awf\Mvc\Controller::redirect
     * @dataProvider    ControllerDataprovider::getTestRedirect
     */
    public function testRedirect($test, $check)
    {
        $msg        = 'Controller::redirect %s - Case: '.$check['case'];
        $controller = new ControllerStub();
        $counter    = 0;
        $fakeapp    = new ClosureHelper(array(
            'redirect' => function () use(&$counter){
                $counter++;
            }
        ));

        ReflectionHelper::setValue($controller, 'redirect', $test['mock']['redirect']);

        // Let's save current app istances, I'll have to restore them later
        $oldinstances = ReflectionHelper::getValue('\\Awf\\Application\\Application', 'instances');
        ReflectionHelper::setValue('\\Awf\\Application\\Application', 'instances', array('tests' => $fakeapp));

        $result = $controller->redirect();

        ReflectionHelper::setValue('\\Awf\\Application\\Application', 'instances', $oldinstances);

        // If the redirection has been invoked, I have to nullify the result. In the real world I would be immediatly
        // redirected to another page.
        if($counter)
        {
            $result = null;
        }

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
        $this->assertEquals($check['redirect'], $counter, sprintf($msg, 'Failed to perform the redirection'));
    }

    /**
     * @group           Controller
     * @group           ControllerRegisterDefaultTask
     * @covers          Awf\Mvc\Controller::registerDefaultTask
     */
    public function testRegisterDefaultTask()
    {
        // In this test I just want to check the result, since I'll test the registerTask in another test
        $container  = new Container();
        $controller = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\ControllerStub', array('registerTask'), array($container));
        $result     = $controller->registerDefaultTask('dummy');

        $this->assertInstanceOf('\\Awf\\Mvc\\Controller', $result, 'Controller::registerDefaultTask should return an instance of itself');
    }

    /**
     * @group           Controller
     * @group           ControllerRegisterTask
     * @covers          Awf\Mvc\Controller::registerTask
     * @dataProvider    ControllerDataprovider::getTestRegisterTask
     */
    public function testRegisterTask($test, $check)
    {
        $msg        = 'Controller::registerDefaultTask %s - Case: '.$check['case'];
        $container  = new Container();
        $controller = new ControllerStub($container);

        ReflectionHelper::setValue($controller, 'methods', $test['mock']['methods']);

        $result  = $controller->registerTask($test['task'], $test['method']);

        $taskMap = ReflectionHelper::getValue($controller, 'taskMap');

        $this->assertInstanceOf('\\Awf\\Mvc\\Controller', $result, sprintf($msg, 'Should return an instance of itself'));

        if($check['register'])
        {
            $this->assertArrayHasKey(strtolower($test['task']), $taskMap, sprintf($msg, 'Should add the method to the internal mapping'));
        }
        else
        {
            $this->assertArrayNotHasKey(strtolower($test['task']), $taskMap, sprintf($msg, 'Should not add the method to the internal mapping'));
        }
    }

    /**
     * @group           Controller
     * @group           ControllerUnregisterTask
     * @covers          Awf\Mvc\Controller::unregisterTask
     */
    public function testUnregisterTask()
    {
        $msg        = 'Controller::unregisterDefaultTask %s';
        $container  = new Container();
        $controller = new ControllerStub($container);

        ReflectionHelper::setValue($controller, 'taskMap', array('foo' => 'bar'));

        $result  = $controller->unregisterTask('foo');

        $taskMap = ReflectionHelper::getValue($controller, 'taskMap');

        $this->assertInstanceOf('\\Awf\\Mvc\\Controller', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertArrayNotHasKey('foo', $taskMap, sprintf($msg, 'Should remove the task form the mapping'));
    }

    /**
     * @group           Controller
     * @group           ControllerSetMessage
     * @covers          Awf\Mvc\Controller::setMessage
     * @dataProvider    ControllerDataprovider::getTestSetMessage
     */
    public function testSetMessage($test, $check)
    {
        $msg        = 'Controller::setMessage %s - Case: '.$check['case'];
        $controller = new ControllerStub();

        ReflectionHelper::setValue($controller, 'message', $test['mock']['previous']);

        if(is_null($test['type']))
        {
            $result  = $controller->setMessage($test['message']);
        }
        else
        {
            $result  = $controller->setMessage($test['message'], $test['type']);
        }

        $message = ReflectionHelper::getValue($controller, 'message');
        $type    = ReflectionHelper::getValue($controller, 'messageType');

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Should return the previous message'));
        $this->assertEquals($check['message'], $message, sprintf($msg, 'Did not set the message correctly'));
        $this->assertEquals($check['type'], $type, sprintf($msg, 'Did not set the message type correctly'));
    }

    /**
     * @group           Controller
     * @group           ControllerSetRedirect
     * @covers          Awf\Mvc\Controller::setRedirect
     * @dataProvider    ControllerDataprovider::getTestSetRedirect
     */
    public function testSetRedirect($test, $check)
    {
        $msg        = 'Controller::setRedirect %s - Case: '.$check['case'];
        $controller = new ControllerStub();

        ReflectionHelper::setValue($controller, 'messageType', $test['mock']['type']);

        $result  = $controller->setRedirect($test['url'], $test['msg'], $test['type']);

        $redirect = ReflectionHelper::getValue($controller, 'redirect');
        $message  = ReflectionHelper::getValue($controller, 'message');
        $type     = ReflectionHelper::getValue($controller, 'messageType');

        $this->assertInstanceOf('\\Awf\\Mvc\\Controller', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals($check['redirect'], $redirect, sprintf($msg, 'Did not set the redirect url correctly'));
        $this->assertEquals($check['message'], $message, sprintf($msg, 'Did not set the message correctly'));
        $this->assertEquals($check['type'], $type, sprintf($msg, 'Did not set the message type correctly'));
    }
}
