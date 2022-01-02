<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\DataModel\Collection;

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
     * @group           CollectionMerge
     * @covers          Awf\Mvc\DataModel\Collection::merge
     */
    public function testMerge()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        $collection1 = new Collection($model->getItemsArray(0, 2));
        $collection2 = new Collection($model->getItemsArray(2, 2));

        $merge = $collection1->merge($collection2);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel\\Collection', $merge, 'Collection::merge Should return an instance of Collection');
        $this->assertCount(4, $merge, 'Collection::merge Failed to merge two arrays');
    }

    /**
     * @group           DataModel
     * @group           CollectionDiff
     * @covers          Awf\Mvc\DataModel\Collection::diff
     */
    public function testDiff()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        $collection1 = new Collection($model->getItemsArray());
        $collection2 = new Collection($model->getItemsArray(2, 2));

        $merge = $collection1->diff($collection2);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel\\Collection', $merge, 'Collection::diff Should return an instance of Collection');
        $this->assertCount(2, $merge, 'Collection::diff Failed to diff two arrays');
    }

    /**
     * @group           DataModel
     * @group           CollectionIntersect
     * @covers          Awf\Mvc\DataModel\Collection::intersect
     */
    public function testIntersect()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        $collection1 = new Collection($model->getItemsArray(1,2));
        $collection2 = new Collection($model->getItemsArray(0, 2));

        $merge = $collection1->intersect($collection2);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel\\Collection', $merge, 'Collection::intersect Should return an instance of Collection');
        $this->assertCount(1, $merge, 'Collection::intersect Failed to intersect two arrays');
    }

    /**
     * @group           DataModel
     * @group           CollectionModelUnique
     * @covers          Awf\Mvc\DataModel\Collection::unique
     */
    public function testUnique()
    {
        $items = $this->buildCollection();

        // Let's duplicate an item
        $items["1"] = $items[1];
        $collection = new Collection($items);
        $newCollection = $collection->unique();

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel\\Collection', $newCollection, 'Collection::unique should return an instance of Collection');
        $this->assertCount(4, $newCollection);
        $this->assertEquals(array(1 => 1, 2 => 2, 3 => 3, 4 => 4), $collection->modelKeys());
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
     * @group           DataModel
     * @group           CollectionCall
     * @covers          Awf\Mvc\DataModel\Collection::__call
     * @dataProvider    CollectionDataprovider::getTest__call
     */
    public function test__call($test, $check)
    {
        $checkCall = null;
        $items     = array();
        $msg       = 'Collection::__call %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        if($test['load'])
        {
            $model = new DataModelStub($container);
            $items = $model->getItemsArray(0, 1);
        }

        $collection = new Collection($items);

        switch ($test['arguments'])
        {
            case 0:
                $collection->dynamicCall();
                break;

            case 1:
                $collection->dynamicCall(1);
                break;

            case 2:
                $collection->dynamicCall(1, 1);
                break;

            case 3:
                $collection->dynamicCall(1, 1, 1);
                break;

            case 4:
                $collection->dynamicCall(1, 1, 1, 1);
                break;

            case 5:
                $collection->dynamicCall(1, 1, 1, 1, 1);
                break;

            case 6:
                $collection->dynamicCall(1, 1, 1, 1, 1, 1);
                break;

            case 7:
                $collection->dynamicCall(1, 1, 1, 1, 1, 1, 1);
                break;
        }

        if($item = $collection->first())
        {
            $checkCall = $item->dynamicCall;
        }

        $this->assertEquals($check['call'], $checkCall, sprintf($msg, 'Failed to correctly invoke DataModel methods'));
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
