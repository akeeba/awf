<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\DataModel;

use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ClosureHelper;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\DataModelStub;

require_once 'MagicMethodsDataprovider.php';

/**
 * @covers      Awf\Mvc\DataModel::<protected>
 * @covers      Awf\Mvc\DataModel::<private>
 * @package     Awf\Tests\DataModel
 */
class DataModelMagicMethodsTest extends DatabaseMysqliCase
{
    /**
     * @group           DataModel
     * @group           DataModelConstruct
     * @covers          Awf\Mvc\DataModel::__construct
     * @dataProvider    MagicMethodsDataprovider::getTest__construct
     */
    public function test__construct($test, $check)
    {
        $msg        = 'DataModel::__construct %s - Case: '.$check['case'];
        $container  = null;
        $counterApp = 0;

        $containerSetup = array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName'           => $test['id'],
                'tableName'             => $test['table'],
                'knownFields'           => $test['knownFields'],
                'autoChecks'            => $test['autoChecks'],
                'fieldsSkipChecks'      => $test['skipChecks'],
                'aliasFields'           => $test['aliasFields'],
                'behaviours'            => $test['behaviours'],
                'fillable_fields'       => $test['fillable'],
                'guarded_fields'        => $test['guarded'],
                'relations'             => $test['relations']
            )
        );

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

        // Setup the class but do not instantiate it, so we an mock the methods
        $model = $this->getMock('Awf\\Mvc\\DataModel', array('getName', 'addBehaviour', 'getState'), array(), '', false);
        $model->expects($this->any())->method('getName')->willReturn('test');
        $model->expects($this->exactly($check['addBehaviour']))->method('addBehaviour')->willReturn(null);
        $model->expects($this->any())->method('getState')->willReturnCallback(function($field) use ($test){
            if(isset($test['mock']['state'][$field])){
                return $test['mock']['state'][$field];
            }

            return null;
        });

        //Finally, let's invoke our crafted mock
        $model->__construct($container);

        if(!$test['container'])
        {
            ReflectionHelper::setValue('\\Awf\\Application\\Application', 'instances', $oldinstances);
        }

        $id             = ReflectionHelper::getValue($model, 'idFieldName');
        $tableName      = ReflectionHelper::getValue($model, 'tableName');
        $knownFields    = ReflectionHelper::getValue($model, 'knownFields');
        $autoChecks     = ReflectionHelper::getValue($model, 'autoChecks');
        $skipChecks     = ReflectionHelper::getValue($model, 'fieldsSkipChecks');
        $aliasFields    = ReflectionHelper::getValue($model, 'aliasFields');
        $fillable       = ReflectionHelper::getValue($model, 'fillable');
        $autoFill       = ReflectionHelper::getValue($model, 'autoFill');
        $guarded        = ReflectionHelper::getValue($model, 'guarded');
        $relations      = $model->getRelations()->getRelationNames();

        $this->assertEquals($check['id'], $id, sprintf($msg, 'Failed to set the id'));
        $this->assertEquals($check['table'], $tableName, sprintf($msg, 'Failed to set the table name'));
        $this->assertEquals($check['autochecks'], $autoChecks, sprintf($msg, 'Failed to set the autochecks'));
        $this->assertEquals($check['skipchecks'], $skipChecks, sprintf($msg, 'Failed to set the field to skip in auto checks'));
        $this->assertEquals($check['alias'], $aliasFields, sprintf($msg, 'Failed to set the alias field'));
        $this->assertEquals($check['fillable'], $fillable, sprintf($msg, 'Failed to set the fillable fields'));
        $this->assertEquals($check['autofill'], $autoFill, sprintf($msg, 'Failed to set the autofill flag'));
        $this->assertEquals($check['guarded'], $guarded, sprintf($msg, 'Failed to set the guarded fields'));
        $this->assertEquals($check['relations'], $relations, sprintf($msg, 'Failed to set the relations'));
        $this->assertEquals($check['counterApp'], $counterApp, sprintf($msg, 'Failed to correctly get the container from the Application'));

        if(!is_null($check['fields']))
        {
            $this->assertEquals($check['fields'], $knownFields, sprintf($msg, 'Failed to set the known fields'));
        }

        foreach ($check['values'] as $field => $value)
        {
            $actual = $model->getFieldValue($field);
            $this->assertEquals($value, $actual, sprintf($msg, 'Failed to set the value of an autofill field'));
        }
    }

    /**
     * @group           DataModel
     * @group           DataModelConstruct
     * @covers          Awf\Mvc\DataModel::__construct
     */
    public function test__constructException()
    {
        $this->setExpectedException('Awf\Mvc\DataModel\Exception\NoTableColumns');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__wrongtable'
            )
        ));

        new DataModelStub($container);
    }

    /**
     * @group           DataModel
     * @group           DataModelCall
     * @covers          Awf\Mvc\DataModel::__call
     * @dataProvider    MagicMethodsDataprovider::getTest__call
     */
    public function test__call($test, $check)
    {
        $msg = 'DataModel::__call %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
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
     * @group           DataModel__isset
     * @covers          Awf\Mvc\DataModel::__isset
     * @dataProvider    MagicMethodsDataprovider::getTest__isset
     */
    public function test__isset($test, $check)
    {
        $msg = 'DataModel::__isset %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
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
     * @group           DataModel__get
     * @covers          Awf\Mvc\DataModel::__get
     * @dataProvider    MagicMethodsDataprovider::getTest__get
     */
    public function test__get($test, $check)
    {
        $msg = 'DataModel::__get %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
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
     * @group           DataModel__set
     * @covers          Awf\Mvc\DataModel::__set
     * @dataProvider    MagicMethodsDataprovider::getTest__set
     */
    public function test__set($test, $check)
    {
        $msg = 'DataModel::__set %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
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
}
