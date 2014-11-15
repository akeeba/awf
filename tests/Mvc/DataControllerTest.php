<?php

namespace Awf\Tests\DataController;

use Awf\Input\Input;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Stubs\Mvc\DataControllerStub;

require_once 'DataControllerDataprovider.php';

class DataControllertest extends DatabaseMysqliCase
{
    /**
     * @group           DataController
     * @group           DataControllerCancel
     * @covers          DataController::cancel
     * @dataProvider    DataControllerDataprovider::getTestCancel
     */
    public function testCancel($test, $check)
    {
        $container = new Container(array(
            'db' => self::$driver,
            'input' => new Input(array(
                'returnurl' => $test['mock']['returnurl'] ? base64_encode($test['mock']['returnurl']) : '',
            )),
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('unlock', 'getId'), array($container));
        $model->expects($this->once())->method('getId')->willReturn($test['mock']['getId']);
        $model->expects($this->once())->method('unlock')->willReturn(null);

        $controller = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataControllerStub', array('getModel', 'getIDsFromRequest', 'setRedirect'), array($container));
        $controller->expects($this->any())->method('getModel')->willReturn($model);
        $controller->expects($check['getFromReq'] ? $this->once() : $this->never())->method('getIDsFromRequest')->willReturn($test['mock']['ids']);
        $controller->expects($this->once())->method('setRedirect')->willReturn(null)->with($this->equalTo($check['url']));

        // In this test we can't check if data has been removed from the Session, since I'll have to mock the entire framework
        // my pc, myself and probably the entire universe
        $controller->cancel();
    }

    /**
     * @group           DataController
     * @group           DataControllerOrderdown
     * @covers          DataController::orderdown
     * @dataProvider    DataControllerDataprovider::getTestOrderdown
     */
    public function testOrderdown($test, $check)
    {
        $container = new Container(array(
            'db' => self::$driver,
            'input' => new Input(array(
                'returnurl' => $test['mock']['returnurl'] ? base64_encode($test['mock']['returnurl']) : '',
            )),
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('move', 'getId'), array($container));
        $model->expects($this->once())->method('getId')->willReturn($test['mock']['getId']);
        $model->expects($this->any())->method('move')->willReturnCallback(
            function() use (&$test)
            {
                // Should I return a value or throw an exception?
                $ret = array_shift($test['mock']['move']);

                if($ret === 'throw')
                {
                    throw new \Exception('Exception in move');
                }

                return $ret;
            }
        );

        $controller = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataControllerStub', array('csrfProtection', 'getModel', 'getIDsFromRequest', 'setRedirect'), array($container));
        $controller->expects($this->any())->method('csrfProtection')->willReturn(null);
        $controller->expects($this->any())->method('getModel')->willReturn($model);
        $controller->expects($check['getFromReq'] ? $this->once() : $this->never())->method('getIDsFromRequest')->willReturn($test['mock']['ids']);
        $controller->expects($this->once())->method('setRedirect')->willReturn(null);

        $controller->expects($this->once())->method('setRedirect')->with($this->equalTo($check['url']), $this->equalTo($check['msg']), $this->equalTo($check['type']));

        $controller->orderdown();
    }

    /**
     * @group           DataController
     * @group           DataControllerOrderup
     * @covers          DataController::orderup
     * @dataProvider    DataControllerDataprovider::getTestOrderup
     */
    public function testOrderup($test, $check)
    {
        $container = new Container(array(
            'db' => self::$driver,
            'input' => new Input(array(
                'returnurl' => $test['mock']['returnurl'] ? base64_encode($test['mock']['returnurl']) : '',
            )),
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('move', 'getId'), array($container));
        $model->expects($this->once())->method('getId')->willReturn($test['mock']['getId']);
        $model->expects($this->any())->method('move')->willReturnCallback(
            function() use (&$test)
            {
                // Should I return a value or throw an exception?
                $ret = array_shift($test['mock']['move']);

                if($ret === 'throw')
                {
                    throw new \Exception('Exception in move');
                }

                return $ret;
            }
        );

        $controller = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataControllerStub', array('csrfProtection', 'getModel', 'getIDsFromRequest', 'setRedirect'), array($container));
        $controller->expects($this->any())->method('csrfProtection')->willReturn(null);
        $controller->expects($this->any())->method('getModel')->willReturn($model);
        $controller->expects($check['getFromReq'] ? $this->once() : $this->never())->method('getIDsFromRequest')->willReturn($test['mock']['ids']);
        $controller->expects($this->once())->method('setRedirect')->willReturn(null);

        $controller->expects($this->once())->method('setRedirect')->with($this->equalTo($check['url']), $this->equalTo($check['msg']), $this->equalTo($check['type']));

        $controller->orderup();
    }

    /**
     * @group           DataController
     * @group           DataControllerRemove
     * @covers          DataController::remove
     * @dataProvider    DataControllerDataprovider::getTestRemove
     */
    public function testRemove($test, $check)
    {
        $container = new Container(array(
            'db' => self::$driver,
            'input' => new Input(array(
                'returnurl' => $test['mock']['returnurl'] ? base64_encode($test['mock']['returnurl']) : '',
            )),
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('find', 'delete'), array($container));
        $model->expects($this->any())->method('find')->willReturnCallback(
            function() use (&$test)
            {
                // Should I return a value or throw an exception?
                $ret = array_shift($test['mock']['find']);

                if($ret === 'throw')
                {
                    throw new \Exception('Exception in find');
                }

                return $ret;
            }
        );

        $model->expects($this->any())->method('delete')->willReturnCallback(
            function() use (&$test)
            {
                // Should I return a value or throw an exception?
                $ret = array_shift($test['mock']['delete']);

                if($ret === 'throw')
                {
                    throw new \Exception('Exception in delete');
                }

                return $ret;
            }
        );

        $controller = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataControllerStub', array('csrfProtection', 'getModel', 'getIDsFromRequest', 'setRedirect'), array($container));
        $controller->expects($this->any())->method('csrfProtection')->willReturn(null);
        $controller->expects($this->any())->method('getModel')->willReturn($model);
        $controller->expects($this->any())->method('getIDsFromRequest')->willReturn($test['mock']['ids']);
        $controller->expects($this->once())->method('setRedirect')->willReturn(null);

        $controller->expects($this->once())->method('setRedirect')->with($this->equalTo($check['url']), $this->equalTo($check['msg']), $this->equalTo($check['type']));

        $controller->remove();
    }

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