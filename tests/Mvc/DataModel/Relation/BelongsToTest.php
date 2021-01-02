<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\DataModel\Relation\Relation\BelongsTo;

use Awf\Mvc\DataModel\Relation\BelongsTo;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;

require_once 'BelongsToDataprovider.php';

/**
 * @covers      Awf\Mvc\DataModel\Relation\BelongsTo::<protected>
 * @covers      Awf\Mvc\DataModel\Relation\BelongsTo::<private>
 * @package     Awf\Tests\DataModel\Relation\BelongsTo
 */
class BelongsToTest extends DatabaseMysqliCase
{
    /**
     * @group           BelongsTo
     * @group           BelongsToConstruct
     * @covers          Awf\Mvc\DataModel\Relation\BelongsTo::__construct
     * @dataProvider    BelongsToDataprovider::getTestConstruct
     */
    public function testConstruct($test, $check)
    {
        $msg = 'BelongsTo::__construct %s - Case: '.$check['case'];

        $model    = $this->buildModel();
        $relation = new BelongsTo($model, 'Fakeapp\Model\Parents', $test['local'], $test['foreign']);

        $this->assertEquals($check['local'], ReflectionHelper::getValue($relation, 'localKey'), sprintf($msg, 'Failed to set the local key'));
        $this->assertEquals($check['foreign'], ReflectionHelper::getValue($relation, 'foreignKey'), sprintf($msg, 'Failed to set the foreign key'));
    }

    /**
     * @group           BelongsTo
     * @group           BelongsToGetNew
     * @covers          Awf\Mvc\DataModel\Relation\BelongsTo::getNew
     */
    public function testGetNew()
    {
        $model = $this->buildModel();
        $relation = new BelongsTo($model, 'Fakeapp\Model\Parents');

        $this->setExpectedException('Awf\Mvc\DataModel\Relation\Exception\NewNotSupported');

        $relation->getNew();
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
                'idFieldName' => 'fakeapp_children_id',
                'tableName'   => '#__fakeapp_children'
            )
        ));

        return new $class($container);
    }
}
