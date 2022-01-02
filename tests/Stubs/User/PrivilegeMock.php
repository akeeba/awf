<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\User;


use Awf\User\Privilege;
use Awf\User\UserInterface;

class PrivilegeMock extends Privilege
{
    private   $methods = array();

    /** @var array Simply counter to check if a specific function is called */
    public    $methodCounter = array();

    public function __construct(array $methods = array())
    {
        foreach($methods as $method => $function)
        {
            $this->methods[$method] = $function;
        }
    }

    public function setName($name)
    {
        isset($this->methodCounter[__FUNCTION__]) ? $this->methodCounter[__FUNCTION__]++ : $this->methodCounter[__FUNCTION__] = 1;

        if(isset($this->methods[__FUNCTION__]))
        {
            $func = $this->methods[__FUNCTION__];

            call_user_func_array($func, array());

            return;
        }

        parent::setName($name);
    }

    public function setUser(UserInterface &$user)
    {
        isset($this->methodCounter[__FUNCTION__]) ? $this->methodCounter[__FUNCTION__]++ : $this->methodCounter[__FUNCTION__] = 1;

        if(isset($this->methods[__FUNCTION__]))
        {
            $func = $this->methods[__FUNCTION__];

            call_user_func_array($func, array());

            return;
        }

        parent::setUser($user);
    }

    public function getPrivilegeNames()
    {
        isset($this->methodCounter[__FUNCTION__]) ? $this->methodCounter[__FUNCTION__]++ : $this->methodCounter[__FUNCTION__] = 1;

        if(isset($this->methods[__FUNCTION__]))
        {
            $func = $this->methods[__FUNCTION__];

            return call_user_func_array($func, array());
        }

        return parent::getPrivilegeNames();
    }

    public function getPrivilege($privilege, $default = false)
    {
        isset($this->methodCounter[__FUNCTION__]) ? $this->methodCounter[__FUNCTION__]++ : $this->methodCounter[__FUNCTION__] = 1;

        if(isset($this->methods[__FUNCTION__]))
        {
            $func = $this->methods[__FUNCTION__];

            return call_user_func_array($func, array());
        }

        return parent::getPrivilege($privilege, $default);
    }

    public function setPrivilege($privilege, $value)
    {
        isset($this->methodCounter[__FUNCTION__]) ? $this->methodCounter[__FUNCTION__]++ : $this->methodCounter[__FUNCTION__] = 1;

        if(isset($this->methods[__FUNCTION__]))
        {
            $func = $this->methods[__FUNCTION__];

            call_user_func_array($func, array());

            return;
        }

        parent::setPrivilege($privilege, $value);
    }

    public function onBeforeSave()
    {
        isset($this->methodCounter[__FUNCTION__]) ? $this->methodCounter[__FUNCTION__]++ : $this->methodCounter[__FUNCTION__] = 1;

        if(isset($this->methods[__FUNCTION__]))
        {
            $func = $this->methods[__FUNCTION__];

            call_user_func_array($func, array());

            return;
        }

        parent::onBeforeSave();
    }

    public function onAfterSave()
    {
        isset($this->methodCounter[__FUNCTION__]) ? $this->methodCounter[__FUNCTION__]++ : $this->methodCounter[__FUNCTION__] = 1;

        if(isset($this->methods[__FUNCTION__]))
        {
            $func = $this->methods[__FUNCTION__];

            call_user_func_array($func, array());

            return;
        }

        parent::onAfterSave();
    }

    public function onBeforeLoad(&$data)
    {
        isset($this->methodCounter[__FUNCTION__]) ? $this->methodCounter[__FUNCTION__]++ : $this->methodCounter[__FUNCTION__] = 1;

        if(isset($this->methods[__FUNCTION__]))
        {
            $func = $this->methods[__FUNCTION__];

            call_user_func_array($func, array());

            return;
        }

        parent::onBeforeLoad($data);
    }

    public function onAfterLoad()
    {
        isset($this->methodCounter[__FUNCTION__]) ? $this->methodCounter[__FUNCTION__]++ : $this->methodCounter[__FUNCTION__] = 1;

        if(isset($this->methods[__FUNCTION__]))
        {
            $func = $this->methods[__FUNCTION__];

            call_user_func_array($func, array());

            return;
        }

        parent::onAfterLoad();
    }
}
