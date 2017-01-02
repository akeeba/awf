<?php
/**
 * @package        awf
 * @subpackage     tests.stubs
 * @copyright      2014-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Mvc;

use Awf\Container\Container;
use Awf\Mvc\DataController;

class DataControllerStub extends DataController
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
}