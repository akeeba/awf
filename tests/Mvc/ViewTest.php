<?php
/**
 * @package        awf
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\View;

use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\ModelStub;
use Awf\Tests\Stubs\Mvc\ViewStub;

require_once 'ViewDataprovider.php';

class ViewTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group           View
     * @group           ViewEscape
     * @covers          View::escape
     */
    public function testEscape()
    {
        $view = new ViewStub();
        $escape = $view->escape('<>àè?"\'');

        $this->assertEquals("&lt;&gt;àè?&quot;'", $escape, 'View::escape Failed to escape the string');
    }

    /**
     * @group           View
     * @group           ViewGetModel
     * @covers          View::getModel
     * @dataProvider    ViewDataprovider::getTestGetModel
     */
    public function testGetModel($test, $check)
    {
        $msg        = 'View::getModel %s - Case: '.$check['case'];
        $container  = new Container();
        $controller = new ViewStub($container);

        ReflectionHelper::setValue($controller, 'defaultModel', $test['mock']['defaultModel']);
        ReflectionHelper::setValue($controller, 'name', $test['mock']['name']);
        ReflectionHelper::setValue($controller, 'modelInstances', $test['mock']['instances']);

        $result = $controller->getModel($test['name'], $test['config']);

        $config = $result->passedContainer['mvc_config'];

        $this->assertInstanceOf($check['result'], $result, sprintf($msg, 'Created the wrong model'));
        $this->assertEquals($check['config'], $config, sprintf($msg, 'Passed configuration was not considered'));
    }

    /**
     * @group           View
     * @group           ViewSetDefaultModel
     * @covers          View::setDefaultModel
     */
    public function testSetDefaultModel()
    {
        $model = new ModelStub();

        $view  = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\ViewStub', array('setDefaultModelName', 'setModel'));
        $view->expects($this->once())->method('setDefaultModelName')->with($this->equalTo('nestedset'));
        // The first param is NULL since we mocked the previous function and the property defaultModel is not set
        $view->expects($this->once())->method('setModel')->with($this->equalTo(null), $this->equalTo($model));

        $view->setDefaultModel($model);
    }

    /**
     * @group           View
     * @group           ViewSetDefaultModelName
     * @covers          View::setDefaultModelName
     */
    public function testDefaultModelName()
    {
        $view = new ViewStub();
        $view->setDefaultModelName('foobar');

        $name = ReflectionHelper::getValue($view, 'defaultModel');

        $this->assertEquals('foobar', $name, 'View::setDefaultModelName Failed to set the internal name');
    }

    /**
     * @group           View
     * @group           ViewSetModel
     * @covers          View::setModel
     */
    public function testSetModel()
    {
        $model      = new ModelStub();
        $controller = new ViewStub();
        $controller->setModel('foobar', $model);

        $models = ReflectionHelper::getValue($controller, 'modelInstances');

        $this->assertArrayHasKey('foobar', $models, 'View::setModel Failed to save the model');
        $this->assertSame($model, $models['foobar'], 'View::setModel Failed to store the same copy of the passed model');
    }
}
