<?php

namespace Awf\Tests\DataModel;

use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\DataModelStub;

require_once 'DataModelDataprovider.php';

class DataModeltest extends DatabaseMysqliCase
{
    /**
     * @group           DataModel
     * @group           DataModelGetDbo
     * @covers          DataModel::getDbo
     * @dataProvider    DataModelDataprovider::getTestGetDbo
     */
    public function testGetDbo($test, $check)
    {
        // Please note that if you try to debug this test, you'll get a "Couldn't fetch mysqli_result" error
        // That's harmless and appears in debug only, you might want to suppress exception thowing
        //\PHPUnit_Framework_Error_Warning::$enabled = false;

        $msg       = 'DataModel::setFieldValue %s - Case: '.$check['case'];
        $dbcounter = 0;
        $selfDb    = clone self::$driver;

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        $newContainer = new Container(array(
            'db' => function() use (&$dbcounter, $selfDb){
                $dbcounter++;
                return $selfDb;
            }
        ));

        ReflectionHelper::setValue($model, 'container', $newContainer);

        if($test['nuke'])
        {
            ReflectionHelper::setValue($model, 'dbo', null);
        }

        $db = $model->getDbo();

        $this->assertInstanceOf('\\Awf\\Database\\Driver', $db, sprintf($msg, 'Should return an instance of Driver'));
        $this->assertEquals($check['dbCounter'], $dbcounter, sprintf($msg, ''));
    }

    /**
     * @group           DataModel
     * @group           DataModelSetFieldValue
     * @covers          DataModel::setFieldValue
     * @dataProvider    DataModelDataprovider::getTestSetFieldValue
     */
    public function testSetFieldValue($test, $check)
    {
        $msg = 'DataModel::setFieldValue %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        ReflectionHelper::setValue($model, 'aliasFields', $test['mock']['alias']);

        $model->setFieldValue($test['name'], $test['value']);

        $data  = ReflectionHelper::getValue($model, 'recordData');
        $count = isset($model->methodCounter[$check['method']]) ? $model->methodCounter[$check['method']] : 0;

        if($check['set'])
        {
            $this->assertArrayHasKey($check['key'], $data, sprintf($msg, ''));
            $this->assertEquals($check['value'], $data[$check['key']], sprintf($msg, ''));
        }
        else
        {
            $this->assertArrayNotHasKey($check['key'], $data, sprintf($msg, ''));
        }

        $this->assertEquals($check['count'], $count, sprintf($msg, 'Called the magic setter the wrong amount of times'));
    }

    /**
     * @group           DataModel
     * @group           DataModelReset
     * @covers          DataModel::reset
     * @dataProvider    DataModelDataprovider::getTestReset
     */
    public function testReset($test, $check)
    {
        $msg = 'DataModel::reset %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => $test['table']
            )
        ));

        $model = new DataModelStub($container);

        $relation = $this->getMock('\\Awf\\Mvc\\DataModel\\RelationManager', array('resetRelations'), array($model));
        $relation->expects($check['resetRelations'] ? $this->once() : $this->never())->method('resetRelations')->willReturn(null);

        ReflectionHelper::setValue($model, 'relationManager', $relation);
        ReflectionHelper::setValue($model, 'recordData', $test['mock']['recordData']);
        ReflectionHelper::setValue($model, 'eagerRelations', $test['mock']['eagerRelations']);
        ReflectionHelper::setValue($model, 'relationFilters', $test['mock']['relationFilters']);

        $return = $model->reset($test['default'], $test['relations']);

        $data    = ReflectionHelper::getValue($model, 'recordData');
        $eager   = ReflectionHelper::getValue($model, 'eagerRelations');
        $filters = ReflectionHelper::getValue($model, 'relationFilters');

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $return, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals($check['data'], $data, sprintf($msg, 'Failed to reset the internal data'));
        $this->assertEquals($check['eager'], $eager, sprintf($msg, 'Eager relations are not correctly set'));
        $this->assertEmpty($filters, sprintf($msg, 'Relations filters should be empty'));
    }

    /**
     * @group           DataModel
     * @group           DataModelCall
     * @covers          DataModel::__call
     * @dataProvider    DataModelDataprovider::getTest__call
     */
    public function test__call($test, $check)
    {
        $msg = 'DataModel::__call %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model  = new DataModelStub($container);

        $relation = $this->getMock('\\Awf\\Mvc\\DataModel\\RelationManager', array('isMagicMethod', '__call'), array($model));
        $relation->expects($check['magic'] ? $this->once() : $this->never())->method('isMagicMethod')->willReturn($test['mock']['magic']);
        $relation->expects($check['relationCall'] ? $this->once() : $this->never())->method('__call')->willReturn(null);

        ReflectionHelper::setValue($model, 'relationManager', $relation);

        $method = $test['method'];

        // I have to use this syntax to check when I don't pass any argument
        // N.B. If I use the __call syntax to set a property, I have to use a REAL property, otherwise the __set magic
        // method kicks in and its behavior it's out the scope of this test
        if(isset($test['argument']))
        {
            $result = $model->$method($test['argument'][0], $test['argument'][1]);
        }
        else
        {
            $result = $model->$method();
        }

        $count = isset($model->methodCounter[$check['method']]) ? $model->methodCounter[$check['method']] : 0;
        $property = ReflectionHelper::getValue($model, $check['property']);

        if(is_object($result))
        {
            $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
        }
        else
        {
            $this->assertNull($result, sprintf($msg, 'Should return null when the relation manager is involved'));
        }

        $this->assertEquals($check['count'], $count, sprintf($msg, 'Invoked the specific caller method a wrong amount of times'));
        $this->assertEquals($check['value'], $property, sprintf($msg, 'Failed to set the property'));
    }

    /**
     * @group           DataModel
     * @group           DataModelIsset
     * @covers          DataModel::__isset
     * @dataProvider    DataModelDataprovider::getTest__isset
     */
    public function test__isset($test, $check)
    {
        $msg = 'DataModel::__isset %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('getFieldValue'), array($container));
        $model->expects($check['getField'] ? $this->once() : $this->never())->method('getFieldValue')->with($check['getField'])
                ->willReturn($test['mock']['getField']);

        $relation = $this->getMock('\\Awf\\Mvc\\DataModel\\RelationManager', array('isMagicProperty', '__get'), array($model));
        $relation->expects($check['magic'] ? $this->once() : $this->never())->method('isMagicProperty')->with($check['magic'])
            ->willReturn($test['mock']['magic']);
        $relation->expects($check['relationGet'] ? $this->once() : $this->never())->method('__get')->willReturn($test['mock']['relationGet']);

        ReflectionHelper::setValue($model, 'relationManager', $relation);

        $property = $test['property'];

        ReflectionHelper::setValue($model, 'aliasFields', $test['mock']['alias']);

        $isset = isset($model->$property);

        $this->assertEquals($check['isset'], $isset, sprintf($msg, 'Failed to correctly detect if a property is set'));
    }

    /**
     * @group           DataModel
     * @group           DataModelGet
     * @covers          DataModel::__get
     * @dataProvider    DataModelDataprovider::getTest__get
     */
    public function test__get($test, $check)
    {
        $msg = 'DataModel::__get %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('getFieldValue', 'getState'), array($container));
        $model->expects($check['getField'] ? $this->once() : $this->never())->method('getFieldValue')->with($check['getField'])
            ->willReturn($test['mock']['getField']);

        $model->expects($check['getState'] ? $this->once() : $this->never())->method('getState')->with($check['getState'])
            ->willReturn($test['mock']['getState']);

        $relation = $this->getMock('\\Awf\\Mvc\\DataModel\\RelationManager', array('isMagicProperty', '__get'), array($model));
        $relation->expects($check['magic'] ? $this->once() : $this->never())->method('isMagicProperty')->with($check['magic'])
            ->willReturn($test['mock']['magic']);
        $relation->expects($check['relationGet'] ? $this->once() : $this->never())->method('__get')->willReturn($test['mock']['relationGet']);

        ReflectionHelper::setValue($model, 'relationManager', $relation);

        $property = $test['property'];

        ReflectionHelper::setValue($model, 'aliasFields', $test['mock']['alias']);

        $get = $model->$property;

        $this->assertEquals($check['get'], $get, sprintf($msg, 'Failed to get the property value'));
    }

    /**
     * @group           DataModel
     * @group           DataModelSet
     * @covers          DataModel::__set
     * @dataProvider    DataModelDataprovider::getTest__set
     */
    public function test__set($test, $check)
    {
        $msg = 'DataModel::__set %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('setFieldValue', 'setState', '__call'), array($container));
        $model->expects($check['call'] ? $this->once() : $this->never())->method('__call')->willReturn(null);

        $model->expects($check['setField'] ? $this->once() : $this->never())->method('setFieldValue')->with($check['setField'])->willReturn(null);
        $model->expects($check['setState'] ? $this->once() : $this->never())->method('setState')->with($check['setState'])->willReturn(null);

        ReflectionHelper::setValue($model, 'aliasFields', $test['mock']['alias']);

        $property = $test['property'];
        $model->$property = $test['value'];

        $count = isset($model->methodCounter[$check['method']]) ? $model->methodCounter[$check['method']] : 0;

        $this->assertEquals($check['count'], $count, sprintf($msg, 'Invoked the specific setter method a wrong amount of times'));
    }

    /**
     * @group           DataModel
     * @group           DataModelGetFieldValue
     * @covers          DataModel::getFieldValue
     * @dataProvider    DataModelDataprovider::getTestGetFieldValue
     */
    public function testGetFieldValue($test, $check)
    {
        $msg = 'DataModel::getFieldValue %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        ReflectionHelper::setValue($model, 'aliasFields', $test['mock']['alias']);

        if($test['find'])
        {
            $model->find($test['find']);
        }

        $result = $model->getFieldValue($test['property'], $test['default']);

        $count = isset($model->methodCounter[$check['method']]) ? $model->methodCounter[$check['method']] : 0;

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong value'));
        $this->assertEquals($check['count'], $count, sprintf($msg, 'Invoked the specific getter method a wrong amount of times'));
    }

    /**
     * @group           DataModel
     * @group           DataModelArchive
     * @covers          DataModel::archive
     * @dataProvider    DataModelDataprovider::getTestArchive
     */
    public function testArchive($test, $check)
    {
        $msg     = 'DataModel::getFieldValue %s - Case: '.$check['case'];
        $methods = array();

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => $test['table']
            )
        ));

        if($test['mock']['before'])
        {
            $methods['onBeforeArchive'] = $test['mock']['before'];
        }

        if($test['mock']['after'])
        {
            $methods['onAfterArchive'] = $test['mock']['after'];
        }

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('save'), array($container, $methods));
        $model->expects($check['save'] ? $this->once() : $this->never())->method('save')->willReturn(null);

        $dispatcher = $this->getMock('\\Awf\\Event\\Dispatcher', array('trigger'), array($container));
        $dispatcher->expects($this->exactly($check['dispatcher']))->method('trigger')->withConsecutive(
            array($this->equalTo('onBeforeArchive')),
            array($this->equalTo('onAfterArchive'))
        );

        ReflectionHelper::setValue($model, 'behavioursDispatcher', $dispatcher);
        ReflectionHelper::setValue($model, 'aliasFields', $test['mock']['alias']);

        if($check['exception'])
        {
            $this->setExpectedException('Exception');
        }

        $result = $model->archive();

        if($check['save'])
        {
            $enabled = $model->getFieldAlias('enabled');
            $value   = $model->$enabled;

            $this->assertEquals(2, $value, sprintf($msg, 'Should set the value of the enabled field to 2'));
        }

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an istance of itself'));
    }

    /**
     * @group           DataModel
     * @group           DataModelHasField
     * @covers          DataModel::hasField
     * @dataProvider    DataModelDataprovider::getTestHasField
     */
    public function testHasField($test, $check)
    {
        $msg = 'DataModel::hasField %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('getFieldAlias'), array($container));
        $model->expects($this->any())->method('getFieldAlias')->willReturn($test['mock']['getAlias']);

        ReflectionHelper::setValue($model, 'knownFields', $test['mock']['fields']);

        $result = $model->hasField($test['field']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong value'));
    }

    /**
     * @group           DataModel
     * @group           DataModelGetFieldAlias
     * @covers          DataModel::getFieldAlias
     * @dataProvider    DataModelDataprovider::getTestGetFieldAlias
     */
    public function testGetFieldAlias($test, $check)
    {
        $msg = 'DataModel::hasField %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        ReflectionHelper::setValue($model, 'aliasFields', $test['mock']['alias']);

        $result = $model->getFieldAlias($test['field']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }
}