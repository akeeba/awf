<?php
/**
 * @package        awf
 * @copyright      2014-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\User;


use Awf\User\Authentication;
use Awf\User\UserInterface;

class AuthenticationMock extends Authentication
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

    public function onAuthentication($params = array())
    {
        isset($this->methodCounter[__FUNCTION__]) ? $this->methodCounter[__FUNCTION__]++ : $this->methodCounter[__FUNCTION__] = 1;

        if(isset($this->methods[__FUNCTION__]))
        {
            $func = $this->methods[__FUNCTION__];

            return call_user_func_array($func, array());
        }

        return parent::onAuthentication($params);
    }
}