<?php
namespace Awf\Tests\DataModel\Number;

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
}