<?php

namespace Awf\Tests\DataModel\Relation\Relation\HasMany;

use Awf\Mvc\DataModel\Relation\HasMany;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Stubs\Fakeapp\Container;

/**
 * @covers      Awf\Mvc\DataModel\Relation\HasMany::<protected>
 * @covers      Awf\Mvc\DataModel\Relation\HasMany::<private>
 * @package     Awf\Tests\DataModel\Relation\HasMany
 */
class HasManyTest extends DatabaseMysqliCase
{
    /**
     * @group           HasMany
     * @group           HasManyGetNew
     * @covers          Awf\Mvc\DataModel\Relation\HasMany::getNew
     */
    public function testGetNew()
    {
        $model    = $this->buildModel();
        $model->find(2);
        $relation = new HasMany($model, 'Fakeapp\Model\Children');

        $new = $relation->getNew();

        $this->assertInstanceOf('Fakeapp\Model\Children', $new);
        $this->assertSame(2, $new->getFieldValue('fakeapp_parent_id'), 'HasMany::getNew Failed to prime the new record');
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