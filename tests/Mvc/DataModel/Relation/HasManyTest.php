<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\DataModel\Relation\Relation\HasMany;

use Awf\Mvc\DataModel\Relation\HasMany;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;

require_once 'HasManyDataprovider.php';

/**
 * @covers      Awf\Mvc\DataModel\Relation\HasMany::<protected>
 * @covers      Awf\Mvc\DataModel\Relation\HasMany::<private>
 * @package     Awf\Tests\DataModel\Relation\HasMany
 */
class HasManyTest extends DatabaseMysqliCase
{
	protected function setUp($resetContainer = true)
	{
		parent::setUp(false);
	}

	/**
     * @group           HasMany
     * @group           HasManyConstruct
     * @covers          Awf\Mvc\DataModel\Relation\HasMany::__construct
     * @dataProvider    HasManyDataprovider::getTestConstruct
     */
    public function testConstruct($test, $check)
    {
        $msg = 'HasMany::__construct %s - Case: '.$check['case'];
        $model    = $this->buildModel();
        $relation = new HasMany($model, 'Fakeapp\Model\Children', $test['local'], $test['foreign']);

        $this->assertEquals($check['local'], ReflectionHelper::getValue($relation, 'localKey'), sprintf($msg, 'Failed to set the local key'));
        $this->assertEquals($check['foreign'], ReflectionHelper::getValue($relation, 'foreignKey'), sprintf($msg, 'Failed to set the foreign key'));
    }

    /**
     * @group           HasMany
     * @group           HasManyGetCountSubquery
     * @covers          Awf\Mvc\DataModel\Relation\HasMany::getCountSubquery
     */
    public function testGetCountSubquery()
    {
        //\PHPUnit_Framework_Error_Warning::$enabled = false;

        $savedInstances = ReflectionHelper::getValue('Awf\Application\Application', 'instances');

        $model    = $this->buildModel();
        $relation = new HasMany($model, 'Fakeapp\Model\Children');

        $query = $relation->getCountSubquery();

        ReflectionHelper::setValue('Awf\Application\Application', 'instances', $savedInstances);

        $check = '
SELECT COUNT(*)
FROM `#__fakeapp_children` AS `reltbl`
WHERE `reltbl`.`fakeapp_parent_id` = `#__fakeapp_parents`.`fakeapp_parent_id`';

        $this->assertEquals($check, $query, 'HasMany::getCountSubquery Returned the wrong query');
    }

    /**
     * @group           HasMany
     * @group           HasManyGetNew
     * @covers          Awf\Mvc\DataModel\Relation\HasMany::getNew
     */
    public function testGetNew()
    {
        $savedInstances = ReflectionHelper::getValue('Awf\Application\Application', 'instances');

        $model    = $this->buildModel();
        $model->find(2);
        $relation = new HasMany($model, 'Fakeapp\Model\Children');

        $new = $relation->getNew();

        ReflectionHelper::setValue('Awf\Application\Application', 'instances', $savedInstances);

        $this->assertInstanceOf('Fakeapp\Model\Children', $new);
        $this->assertEquals(2, $new->getFieldValue('fakeapp_parent_id'), 'HasMany::getNew Failed to prime the new record');
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
