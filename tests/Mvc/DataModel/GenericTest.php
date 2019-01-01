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
use Awf\Tests\Stubs\Utils\TestClosure;

require_once 'GenericDataprovider.php';

/**
 * @covers      Awf\Mvc\DataModel::<protected>
 * @covers      Awf\Mvc\DataModel::<private>
 * @package     Awf\Tests\DataModel
 */
class DataModelGenericTest extends DatabaseMysqliCase
{
    /**
     * @group           DataModel
     * @group           DataModelGetTableFields
     * @covers          Awf\Mvc\DataModel::getTableFields
     * @dataProvider    DataModelGenericDataprovider::getTestGetTableFields
     */
    public function testGetTableFields($test, $check)
    {
        $msg = 'DataModel::getTableFields %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        // Mocking the whole database it's simply too hard. We will play with the cache and we won't get 100% code coverage
        if($test['mock']['tables'] !== null)
        {
            $tables = ReflectionHelper::getValue($model, 'tableFieldCache');

            if($test['mock']['tables'] == 'nuke')
            {
                $tables = array();
            }
            else
            {
                foreach($test['mock']['tables'] as $mockedTable => $value)
                {
                    if($value == 'unset')
                    {
                        unset($tables[$mockedTable]);
                    }
                    else
                    {
                        $tables[$mockedTable] = $value;
                    }
                }
            }

            ReflectionHelper::setValue($model, 'tableFieldCache', $tables);
        }

        if($test['mock']['tableName'] !== null)
        {
            ReflectionHelper::setValue($model, 'tableName', $test['mock']['tableName']);
        }

        $result = $model->getTableFields($test['table']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @group           DataModel
     * @group           DataModelGetDbo
     * @covers          Awf\Mvc\DataModel::getDbo
     * @dataProvider    DataModelGenericDataprovider::getTestGetDbo
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
     * @covers          Awf\Mvc\DataModel::setFieldValue
     * @dataProvider    DataModelGenericDataprovider::getTestSetFieldValue
     */
    public function testSetFieldValue($test, $check)
    {
        $msg = 'DataModel::setFieldValue %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
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
     * @covers          Awf\Mvc\DataModel::reset
     * @dataProvider    DataModelGenericDataprovider::getTestReset
     */
    public function testReset($test, $check)
    {
        $msg = 'DataModel::reset %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
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
     * @group           DataModelGetFieldValue
     * @covers          Awf\Mvc\DataModel::getFieldValue
     * @dataProvider    DataModelGenericDataprovider::getTestGetFieldValue
     */
    public function testGetFieldValue($test, $check)
    {
        $msg = 'DataModel::getFieldValue %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
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
     * @group           DataModelHasField
     * @covers          Awf\Mvc\DataModel::hasField
     * @dataProvider    DataModelGenericDataprovider::getTestHasField
     */
    public function testHasField($test, $check)
    {
        $msg = 'DataModel::hasField %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
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
     * @covers          Awf\Mvc\DataModel::getFieldAlias
     * @dataProvider    DataModelGenericDataprovider::getTestGetFieldAlias
     */
    public function testGetFieldAlias($test, $check)
    {
        $msg = 'DataModel::getFieldAlias %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        ReflectionHelper::setValue($model, 'aliasFields', $test['mock']['alias']);

        $result = $model->getFieldAlias($test['field']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @group           DataModel
     * @group           DataModelGetData
     * @covers          Awf\Mvc\DataModel::getData
     */
    public function testGetData()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);
        $model->find(1);

        $result = $model->getData();

        $check = array('id' => 1, 'title' => 'Testing', 'start_date' => '1980-04-18 00:00:00', 'description' => 'one');

        $this->assertEquals($check, $result, 'DataModel::getData Returned the wrong result');
    }

    /**
     * @group           DataModel
     * @group           DataModelChunk
     * @covers          Awf\Mvc\DataModel::chunk
     * @dataProvider    DataModelGenericDataprovider::getTestChunk
     */
    public function testChunk($test, $check)
    {
        $msg     = 'DataModel::chunk %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $fakeGet = new TestClosure(array(
            'transform' => function(){}
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('count', 'get'), array($container));
        $model->expects($this->once())->method('count')->willReturn($test['mock']['count']);
        $model->expects($this->exactly($check['get']))->method('get')->willReturn($fakeGet);

        $result = $model->chunk($test['chunksize'], function(){});

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
    }

    /**
     * @group           DataModel
     * @group           DataModelCount
     * @covers          Awf\Mvc\DataModel::count
     */
    public function testCount()
    {
        $db     = self::$driver;
        $after  = 0;

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        // I am passing those methods so I can double check if the method is really called
        $methods = array(
            'buildCountQuery' => function() use(&$after){
                $after++;
            }
        );

        $mockedQuery = $db->getQuery(true)->select('*')->from('#__dbtest');
        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('buildQuery'), array($container, $methods));
        $model->expects($this->any())->method('buildQuery')->willReturn($mockedQuery);

        // Let's mock the dispatcher, too. So I can check if events are really triggered
        $dispatcher = $this->getMock('\\Awf\\Event\\Dispatcher', array('trigger'), array($container));
        $dispatcher->expects($this->once())->method('trigger')->withConsecutive(
            array($this->equalTo('buildCountQuery'))
        );

        ReflectionHelper::setValue($model, 'behavioursDispatcher', $dispatcher);

        $result = $model->count();

        $query = $db->getQuery(true)->select('COUNT(*)')->from('#__dbtest');
        $count = $db->setQuery($query)->loadResult();

        $this->assertEquals($count, $result, 'DataModel::count Failed to return the right amount of rows');
    }

    /**
     * @group           DataModel
     * @group           DataModelBuildQuery
     * @covers          Awf\Mvc\DataModel::buildQuery
     * @dataProvider    DataModelGenericDataprovider::getTestBuildQuery
     */
    public function testBuildQuery($test, $check)
    {
        // Please note that if you try to debug this test, you'll get a "Couldn't fetch mysqli_result" error
        // That's harmless and appears in debug only, you might want to suppress exception thowing
        //\PHPUnit_Framework_Error_Warning::$enabled = false;

        $before = 0;
        $after  = 0;
        $msg    = 'DataModel::buildQuery %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        // I am passing those methods so I can double check if the method is really called
        $methods = array(
            'onBeforeBuildQuery' => function() use(&$before){
                $before++;
            },
            'onAfterBuildQuery' => function() use(&$after){
                $after++;
            }
        );

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('getState'), array($container, $methods));
        $model->expects($check['filter'] ? $this->exactly(2) : $this->never())->method('getState')->willReturnCallback(
            function($state, $default) use ($test)
            {
                if($state == 'filter_order')
                {
                    if(isset($test['mock']['order']))
                    {
                        return $test['mock']['order'];
                    }
                }
                elseif($state == 'filter_order_Dir')
                {
                    if(isset($test['mock']['dir']))
                    {
                        return $test['mock']['dir'];
                    }
                }

                return $default;
            }
        );

        // Let's mock the dispatcher, too. So I can check if events are really triggered
        $dispatcher = $this->getMock('\\Awf\\Event\\Dispatcher', array('trigger'), array($container));
        $dispatcher->expects($this->exactly(2))->method('trigger')->withConsecutive(
            array($this->equalTo('onBeforeBuildQuery')),
            array($this->equalTo('onAfterBuildQuery'))
        );

        ReflectionHelper::setValue($model, 'behavioursDispatcher', $dispatcher);
        ReflectionHelper::setValue($model, 'whereClauses', $test['mock']['where']);

        $query = $model->buildQuery($test['override']);

        $select = $query->select->getElements();
        $table  = $query->from->getElements();
        $where  = $query->where ? $query->where->getElements() : array();
        $order  = $query->order ? $query->order->getElements() : array();

        $this->assertInstanceOf('\\Awf\\Database\\Query', $query, sprintf($msg, 'Should return an instance of Awf\\Database\\Query'));

        $this->assertEquals(array('*'), $select, sprintf($msg, 'Wrong SELECT clause'));
        $this->assertEquals(array('#__dbtest'), $table, sprintf($msg, 'Wrong FROM clause'));
        $this->assertEquals($check['where'], $where, sprintf($msg, 'Wrong WHERE clause'));
        $this->assertEquals($check['order'], $order, sprintf($msg, 'Wrong ORDER BY clause'));
    }

    /**
     * @group           DataModel
     * @group           DataModelGet
     * @covers          Awf\Mvc\DataModel::get
     * @dataProvider    DataModelGenericDataprovider::getTestGet
     */
    public function testGet($test, $check)
    {
        $msg = 'DataModel::get %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('getState', 'getItemsArray', 'eagerLoad'), array($container));

        $model->expects($this->any())->method('eagerLoad')->willReturn(null);
        $model->expects($this->any())->method('getState')->willReturnCallback(
            function($state, $default) use ($test)
            {
                if($state == 'limitstart')
                {
                    return $test['mock']['limitstart'];
                }
                elseif($state == 'limit')
                {
                    return $test['mock']['limit'];
                }

                return $default;
            }
        );

        $model->expects($this->once())->method('getItemsArray')
            ->with($this->equalTo($check['limitstart']), $this->equalTo($check['limit']))
            ->willReturn(array());

        $result = $model->get($test['override'], $test['limitstart'], $test['limit']);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel\\Collection', $result, sprintf($msg, 'Returned the wrong object'));
    }

    /**
     * @group           DataModel
     * @group           DataModelGetId
     * @covers          Awf\Mvc\DataModel::getId
     */
    public function testGetId()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);
        $model->find(2);

        $id = $model->getId();

        $this->assertEquals(2, $id, 'DataModel::getId Failed to return the correct id');
    }

    /**
     * @group           DataModel
     * @group           DataModelGetIdFieldName
     * @covers          Awf\Mvc\DataModel::getIdFieldName
     */
    public function testGetIdFieldName()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);
        $id = $model->getIdFieldName();

        $this->assertEquals('id', $id, 'DataModel::getIdFieldName Failed to return the table column id');
    }

    /**
     * @group           DataModel
     * @group           DataModelGetTableName
     * @covers          Awf\Mvc\DataModel::getTableName
     */
    public function testGetTableName()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);
        $table = $model->getTableName();

        $this->assertEquals('#__dbtest', $table, 'DataModel::getTableName Failed to return the table name');
    }

    /**
     * @group           DataModel
     * @group           DataModelAddBehaviour
     * @covers          Awf\Mvc\DataModel::addBehaviour
     * @dataProvider    DataModelGenericDataprovider::getTestAddBehaviour
     */
    public function testAddBehaviour($test, $check)
    {
        $msg = 'DataModel::addBehaviour %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        $result = $model->addBehaviour($test['class']);

        $dispatcher = $model->getBehavioursDispatcher();
        $attached   = $dispatcher->hasObserverClass($check['class']);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return and instance of itself'));
        $this->assertEquals($check['attached'], $attached, sprintf($msg, 'Failed to properly attach the behaviour'));
    }

    /**
     * @group           DataModel
     * @group           DataModelGetBehavioursDispatcher
     * @covers          Awf\Mvc\DataModel::getBehavioursDispatcher
     */
    public function testGetBehavioursDispatcher()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        $reflDisp = ReflectionHelper::getValue($model, 'behavioursDispatcher');
        $disp     = $model->getBehavioursDispatcher();

        $this->assertSame($reflDisp, $disp, 'DataModel::getBehavioursDispatcher did not return the same object');
    }

    /**
     * @group           DataModel
     * @group           DataModelOrderBy
     * @covers          Awf\Mvc\DataModel::orderBy
     * @dataProvider    DataModelGenericDataprovider::getTestOrderBy
     */
    public function testOrderBy($test, $check)
    {
        $msg    = 'DataModel::orderBy %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('setState'), array($container));
        $model->expects($this->exactly(2))->method('setState')->willReturn(null)->withConsecutive(
            array($this->equalTo('filter_order'), $this->equalTo($check['field'])),
            array($this->equalTo('filter_order_Dir'), $this->equalTo($check['dir']))
        );

        $result = $model->orderBy($test['field'], $test['dir']);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
    }

    /**
     * @group           DataModel
     * @group           DataModelSkip
     * @covers          Awf\Mvc\DataModel::skip
     * @dataProvider    DataModelGenericDataprovider::getTestSkip
     */
    public function testSkip($test, $check)
    {
        $msg = 'DataModel::skip %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('setState'), array($container));
        $model->expects($this->once())->method('setState')->willReturn(null)->with($this->equalTo('limitstart'), $this->equalTo($check['limitstart']));

        $result = $model->skip($test['limitstart']);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
    }

    /**
     * @group           DataModel
     * @group           DataModelTake
     * @covers          Awf\Mvc\DataModel::take
     * @dataProvider    DataModelGenericDataprovider::getTestTake
     */
    public function testTake($test, $check)
    {
        $msg = 'DataModel::take %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('setState'), array($container));
        $model->expects($this->once())->method('setState')->willReturn(null)->with($this->equalTo('limit'), $this->equalTo($check['limit']));

        $result = $model->take($test['limit']);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
    }

    /**
     * @group           DataModel
     * @group           DataModelToArray
     * @covers          Awf\Mvc\DataModel::toArray
     */
    public function testToarray()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);
        $model->find(1);

        $result = $model->toArray();

        $check = array(
            'id' => 1,
            'title' => 'Testing',
            'start_date' => '1980-04-18 00:00:00',
            'description' => 'one'
        );

        $this->assertEquals($check, $result, 'DataModel::toArray Failed to return the array format');
    }

    /**
     * @group           DataModel
     * @group           DataModelToJson
     * @covers          Awf\Mvc\DataModel::toJson
     * @dataProvider    DataModelGenericDataprovider::getTestToJson
     */
    public function testToJson($test)
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);
        $model->find(1);

        $result = $model->toJSon($test['pretty']);

        $check = array(
            'id' => '1',
            'title' => 'Testing',
            'start_date' => '1980-04-18 00:00:00',
            'description' => 'one'
        );

        if (defined('JSON_PRETTY_PRINT'))
        {
            $options = $test['pretty'] ? JSON_PRETTY_PRINT : 0;
        }
        else
        {
            $options = 0;
        }

        $check = json_encode($check, $options);

        $this->assertEquals($check, $result, 'DataModel::toJson Failed to return the correct result');
    }

    /**
     * @group           DataModel
     * @group           DataModelWhere
     * @covers          Awf\Mvc\DataModel::where
     * @dataProvider    DataModelGenericDataprovider::getTestWhere
     */
    public function testWhere($test, $check)
    {
        $msg = 'DataModel::where %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('getIdFieldName', 'setState', 'addBehaviour'), array($container));
        $model->expects($check['add'] ? $this->once() : $this->never())->method('addBehaviour')->willReturn(null);
        $model->expects($this->any())->method('getIdFieldName')->willReturn($test['mock']['id_field']);
        $model->expects($this->once())->method('setState')->with($this->equalTo($check['field']), $this->equalTo($check['options']));

        $dispatcher = $this->getMock('\\Awf\\Event\\Dispatcher', array('hasObserverClass'), array($container));
        $dispatcher->expects($this->any())->method('hasObserverClass')->willReturn($test['mock']['hasClass']);

        ReflectionHelper::setValue($model, 'behavioursDispatcher', $dispatcher);

        $result = $model->where($test['field'], $test['method'], $test['values']);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
    }

    /**
     * @group           DataModel
     * @group           DataModelWhere
     * @covers          Awf\Mvc\DataModel::where
     */
    public function testWhereException()
    {
        $this->setExpectedException('Awf\Mvc\DataModel\Exception\InvalidSearchMethod');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);
        $model->where('id', 'wrong', null);
    }

    /**
     * @group           DataModel
     * @group           DataModelWhereRaw
     * @covers          Awf\Mvc\DataModel::whereRaw
     */
    public function testWhereRaw()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        $result = $model->whereRaw('foo = bar');
        $where  = ReflectionHelper::getValue($model, 'whereClauses');

        $this->assertEquals(array('foo = bar'), $where, 'DataModel::whereRaw failed to save custom where clause');
        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, 'DataModel::whereRaw should return an instance of itself');
    }

    /**
     * @group           DataModel
     * @group           DataModelWith
     * @covers          Awf\Mvc\DataModel::with
     * @dataProvider    DataModelGenericDataprovider::getTestWith
     */
    public function testWith($test, $check)
    {
        $msg = 'DataModel::has %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $fakeRelationManager = new TestClosure(array(
            'getRelationNames' => function() use ($test){
                return $test['mock']['relNames'];
            }
        ));

        $model = new DataModelStub($container);

        ReflectionHelper::setValue($model, 'relationManager', $fakeRelationManager);

        $result = $model->with($test['relations']);

        $eager = ReflectionHelper::getValue($model, 'eagerRelations');

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals($check['eager'], $eager, sprintf($msg, 'Failed to set the eagerLoad relationships'));
    }
}
