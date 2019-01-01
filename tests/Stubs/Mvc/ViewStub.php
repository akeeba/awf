<?php
/**
 * @package    awf
 * @copyright  Copyright (c)2014-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Mvc;

use Awf\Container\Container;
use Awf\Mvc\View;

class ViewStub extends View
{
    private   $methods = array();

    /**  @var null The container passed in the construct */
    public    $passedContainer = null;

    protected $name   = 'nestedset';

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
            $this->methods[$method] = $function;
        }

        // We will save the passed container in order to check it later
        if(is_object($container))
        {
            $this->passedContainer = clone $container;
        }
	    else
	    {
		    if (!defined('APATH_BASE'))
		    {
			    define('APATH_BASE', realpath(__DIR__ . '/../Stubs/Fakeapp'));
		    }

		    $_SERVER['HTTPS'] = 'off';
		    $_SERVER['HTTP_HOST'] = 'www.example.com';
		    $_SERVER['REQUEST_URI'] = '/foo/bar/baz.html?q=1';

		    $container = \Awf\Application\Application::getInstance('Fakeapp')->getContainer();
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

    /**
     * I have to hardcode these function since sometimes we do a method_exists check and that won't
     * trigger __call
     *
     * @return mixed
     */
    public function onBeforeDummy($tpl = null)
    {
        if(isset($this->methods['onBeforeDummy']))
        {
            $func = $this->methods['onBeforeDummy'];

            return call_user_func_array($func, array($this, $tpl));
        }

        return true;
    }

    public function onAfterDummy($tpl = null)
    {
        if(isset($this->methods['onAfterDummy']))
        {
            $func = $this->methods['onAfterDummy'];

            return call_user_func_array($func, array($this, $tpl));
        }

        return true;
    }
}

