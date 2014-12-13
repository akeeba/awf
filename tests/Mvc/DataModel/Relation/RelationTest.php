<?php
namespace Awf\Tests\DataModel\Relation;

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
