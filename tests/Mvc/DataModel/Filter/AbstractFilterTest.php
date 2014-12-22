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
     * @group       AbstractFilter
     * @group       AbstractFilterConstruct
     * @covers      Awf\Mvc\DataModel\Filter\AbstractFilter::__construct
     * @dataProvider    AbstractFilterDataprovider::getTest__constructException
     */
    public function test__constructException($test)
    {
        $this->setExpectedException('InvalidArgumentException');

        $db = self::$driver;

        new FilterStub($db, $test['field']);
    }
}
