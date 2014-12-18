<?php
namespace Awf\Tests\DataModel\Relation\Relation\BelongsToMany;

use Awf\Mvc\DataModel\Relation\BelongsToMany;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Fakeapp\Model\Groups;

require_once 'BelongsToManyDataprovider.php';

/**
 * @covers      Awf\Mvc\DataModel\Relation\BelongsToMany::<protected>
 * @covers      Awf\Mvc\DataModel\Relation\BelongsToMany::<private>
 * @package     Awf\Tests\DataModel\Relation\BelongsToMany
 */
class BelongsToManyTest extends DatabaseMysqliCase
{
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
