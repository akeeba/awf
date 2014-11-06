<?php
/**
 * @package        awf
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 *
 * This class is adapted from Joomla! Framework
 */

namespace Awf\Tests\Controller;

use Awf\Tests\Database\DatabaseMysqlCase;
use Awf\Database\Driver;
use Awf\Tests\Helpers\ClosureHelper;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\ControllerStub;

require_once 'ControllerDataprovider.php';

class ControllerTest extends DatabaseMysqlCase
{

    /**
     * @group           Controller
     * @group           ControllerSetViewName
     * @covers          Controller::setViewName
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
     * @group           ControllerGetTask
     * @covers          Controller::getTask
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
     * @covers          Controller::getTasks
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
     * @covers          Controller::redirect
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
     * @covers          Controller::registerDefaultTask
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
     * @covers          Controller::registerTask
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
     * @covers          Controller::unregisterTask
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
     * @covers          Controller::setMessage
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
     * @covers          Controller::setRedirect
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
