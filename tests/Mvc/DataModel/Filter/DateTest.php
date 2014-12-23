<?php
namespace Awf\Tests\DataModel\Date;

use Awf\Mvc\DataModel\Filter\Date;
use Awf\Tests\Database\DatabaseMysqliCase;

require_once 'DateDataprovider.php';
/**
 * @covers      Awf\Mvc\DataModel\Filter\Date::<protected>
 * @covers      Awf\Mvc\DataModel\Filter\Date::<private>
 * @package     Awf\Tests\DataModel\Filter\Date
 */
class DateTest extends DatabaseMysqliCase
{
    protected function setUp()
    {
        parent::setUp(false);
    }

    /**
     * @covers      Awf\Mvc\DataModel\Filter\Date::getDefaultSearchMethod
     */
    public function testGetDefaultSearchMethod()
    {
        $filter = new Date(self::$driver, (object)array('name' => 'test', 'type' => 'datetime'));

        $this->assertEquals('exact', $filter->getDefaultSearchMethod());
    }

    /**
     * @group           DateFilter
     * @group           DateFilterBetween
     * @covers          Awf\Mvc\DataModel\Filter\Date::between
     * @dataProvider    DateDataprovider::getTestBetween
     */
    public function testBetween($test, $check)
    {
        $msg    = 'Date::between %s - Case: '.$check['case'];
        $filter = new Date(self::$driver, (object)array('name' => 'test', 'type' => 'datetime'));

        $result = $filter->between($test['from'], $test['to'], $test['include']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Failed to build the correct SQL query'));
    }

    /**
     * @group           DateFilter
     * @group           DateFilterOutside
     * @covers          Awf\Mvc\DataModel\Filter\Date::outside
     * @dataProvider    DateDataprovider::getTestOutside
     */
    public function testOutside($test, $check)
    {
        $msg    = 'Date::outside %s - Case: '.$check['case'];
        $filter = new Date(self::$driver, (object)array('name' => 'test', 'type' => 'datetime'));

        $result = $filter->outside($test['from'], $test['to'], $test['include']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Failed to build the correct SQL query'));
    }
}
