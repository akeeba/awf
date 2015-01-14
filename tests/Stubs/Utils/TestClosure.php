<?php

namespace Awf\Tests\Stubs\Utils;

/**
 * We can use instances of this class in order to create "on-the-fly" methods, so we can inject our code
 * inside a function with the name the System Under Test is expecting, for example:
 *
 * $object = new AkeebaCoreClosure();
 * $object->foo = function(){ return "Hello World!"};
 *
 * $object->foo() // Returns "Hello World!"
 *
 * See: http://stackoverflow.com/a/2938020/485241
 */
class TestClosure
{
    /**
     * Assigns callback functions to the class, the $methods array should be an associative one, where
     * the keys are the method names, while the values are the closure functions, e.g.
     *
     * array(
     *    'foobar' => function(){ return 'Foobar'; }
     * )
     *
     * @param array $methods
     */
    public function __construct(array $methods = array())
    {
        foreach($methods as $method => $function)
        {
            $this->$method = $function;
        }
    }

    public function __call($method, $args)
    {
        if (isset($this->$method))
        {
            $func   = $this->$method;
            $pass[] = $this;

            // Pass everything by reference
            foreach($args as $arg)
            {
                $pass[] =& $arg;
            }

            $result = call_user_func_array($func, $pass);

            return $result;
        }
    }
}