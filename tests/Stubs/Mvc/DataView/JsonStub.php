<?php
/**
 * @package        awf
 * @copyright      2014-2016 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Mvc\DataView;

use Awf\Mvc\DataView\Json;
use Awf\Tests\Stubs\Fakeapp\Container;

class JsonStub extends Json
{
    private   $methods = array();

    protected $name   = 'parent';

    /**
     * Assigns callback functions to the class, the $methods array should be an associative one, where
     * the keys are the method names, while the values are the closure functions, e.g.
     *
     * array(
     *    'foobar' => function(){ return 'Foobar'; }
     * )
     *
     * @param Container $container
     * @param array     $methods
     */
    public function __construct(Container $container = null, array $methods = array())
    {
        foreach($methods as $method => $function)
        {
            $this->methods[$method] = $function;
        }

        parent::__construct($container);
    }

    public function __call($method, $args)
    {
        if (isset($this->methods[$method]))
        {
            $func = $this->methods[$method];

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
        if(isset($this->methods['getName']))
        {
            $func = $this->methods['getName'];

            return call_user_func_array($func, array());
        }

        return $this->name;
    }

    public function onBeforeFoobar()
    {
        if(isset($this->methods['onBeforeFoobar']))
        {
            $func = $this->methods['onBeforeFoobar'];

            return call_user_func_array($func, array());
        }

        return true;
    }

    public function onAfterFoobar()
    {
        if(isset($this->methods['onAfterFoobar']))
        {
            $func = $this->methods['onAfterFoobar'];

            return call_user_func_array($func, array());
        }

        return true;
    }
}