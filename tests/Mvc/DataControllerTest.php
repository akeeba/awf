<?php

namespace Awf\Tests\DataController;

use Awf\Container\Container;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Stubs\Mvc\DataControllerStub;

require_once 'DataControllerDataprovider.php';

class DataControllertest extends DatabaseMysqliCase
{
    /**
     * @group           DataController
     * @group           DataControllerGetModel
     * @covers          DataController::getModel
     * @dataProvider    DataControllerDataprovider::getTestGetModel
     */
    public function testGetModel($test, $check)
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $controller = new DataControllerStub($container);

        if($check['exception'])
        {
            $this->setExpectedException('Exception');
        }

        $model = $controller->getModel($test['model']);

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $model, 'DataController::getModel should return a DataModel');
    }
}