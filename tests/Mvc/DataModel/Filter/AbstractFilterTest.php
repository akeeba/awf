<?php

namespace Awf\Tests\DataModel\AbstracFilter;

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
}
