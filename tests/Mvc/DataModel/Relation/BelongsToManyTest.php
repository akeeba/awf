<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\DataModel\Relation\Relation\BelongsToMany;

use Awf\Mvc\DataModel\Collection;
use Awf\Mvc\DataModel\Relation\BelongsToMany;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Fakeapp\Model\Groups;
use Fakeapp\Model\Parts;

require_once 'BelongsToManyDataprovider.php';

/**
 * @covers      Awf\Mvc\DataModel\Relation\BelongsToMany::<protected>
 * @covers      Awf\Mvc\DataModel\Relation\BelongsToMany::<private>
 * @package     Awf\Tests\DataModel\Relation\BelongsToMany
 */
class BelongsToManyTest extends DatabaseMysqliCase
{
	protected function setUp($resetContainer = true)
	{
		parent::setUp(false);
	}

	/**
     * @group           BelongsToMany
     * @group           BelongsToManyContruct
     * @covers          Awf\Mvc\DataModel\Relation\BelongsToMany::__construct
     * @dataProvider    BelongsToManyDataprovider::getTestConstruct
     */
    public function testConstruct($test, $check)
    {
        //\PHPUnit_Framework_Error_Warning::$enabled = false;

        $msg = 'BelongsToMany::__construct %s - Case: '.$check['case'];

        $model    = new Groups();
        $relation = new BelongsToMany($model, 'Fakeapp\Model\Parts', $test['local'], $test['foreign'], $test['pvTable'], $test['pvLocal'], $test['pvForeign']);

        $this->assertEquals($check['local'], ReflectionHelper::getValue($relation, 'localKey'), sprintf($msg, 'Failed to set the local key'));
        $this->assertEquals($check['foreign'], ReflectionHelper::getValue($relation, 'foreignKey'), sprintf($msg, 'Failed to set the foreign key'));
        $this->assertEquals($check['pvTable'], ReflectionHelper::getValue($relation, 'pivotTable'), sprintf($msg, 'Failed to set the pivot table'));
        $this->assertEquals($check['pvLocal'], ReflectionHelper::getValue($relation, 'pivotLocalKey'), sprintf($msg, 'Failed to set the pivot local key'));
        $this->assertEquals($check['pvForeign'], ReflectionHelper::getValue($relation, 'pivotForeignKey'), sprintf($msg, 'Failed to set the pivot foreign key'));
    }

    /**
     * @group           BelongsToMany
     * @group           BelongsToManyContruct
     * @covers          Awf\Mvc\DataModel\Relation\BelongsToMany::__construct
     */
    public function testConstructException()
    {
        $this->setExpectedException('Awf\Mvc\DataModel\Relation\Exception\PivotTableNotFound');

        $model    = new Groups();
        $relation = new BelongsToMany($model, 'Fakeapp\Model\Children');
    }

    /**
     * @group           BelongsToMany
     * @group           BelongsToManySetDataFromCollection
     * @covers          Awf\Mvc\DataModel\Relation\BelongsToMany::setDataFromCollection
     * @dataProvider    BelongsToManyDataprovider::getTestSetDataFromCollection
     */
    public function testSetDataFromCollection($test, $check)
    {
        $msg = 'BelongsToMany::setDataFromCollection %s - Case: '.$check['case'];

        $parts    = new Parts();
        $model    = new Groups();
        $model->find(2);
        $relation = new BelongsToMany($model, 'Fakeapp\Model\Parts');

        $items[0] = clone $parts;
        $items[0]->find(1);

        $items[1] = clone $parts;
        $items[1]->find(2);

        $items[2] = clone $parts;
        $items[2]->find(3);

        $data = new Collection($items);

        $relation->setDataFromCollection($data, $test['keymap']);

        $this->assertCount($check['count'], ReflectionHelper::getValue($relation, 'data'), sprintf($msg, ''));
    }

    /**
     * @group           BelongsToMany
     * @group           BelongsToManyGetCountSubquery
     * @covers          Awf\Mvc\DataModel\Relation\BelongsToMany::getCountSubquery
     */
    public function testGetCountSubquery()
    {
        $model    = new Groups();
        $relation = new BelongsToMany($model, 'Fakeapp\Model\Parts');

        $result = $relation->getCountSubquery();

        $check = '
SELECT COUNT(*)
FROM `#__fakeapp_parts` AS `reltbl`
INNER JOIN `#__fakeapp_parts_groups` AS `pivotTable` ON(`pivotTable`.`fakeapp_part_id` = `reltbl`.`fakeapp_part_id`)
WHERE `pivotTable`.`fakeapp_group_id` =`#__fakeapp_groups`.`fakeapp_group_id`';

        $this->assertInstanceOf('Awf\Database\Query', $result, 'BelongsToMany::getCountSubquery Should return an instance of Query');
        $this->assertEquals($check, (string) $result, 'BelongsToMany::getCountSubquery Failed to return the correct query');
    }

    /**
     * @group           BelongsToMany
     * @group           BelongsToManySaveAll
     * @covers          Awf\Mvc\DataModel\Relation\BelongsToMany::saveAll
     */
    public function testSaveAll()
    {
        $model    = new Groups();
        $model->find(1);
        $relation = new BelongsToMany($model, 'Fakeapp\Model\Parts');

        $items = array();

        // Let's mix datamodels with integers
        $items[0] = new Parts();
        $items[0]->find(1);
        $items[0]->description = 'Modified';

        for($i = 1; $i <= 55; $i++)
        {
            $items[] = $i;
        }

        $data = new Collection($items);

        ReflectionHelper::setValue($relation, 'data', $data);

        $relation->saveAll();

        $db = self::$driver;

        // First of all double check if the part was updated
        $query = $db->getQuery(true)
                    ->select($db->qn('description'))
                    ->from($db->qn('#__fakeapp_parts'))
                    ->where($db->qn('fakeapp_part_id').' = '.$db->q(1));
        $descr = $db->setQuery($query)->loadResult();

        $this->assertEquals('Modified', $descr, 'BelongsToMany::saveAll Failed to save item in the relationship');

        // Then let's check if all the items were saved in the glue table
        $query = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->qn('#__fakeapp_parts_groups'))
                    ->where($db->qn('fakeapp_group_id'). ' = '.$db->q(1));
        $count = $db->setQuery($query)->loadResult();

        $this->assertEquals(55, $count, 'BelongsToMany::saveAll Failed to save data inside the glue table');
    }

    /**
     * @group       BelongsToMany
     * @group       BelongsToManyGetNew
     * @covers      Awf\Mvc\DataModel\Relation\BelongsToMany::getNew
     */
    public function testGetNew()
    {
        $model    = new Groups();
        $relation = new BelongsToMany($model, 'Fakeapp\Model\Parts');

        $this->setExpectedException('Awf\Mvc\DataModel\Relation\Exception\NewNotSupported');

        $relation->getNew();
    }
}
