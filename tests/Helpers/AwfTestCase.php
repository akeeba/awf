<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Helpers;

use Awf\Application\Application;
use Awf\Tests\Stubs\Fakeapp\Container as FakeContainer;
use Awf\Uri\Uri;

abstract class AwfTestCase extends \PHPUnit_Framework_TestCase
{
    // This should be removed and refactor the tests to not use it
    public static $container = null;

    /** @var array If not empty, only tests inside this array would be executed, skipping the rest */
    private $whiteListTests = array();
    /** @var array Tests that should be skipped */
    private $blackLisTests  = array();

    /**
     * Executed before every test: we will reset the Container and check if we are asked to skip some tests
     *
     * @param bool $resetContainer  Should I reset the Container?
     */
    protected function setUp($resetContainer = true)
    {
        $class       = get_class($this);
        $parts       = explode('\\', $class);
        $currentTest = array_pop($parts);

        // Do I have to skip any tests? This is our latest resort when we have entangled tests: Test A is failing when the
        // whole suite is executed in a precise order, however we don't know WHICH tests is corrupting the environment.
        // We can't exclude any test since we would have a whole different suite, so the only solution is to SKIP them
        if($this->whiteListTests && !in_array($currentTest, $this->whiteListTests))
        {
            $this->markTestSkipped('Skipped due whitelist settings');
        }

        if(in_array($currentTest, $this->blackLisTests))
        {
            $this->markTestSkipped('Skipped due blacklist settings');
        }

        // Am I asked to reset the Application Container?
        if($resetContainer)
        {
            ReflectionHelper::setValue('\\Awf\\Application\\Application', 'instances', array());
            static::$container = new FakeContainer();

            Application::getInstance('Fakeapp', static::$container);
        }

        // Always reset the URI instances
        Uri::reset();

        parent::setUp();
    }
}
