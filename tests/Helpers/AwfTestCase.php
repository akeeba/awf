<?php

namespace Awf\Tests\Helpers;

use Fakeapp\Application;

abstract class AwfTestCase extends \PHPUnit_Framework_TestCase
{
    private $savedContainer;

    protected function setUp()
    {
        // I have to save the current container, since in several tests the Controller/Model/View will inject new
        // params, polluting following tests
        $app = Application::getInstance('fakeapp');
        $container = $app->getContainer();
        $this->savedContainer = is_object($container) ? clone $container : null;

        parent::setUp();
    }

    protected function tearDown()
    {
        // Let's revert back to the old container
        $app = Application::getInstance('fakeapp');
        ReflectionHelper::setValue($app, 'container', $this->savedContainer);

        parent::tearDown();
    }
}