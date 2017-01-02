<?php
/**
 * @package        awf
 * @copyright      2014-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\User;

use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\User\PrivilegeMock;
use Awf\Tests\Stubs\Utils\TestClosure;
use Awf\User\User;

require_once 'PrivilegeDataprovider.php';

/**
 * @covers          Awf\User\Privilege::<protected>
 * @covers          Awf\User\Privilege::<private>
 */
class PrivilegeTest extends AwfTestCase
{
    /**
     * @covers          Awf\User\Privilege::setName
     */
    public function testSetName()
    {
        $priv = new PrivilegeMock();
        $priv->setName('foo');

        $this->assertEquals('foo', ReflectionHelper::getValue($priv, 'name'), 'Privilege::setName Failed to set the name');
    }

    /**
     * @covers          Awf\User\Privilege::setUser
     */
    public function testSetUser()
    {
        $user = new User();
        $priv = new PrivilegeMock();

        $priv->setUser($user);

        $this->assertSame($user, ReflectionHelper::getValue($priv, 'user'), 'Privilege::setUser Failed to set the user');
    }

    /**
     * @covers          Awf\User\Privilege::getPrivilegeNames
     */
    public function testGetPrivilegeNames()
    {
        $priv = new PrivilegeMock();
        $privileges = array('foo' => 'test', 'bar' => 'test');

        ReflectionHelper::setValue($priv, 'privileges', $privileges);

        $result = $priv->getPrivilegeNames();

        $this->assertEquals(array_keys($privileges), $result, 'Privilege::getPrivilegesName Returned the wrong result');
    }

    /**
     * @covers          Awf\User\Privilege::getPrivilege
     * @dataProvider    PrivilegeDataprovider::getTestGetPrivilege
     */
    public function testGetPrivilege($test, $check)
    {
        $msg  = 'Privilege::getPrivilege % s - Case: '.$check['case'];
        $priv = new PrivilegeMock();

        ReflectionHelper::setValue($priv, 'privileges', array('foobar' => 'test'));

        $result = $priv->getPrivilege($test['privilege'], 'default');

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @covers          Awf\User\Privilege::setPrivilege
     */
    public function testSetPrivilege()
    {
        $priv = new PrivilegeMock();

        $priv->setPrivilege('foobar', 'test');

        $privileges = ReflectionHelper::getValue($priv, 'privileges');

        $this->assertEquals(array('foobar' => 'test'), $privileges, 'Privilege::setPrivilege Failed to set the privilege');
    }

    /**
     * @covers          Awf\User\Privilege::onBeforeSave
     */
    public function testOnBeforeSave()
    {
        $checker = array();
        $priv    = new PrivilegeMock();

        // Let's use our Closure object to effectly mock everything
        $user = new TestClosure(array(
            'getParameters' => function() use(&$checker)
            {
                // Since we are returning a different object we have to mock it: we'll do it Inception-style
                // We have to go deeper!
                return new TestClosure(array(
                    'set' => function($self, $key, $value) use (&$checker){
                        $args = func_get_args();
                        array_shift($args);
                        $checker = $args;
                    }
                ));
            }
        ));

        ReflectionHelper::setValue($priv, 'name', 'login');
        ReflectionHelper::setValue($priv, 'user', $user);
        ReflectionHelper::setValue($priv, 'privileges', array('foobar' => 'test'));

        $priv->onBeforeSave();

        $this->assertEquals(array('acl.login.foobar', 'test'), $checker, 'Privilege::onBeforeSave Failed to set the correct privilege');
    }

    /**
     * @covers          Awf\User\Privilege::onAfterLoad
     */
    public function testOnAfterLoad()
    {
        $priv    = new PrivilegeMock();

        // Let's use our Closure object to effectly mock everything
        $user = new TestClosure(array(
            'getParameters' => function(){
                // Since we are returning a different object we have to mock it: we'll do it Inception-style
                // We have to go deeper!
                return new TestClosure(array(
                    'get' => function(){
                        return 'dummy';
                    }
                ));
            }
        ));

        ReflectionHelper::setValue($priv, 'name', 'login');
        ReflectionHelper::setValue($priv, 'user', $user);
        ReflectionHelper::setValue($priv, 'privileges', array('foobar' => 'test'));

        $priv->onAfterLoad();

        $privileges = ReflectionHelper::getValue($priv, 'privileges');

        $this->assertEquals($privileges['foobar'], 'dummy', 'Privilege::onAfterLoad Privilege should be overwritten by user loaded ones');
    }
}