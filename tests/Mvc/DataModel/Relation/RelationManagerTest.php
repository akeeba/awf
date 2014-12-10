<?php
namespace Awf\Tests\DataModel\RelationManager;

use Awf\Mvc\DataModel\Collection;
use Awf\Mvc\DataModel\RelationManager;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\DataModelStub;
use Awf\Tests\Stubs\Utils\TestClosure;

require_once 'RelationManagerDataprovider.php';

/**
 * @covers      Awf\Mvc\DataModel\RelationManager::<protected>
 * @covers      Awf\Mvc\DataModel\RelationManager::<private>
 * @package     Awf\Tests\DataModel\RelationManager
 */
class RelationManagerTest extends DatabaseMysqliCase
{
    /**
     * @group       RelationManager
     * @group       RelationManagerRebase
     * @covers      RelationManager::rebase
     */
    public function testRebase()
    {
        $passedModel  = null;
        $fakeRelation = new TestClosure(array(
            'rebase' => function($closure, $model) use (&$passedModel){ $passedModel = $model;}
        ));

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $container2 = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'fakeapp_parent_id',
                'tableName'   => '#__fakeapp_parents'
            )
        ));

        $model      = new DataModelStub($container);
        $newModel   = new DataModelStub($container2);
        $relation   = new RelationManager($model);

        ReflectionHelper::setValue($relation, 'relations', array('test' => $fakeRelation));

        $relation->rebase($newModel);

        $newParent = ReflectionHelper::getValue($relation, 'parentModel');

        $this->assertSame($newModel, $passedModel, 'RelationManager::rebase Failed to pass the new model to the relations');
        $this->assertSame($newModel, $newParent, 'RelationManager::rebase Failed to save the new parent model');
    }

    /**
     * @group       RelationManager
     * @group       RelationManagerSetDataFromCollection
     * @covers      RelationManager::setDataFromCollection
     */
    public function testSetDataFromCollection()
    {
        $result       = false;
        $fakeRelation = new TestClosure(array(
            'setDataFromCollection' => function() use (&$result){ $result = true;}
        ));

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'fakeapp_parent_id',
                'tableName'   => '#__fakeapp_parents'
            )
        ));

        $collection = new Collection();
        $model      = new DataModelStub($container);
        $relation   = new RelationManager($model);

        ReflectionHelper::setValue($relation, 'relations', array('test' => $fakeRelation));

        $relation->setDataFromCollection('test', $collection);

        $this->assertTrue($result, 'RelationManager::setDataFromCollection Failed to invoke the correct method');
    }

    /**
     * @group       RelationManager
     * @group       RelationManagerSetDataFromCollection
     * @covers      RelationManager::setDataFromCollection
     */
    public function testSetDataFromCollectionException()
    {
        $this->setExpectedException('Awf\Mvc\DataModel\Relation\Exception\RelationNotFound');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'fakeapp_parent_id',
                'tableName'   => '#__fakeapp_parents'
            )
        ));

        $collection = new Collection();
        $model      = new DataModelStub($container);
        $relation   = new RelationManager($model);

        $relation->setDataFromCollection('test', $collection);
    }
}