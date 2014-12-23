<?php
namespace Awf\Tests\DataModel\Text;

use Awf\Mvc\DataModel\Filter\Boolean;
use Awf\Tests\Database\DatabaseMysqliCase;

require_once 'BooleanDataprovider.php';

/**
 * @covers      Awf\Mvc\DataModel\Filter\Boolean::<protected>
 * @covers      Awf\Mvc\DataModel\Filter\Boolean::<private>
 * @package     Awf\Tests\DataModel\Filter\Boolean
 */
class BooleanTest extends DatabaseMysqliCase
{
    /**
     * @group           BooleanFilter
     * @group           BooleanFilterIsEmpty
     * @covers          Awf\Mvc\DataModel\Filter\Boolean::isEmpty
     * @dataProvider    BooleanDataprovider::getTestIsEmpty
     */
    public function testIsEmpty($test, $check)
    {
        $msg    = 'Boolean::isEmpty %s - Case: '.$check['case'];
        $filter = new Boolean(self::$driver, (object)array('name' => 'test', 'type' => 'tinyint(1)'));

        $result = $filter->isEmpty($test['value']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Failed to detect if a variable is empty'));
    }
}
