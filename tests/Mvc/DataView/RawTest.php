<?php

namespace Awf\Tests\DataView\Raw;

use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\DataView\RawStub;
use Fakeapp\Model\Children;

/**
 * @covers      Awf\Mvc\DataView\Raw::<protected>
 * @covers      Awf\Mvc\DataView\Raw::<private>
 * @package     Awf\Tests\DataView\Raw
 */
class RawTest extends DatabaseMysqliCase
{
    /**
     * @group       DataViewRaw
     * @group       DataViewRawOnBeforeBrowse
     * @covers      Awf\Mvc\DataView\Raw::onBeforeBrowse
     */
    public function testOnBeforeBrowse()
    {
        $msg = 'DataView\Raw::onBeforeBrowse %s';

        $model = new Children();
        $view  = new RawStub();

        $view->setModel($view->getName(), $model);

        $result = $view->onBeforeBrowse();

        $lists = ReflectionHelper::getValue($view, 'lists');

        $this->assertTrue($result, sprintf($msg, 'Should return true'));
        $this->assertInstanceOf('\stdClass', $lists, sprintf($msg, 'Lists property should be an object'));
        $this->assertObjectHasAttribute('limit', $lists);
        $this->assertObjectHasAttribute('limitStart', $lists);
        $this->assertEquals(0, $lists->limit);
        $this->assertEquals(0, $lists->limitStart);
        $this->assertEquals('fakeapp_child_id', $lists->order);
        $this->assertEquals('DESC', $lists->order_Dir);
        $this->assertObjectHasAttribute('items', $view);
        $this->assertObjectHasAttribute('itemsCount', $view);
        $this->assertObjectHasAttribute('pagination', $view);
    }
}