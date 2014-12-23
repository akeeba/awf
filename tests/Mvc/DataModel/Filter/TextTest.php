<?php
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
    protected function setUp()
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
}