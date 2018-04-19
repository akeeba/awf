<?php
/**
 * @package        awf
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\DataView\Raw;

use Awf\Input\Input;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Stubs\Mvc\DataView\JsonStub;
use Awf\Application\Application;
use Fakeapp\Model\Parents;


require_once 'JsonDataprovider.php';

/**
 * @covers      Awf\Mvc\DataView\Json::<protected>
 * @covers      Awf\Mvc\DataView\Json::<private>
 * @package     Awf\Tests\DataView\Json
 */
class JsonTest extends DatabaseMysqliCase
{
	protected function setUp($resetContainer = true)
	{
		parent::setUp(false);
	}

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

    /**
     * @group           DataViewJson
     * @group           DataViewJsonDisplay
     * @covers          Awf\Mvc\DataView\Json::display
     * @dataProvider    JsonDataprovider::getTestDisplay
     */
    public function testDisplay($test, $check)
    {
        $msg  = 'DataView\Json::display %s - Case: '.$check['case'];

        $methods = array(
            'onBeforeFoobar' => function() use ($test) { return $test['mock']['before'];},
            'onAfterFoobar'  => function() use ($test) { return $test['mock']['after'];}
        );

        $view = new JsonStub(null, $methods);

        $view->setDoTask($test['task']);

        if($check['exception'])
        {
            $this->setExpectedException('Exception');
        }

        $result = $view->display();

        $this->assertTrue($result, sprintf($msg, 'Should return true'));
    }

    /**
     * @group           DataViewJson
     * @group           DataViewJsonDisplayBrowse
     * @covers          Awf\Mvc\DataView\Json::display
     * @covers          Awf\Mvc\DataView\Json::onBeforeBrowse
     * @dataProvider    JsonDataprovider::getTestDisplayBrowse
     */
    public function testDisplayBrowse($test, $check)
    {
        //\PHPUnit_Framework_Error_Warning::$enabled = false;

        $msg  = 'DataView\Json::display (browse task) %s - Case: '.$check['case'];

        $container = Application::getInstance()->getContainer();
	    $container->input->setData(array());

	    if (!empty($test['callback']))
	    {
		    $container->input->set('callback', $test['callback']);
	    }

        $view  = new JsonStub($container);
        $model = new Parents();

        $model->setState('limitstart', $test['limitstart']);
        $model->setState('limit', $test['limit']);

        if($test['item'])
        {
            $view->items = $model->getItemsArray($test['limitstart'], $test['limit']);
        }

        $view->setModel('parent', $model);

        // I have to setup some variables that are not present inside CLI environment
        if($test['hyper'])
        {
            $_SERVER['REQUEST_URI'] = '/';
            $_SERVER['HTTP_HOST']   = 'localhost';
        }

        $view->useHypermedia = $test['hyper'];
        $view->alreadyLoaded = $test['loaded'];
        $view->setDoTask('browse');

        $this->expectOutputString($check['output']);

        $result = $view->display();

        if($test['hyper'])
        {
            unset($_SERVER['HTTP_HOST']);
            unset($_SERVER['REQUEST_URI']);
        }

        $this->assertTrue($result, sprintf($msg, 'Should return true'));
    }

    /**
     * @group           DataViewJson
     * @group           DataViewJsonDisplayRead
     * @covers          Awf\Mvc\DataView\Json::display
     * @dataProvider    JsonDataprovider::getTestDisplayRead
     */
    public function testDisplayRead($test, $check)
    {
        $msg  = 'DataView\Json::display (read task) %s - Case: '.$check['case'];

        $container = Application::getInstance()->getContainer();
	    $container->input->setData(array());

	    $container->input->set('id', 2);

	    if (!empty($test['callback']))
	    {
		    $container->input->set('callback', $test['callback']);
	    }

        $view = new JsonStub($container);

        if($test['item'])
        {
            $model = new Parents();
            $model->find(3);
            $view->item = $model;
        }

        // I have to setup some variables that are not present inside CLI environment
        if($test['hyper'])
        {
            $_SERVER['REQUEST_URI'] = '/';
            $_SERVER['HTTP_HOST']   = 'localhost';
        }

        $view->useHypermedia = $test['hyper'];
        $view->alreadyLoaded = $test['loaded'];
        $view->setDoTask('read');

        $this->expectOutputString($check['output']);

        $result = $view->display();

        if($test['hyper'])
        {
            unset($_SERVER['HTTP_HOST']);
            unset($_SERVER['REQUEST_URI']);
        }

        $this->assertTrue($result, sprintf($msg, 'Should return true'));
    }
}
