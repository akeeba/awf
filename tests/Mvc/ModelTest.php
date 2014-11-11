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
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\ModelStub;

require_once 'ModelDataprovider.php';

class ModelTest extends \PHPUnit_Framework_TestCase
{
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