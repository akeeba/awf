<?php
/**
 * @package        awf
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\DataModel\Relation;

use Awf\Mvc\DataModel;
use Awf\Mvc\DataModel\Collection;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\RelationStub;

require_once 'RelationDataprovider.php';

/**
 * @covers      Awf\Mvc\DataModel\Relation::<protected>
 * @covers      Awf\Mvc\DataModel\Relation::<private>
 * @package     Awf\Tests\DataModel\Relation
 */
class RelationTest extends DatabaseMysqliCase
{
	protected function setUp($resetContainer = true)
	{
		parent::setUp(false);
	}

	/**
     * @group           Relation
     * @group           RelationContruct
     * @covers          Awf\Mvc\DataModel\Relation::__construct
     */
    public function testConstruct()
    {
        $msg      = 'Reflection::__construct %s';
        $model    = $this->buildModel();
        $relation = new RelationStub($model, 'Fakeapp\Model\Children', 'localkey', 'foreignkey', 'pivotTable', 'pvLocal', 'pvForeign');

        $this->assertSame($model, ReflectionHelper::getValue($relation, 'parentModel'), sprintf($msg, 'Failed to set the parent model'));
        $this->assertEquals('Fakeapp\Model\Children', ReflectionHelper::getValue($relation, 'foreignModelClass'), sprintf($msg, 'Failed to set the foreign model'));
        $this->assertEquals('localkey', ReflectionHelper::getValue($relation, 'localKey'), sprintf($msg, 'Failed to set the local key'));
        $this->assertEquals('foreignkey', ReflectionHelper::getValue($relation, 'foreignKey'), sprintf($msg, 'Failed to set the foreign key'));
        $this->assertEquals('pivotTable', ReflectionHelper::getValue($relation, 'pivotTable'), sprintf($msg, 'Failed to set the pivot table'));
        $this->assertEquals('pvLocal', ReflectionHelper::getValue($relation, 'pivotLocalKey'), sprintf($msg, 'Failed to set the pivot local key'));
        $this->assertEquals('Fakeapp', ReflectionHelper::getValue($relation, 'foreignModelApp'), sprintf($msg, 'Failed to set the foreign model app'));
        $this->assertEquals('Children', ReflectionHelper::getValue($relation, 'foreignModelName'), sprintf($msg, 'Failed to set the foreign model name'));
    }

    /**
     * @group           Relation
     * @group           RelationReset
     * @covers          Awf\Mvc\DataModel\Relation::reset
     */
    public function testReset()
    {
        $msg = 'Relation::reset %s';

        $model    = $this->buildModel();
        $relation = new RelationStub($model, 'Fakeapp\Model\Children');

        ReflectionHelper::setValue($relation, 'data', array(1,2,3));
        ReflectionHelper::setValue($relation, 'foreignKeyMap' ,array(1,2,3));

        $result = $relation->reset();

        $this->assertInstanceOf('Awf\Mvc\DataModel\Relation', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEmpty(ReflectionHelper::getValue($relation, 'data'), sprintf($msg, 'Should empty the internal data'));
        $this->assertEmpty(ReflectionHelper::getValue($relation, 'foreignKeyMap'), sprintf($msg, 'Should empty the foreign key map'));
    }

    /**
     * @group           Relation
     * @group           RelationRebase
     * @covers          Awf\Mvc\DataModel\Relation::rebase
     */
    public function testRebase()
    {
        $model    = $this->buildModel();
        $relation = $this->getMock('Awf\Tests\Stubs\Mvc\RelationStub', array('reset'), array($model, 'Fakeapp\Model\Children'));
        $relation->expects($this->any())->method('reset')->willReturnSelf();

        $newModel = $this->buildModel('\Fakeapp\Model\Datafoobars');

        $result = $relation->rebase($newModel);

        $this->assertInstanceOf('Awf\Mvc\DataModel\Relation', $result, 'Relation::rebase should return an instance of itself');
        $this->assertSame($newModel, ReflectionHelper::getValue($relation, 'parentModel'), 'Relation::rebase Failed to change the parent model');
    }

    /**
     * @group           Relation
     * @group           RelationGetData
     * @covers          Awf\Mvc\DataModel\Relation::getData
     * @dataProvider    RelationDataprovider::getTestGetData
     */
    public function testGetData($test, $check)
    {
        $msg = 'Relation::getData %s - Case: '.$check['case'];
        $applyCallback = false;

        $model    = $this->buildModel();
        $relation = new RelationStub($model, 'Fakeapp\Model\Children');
        $relation->setupMocks(array(
            'filterForeignModel' => function() use($test){
                return $test['mock']['filter'];
            }
        ));

        ReflectionHelper::setValue($relation, 'data', $test['mock']['data']);

        $callable = function() use(&$applyCallback){
            $applyCallback = true;
        };

        $result = $relation->getData($callable);

        $instanceok = false;

        if($result instanceof Collection || $result instanceof DataModel)
        {
            $instanceok = true;
        }

        $this->assertTrue($instanceok, sprintf($msg, 'Should return an instance of Collection or DataModel'));
        $this->assertEquals($check['applyCallback'], $applyCallback, sprintf($msg, 'Failed to correctly apply the callback'));
        $this->assertCount($check['count'], $result, sprintf($msg, 'Failed to return the correct amount of data'));
    }

    /**
     * @group           Relation
     * @group           RelationSetDataFromCollection
     * @covers          Awf\Mvc\DataModel\Relation::setDataFromCollection
     */
    public function testSetDataFromCollection()
    {
        //\PHPUnit_Framework_Error_Warning::$enabled = false;

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'fakeapp_child_id',
                'tableName'   => '#__fakeapp_children'
            )
        ));

        $childrenModel = new \Fakeapp\Model\Children($container);
        $model         = $this->buildModel();

        $model->find(2);
        $relation = new RelationStub($model, 'Fakeapp\Model\Children', 'fakeapp_parent_id', 'fakeapp_parent_id');

        $items[0] = clone $childrenModel;
        $items[1] = clone $childrenModel;

        $items[0]->find(1); // This child record IS NOT related to the current parent
        $items[1]->find(3); // This child record IS related to the current parent

        $collection = new Collection($items);

        $relation->setDataFromCollection($collection);

        $data = ReflectionHelper::getValue($relation, 'data');

        // I should have only one record, since the other one is not related to the current parent model
        $this->assertCount(1, $data);
    }

    /**
     * @group           Relation
     * @group           RelationSaveAll
     * @covers          Awf\Mvc\DataModel\Relation::saveAll
     */
    public function testSaveAll()
    {
        $model    = $this->buildModel();
        $relation = new RelationStub($model, 'Fakeapp\Model\Children');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'fakeapp_parent_id',
                'tableName'   => '#__fakeapp_parents'
            )
        ));

        $item = $this->getMock('\Awf\Tests\Stubs\Mvc\DataModelStub', array('save'), array($container));
        $item->expects($this->once())->method('save')->willReturn(null);

        $collection = new Collection(array($item));

        ReflectionHelper::setValue($relation, 'data', $collection);

        $relation->saveAll();
    }

    /**
     * @group           Relation
     * @group           RelationGetforeignKeyMap
     * @covers          Awf\Mvc\DataModel\Relation::getForeignKeyMap
     */
    public function testGetForeignKeyMap()
    {
        $model    = $this->buildModel();
        $relation = new RelationStub($model, 'Fakeapp\Model\Children');

        $keymap = array(1,2,3);

        ReflectionHelper::setValue($relation, 'foreignKeyMap', $keymap);

        $result = $relation->getForeignKeyMap();

        $this->assertEquals($keymap, $result, 'Relation::getForeignKeyMap Returned the wrong result');
    }

    /**
     * @param   string    $class
     *
     * @return \Awf\Mvc\DataModel
     */
    protected function buildModel($class = null)
    {
        if(!$class)
        {
            $class = '\Awf\Tests\Stubs\Mvc\DataModelStub';
        }

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'fakeapp_parent_id',
                'tableName'   => '#__fakeapp_parents'
            )
        ));

        return new $class($container);
    }
}
