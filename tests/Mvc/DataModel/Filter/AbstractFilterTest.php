<?php
/**
 * @package        awf
 * @copyright      2014-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\DataModel\AbstracFilter;

use Awf\Mvc\DataModel\Filter\AbstractFilter;
use Awf\Tests\Stubs\Mvc\DataModel\Filter\FilterStub;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;

require_once 'AbstractFilterDataprovider.php';
/**
 * @covers      Awf\Mvc\DataModel\Filter\AbstractFilter::<protected>
 * @covers      Awf\Mvc\DataModel\Filter\AbstractFilter::<private>
 * @package     Awf\Tests\DataModel\Filter\AbstractFilter
 */
class AbstractFilterTest extends DatabaseMysqliCase
{
    protected function setUp($resetContainer = true)
    {
        parent::setUp(false);
    }

    /**
     * @group       AbstractFilter
     * @group       AbstractFilterConstruct
     * @covers      Awf\Mvc\DataModel\Filter\AbstractFilter::__construct
     */
    public function test__construct()
    {
        $db = self::$driver;
        $field = (object) array(
            'name' => 'test',
            'type' => 'test'
        );

        $filter = new FilterStub($db, $field);

        $this->assertEquals('test', ReflectionHelper::getValue($filter, 'name'), 'AbstractFilter::__construct Failed to set the field name');
        $this->assertEquals('test', ReflectionHelper::getValue($filter, 'type'), 'AbstractFilter::__construct Failed to set the fiel type');
    }

    /**
     * @group           AbstractFilter
     * @group           AbstractFilterConstruct
     * @covers          Awf\Mvc\DataModel\Filter\AbstractFilter::__construct
     * @dataProvider    AbstractFilterDataprovider::getTest__constructException
     */
    public function test__constructException($test)
    {
        $this->setExpectedException('InvalidArgumentException');

        $db = self::$driver;

        new FilterStub($db, $test['field']);
    }

    /**
     * @group           AbstractFilter
     * @group           AbstractFilterIsEmpty
     * @covers          Awf\Mvc\DataModel\Filter\AbstractFilter::isEmpty
     * @dataProvider    AbstractFilterDataprovider::getTestIsEmpty
     */
    public function testIsEmpty($test, $check)
    {
        $msg = 'AbstractFilter::isEmpty %s - Case: '.$check['case'];

        $filter = new FilterStub(self::$driver, (object)array('name' => 'test', 'type' => 'test'));
        $filter->null_value = $test['null'];

        $result = $filter->isEmpty($test['value']);

        $this->assertSame($check['result'], $result, sprintf($msg, 'Failed to return the correct value'));
    }

    /**
     * @group           AbstractFilter
     * @group           AbstractFilterSearchMethods
     * @covers          Awf\Mvc\DataModel\Filter\AbstractFilter::getSearchMethods
     */
    public function testGetSearchMethod()
    {
        $filter = new FilterStub(self::$driver, (object)array('name' => 'test', 'type' => 'test'));

        $result = $filter->getSearchMethods();
        $result = array_values($result);

        $check = array('exact', 'partial', 'between', 'outside', 'interval', 'search');

        sort($result);
        sort($check);

        $this->assertEquals($check, $result, 'AbstractFilter::getSearchMethods Failed to detect the correct methods');
    }

    /**
     * @group           AbstractFilter
     * @group           AbstractFilterExact
     * @covers          Awf\Mvc\DataModel\Filter\AbstractFilter::exact
     * @dataProvider    AbstractFilterDataprovider::getTestExact
     */
    public function testExact($test, $check)
    {
        $msg = 'AbstractFilter::exact %s - Case: '.$check['case'];

        $field  = (object)array('name' => 'test', 'type' => 'varchar');
        $filter = $this->getMock('\Awf\Tests\Stubs\Mvc\DataModel\Filter\FilterStub', array('isEmpty', 'getFieldName', 'search'), array(self::$driver, $field));

        $filter->expects($this->any())->method('isEmpty')->willReturn($test['mock']['isEmpty']);
        $filter->expects($check['name'] ? $this->once() : $this->never())->method('getFieldName')->willReturn('`test`');
        $filter->expects($check['search'] ? $this->once() : $this->never())->method('search')->willReturn('search');

        $result = $filter->exact($test['value']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Return the wrong value'));
    }

    /**
     * @group           AbstractFilter
     * @group           AbstractFilterSearch
     * @covers          Awf\Mvc\DataModel\Filter\AbstractFilter::search
     * @dataProvider    AbstractFilterDataprovider::getTestSearch
     */
    public function testSearch($test, $check)
    {
        $msg = 'AbstractFilter::search %s - Case: '.$check['case'];

        $field  = (object)array('name' => 'test', 'type' => 'varchar');
        $filter = $this->getMock('\Awf\Tests\Stubs\Mvc\DataModel\Filter\FilterStub', array('isEmpty', 'getFieldName'), array(self::$driver, $field));

        $filter->expects($this->any())->method('isEmpty')->willReturn($test['mock']['isEmpty']);
        $filter->expects($this->any())->method('getFieldName')->willReturn('`test`');

        $result = $filter->search($test['value'], $test['operator']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Return the wrong value'));
    }

    /**
     * @group           AbstractFilter
     * @group           AbstractFilterGetFieldName
     * @covers          Awf\Mvc\DataModel\Filter\AbstractFilter::getFieldName
     */
    public function testGetFieldName()
    {
        $filter = new FilterStub(self::$driver, (object)array('name' => 'test', 'type' => 'test'));

        $result = $filter->getFieldName();

        $this->assertEquals('`test`', $result, 'AbstractFilter::getFieldName Failed to return the correct field name');
    }

    /**
     * @group           AbstractFilter
     * @group           AbstractFilterGetField
     * @covers          Awf\Mvc\DataModel\Filter\AbstractFilter::getField
     */
    public function testGetField()
    {
        $field = (object)array('name' => 'test', 'type' => 'int (10)');

        $result = AbstractFilter::getField($field, array('dbo' => self::$driver));

        $this->assertInstanceOf('\Awf\Mvc\DataModel\Filter\AbstractFilter', $result, 'AbstractFilter::getField Failed to return the correct filter');
    }

    /**
     * @group           AbstractFilter
     * @group           AbstractFilterGetField
     * @covers          Awf\Mvc\DataModel\Filter\AbstractFilter::getField
     * @dataProvider    AbstractFilterDataprovider::getTestGetFieldException
     */
    public function testGetFieldException($test)
    {
        $this->setExpectedException('InvalidArgumentException');

        AbstractFilter::getField($test['field'], array());
    }

    /**
     * @group           AbstractFilter
     * @group           AbstractFilterGetFieldType
     * @covers          Awf\Mvc\DataModel\Filter\AbstractFilter::getFieldType
     * @dataProvider    AbstractFilterDataprovider::getTestGetFieldType
     */
    public function testGetFieldType($test, $check)
    {
        $msg = 'AbstractFilter::getFieldType %s - Case: '.$check['case'];

        $result = AbstractFilter::getFieldType($test['type']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Failed to get the correct field type'));
    }
}
