<?php
/**
 * @package        awf
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\DataModel\Text;

use Awf\Mvc\DataModel\Filter\Text;
use Awf\Tests\Database\DatabaseMysqliCase;

require_once 'TextDataprovider.php';
/**
 * @covers      Awf\Mvc\DataModel\Filter\Text::<protected>
 * @covers      Awf\Mvc\DataModel\Filter\Text::<private>
 * @package     Awf\Tests\DataModel\Filter\Text
 */
class TextTest extends DatabaseMysqliCase
{
    protected function setUp($resetContainer = true)
    {
        parent::setUp(false);
    }

    /**
     * @group       TextFilter
     * @group       TextFilterConstruct
     * @covers      Awf\Mvc\DataModel\Filter\Text::__construct
     */
    public function test__construct()
    {
        $filter = new Text(self::$driver, (object)array('name' => 'test', 'type' => 'varchar(10)'));

        $null_value = $filter->null_value;

        $this->assertSame('', $null_value, 'Text::__construct should set the null value to an empty string');
    }

    /**
     * @group           TextFilter
     * @group           TextFilterPartial
     * @covers          Awf\Mvc\DataModel\Filter\Text::partial
     * @dataProvider    TextDataprovider::getTestPartial
     */
    public function testPartial($test, $check)
    {
        $msg    = 'Text::partial %s - Case: '.$check['case'];
        $filter = new Text(self::$driver, (object)array('name' => 'test', 'type' => 'varchar(10)'));

        $result = $filter->partial($test['value']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Failed to build the correct SQL query'));
    }

    /**
     * @group           TextFilter
     * @group           TextFilterExact
     * @covers          Awf\Mvc\DataModel\Filter\Text::exact
     * @dataProvider    TextDataprovider::getTestExact
     */
    public function testExact($test, $check)
    {
        $msg    = 'Text::exact %s - Case: '.$check['case'];
        $filter = new Text(self::$driver, (object)array('name' => 'test', 'type' => 'varchar(10)'));

        $result = $filter->exact($test['value']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Failed to build the correct SQL query'));
    }

    /**
     * @group           TextFilter
     * @group           TextFilterBetween
     * @covers          Awf\Mvc\DataModel\Filter\Text::between
     */
    public function testBetween()
    {
        $filter = new Text(self::$driver, (object)array('name' => 'test', 'type' => 'varchar(10)'));

        $this->assertSame('', $filter->between('', ''), 'Text::between Should return an empty string');
    }

    /**
     * @group           TextFilter
     * @group           TextFilterOutside
     * @covers          Awf\Mvc\DataModel\Filter\Text::outside
     */
    public function testOutside()
    {
        $filter = new Text(self::$driver, (object)array('name' => 'test', 'type' => 'varchar(10)'));

        $this->assertSame('', $filter->outside('', ''), 'Text::outside Should return an empty string');
    }

    /**
     * @group           TextFilter
     * @group           TextFilterInterval
     * @covers          Awf\Mvc\DataModel\Filter\Text::interval
     */
    public function testInterval()
    {
        $filter = new Text(self::$driver, (object)array('name' => 'test', 'type' => 'varchar(10)'));

        $this->assertSame('', $filter->interval('', ''), 'Text::interval Should return an empty string');
    }
}
