<?php
namespace Awf\Tests\DataModel\Number;

use Awf\Mvc\DataModel\Filter\Number;
use Awf\Tests\Database\DatabaseMysqliCase;

require_once 'NumberDataprovider.php';
/**
 * @covers      Awf\Mvc\DataModel\Filter\Number::<protected>
 * @covers      Awf\Mvc\DataModel\Filter\Number::<private>
 * @package     Awf\Tests\DataModel\Filter\Number
 */
class NumberTest extends DatabaseMysqliCase
{
    protected function setUp()
    {
        parent::setUp(false);
    }

    /**
     * @group       NumberFilter
     * @group       NumberFilterPartial
     * @covers      Awf\Mvc\DataModel\Filter\Number::partial
     */
    public function testPartial()
    {
        $field  = (object)array('name' => 'test', 'type' => 'int (10)');
        $filter = $this->getMock('Awf\Mvc\DataModel\Filter\Number', array('exact'), array(self::$driver, $field));

        // Should just invoke "exact"
        $filter->expects($this->once())->method('exact')->willReturn(null);

        $filter->partial(10);
    }

    /**
     * @group           NumberFilter
     * @group           NumberFilterBetween
     * @covers          Awf\Mvc\DataModel\Filter\Number::between
     * @dataProvider    NumberDataprovider::getTestBetween
     */
    public function testBetween($test, $check)
    {
        $msg    = 'Number::between %s - Case: '.$check['case'];
        $filter = new Number(self::$driver, (object)array('name' => 'test', 'type' => 'int (10)'));

        $result = $filter->between($test['from'], $test['to'], $test['inclusive']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Failed to return the correct SQL'));
    }

    /**
     * @group           NumberFilter
     * @group           NumberFilterOutside
     * @covers          Awf\Mvc\DataModel\Filter\Number::outside
     * @dataProvider    NumberDataprovider::getTestOutside
     */
    public function testOutside($test, $check)
    {
        $msg    = 'Number::outside %s - Case: '.$check['case'];
        $filter = new Number(self::$driver, (object)array('name' => 'test', 'type' => 'int (10)'));

        $result = $filter->outside($test['from'], $test['to'], $test['inclusive']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Failed to return the correct SQL'));
    }
}