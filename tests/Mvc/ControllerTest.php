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
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\ControllerStub;

require_once 'ControllerDataprovider.php';

class ControllerTest extends DatabaseMysqlCase
{
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
}
