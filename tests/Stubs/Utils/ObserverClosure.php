<?php

namespace Awf\Tests\Stubs\Utils;

use Awf\Event\Observable;
use Awf\Event\Observer;

/**
 * This observer allows us to attach anonymous functions to the event, so we can dynamically perform all the tests we need
 *
 * @package Awf\Tests\Stubs\Utils
 */
class ObserverClosure extends Observer
{
    protected $methods = array();

    /**
     * Assigns callback functions to the class, the $methods array should be an associative one, where
     * the keys are the method names, while the values are the closure functions, e.g.
     *
     * array(
     *    'onBeforeMove' => function(){ return 'Foobar'; }
     * )
     *
     * @param Observable $subject
     * @param array $methods
     */
    public function __construct(Observable &$subject, array $methods = array())
    {
        parent::__construct($subject);

        foreach($methods as $method => $function)
        {
            $this->methods[$method] = $function;
        }
    }

    /*
     * The base object will perform a "method_exists" check, so we have to create them, otherwise they won't be invoked
     */
    public function onBeforeMove(&$subject, &$delta, &$where)
    {
        if(isset($this->methods['onBeforeMove']))
        {
            $func = $this->methods['onBeforeMove'];

            return call_user_func_array($func, array(&$subject, &$delta, &$where));
        }
    }

    public function onAfterMove()
    {
        if(isset($this->methods['onAfterMove']))
        {
            $func = $this->methods['onAfterMove'];

            return call_user_func_array($func, array());
        }
    }
}