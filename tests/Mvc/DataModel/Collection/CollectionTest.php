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
     * @covers          Collection::find
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