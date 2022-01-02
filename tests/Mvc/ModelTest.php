<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Model;

use Awf\Input\Input;
use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\ClosureHelper;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\ModelStub;

require_once 'ModelDataprovider.php';

/**
 * @covers      Awf\Mvc\Model::<protected>
 * @covers      Awf\Mvc\Model::<private>
 * @package     Awf\Tests\Model
 */
class ModelTest extends AwfTestCase
{
    /**
     * @group           Model
     * @group           ModelGetInstance
     * @covers          Awf\Mvc\Model::getInstance
     * @dataProvider    ModelDataprovider::getTestGetInstance
     */
    public function testGetInstance($test, $check)
    {
        $msg       = 'Model::getInstance %s - Case: '.$check['case'];
        $container = null;
        $counter   = array(
            'getClone'   => 0,
            'savestate'  => 0,
            'clearState' => 0,
            'clearInput' => 0
        );

        if($test['container'])
        {
            $setup = array(
                'input' => new Input(array(
                    'view' => $test['view']
                ))
            );

            // This is an Ugly Test™, however in this way we will be able to test if the option we are passing are considered
            if(isset($test['tempInstance']))
            {
                $setup['mvc_config']['modelTemporaryInstance'] = $test['tempInstance'];
            }

            if(isset($test['clearState']))
            {
                $setup['mvc_config']['modelClearState'] = $test['clearState'];
            }

            if(isset($test['clearInput']))
            {
                $setup['mvc_config']['modelClearInput'] = $test['clearInput'];
            }

            $container = new Container($setup);
        }

        $result  = ModelStub::getInstance($test['appName'], $test['model'], $container);

        if(isset($result->methodCounter))
        {
            $counter = $result->methodCounter;
        }

        $this->assertInstanceOf($check['result'], $result, sprintf($msg, 'Loaded the wrong controller'));
        $this->assertEquals($check['getClone'], $counter['getClone'], sprintf($msg, 'Invoked getClone the wrong number of times (modelTemporaryInstance option)'));
        $this->assertEquals($check['savestate'], $counter['savestate'], sprintf($msg, 'Invoked savestate the wrong number of times (modelTemporaryInstance option)'));
        $this->assertEquals($check['clearState'], $counter['clearState'], sprintf($msg, 'Invoked clearState the wrong number of times (modelClearState option)'));
        $this->assertEquals($check['clearInput'], $counter['clearInput'], sprintf($msg, 'Invoked clearInput the wrong number of times (modelClearInput option)'));
    }

    /**
     * @group           Model
     * @group           ModelGetTmpInstance
     * @covers          Awf\Mvc\Model::getTmpInstance
     */
    public function testGetTmpInstance()
    {
        // I will only check if the additional config options are set
        $model = ModelStub::getTmpInstance('', 'Foobar');

        $container = $model::$passedContainerStatic;

        $this->assertArrayHasKey('modelTemporaryInstance', $container['mvc_config']);
        $this->assertArrayHasKey('modelClearState', $container['mvc_config']);
        $this->assertArrayHasKey('modelClearInput', $container['mvc_config']);

        $this->assertTrue($container['mvc_config']['modelTemporaryInstance']);
        $this->assertTrue($container['mvc_config']['modelClearState']);
        $this->assertTrue($container['mvc_config']['modelClearInput']);
    }

    /**
     * @group           Model
     * @group           Model__construct
     * @covers          Awf\Mvc\Model::__construct
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

            // Let's save current app instances, I'll have to restore them later
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
     * @covers          Awf\Mvc\Model::getHash
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
     * @covers          Awf\Mvc\Model::setState
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
     * @covers          Awf\Mvc\Model::clearState
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
     * @covers          Awf\Mvc\Model::clearInput
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
     * @covers          Awf\Mvc\Model::getClone
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
     * @covers          Awf\Mvc\Model::__get
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
     * @covers          Awf\Mvc\Model::__set
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
     * @covers          Awf\Mvc\Model::__call
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
     * @covers          Awf\Mvc\Model::savestate
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
     * @covers          Awf\Mvc\Model::populateSavestate
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
     * @covers      Awf\Mvc\Model::setIgnoreRequest
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
     * @covers      Awf\Mvc\Model::getIgnoreRequest
     */
    public function testGetIgnoreRequest()
    {
        $model = new ModelStub();

        ReflectionHelper::setValue($model, '_ignoreRequest', 'foobar');

        $result = $model->getIgnoreRequest();

        $this->assertEquals('foobar', $result, 'Model::getIgnoreRequest returned the wrong value');
    }
}
