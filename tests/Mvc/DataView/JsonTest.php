<?php

namespace Awf\Tests\DataView\Raw;

use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Stubs\Mvc\DataView\JsonStub;
use Awf\Application\Application;


require_once 'JsonDataprovider.php';

/**
 * @covers      Awf\Mvc\DataView\Json::<protected>
 * @covers      Awf\Mvc\DataView\Json::<private>
 * @package     Awf\Tests\DataView\Json
 */
class JsonTest extends DatabaseMysqliCase
{
    /**
     * @group           DataViewJson
     * @group           DataViewJsonConstruct
     * @covers          Awf\Mvc\DataView\Json::__construct
     * @dataProvider    JsonDataprovider::getTest__construct
     */
    public function test__construct($test, $check)
    {
        $msg       = 'DataView\Json::__construct %s - Case: '.$check['case'];
        $container = Application::getInstance()->getContainer();

        if(!is_null($test['hyper']))
        {
            $container['use_hypermedia'] = $test['hyper'];
        }

        $view = new JsonStub($container);

        $this->assertEquals($check['hyper'], $view->useHypermedia, sprintf($msg, 'Failed to set the hypermedia flag'));
    }
}
