<?php

namespace Awf\Tests\Helpers;

use Awf\Application\Application;
use Awf\Tests\Stubs\Fakeapp\Container as FakeContainer;

abstract class AwfTestCase extends \PHPUnit_Framework_TestCase
{
    // This should be removed and refactor the tests to not use it
    public static $container = null;

    private $whiteListTests = array();
    private $blackLisTests  = array();

    protected function setUp()
    {
        $class       = get_class($this);
        $parts       = explode('\\', $class);
        $currentTest = array_pop($parts);

        if($this->whiteListTests && !in_array($currentTest, $this->whiteListTests))
        {
            $this->markTestSkipped('Skipped due whitelist settings');
        }

        if(in_array($currentTest, $this->blackLisTests))
        {
            $this->markTestSkipped('Skipped due blacklist settings');
        }

        ReflectionHelper::setValue('\\Awf\\Application\\Application', 'instances', array());
        static::$container = new FakeContainer();

        Application::getInstance('Fakeapp', static::$container);

        parent::setUp();
    }
}