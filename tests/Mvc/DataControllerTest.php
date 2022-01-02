<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\DataController;

use Awf\Input\Input;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Stubs\Mvc\DataControllerStub;
use Awf\Tests\Stubs\Mvc\DataModelStub;

require_once 'DataControllerDataprovider.php';

/**
 * @covers      Awf\Mvc\DataController::<protected>
 * @covers      Awf\Mvc\DataController::<private>
 * @package     Awf\Tests\DataController
 */
class DataControllertest extends DatabaseMysqliCase
{
    /**
     * @group           DataController
     * @group           DataControllerConstruct
     * @covers          Awf\Mvc\DataController::__construct
     * @dataProvider    DataControllerDataprovider::getTest__construct
     */
    public function test__construct($test, $check)
    {
        $msg = 'DataController::__construct %s - Case: '.$check['case'];

        $setup = array();

        if($test['model'])
        {
            $setup['mvc_config']['modelName'] = $test['model'];
        }

        if($test['view'])
        {
            $setup['mvc_config']['viewName'] = $test['view'];
        }

        $container  = new Container($setup);
        $controller = new DataControllerStub($container);

        $modelName = ReflectionHelper::getValue($controller, 'modelName');
        $viewName  = ReflectionHelper::getValue($controller, 'viewName');

        $this->assertEquals($check['model'], $modelName, sprintf($msg, 'Failed to set the correct modelName'));
        $this->assertEquals($check['view'], $viewName, sprintf($msg, 'Failed to set the correct viewName'));
    }

    /**
     * @group           DataController
     * @group           DataControllerBrowse
     * @covers          Awf\Mvc\DataController::browse
     * @dataProvider    DataControllerDataprovider::getTestBrowse
     */
    public function testBrowse($test, $check)
    {
        $input = $this->getMock('\\Awf\\Input\\Input', array('set'), array($test['mock']['input']));
        $input->expects($check['set'] ? $this->once() : $this->never())->method('set')->with($this->equalTo('savestate'), $this->equalTo(true));

        $container = new Container(array(
            'input' => $input
        ));

        $controller = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataControllerStub', array('display'), array($container));
        $controller->expects($this->any())->method('display')->willReturn(null);

        $controller->browse();
    }

    /**
     * @group           DataController
     * @group           DataControllerRead
     * @covers          Awf\Mvc\DataController::read
     * @dataProvider    DataControllerDataprovider::getTestRead
     */
    public function testRead($test, $check)
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('getId'), array($container));
        $model->expects($this->exactly($check['getIdCount']))->method('getId')->willReturnOnConsecutiveCalls($test['mock']['getId'][0], $test['mock']['getId'][1]);

        $controller = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataControllerStub', array('getModel', 'getIDsFromRequest', 'display'), array($container));
        $controller->expects($this->any())->method('getModel')->willReturn($model);
        $controller->expects($check['getIdFromReq'] ? $this->once() : $this->never())->method('getIDsFromRequest')->willReturn($test['mock']['ids']);
        $controller->expects($check['display'] ? $this->once() : $this->never())->method('display')->willReturn(null);

        ReflectionHelper::setValue($controller, 'layout', $test['mock']['layout']);

        if($check['exception'])
        {
            $this->setExpectedException('Exception', 'FAKEAPP_ERR_NESTEDSET_NOTFOUND');
        }

        $controller->read();

        $layout = ReflectionHelper::getValue($controller, 'layout');

        $this->assertEquals($check['layout'], $layout, 'DataController::read failed to set the layout');
    }

    /**
     * @group           DataController
     * @group           DataControllerAdd
     * @covers          Awf\Mvc\DataController::add
     * @dataProvider    DataControllerDataprovider::getTestAdd
     */
    public function testAdd($test, $check)
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $container->segment->setFlash('fakeapp_dummycontrollers', $test['mock']['flash']);

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('reset', 'bind'), array($container));
        $model->expects($this->any())->method('reset')->willReturn(null);
        $model->expects($check['bind'] ? $this->once() : $this->never())->method('bind')
            ->with($check['bind'])->willReturn(null);

        $controller = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataControllerStub', array('getModel', 'display'), array($container));
        $controller->expects($this->any())->method('getModel')->willReturn($model);
        $controller->expects($this->any())->method('display')->willReturn(null);

        $controller->add();
    }

    /**
     * @group           DataController
     * @group           DataControllerEdit
     * @covers          Awf\Mvc\DataController::edit
     * @dataProvider    DataControllerDataprovider::getTestEdit
     */
    public function testEdit($test, $check)
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

        $container->segment->setFlash('fakeapp_dummycontrollers', $test['mock']['flash']);

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('getId', 'lock', 'bind'), array($container));
        $model->expects($this->any())->method('getId')->willReturn($test['mock']['getId']);

        $method = $model->expects($this->any())->method('lock');

        if($test['mock']['lock'] === 'throw')
        {
            $method->willThrowException(new \Exception('Exception thrown while locking'));
        }
        else
        {
            $method->willReturn(null);
        }

        $model->expects($check['bind'] ? $this->once() : $this->never())->method('bind')
                ->with($check['bind'])->willReturn(null);

        $controller = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataControllerStub',
            array('getModel', 'getIDsFromRequest', 'setRedirect', 'display'), array($container));

        $controller->expects($this->any())->method('getModel')->willReturn($model);
        $controller->expects($check['getFromReq'] ? $this->once() : $this->never())->method('getIDsFromRequest')->willReturn(null);
        $controller->expects($check['redirect'] ? $this->once() : $this->never())->method('setRedirect')
            ->willReturn(null)->with($this->equalTo($check['url']), $this->equalTo($check['msg']), $this->equalTo('error'));
        $controller->expects($check['display'] ? $this->once() : $this->never())->method('display')->willReturn(null);

        ReflectionHelper::setValue($controller, 'layout', $test['mock']['layout']);

        $controller->edit();

        $layout = ReflectionHelper::getValue($controller, 'layout');
        $this->assertEquals($check['layout'], $layout, 'DataController::edit failed to set the layout');
    }

    /**
     * @group           DataController
     * @group           DataControllerApply
     * @covers          Awf\Mvc\DataController::apply
     * @dataProvider    DataControllerDataprovider::getTestApply
     */
    public function testApply($test, $check)
    {
        $container = new Container(array(
            'db' => self::$driver,
            'input' => new Input(array(
                'id' => $test['mock']['id'],
                'returnurl' => $test['mock']['returnurl'] ? base64_encode($test['mock']['returnurl']) : '',
            )),
        ));

        $controller = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataControllerStub', array('csrfProtection', 'applySave', 'setRedirect'), array($container));
        $controller->expects($this->any())->method('csrfProtection')->willReturn(null);
        $controller->expects($this->any())->method('applySave')->willReturn($test['mock']['apply']);
        $controller->expects($check['redirect'] ? $this->once() : $this->never())->method('setRedirect')
            ->willReturn(null)->with($this->equalTo($check['url']), $this->equalTo($check['msg']));

        $controller->apply();
    }

    /**
     * @group           DataController
     * @group           DataControllerCopy
     * @covers          Awf\Mvc\DataController::copy
     * @dataProvider    DataControllerDataprovider::getTestCopy
     */
    public function testCopy($test, $check)
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

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('find', 'copy'), array($container));
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

        $model->expects($this->any())->method('copy')->willReturnCallback(
            function() use (&$test)
            {
                // Should I return a value or throw an exception?
                $ret = array_shift($test['mock']['copy']);

                if($ret === 'throw')
                {
                    throw new \Exception('Exception in copy');
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

        $controller->copy();
    }

    /**
     * @group           DataController
     * @group           DataControllerSave
     * @covers          Awf\Mvc\DataController::save
     * @dataProvider    DataControllerDataprovider::getTestSave
     */
    public function testSave($test, $check)
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

        $controller = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataControllerStub', array('csrfProtection', 'applySave', 'setRedirect'), array($container));
        $controller->expects($this->once())->method('csrfProtection')->willReturn(null);
        $controller->expects($this->once())->method('applySave')->willReturn($test['mock']['apply']);
        $controller->expects($check['redirect'] ? $this->once() : $this->never())->method('setRedirect')->willReturn(null)
            ->with($this->equalTo($check['url']), $this->equalTo($check['msg']))
        ;

        $controller->save();
    }

    /**
     * @group           DataController
     * @group           DataControllerSavenew
     * @covers          Awf\Mvc\DataController::savenew
     * @dataProvider    DataControllerDataprovider::getTestSavenew
     */
    public function testSavenew($test, $check)
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

        $controller = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataControllerStub', array('csrfProtection', 'applySave', 'setRedirect'), array($container));
        $controller->expects($this->once())->method('csrfProtection')->willReturn(null);
        $controller->expects($this->once())->method('applySave')->willReturn($test['mock']['apply']);
        $controller->expects($check['redirect'] ? $this->once() : $this->never())->method('setRedirect')->willReturn(null)
            ->with($this->equalTo($check['url']), $this->equalTo($check['msg']))
        ;

        $controller->savenew();
    }

    /**
     * @group           DataController
     * @group           DataControllerCancel
     * @covers          Awf\Mvc\DataController::cancel
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
        $model->expects($this->exactly(2))->method('getId')->willReturn($test['mock']['getId']);
	    $expectedCalls = empty($test['mock']['getId']) ? 0 : 1;
        $model->expects($this->exactly($expectedCalls))->method('unlock')->willReturn(null);

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
     * @group           DataControllerPublish
     * @covers          Awf\Mvc\DataController::publish
     * @dataProvider    DataControllerDataprovider::getTestPublish
     */
    public function testPublish($test, $check)
    {
        $container = new Container(array(
            'db' => self::$driver,
            'input' => new Input(array(
                'returnurl' => $test['mock']['returnurl'] ? base64_encode($test['mock']['returnurl']) : '',
            )),
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('publish'), array($container));
        $model->expects($this->any())->method('publish')->willReturnCallback(
            function() use (&$test)
            {
                // Should I return a value or throw an exception?
                $ret = array_shift($test['mock']['publish']);

                if($ret === 'throw')
                {
                    throw new \Exception('Exception in publish');
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

        $controller->publish();
    }

    /**
     * @group           DataController
     * @group           DataControllerUnpublish
     * @covers          Awf\Mvc\DataController::unpublish
     * @dataProvider    DataControllerDataprovider::getTestUnpublish
     */
    public function testUnpublish($test, $check)
    {
        $container = new Container(array(
            'db' => self::$driver,
            'input' => new Input(array(
                'returnurl' => $test['mock']['returnurl'] ? base64_encode($test['mock']['returnurl']) : '',
            )),
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('unpublish'), array($container));
        $model->expects($this->any())->method('unpublish')->willReturnCallback(
            function() use (&$test)
            {
                // Should I return a value or throw an exception?
                $ret = array_shift($test['mock']['unpublish']);

                if($ret === 'throw')
                {
                    throw new \Exception('Exception in unpublish');
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

        $controller->unpublish();
    }

    /**
     * @group           DataController
     * @group           DataControllerArchive
     * @covers          Awf\Mvc\DataController::archive
     * @dataProvider    DataControllerDataprovider::getTestArchive
     */
    public function testArchive($test, $check)
    {
        $container = new Container(array(
            'db' => self::$driver,
            'input' => new Input(array(
                'returnurl' => $test['mock']['returnurl'] ? base64_encode($test['mock']['returnurl']) : '',
            )),
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('archive'), array($container));
        $model->expects($this->any())->method('archive')->willReturnCallback(
            function() use (&$test)
            {
                // Should I return a value or throw an exception?
                $ret = array_shift($test['mock']['archive']);

                if($ret === 'throw')
                {
                    throw new \Exception('Exception in archive');
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

        $controller->archive();
    }

    /**
     * @group           DataController
     * @group           DataControllerTrash
     * @covers          Awf\Mvc\DataController::trash
     * @dataProvider    DataControllerDataprovider::getTestTrash
     */
    public function testTrash($test, $check)
    {
        $container = new Container(array(
            'db' => self::$driver,
            'input' => new Input(array(
                'returnurl' => $test['mock']['returnurl'] ? base64_encode($test['mock']['returnurl']) : '',
            )),
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('trash'), array($container));
        $model->expects($this->any())->method('trash')->willReturnCallback(
            function() use (&$test)
            {
                // Should I return a value or throw an exception?
                $ret = array_shift($test['mock']['trash']);

                if($ret === 'throw')
                {
                    throw new \Exception('Exception in trash');
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

        $controller->trash();
    }

    /**
     * The best way to test with method is to run it and check vs the database
     *
     * @group           DataController
     * @group           DataControllerSaveorder
     * @covers          Awf\Mvc\DataController::saveorder
     * @dataProvider    DataControllerDataprovider::getTestsaveorder
     */
    public function testSaveorder($test, $check)
    {
        $msg = 'DataController::saveorder %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'input' => new Input(array(
                'order'     => $test['ordering'],
                'returnurl' => $test['returnurl'] ? base64_encode($test['returnurl']) : '',
            )),
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => $test['table']
            )
        ));

        $model      = new DataModelStub($container);
        $controller = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataControllerStub', array('csrfProtection', 'getModel', 'getIDsFromRequest', 'setRedirect'), array($container));
        $controller->expects($this->any())->method('getModel')->willReturn($model);
        $controller->expects($this->any())->method('getIDsFromRequest')->willReturn($test['mock']['ids']);
        $controller->expects($this->once())->method('setRedirect')->willReturn(null)
            ->with($this->equalTo($check['url']), $this->equalTo($check['msg']), $this->equalTo($check['type']));

        $controller->saveorder();

        $db = self::$driver;

        $query = $db->getQuery(true)
                    ->select('id')
                    ->from($db->qn('#__dbtest_extended'))
                    ->order($db->qn('ordering').' ASC');
        $rows = $db->setQuery($query)->loadColumn();

        $this->assertEquals($check['rows'], $rows, sprintf($msg, 'Failed to save the order of the rows'));
    }

    /**
     * @group           DataController
     * @group           DataControllerOrderdown
     * @covers          Awf\Mvc\DataController::orderdown
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
     * @covers          Awf\Mvc\DataController::orderup
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
     * @covers          Awf\Mvc\DataController::remove
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
     * @covers          Awf\Mvc\DataController::getModel
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
     * @covers          Awf\Mvc\DataController::getIDsFromRequest
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
