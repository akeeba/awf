<?php

namespace Awf\Tests\DataController;

use Awf\Input\Input;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Stubs\Mvc\DataControllerStub;
use Awf\Tests\Stubs\Mvc\DataModelStub;

require_once 'DataControllerDataprovider.php';

class DataControllertest extends DatabaseMysqliCase
{
    /**
     * @group           DataController
     * @group           DataControllerGetModel
     * @covers          DataController::getModel
     * @dataProvider    DataControllerDataprovider::getTestGetModel
     */
    public function testGetModel($test, $check)
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $controller = new DataControllerStub($container);

        if($check['exception'])
        {
            $this->setExpectedException('Exception');
        }

        $model = $controller->getModel($test['model']);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $model, 'DataController::getModel should return a DataModel');
    }

    /**
     * @group           DataController
     * @group           DataControllerGetIDsFromRequest
     * @covers          DataController::getIDsFromRequest
     * @dataProvider    DataControllerDataprovider::getTestGetIDsFromRequest
     */
    public function testGetIDsFromRequest($test, $check)
    {
        $msg = 'DataController::getIDsFromRequest %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'input' => new Input(array(
                'cid' => $test['mock']['cid'],
                'id'  => $test['mock']['id'],
                'dbtest_nestedset_id' => $test['mock']['kid']
            )),
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('find'), array($container));
        $model->expects($check['load'] ? $this->once() : $this->never())->method('find')->with($check['loadid']);

        $controller = new DataControllerStub($container);

        $result = $controller->getIDsFromRequest($model, $test['load']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong value'));
    }
}