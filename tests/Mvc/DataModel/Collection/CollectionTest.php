<?php

namespace Awf\Tests\DataModel;

use Awf\Mvc\DataModel\Collection;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\DataModelStub;

require_once 'CollectionDataprovider.php';

class CollectionTest extends DatabaseMysqliCase
{
    /**
     * @group           DataModel
     * @group           CollectionFind
     * @covers          Awf\Mvc\DataModel\Collection::find
     * @dataProvider    CollectionDataprovider::getTestFind
     */
    public function testFind($test, $check)
    {
        $msg   = 'Collection::find %s - Case: '.$check['case'];
        $items = $this->buildCollection();

        $collection = new Collection($items);

        $key = $test['key'] == 'object' ? $items[2] : $test['key'];

        $result = $collection->find($key, $test['default']);

        if($check['type'] == 'object')
        {
            $this->assertInstanceOf('Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of DataModel'));
            $this->assertEquals($check['result'], $result->getId(), sprintf($msg, 'Failed to return the correct item'));
        }
        else
        {
            $this->assertSame($check['result'], $result, sprintf($msg, 'Failed to return the correct item'));
        }
    }

    /**
     * @group           DataModel
     * @group           CollectionRemoveById
     * @covers          Awf\Mvc\DataModel\Collection::removeById
     * @dataProvider    CollectionDataprovider::getTestRemoveById
     */
    public function testRemoveById($test, $check)
    {
        $msg   = 'Collection::removeById %s - Case: '.$check['case'];
        $items = $this->buildCollection();

        $collection = new Collection($items);

        $key = $test['key'] == 'object' ? $items[2] : $test['key'];

        $collection->removeById($key);

        $this->assertArrayNotHasKey($check['key'], $collection, sprintf($msg, 'Failed to remove the item'));
    }

    /**
     * @group           DataModel
     * @group           CollectionAdd
     * @covers          Awf\Mvc\DataModel\Collection::add
     */
    public function testAdd()
    {
        $items = $this->buildCollection();

        $collection = new Collection($items);

        $result = $collection->add('foobar');
        $last   = $collection->pop();

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel\\Collection', $result, 'Collection::add Should return an instance of itself');
        $this->assertEquals('foobar', $last, 'Collection::add Failed to add an element');
    }

    /**
     * @group           DataModel
     * @group           CollectionContains
     * @covers          Awf\Mvc\DataModel\Collection::contains
     * @dataProvider    CollectionDataprovider::getTestContains
     */
    public function testContains($test, $check)
    {
        $msg   = 'Collection::contains %s - Case: '.$check['case'];
        $items = $this->buildCollection();

        $collection = new Collection($items);

        $result = $collection->contains($test['key']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Failed to return the correct value'));
    }

    /**
     * @group           DataModel
     * @group           CollectionFetch
     * @covers          Awf\Mvc\DataModel\Collection::fetch
     */
    public function testFetch()
    {
        $this->markTestSkipped('Skipped test until we decide what Collection::fetch should do');

        /*$items = $this->buildCollection();

        $collection = new Collection($items);

        $result = $collection->fetch(2);*/
    }

    /**
     * @group           DataModel
     * @group           CollectionMax
     * @covers          Awf\Mvc\DataModel\Collection::max
     */
    public function testMax()
    {
        $items = $this->buildCollection();

        $collection = new Collection($items);

        $result = $collection->max('id');

        // Let's get the maximum value directly from the db
        $db = self::$driver;

        $query = $db->getQuery(true)->select('MAX(id)')->from('#__dbtest');
        $max   = $db->setQuery($query)->loadResult();

        $this->assertEquals($max, $result, 'Collection::max Failed to return highest value');
    }

    /**
     * @group           DataModel
     * @group           CollectionMin
     * @covers          Awf\Mvc\DataModel\Collection::min
     */
    public function testMin()
    {
        $items = $this->buildCollection();

        $collection = new Collection($items);

        $result = $collection->min('id');

        // Let's get the maximum value directly from the db
        $db = self::$driver;

        $query = $db->getQuery(true)->select('MIN(id)')->from('#__dbtest');
        $min   = $db->setQuery($query)->loadResult();

        $this->assertEquals($min, $result, 'Collection::min Failed to return lowest value');
    }

    /**
     * @group           DataModel
     * @group           CollectionModelKeys
     * @covers          Awf\Mvc\DataModel\Collection::modelKeys
     */
    public function testModelKeys()
    {
        $items = $this->buildCollection();

        $collection = new Collection($items);

        $result = $collection->modelKeys();

        $this->assertEquals(array(1 => 1, 2 => 2, 3 => 3, 4 => 4), $result, 'Collection::modelKeys Failed to get the array of primary keys');
    }

    /**
     * @group           DataModel
     * @group           CollectionModelToBase
     * @covers          Awf\Mvc\DataModel\Collection::toBase
     */
    public function testToBase()
    {
        $items = $this->buildCollection();

        $collection = new Collection($items);

        $base = $collection->toBase();

        $this->assertEquals('Awf\\Utils\\Collection', get_class($base), 'Collection::toBase Should return a BaseCollection object');
    }

    /**
     * Build a collection of DataModels, used inside the tests
     *
     * return   DataModel[]
     */
    protected function buildCollection()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        return $model->getItemsArray();
    }
}