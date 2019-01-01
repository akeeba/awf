<?php
/**
 * @package    awf
 * @copyright  Copyright (c)2014-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU GPL version 3 or later
 */

namespace Awf\Tests\DataModel;

use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\DataModelStub;
use Awf\Tests\Stubs\Utils\ObserverClosure;
use Awf\Tests\Stubs\Utils\TestClosure;

require_once 'CrudDataprovider.php';

/**
 * @covers      Awf\Mvc\DataModel::<protected>
 * @covers      Awf\Mvc\DataModel::<private>
 * @package     Awf\Tests\DataModel
 */
class DataModelCrudTest extends DatabaseMysqliCase
{
    /**
     * @group           DataModel
     * @group           DataModelSave
     * @covers          Awf\Mvc\DataModel::save
     * @dataProvider    DataModelCrudDataprovider::getTestSave
     */
    public function testSave($test, $check)
    {
        //\PHPUnit_Framework_Error_Warning::$enabled = false;

        $db          = self::$driver;
        $msg         = 'DataModel::save %s - Case: '.$check['case'];
        $events      = array('onBeforeSave'  => 0, 'onAfterSave'  => 0, 'onBeforeCreate'  => 0, 'onAfterCreate'  => 0, 'onBeforeUpdate'  => 0, 'onAfterUpdate'  => 0);
        $dispEvents  = $events;
        $modelEvents = $events;

        // I need to fake the user id, since in CLI I don't have one
        $fakeUserManager = new TestClosure(array(
            'getUser' => function() {
                return new TestClosure(array(
                    'getId' => function(){
                        return 99;
                    }
                ));
            }
        ));

        $container = new Container(array(
            'db'          => self::$driver,
            'userManager' => $fakeUserManager,
            'mvc_config'  => array(
                'idFieldName' => 'id',
                'tableName'   => $test['table']
            )
        ));

        $methods = array(
            'onBeforeSave' => function() use (&$modelEvents){
                $modelEvents['onBeforeSave']++;
            },
            'onAfterSave' => function() use (&$modelEvents){
                $modelEvents['onAfterSave']++;
            },
            'onBeforeCreate' => function() use (&$modelEvents){
                $modelEvents['onBeforeCreate']++;
            },
            'onAfterCreate' => function() use (&$modelEvents){
                $modelEvents['onAfterCreate']++;
            },
            'onBeforeUpdate' => function() use (&$modelEvents){
                $modelEvents['onBeforeUpdate']++;
            },
            'onAfterUpdate' => function() use (&$modelEvents){
                $modelEvents['onAfterUpdate']++;
            }
        );

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('check', 'reorder'), array($container, $methods));
        $model->expects($this->any())->method('check')->willReturn(null);
        $model->expects($check['reorder'] ? $this->once() : $this->never())->method('reorder')->with($this->equalTo($check['reorder']))
            ->willReturn(null);

        $dispatcher = $model->getBehavioursDispatcher();

        // Let's attach a custom observer, so I can mock and check all the calls performed by the dispatcher
        // P.A. The object is immediatly attached to the dispatcher, so I don't need to manually do that
        new ObserverClosure($dispatcher, array(
            'onBeforeSave' => function(&$subject, &$data) use ($test, &$dispEvents){
                if($test['mock']['blankId']){
                    $subject->id = null;
                }

                if(!is_null($test['mock']['dataSave'])){
                    $data = $test['mock']['dataSave'];
                }

                $dispEvents['onBeforeSave']++;
            },
            'onBeforeCreate' => function(&$subject, &$dataObject) use($test, &$dispEvents){
                if(!is_null($test['mock']['dataCreate'])){
                    foreach($test['mock']['dataCreate'] as $prop => $value){
                        $dataObject->$prop = $value;
                    }
                }

                $dispEvents['onBeforeCreate']++;
            },
            'onAfterCreate' => function() use(&$dispEvents){
                $dispEvents['onAfterCreate']++;
            },
            'onBeforeUpdate' => function(&$subject, &$dataObject) use($test, &$dispEvents){
                if(!is_null($test['mock']['dataUpdate'])) {
                    foreach ($test['mock']['dataUpdate'] as $prop => $value) {
                        $dataObject->$prop = $value;
                    }
                }

                $dispEvents['onBeforeUpdate']++;
            },
            'onAfterUpdate' => function() use(&$dispEvents){
                $dispEvents['onAfterUpdate']++;
            },
            'onAfterSave' => function() use(&$dispEvents){
                $dispEvents['onAfterSave']++;
            }
        ));

        if($test['id'])
        {
            $model->find($test['id']);
        }

        $result = $model->save($test['data'], $test['ordering'], $test['ignore']);

        // Did I add a new record or update an old one? Let's get the correct id
        if($check['id'] == 'max')
        {
            $query = $db->getQuery(true)
                ->select('MAX(id)')
                ->from($test['table']);
            $checkid = $db->setQuery($query)->loadResult();
        }
        else
        {
            $checkid = $check['id'];
        }

        $query = $db->getQuery(true)->select('*')->from($test['table'])->where('id = '.$checkid);
        $row   = $db->setQuery($query)->loadObject();

        // If the model has "time columns" I can only check if they are not null
        if($check['created_on'])
        {
            $created_on = $model->getFieldAlias('created_on');
            $this->assertNotNull($row->$created_on, sprintf($msg, 'Failed to set the creation time'));
            unset($row->$created_on);
        }

        if($check['modified_on'])
        {
            $modified_on = $model->getFieldAlias('modified_on');
            $this->assertNotNull($row->$modified_on, sprintf($msg, 'Failed to set the modification time'));
            unset($row->$modified_on);
        }

        // If I am inserting a new record I can't know its id, so let's remove it from the object
        if($check['id'] == 'max')
        {
            $id = $model->getIdFieldName();
            unset($row->$id);
        }

        // Let's merge the arrays, otherwise I'll have to write the whole list inside the dataprovider
        $check['modelEvents'] = array_merge($events, $check['modelEvents']);
        $check['dispEvents']  = array_merge($events, $check['dispEvents']);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals($check['modelEvents'], $modelEvents, sprintf($msg, 'Failed to invoke model events'));
        $this->assertEquals($check['dispEvents'], $dispEvents, sprintf($msg, 'Failed to invoke dispatcher events'));
        $this->assertEquals($check['row'], $row, sprintf($msg, 'Failed to correctly save the data into the db'));
    }

    /**
     * @group           DataModel
     * @group           DataModelBind
     * @covers          Awf\Mvc\DataModel::bind
     * @dataProvider    DataModelCrudDataprovider::getTestBind
     */
    public function testBind($test, $check)
    {
        $msg       = 'DataModel::bind %s - Case: '.$check['case'];
        $checkBind = array();

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('setFieldValue'), array($container));
        $model->expects($this->any())->method('setFieldValue')->willReturnCallback(
            function($key, $value) use (&$checkBind){
                $checkBind[$key] = $value;
            }
        );

        $dispatcher = $this->getMock('\\Awf\\Event\\Dispatcher', array('trigger'), array($container));
        $dispatcher->expects($this->exactly($check['dispatcher']))->method('trigger')->withConsecutive(
            array($this->equalTo('onBeforeBind')),
            array($this->equalTo('onAfterBind'))
        )
            ->willReturnCallback(
                function($event, $params) use ($test){
                    if($event == 'onBeforeBind' && !is_null($test['mock']['beforeDisp'])){
                        $params[1] = $test['mock']['beforeDisp'];
                    }
                }
            );

        ReflectionHelper::setValue($model, 'behavioursDispatcher', $dispatcher);

        $result = $model->bind($test['data'], $test['ignore']);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals($check['bind'], $checkBind, sprintf($msg, 'Failed to bind the data to the model'));
    }

    /**
     * @group           DataModel
     * @group           DataModelBind
     * @covers          Awf\Mvc\DataModel::bind
     * @dataProvider    DataModelCrudDataprovider::getTestBindException
     */
    public function testBindException($test)
    {
        $this->setExpectedException('InvalidArgumentException');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        $model->bind($test['data']);
    }

    /**
     * @group           DataModel
     * @group           DataModelCheck
     * @covers          Awf\Mvc\DataModel::check
     * @dataProvider    DataModelCrudDataprovider::getTestCheck
     */
    public function testCheck($test, $check)
    {
        $msg = 'DataModel::check %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => $test['table']
            )
        ));

        $model = new DataModelStub($container);

        ReflectionHelper::setValue($model, 'autoChecks', $test['mock']['auto']);

        if($test['load'])
        {
            $model->find($test['load']);
        }

        if($check['exception'])
        {
            $this->setExpectedException('RuntimeException', $check['exception']);
        }

        $result = $model->check();

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
    }

    /**
     * @group           DataModel
     * @group           DataModelCopy
     * @covers          Awf\Mvc\DataModel::copy
     */
    public function testCopy()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('save'), array($container));
        $model->expects($this->any())->method('save')->willReturn(null);

        $model->find(2);
        $model->copy();

        $id = $model->getId();

        $this->assertNull($id, 'DataModel::copy Should set the table ID to null before saving the record');
    }

    /**
     * @group           DataModel
     * @group           DataModelDelete
     * @covers          Awf\Mvc\DataModel::delete
     * @dataProvider    DataModelCrudDataprovider::getTestDelete
     */
    public function testDelete($test, $check)
    {
        $msg = 'DataModel::delete %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('trash', 'forceDelete'), array($container));
        $model->expects($check['trash'] ? $this->once() : $this->never())->method('trash')->willReturnSelf();
        $model->expects($check['force'] ? $this->once() : $this->never())->method('forceDelete')->willReturnSelf();

        ReflectionHelper::setValue($model, 'softDelete', $test['soft']);

        $result = $model->delete($test['id']);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
    }

    /**
     * @group           DataModel
     * @group           DataModelFindOrFail
     * @covers          Awf\Mvc\DataModel::findOrFail
     * @dataProvider    DataModelCrudDataprovider::getTestFindOrFail
     */
    public function testFindOrFail($test, $check)
    {
        $msg    = 'DataModel::findOrFail %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('find', 'getId'), array($container));
        $model->expects($this->any())->method('find')->willReturn(null);
        $model->expects($this->any())->method('getId')->willReturn($test['mock']['getId']);

        if($check['exception'])
        {
            $this->setExpectedException('RuntimeException');
        }

        $result = $model->findOrFail($test['keys']);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
    }

    /**
     * @group           DataModel
     * @group           DataModelFind
     * @covers          Awf\Mvc\DataModel::find
     * @dataProvider    DataModelCrudDataprovider::getTestFind
     */
    public function testFind($test, $check)
    {
        //\PHPUnit_Framework_Error_Warning::$enabled = false;

        $beforeDisp = 0;
        $afterDisp  = 0;
        $before = 0;
        $after  = 0;
        $msg    = 'DataModel::find %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        // I am passing those methods so I can double check if the method is really called
        $methods = array(
            'onBeforeLoad' => function() use(&$before){
                $before++;
            },
            'onAfterLoad' => function() use(&$after){
                $after++;
            }
        );

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('reset', 'getId', 'bind'), array($container, $methods));
        $model->expects($this->any())->method('reset')->willReturn(null);
        $model->expects($this->any())->method('getId')->willReturn($test['mock']['id']);
        $model->expects($check['bind'] ? $this->once() : $this->never())->method('bind')->willReturn(null);

        $dispatcher = $model->getBehavioursDispatcher();

        // Let's attach a custom observer, so I can mock and check all the calls performed by the dispatcher
        // P.A. The object is immediatly attached to the dispatcher, so I don't need to manually do that
        new ObserverClosure($dispatcher, array(
            'onBeforeLoad' => function(&$subject, &$keys) use ($test, &$beforeDisp){
                if(!is_null($test['mock']['keys'])){
                    $keys = $test['mock']['keys'];
                }

                $beforeDisp++;
            },
            'onAfterLoad' => function(&$subject, $success, $keys) use(&$afterDisp){
                $afterDisp++;
            }
        ));

        if(!is_null($test['mock']['state_id']))
        {
            $model->setState('id', $test['mock']['state_id']);
        }

        $result = $model->find($test['keys']);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals(1, $before, sprintf($msg, 'Failed to invoke the onBefore method'));
        $this->assertEquals(1, $beforeDisp, sprintf($msg, 'Failed to invoke the onBefore event'));
        $this->assertEquals(1, $after, sprintf($msg, 'Failed to invoke the onAfter method'));
        $this->assertEquals(1, $afterDisp, sprintf($msg, 'Failed to invoke the onAfter event'));
    }

    /**
     * @group           DataModel
     * @group           DataModelForceDelete
     * @covers          Awf\Mvc\DataModel::forceDelete
     * @dataProvider    DataModelCrudDataprovider::getTestForceDelete
     */
    public function testForceDelete($test, $check)
    {
        $before = 0;
        $after  = 0;
        $msg    = 'DataModel::forceDelete %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        // I am passing those methods so I can double check if the method is really called
        $methods = array(
            'onBeforeDelete' => function() use(&$before){
                $before++;
            },
            'onAfterDelete' => function() use(&$after){
                $after++;
            }
        );

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('getId', 'findOrFail', 'reset'), array($container, $methods));
        $model->expects($this->once())->method('reset')->willReturn(null);
        $model->expects($this->any())->method('getId')->willReturn($test['mock']['id']);
        $model->expects($check['find'] ? $this->once() : $this->never())->method('findOrFail')->willReturn(null);

        // Let's mock the dispatcher, too. So I can check if events are really triggered
        $dispatcher = $this->getMock('\\Awf\\Event\\Dispatcher', array('trigger'), array($container));
        $dispatcher->expects($this->exactly(2))->method('trigger')->withConsecutive(
            array($this->equalTo('onBeforeDelete')),
            array($this->equalTo('onAfterDelete'))
        );

        ReflectionHelper::setValue($model, 'behavioursDispatcher', $dispatcher);

        $result = $model->delete($test['id']);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals(1, $before, sprintf($msg, 'Failed to call the onBefore method'));
        $this->assertEquals(1, $after, sprintf($msg, 'Failed to call the onAfter method'));

        // Now let's check if the record was really deleted
        $db = self::$driver;

        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->qn('#__dbtest'))
            ->where($db->qn('id').' = '.$db->q($check['id']));
        $count = $db->setQuery($query)->loadResult();

        $this->assertEquals(0, $count, sprintf($msg, ''));
    }

    /**
     * @group           DataModel
     * @group           DataModelForceDelete
     * @covers          Awf\Mvc\DataModel::forceDelete
     */
    public function testForceDeleteException()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        $this->setExpectedException('Awf\Mvc\DataModel\Exception\RecordNotLoaded');

        $model->forceDelete();
    }

    /**
     * @group           DataModel
     * @group           DataModelFirstOrCreate
     * @covers          Awf\Mvc\DataModel::firstOrCreate
     * @dataProvider    DataModelCrudDataprovider::getTestFirstOrCreate
     */
    public function testFirstOrCreate($test, $check)
    {
        $msg = 'DataModel::firstOrCreate %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $fakeCollection = new TestClosure(array(
            'first' => function() use ($test){
                return $test['mock']['first'];
            }
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('get', 'create'), array($container));
        $model->expects($this->once())->method('get')->willReturn($fakeCollection);
        $model->expects($check['create'] ? $this->once() : $this->never())->method('create')->willReturn(null);

        $result = $model->firstOrCreate(array());

        if($check['result'] == 'object')
        {
            $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Returned the wrong value'));
        }
        else
        {
            $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong value'));
        }
    }

    /**
     * @group           DataModel
     * @group           DataModelCreate
     * @covers          Awf\Mvc\DataModel::create
     */
    public function testCreate()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('reset', 'bind', 'save'), array($container));
        $model->expects($this->once())->method('reset')->willReturnSelf();
        $model->expects($this->once())->method('bind')->willReturnSelf();
        $model->expects($this->once())->method('save')->willReturnSelf();

        $model->create(array('foo' => 'bar'));
    }

    /**
     * @group           DataModel
     * @group           DataModelFirstOrFail
     * @covers          Awf\Mvc\DataModel::firstOrFail
     * @dataProvider    DataModelCrudDataprovider::getTestFirstOrFail
     */
    public function testFirstOrFail($test, $check)
    {
        $msg = 'DataModel::firstOrFail %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $fakeCollection = new TestClosure(array(
            'first' => function() use ($test){
                return $test['mock']['first'];
            }
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('get'), array($container));
        $model->expects($this->once())->method('get')->willReturn($fakeCollection);

        if($check['exception'])
        {
            $this->setExpectedException('RuntimeException');
        }

        $result = $model->firstOrFail(array());

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong value'));
    }

    /**
     * @group           DataModel
     * @group           DataModelFirstOrNew
     * @covers          Awf\Mvc\DataModel::firstOrNew
     * @dataProvider    DataModelCrudDataprovider::getTestFirstOrNew
     */
    public function testFirstOrNew($test, $check)
    {
        $msg = 'DataModel::firstOrNew %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $fakeCollection = new TestClosure(array(
            'first' => function() use ($test){
                return $test['mock']['first'];
            }
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('get', 'reset'), array($container));
        $model->expects($this->once())->method('get')->willReturn($fakeCollection);
        $model->expects($check['reset'] ? $this->once() : $this->never())->method('reset')->willReturn(null);

        $result = $model->firstOrNew(array());

        if($check['result'] == 'object')
        {
            $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Returned the wrong value'));
        }
        else
        {
            $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong value'));
        }
    }
}
