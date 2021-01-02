<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\DataModel;

use Awf\Mvc\DataModel\Collection;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\DataModelStub;
use Awf\Tests\Stubs\Utils\TestClosure;
use Fakeapp\Application;

require_once 'RelationDataprovider.php';

/**
 * @covers      Awf\Mvc\DataModel::<protected>
 * @covers      Awf\Mvc\DataModel::<private>
 * @package     Awf\Tests\DataModel
 */
class DataModelRelationTest extends DatabaseMysqliCase
{
	protected function setUp($resetContainer = true)
	{
		parent::setUp(false);
	}

	/**
     * @group           DataModel
     * @group           DataModelSaveTouches
     * @covers          Awf\Mvc\DataModel::save
     */
    public function testSaveTouches()
    {
        //\PHPUnit_Framework_Error_Warning::$enabled = false;

        // I need to fake the user id, since in CLI I don't have one
        $fakeUserManager = new TestClosure(array(
            'getUser' => function(){
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
                'autoChecks'  => false,
                'idFieldName' => 'fakeapp_parent_id',
                'tableName'   => '#__fakeapp_parents',
                'relations'   => array(
                    'children' => array(
                        'type' => 'hasMany',
                        'foreignModelClass' => 'Fakeapp\Model\Children',
                        'localKey' => 'fakeapp_parent_id',
                        'foreignKey' => 'fakeapp_parent_id'
                    )
                )
            )
        ));

        $app = Application::getInstance('fakeapp');
        $fakeAppContainer = $app->getContainer();
        $fakeAppContainer->userManager = $fakeUserManager;

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('check', 'reorder'), array($container));
        $model->expects($this->any())->method('check')->willReturn(null);
        $model->expects($this->any())->method('reorder')->willReturn(null);

        ReflectionHelper::setValue($model, 'touches', array('children'));

        $model->find(1);
        $model->save(null, null, null);

        $db = self::$driver;
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->qn('#__fakeapp_children'))
            ->where($db->qn('fakeapp_parent_id').' = '.$db->q(1));
        $children = $db->setQuery($query)->loadObjectList();

        foreach($children as $child)
        {
            $this->assertEquals(99, $child->modified_by, 'DataModel::save Failed to touch "modified_by" field in children record');
            $this->assertNotEquals('0000-00-00 00:00:00', $child->modified_on, 'DataModel::save Failed to touch "modified_on" field in children record');
        }
    }

    /**
     * @group           DataModel
     * @group           DataModelPush
     * @covers          Awf\Mvc\DataModel::push
     * @dataProvider    DataModelRelationDataprovider::getTestPush
     */
    public function testPush($test, $check)
    {
        $msg       = 'DataModel::push %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('save'), array($container));
        $model->expects($this->any())->method('save')->willReturn(null);

        $relation = $this->getMock('\\Awf\\Mvc\\DataModel\\RelationManager', array('getRelationNames', 'save'), array($model));
        $relation->expects($this->any())->method('getRelationNames')->willReturn($test['mock']['names']);
        $relation->expects($this->any())->method('save')->with($this->callback(function($name) use (&$check){
            $current = array_shift($check['save']);
            return ($name == $current) && $current;
        }));

        ReflectionHelper::setValue($model, 'relationManager', $relation);
        ReflectionHelper::setValue($model, 'touches', $test['mock']['touches']);

        $result  = $model->push(null, '', null, $test['relations']);
        $touches = ReflectionHelper::getValue($model, 'touches');

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals($check['touches'], $touches, sprintf($msg, 'Failed to handle touches array'));
    }

    /**
     * @group           DataModel
     * @group           DataModelEagerLoad
     * @covers          Awf\Mvc\DataModel::eagerLoad
     * @dataProvider    DataModelRelationDataprovider::getTestEagerLoad
     */
    public function testEagerLoad($test, $check)
    {
        $globRelation = null;
        $items = array();
        $msg   = 'DataModel::eagerLoad %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        // The collection should contain items?
        if($test['items'])
        {
            $fakeRelationManager = new TestClosure(array(
                'setDataFromCollection' => function(){}
            ));

            $mockedItem = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('getRelations'), array($container));
            $mockedItem->expects($this->any())->method('getRelations')->willReturn($fakeRelationManager);

            $item = clone $mockedItem;
            $items[] = $item;
        }

        $collection = Collection::make($items);

        $model    = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('getRelations'), array($container));
        $relation = $this->getMock('\\Awf\\Mvc\\DataModel\\RelationManager', array('getData', 'getForeignKeyMap'), array($model));
        $relation->expects($this->any())->method('getForeignKeyMap')->willReturn(null);

        // Let's check if the logic of swapping the callback function when it's not callable works
        $relation->expects($check['getData'] ? $this->atLeastOnce() : $this->never())->method('getData')->with(
            $this->equalTo(isset($check['getData']['relation']) ? $check['getData']['relation'] : null),
            $this->callback(function($callback = '') use (&$check)
            {
                if($check['getData']['callback'] == 'function'){
                    $checkCallback = is_callable($callback);
                }
                else{
                    $checkCallback = ($callback == $check['getData']['callback']);
                }

                return $checkCallback;
            })
        );

        $model->expects($this->any())->method('getRelations')->willReturn($relation);

        ReflectionHelper::setValue($model, 'eagerRelations', $test['mock']['eager']);

        $result = $model->eagerLoad($collection, $test['relations']);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
    }

    /**
     * @group           DataModel
     * @group           DataModelHas
     * @covers          Awf\Mvc\DataModel::has
     * @dataProvider    DataModelRelationDataprovider::getTestHas
     */
    public function testHas($test, $check)
    {
        $msg = 'DataModel::has %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('addBehaviour'), array($container));
        $model->expects($check['add'] ? $this->once() : $this->never())->method('addBehaviour')->willReturn(null);

        $dispatcher = $this->getMock('\\Awf\\Event\\Dispatcher', array('hasObserverClass'), array($container));
        $dispatcher->expects($this->any())->method('hasObserverClass')->willReturn($test['mock']['hasClass']);

        ReflectionHelper::setValue($model, 'behavioursDispatcher', $dispatcher);
        ReflectionHelper::setValue($model, 'relationFilters', $test['mock']['filters']);


        $result  = $model->has($test['relation'], $test['method'], $test['values'], $test['replace']);
        $filters = $model->getRelationFilters();

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals($check['filters'], $filters, sprintf($msg, 'Failed to correctly add the filter'));
    }

    /**
     * @group           DataModel
     * @group           DataModelHas
     * @covers          Awf\Mvc\DataModel::has
     */
    public function testHasException()
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
        $model->has('posts', 'wrong', true);
    }

    /**
     * @group           DataModel
     * @group           DataModelGetRelations
     * @covers          Awf\Mvc\DataModel::getRelations
     */
    public function testGetRelations()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        $refl = ReflectionHelper::getValue($model, 'relationManager');
        $obj  = $model->getRelations();

        $this->assertSame($refl, $obj, 'DataModel::getRelations failed to return the internal object');
    }

    /**
     * @group           DataModel
     * @group           DataModelWhereHas
     * @covers          Awf\Mvc\DataModel::whereHas
     */
    public function testWhereHas()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('has'), array($container));
        $model->expects($this->any())->method('has')->with(
            $this->equalTo('dummy'),
            $this->equalTo('callback'),
            $this->callback(function($callback){
                return is_callable($callback);
            }),
            $this->equalTo(true)
        );

        $result = $model->whereHas('dummy', function(){}, true);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, 'DataModel::whereHas Should return an instance of itself');
    }

    /**
     * @group           DataModel
     * @group           DataModelGetRelationFilters
     * @covers          Awf\Mvc\DataModel::getRelationFilters
     */
    public function testGetRelationFilters()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        $filters = array('foo', 'bar');

        ReflectionHelper::setValue($model, 'relationFilters', $filters);
        $obj  = $model->getRelationFilters();

        $this->assertSame($filters, $obj, 'DataModel::relationFilters failed to return the internal array');
    }

    /**
     * @group           DataModel
     * @group           DataModelGetTouches
     * @covers          Awf\Mvc\DataModel::getTouches
     */
    public function testGetTouches()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        $touches = array('foo', 'bar');

        ReflectionHelper::setValue($model, 'touches', $touches);
        $obj  = $model->getTouches();

        $this->assertSame($touches, $obj, 'DataModel::getTouches failed to return the internal array');
    }
}
