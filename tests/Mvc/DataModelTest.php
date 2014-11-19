<?php

namespace Awf\Tests\DataModel;

use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\DataModelStub;

require_once 'DataModelDataprovider.php';

class DataModeltest extends DatabaseMysqliCase
{
    /**
     * @group           DataModel
     * @group           DataModelSetFieldValue
     * @covers          DataModel::setFieldValue
     * @dataProvider    DataModelDataprovider::getTestSetFieldValue
     */
    public function testSetFieldValue($test, $check)
    {
        $msg = 'DataModel::setFieldValue %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);

        ReflectionHelper::setValue($model, 'aliasFields', $test['mock']['alias']);

        $model->setFieldValue($test['name'], $test['value']);

        $data  = ReflectionHelper::getValue($model, 'recordData');
        $count = isset($model->methodCounter[$check['method']]) ? $model->methodCounter[$check['method']] : 0;

        if($check['set'])
        {
            $this->assertArrayHasKey($check['key'], $data, sprintf($msg, ''));
            $this->assertEquals($check['value'], $data[$check['key']], sprintf($msg, ''));
        }
        else
        {
            $this->assertArrayNotHasKey($check['key'], $data, sprintf($msg, ''));
        }

        $this->assertEquals($check['count'], $count, sprintf($msg, 'Called the magic setter the wrong amount of times'));
    }
}