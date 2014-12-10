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

        $container2 = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'fakeapp_parent_id',
                'tableName'   => '#__fakeapp_parents'
            )
        ));

        $model      = $this->buildModel();
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

        $collection = new Collection();
        $model      = $this->buildModel();
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

        $collection = new Collection();
        $model      = $this->buildModel();
        $relation   = new RelationManager($model);

        $relation->setDataFromCollection('test', $collection);
    }

    /**
     * @group       RelationManager
     * @group       RelationManagerRemoveRelation
     * @covers      RelationManager::removeRelation
     */
    public function testRemoveRelation()
    {
        $fakeRelation = new TestClosure(array(
            'setDataFromCollection' => function(){ }
        ));

        $model      = $this->buildModel();
        $relation   = new RelationManager($model);

        ReflectionHelper::setValue($relation, 'relations', array('test' => $fakeRelation));

        $result    = $relation->removeRelation('test');
        $relations = ReflectionHelper::getValue($relation, 'relations');

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, 'RelationManager::removeRelation Should return the parent model');
        $this->assertArrayNotHasKey('test', $relations, 'RelationManager::removeRelation Failed to remove the relation');
    }

    /**
     * @group       RelationManager
     * @group       RelationManagerResetRelations
     * @covers      RelationManager::resetRelations
     */
    public function testResetRelations()
    {
        $fakeRelation = new TestClosure(array(
            'setDataFromCollection' => function(){ }
        ));

        $model      = $this->buildModel();
        $relation   = new RelationManager($model);

        ReflectionHelper::setValue($relation, 'relations', array('test' => $fakeRelation));

        $relation->resetRelations();
        $relations = ReflectionHelper::getValue($relation, 'relations');

        $this->assertEmpty($relations, 'RelationManager::resetRelations Failed to reset the whole relations');
    }

    /**
     * @group       RelationManager
     * @group       RelationManagerGetRelationNames
     * @covers      RelationManager::getRelationNames
     */
    public function testGetRelationNames()
    {
        $model      = $this->buildModel();
        $relation   = new RelationManager($model);

        ReflectionHelper::setValue($relation, 'relations', array('test' => '', 'foobar' => ''));

        $names = $relation->getRelationNames();

        $this->assertEquals(array('test', 'foobar'), $names, 'RelationManager::getRelationNames Failed to return the name of the relations');
    }

    /**
     * @group       RelationManager
     * @group       RelationManagerGetRelation
     * @covers      RelationManager::getRelation
     */
    public function testGetRelation()
    {
        $model      = $this->buildModel();
        $relation   = new RelationManager($model);

        ReflectionHelper::setValue($relation, 'relations', array('test' => 'test'));

        $result = $relation->getRelation('test');

        $this->assertEquals('test', $result, 'RelationManager::getRelation Failed to return the relation');
    }

    /**
     * @group       RelationManager
     * @group       RelationManagerGetRelation
     * @covers      RelationManager::getRelation
     */
    public function testGetRelationException()
    {
        $this->setExpectedException('\Awf\Mvc\DataModel\Relation\Exception\RelationNotFound');

        $model      = $this->buildModel();
        $relation   = new RelationManager($model);

        $relation->getRelation('test');
    }

    /**
     * @group       RelationManager
     * @group       RelationManagerGetNew
     * @covers      RelationManager::getNew
     */
    public function testGetNew()
    {
        $result       = false;
        $fakeRelation = new TestClosure(array(
            'getNew' => function() use (&$result){ $result = true;}
        ));

        $model      = $this->buildModel();
        $relation   = new RelationManager($model);

        ReflectionHelper::setValue($relation, 'relations', array('test' => $fakeRelation));

        $relation->getNew('test');

        $this->assertTrue($result, 'RelationManager::getNew Failed to invoke the correct method');
    }

    /**
     * @group       RelationManager
     * @group       RelationManagerGetNew
     * @covers      RelationManager::getNew
     */
    public function testGetNewException()
    {
        $this->setExpectedException('\Awf\Mvc\DataModel\Relation\Exception\RelationNotFound');

        $model      = $this->buildModel();
        $relation   = new RelationManager($model);

        $relation->getNew('test');
    }

    /**
     * @group       RelationManager
     * @group       RelationManagerGetData
     * @covers      RelationManager::getData
     */
    public function testGetData()
    {
        $result       = false;
        $fakeRelation = new TestClosure(array(
            'getData' => function() use (&$result){ $result = true;}
        ));

        $model      = $this->buildModel();
        $relation   = new RelationManager($model);

        ReflectionHelper::setValue($relation, 'relations', array('test' => $fakeRelation));

        $relation->getData('test');

        $this->assertTrue($result, 'RelationManager::getData Failed to invoke the correct method');
    }

    /**
     * @group       RelationManager
     * @group       RelationManagerGetData
     * @covers      RelationManager::getData
     */
    public function testGetDataException()
    {
        $this->setExpectedException('\Awf\Mvc\DataModel\Relation\Exception\RelationNotFound');

        $model      = $this->buildModel();
        $relation   = new RelationManager($model);

        $relation->getData('test');
    }

    /**
     * @group       RelationManager
     * @group       RelationManagerGetForeignKeyMap
     * @covers      RelationManager::getForeignKeyMap
     */
    public function testGetForeignKeyMap()
    {
        $model      = $this->buildModel();
        $relation   = new RelationManager($model);

        $hasMany = $this->getMock('Awf\Mvc\DataModel\Relation\HasMany', array('getForeignKeyMap'), array($model, 'Fakeapp\Model\Children', ));
        $hasMany->expects($this->once())->method('getForeignKeyMap')->willReturn(null);

        ReflectionHelper::setValue($relation, 'relations', array('test' => $hasMany));

        $relation->getForeignKeyMap('test');
    }

    /**
     * @group       RelationManager
     * @group       RelationManagerGetForeignKeyMap
     * @covers      RelationManager::getForeignKeyMap
     */
    public function testGetForeignKeyMapException()
    {
        $this->setExpectedException('\Awf\Mvc\DataModel\Relation\Exception\RelationNotFound');

        $model      = $this->buildModel();
        $relation   = new RelationManager($model);

        $relation->getForeignKeyMap('test');
    }

    /**
     * @group           RelationManager
     * @group           RelationManagerIsMagicMethod
     * @covers          RelationManager::isMagicMethod
     * @dataProvider    RelationManagerDataprovider::getTestIsMagicMethod
     */
    public function testIsMagicMethod($test, $check)
    {
        $msg = 'RelationManager::isMagicMethod %s - Case: '.$check['case'];

        $model    = $this->buildModel();
        $relation = new RelationManager($model);

        ReflectionHelper::setValue($relation, 'relations', array('foobar' => ''));

        $result = $relation->isMagicMethod($test['method']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Failed to return the corret result'));
    }

    /**
     * @group           RelationManager
     * @group           RelationManagerIsMagicProperty
     * @covers          RelationManager::isMagicProperty
     * @dataProvider    RelationManagerDataprovider::getTestIsMagicProperty
     */
    public function testIsMagicProperty($test, $check)
    {
        $msg = 'RelationManager::isMagicProperty %s - Case: '.$check['case'];

        $model    = $this->buildModel();
        $relation = new RelationManager($model);

        ReflectionHelper::setValue($relation, 'relations', array('foobar' => ''));

        $result = $relation->isMagicProperty($test['name']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Failed to return the corret result'));
    }

    protected function buildModel()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'fakeapp_parent_id',
                'tableName'   => '#__fakeapp_parents'
            )
        ));

        return new DataModelStub($container);
    }
}