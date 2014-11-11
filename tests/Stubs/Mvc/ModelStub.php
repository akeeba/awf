<?php
/**
 * @package        awf
 * @subpackage     tests.stubs
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Mvc;

use Awf\Container\Container;
use Awf\Mvc\Model;

class ModelStub extends Model
{
    private   $methods = array();

    /**  @var null The container passed in the construct */
    public    $passedContainer = null;

    /**  @var null The container passed in the getInstance method */
    public static $passedContainerStatic = null;

    /** @var array Simply counter to check if a specific function is called */
    public    $methodCounter = array(
        'getClone'   => 0,
        'savestate'  => 0,
        'clearState' => 0,
        'clearInput' => 0
    );

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

        parent::__construct($container);
    }

    public static function getInstance($appName = '', $modelName = '', $container = null)
    {
        if(is_object($container))
        {
            self::$passedContainerStatic = clone $container;
        }

        return parent::getInstance($appName, $modelName, $container);
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

        return parent::__call($method, $args);
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

    public function clearInput()
    {
        $this->methodCounter['clearInput']++;

        return parent::clearInput();
    }

    public function clearState()
    {
        $this->methodCounter['clearState']++;

        return parent::clearState();
    }

    public function getClone()
    {
        $this->methodCounter['getClone']++;

        return parent::getClone();
    }

    public function savestate($newState)
    {
        $this->methodCounter['savestate']++;

        return parent::savestate($newState);
    }
}

