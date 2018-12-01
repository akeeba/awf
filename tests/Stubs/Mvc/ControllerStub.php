<?php
/**
 * @package        awf
 * @subpackage     tests.stubs
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Mvc;

use Awf\Container\Container;
use Awf\Mvc\Controller;

class ControllerStub extends Controller
{
    private   $mockedMethods = array();
    protected $name          = 'dummycontroller';

    /**
     * Assigns callback functions to the class, the $methods array should be an associative one, where
     * the keys are the method names, while the values are the closure functions, e.g.
     *
     * array(
     *    'foobar' => function(){ return 'Foobar'; }
     * )
     *
     * @param           $container
     * @param array     $methods
     */
    public function __construct(Container $container = null, array $methods = array())
    {
        foreach($methods as $method => $function)
        {
            $this->mockedMethods[$method] = $function;
        }

        parent::__construct($container);
    }

    public function __call($method, $args)
    {
        if (isset($this->mockedMethods[$method]))
        {
            $func = $this->mockedMethods[$method];

            // Let's pass an instance of ourself, so we can manipulate other closures
            array_unshift($args, $this);

            return call_user_func_array($func, $args);
        }
    }

    /**
     * A mocked object will have a random name, that won't match the regex expression in the parent.
     * To prevent exceptions, we have to manually set the name
     *
     * @return string
     */
    public function getName()
    {
        if(isset($this->mockedMethods['getName']))
        {
            $func = $this->mockedMethods['getName'];

            return call_user_func_array($func, array());
        }

        return parent::getName();
    }

    /**
     * I have to hardcode these function since sometimes we do a method_exists check and that won't
     * trigger __call
     *
     * @return mixed
     */
    public function onBeforeDummy()
    {
        if(isset($this->mockedMethods['onBeforeDummy']))
        {
            $func = $this->mockedMethods['onBeforeDummy'];

            return call_user_func_array($func, array($this));
        }

        return true;
    }

    public function onAfterDummy()
    {
        if(isset($this->mockedMethods['onAfterDummy']))
        {
            $func = $this->mockedMethods['onAfterDummy'];

            return call_user_func_array($func, array($this));
        }

        return true;
    }

    protected function imnothere()
    {
        // This function is here just to be sure that the Controller is not including it inside the task map
    }
}

