<?php
namespace Awf\Tests\DataModel\Relation;

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
