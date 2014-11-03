<?php
/**
 * @package        awf
 * @subpackage     tests.stubs
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Mvc;

use Awf\Container\Container;
use Awf\Mvc\TreeModel;

class TreeModelStub extends TreeModel
{
    protected $name = 'nestedset';

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
            $this->$method = $function;
        }

        parent::__construct($container);
    }

    /**
     * A mocked object will have a random name, that won't match the regex expression in the parent.
     * To prevent exceptions, we have to manually set the name
     *
     * @return string
     */
    public function getName()
    {
        if(isset($this->_getName))
        {
            $func = $this->_getName;

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
    public function onBeforeDelete()
    {
        if(isset($this->_onBeforeDelete))
        {
            $func = $this->_onBeforeDelete;

            return call_user_func_array($func, array());
        }

        return null;
    }

    public function onAfterDelete($oid)
    {
        if(isset($this->_onAfterDelete))
        {
            $func = $this->_onAfterDelete;

            return call_user_func_array($func, array());
        }

        return parent::onAfterDelete($oid);
    }
}

