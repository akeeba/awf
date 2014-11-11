<?php
/**
 * @package        awf
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 *
 * This class is adapted from Joomla! Framework
 */

namespace Awf\Tests\Model;

use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Mvc\ModelStub;

require_once 'ModelDataprovider.php';

class ModelTest extends \PHPUnit_Framework_TestCase
{
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