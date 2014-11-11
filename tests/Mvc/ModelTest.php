<?php
/**
 * @package        awf
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 *
 * This class is adapted from Joomla! Framework
 */

namespace Awf\Tests\Model;

use Awf\Input\Input;
use Awf\Tests\Helpers\ClosureHelper;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\ModelStub;

require_once 'ModelDataprovider.php';

class ModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group           Model
     * @group           Model__construct
     * @covers          Model::__construct
     * @dataProvider    ModelDataprovider::getTest__construct()
     */
    public function test__construct($test, $check)
    {
        $containerSetup = array(
            'input' => new Input(array(
                'foo' => 'bar'
            ))
        );

        if($test['mvc'])
        {
            $containerSetup['mvc_config'] = $test['mvc'];
        }

        $msg        = 'Model::__construct %s - Case: '.$check['case'];
        $container  = null;
        $counterApp = 0;

        if($test['container'])
        {
            $container = new Container($containerSetup);
        }
        else
        {
            $fakeapp    = new ClosureHelper(array(
                'getContainer' => function () use(&$counterApp, &$containerSetup){
                    $counterApp++;

                    return new Container($containerSetup);
                }
            ));

            // Let's save current app istances, I'll have to restore them later
            $oldinstances = ReflectionHelper::getValue('\\Awf\\Application\\Application', 'instances');
            ReflectionHelper::setValue('\\Awf\\Application\\Application', 'instances', array('tests' => $fakeapp));
        }

        $model = new ModelStub($container);

        if(!$test['container'])
        {
            ReflectionHelper::setValue('\\Awf\\Application\\Application', 'instances', $oldinstances);
        }

        $state     = ReflectionHelper::getValue($model, 'state');
        $populate  = ReflectionHelper::getValue($model, '_state_set');
        $ignore    = ReflectionHelper::getValue($model, '_ignoreRequest');
        $input     = ReflectionHelper::getValue($model, 'input');

        $this->assertEquals(array('foo' => 'bar'), $input->getData(), sprintf($msg, 'Should use the passed input object'));
        $this->assertEquals($check['state'], $state, sprintf($msg, 'Failed to set the internal state object'));
        $this->assertEquals($check['populate'], $populate, sprintf($msg, 'Failed to set the internal state marker'));
        $this->assertEquals($check['ignore'], $ignore, sprintf($msg, 'Failed to set the internal state marker'));
        $this->assertEquals($check['counterApp'], $counterApp, sprintf($msg, 'Failed to correctly get the container from the Application'));
    }

    /**
     * @group           Model
     * @group           ModelGetHash
     * @covers          Model::getHash
     */
    public function testGetHash()
    {
        $model = new ModelStub();

        // Sadly I can't test for the internal cache, since the variable is declared as static local, so I can't manipulate it :(
        $hash = $model->getHash();

        $this->assertEquals('Fakeapp.nestedset.', $hash, 'Model::getHash returned the wrong value');
    }

    /**
     * @group           Model
     * @group           ModelSetState
     * @covers          Model::setState
     * @dataProvider    ModelDataprovider::getTestSetState
     */
    public function testSetState($test, $check)
    {
        $msg = 'Model::setState %s - Case: '.$check['case'];

        $model = new ModelStub();

        ReflectionHelper::setValue($model, 'state', $test['mock']['state']);

        $result = $model->setState($test['property'], $test['value']);

        $state  = ReflectionHelper::getValue($model, 'state');

        $this->assertEquals($check['state'], $state, sprintf($msg, 'Failed to set the property'));
        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong value'));
    }

    /**
     * @group           Model
     * @group           ModelClearState
     * @covers          Model::clearState
     */
    public function testClearState()
    {
        $model = new ModelStub();
        ReflectionHelper::setValue($model, 'state', (object) array('foo' => 'bar'));

        $result = $model->clearState();

        // Let's convert the object to an array, so I can assert that is empty
        $state = (array) ReflectionHelper::getValue($model, 'state');

        $this->assertInstanceOf('\\Awf\\Mvc\\Model', $result, 'Model::clearState should return an instance of itself');
        $this->assertEmpty($state, 'Model::clearState failed to clear the internal state');
    }

    /**
     * @group           Model
     * @group           ModelClearInput
     * @covers          Model::clearInput
     */
    public function testClearInput()
    {
        $container = new Container(array(
            'input' => new Input(array(
                'foo' => 'bar'
            ))
        ));

        $model = new ModelStub($container);

        $result = $model->clearInput();

        /** @var Input $input */
        $input = ReflectionHelper::getValue($model, 'input');
        $data  = $input->getData();

        $this->assertInstanceOf('\\Awf\\Mvc\\Model', $result, 'Model::clearInput should return an instance of itself');
        $this->assertEmpty($data, 'Model::clearInput should clear the internal input');
    }

    /**
     * @group           Model
     * @group           ModelGetClone
     * @covers          Model::getClone
     */
    public function testGetClone()
    {
        $model = new ModelStub();
        $clone = $model->getClone();

        $this->assertNotSame($model, $clone, 'Model::getClone failed to clone the current instance');
    }

    /**
     * @group           Model
     * @group           Model__get
     * @covers          Model::__get
     */
    public function test__get()
    {
        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\ModelStub', array('getState'));
        $model->expects($this->once())->method('getState')->with($this->equalTo('foo'))->willReturn('bar');

        $result = $model->foo;

        $this->assertEquals('bar', $result, 'Model::__get Returned the wrong value');
    }

    /**
     * @group           Model
     * @group           Model__set
     * @covers          Model::__set
     */
    public function test__set()
    {
        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\ModelStub', array('setState'));
        $model->expects($this->once())->method('setState')->with($this->equalTo('foo'), $this->equalTo('bar'));

        $result = $model->foo = 'bar';

        $this->assertEquals('bar', $result, 'Model::__set Returned the wrong value');
    }

    /**
     * @group           Model
     * @group           Model__call
     * @covers          Model::__call
     */
    public function test__call()
    {
        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\ModelStub', array('setState'));
        $model->expects($this->once())->method('setState')->with($this->equalTo('foo'), $this->equalTo('bar'));

        $result = $model->foo('bar');

        $this->assertInstanceOf('\\Awf\\Mvc\\Model', $result, 'Model::__call should return an istance of itself');
    }

    /**
     * @group           Model
     * @group           ModelSavestate
     * @covers          Model::savestate
     * @dataProvider    ModelDataprovider::getTestSavestate
     */
    public function testSaveState($test, $check)
    {
        $msg   = 'Model::savestate %s - Case: '.$check['case'];
        $model = new ModelStub();

        $result = $model->savestate($test['state']);
        $state  = ReflectionHelper::getValue($model, '_savestate');

        $this->assertInstanceOf('\\Awf\\Mvc\\Model', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertSame($check['state'], $state, sprintf($msg, 'Failed to set the savestate'));
    }

    /**
     * @group           Model
     * @group           ModelPopulateSavestate
     * @covers          Model::populateSavestate
     * @dataProvider    ModelDataprovider::getTestPopulatesavestate
     */
    public function testPopulateSavestate($test, $check)
    {
        $container = new Container(array(
            'input' => new Input(array(
                'savestate' => $test['state']
            ))
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\ModelStub', array('savestate'), array($container));

        $matcher = $this->never();

        if($check['savestate'])
        {
            $matcher = $this->once();
        }

        $model->expects($matcher)->method('savestate')->with($this->equalTo($check['state']));

        ReflectionHelper::setValue($model, '_savestate', $test['mock']['state']);

        $model->populateSavestate();
    }

    /**
     * @group       Model
     * @group       ModelSetIgnoreRequest
     * @covers      Model::setIgnoreRequest
     */
    public function testSetIgnoreRequest()
    {
        $model = new ModelStub();

        $result = $model->setIgnoreRequest(true);

        $ignore = ReflectionHelper::getValue($model, '_ignoreRequest');

        $this->assertInstanceOf('\\Awf\\Mvc\\Model', $result, 'Model::setIgnoreRequest should return an instance of itself');
        $this->assertEquals(true, $ignore, 'Model::setIgnoreRequest failed to set the flag');
    }

    /**
     * @group       Model
     * @group       ModelGetIgnoreRequest
     * @covers      Model::getIgnoreRequest
     */
    public function testGetIgnoreRequest()
    {
        $model = new ModelStub();

        ReflectionHelper::setValue($model, '_ignoreRequest', 'foobar');

        $result = $model->getIgnoreRequest();

        $this->assertEquals('foobar', $result, 'Model::getIgnoreRequest returned the wrong value');
    }
}