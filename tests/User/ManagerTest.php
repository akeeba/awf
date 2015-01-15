<?php

namespace Awf\Tests\User;

use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\User\Manager;
use Awf\User\User;
use Fakeapp\Application;

require_once 'ManagerDataprovider.php';

/**
 * @covers          Awf\User\Manager::<protected>
 * @covers          Awf\User\Manager::<private>
 */
class ManagerTest extends DatabaseMysqliCase
{
    /**
     * @covers          Awf\User\Manager::__construct
     */
    public function test__construct()
    {
        $manager = new Manager();

        $this->assertEquals('#__users', ReflectionHelper::getValue($manager, 'user_table'));
        $this->assertEquals('\\Awf\\User\\User', ReflectionHelper::getValue($manager, 'user_class'));
    }

    /**
     * @covers          Awf\User\Manager::getUser
     * @dataProvider    ManagerDataprovider::getTestGetUser
     */
    public function testGetUser($test, $check)
    {
        \PHPUnit_Framework_Error_Warning::$enabled = false;

        $msg     = 'Manager::getUser %s - Case: '.$check['case'];

        $container = Application::getInstance()->getContainer();
        $container->segment->set('user_id', $test['mock']['user_id']);

        $manager = new Manager($container);

        ReflectionHelper::setValue($manager, 'currentUser', $test['mock']['user']);
        ReflectionHelper::setValue($manager, 'privileges', $test['privileges']);
        ReflectionHelper::setValue($manager, 'authentications', $test['authentications']);

        $result = $manager->getUser($test['user']);

        if($check['result'])
        {
            $this->assertInstanceOf('Awf\User\User', $result, sprintf($msg, 'Returned the wrong result'));

            if($check['loaded'])
            {
                $this->assertNotEmpty($result->getName(), sprintf($msg, 'Failed to bind properties to the user'));
                $this->assertNotEmpty(ReflectionHelper::getValue($result, 'authentications'), sprintf($msg, 'Failed to attach auth plugins'));
                $this->assertNotEmpty(ReflectionHelper::getValue($result, 'privileges'), sprintf($msg, 'Failed to attach privileges'));
            }
        }
        else
        {
            $this->assertNull($result, sprintf($msg, 'Returned the wrong result'));
        }
    }

    /**
     * @covers          Awf\User\Manager::getUserByUsername
     * @dataProvider    ManagerDataprovider::getTestGetUserByUsername
     */
    public function testGetUserByUsername($test, $check)
    {
        $msg     = 'Manager::getUserByUsername %s - Case: '.$check['case'];
        $manager = new Manager();

        $result  = $manager->getUserByUsername($test['username']);

        if($check['result'])
        {
            $this->assertInstanceOf('\Awf\User\User', $result, sprintf($msg, 'Returned the wrong result'));
        }
        else
        {
            $this->assertNull($result, sprintf($msg, 'Returned the wrong result'));
        }
    }

    /**
     * @covers          Awf\User\Manager::loginUser
     * @dataProvider    ManagerDataprovider::getTestLoginUser
     */
    public function testLoginUser($test, $check)
    {
        $msg     = 'Manager::loginUser %s - Case: '.$check['case'];

        $user    = $this->getMock('Awf\User\User', array('verifyPassword', 'getId'));
        $user->expects($check['verify'] ? $this->once() : $this->never())->method('verifyPassword')->willReturn($test['mock']['verify']);
        $user->expects($this->any())->method('getId')->willReturn(1);

        $manager = $this->getMock('Awf\User\Manager', array('getUserByUsername'));
        $manager->expects($this->any())->method('getUserByUsername')->willReturn($test['mock']['username'] ? $user : null);

        if($check['exception'])
        {
            $this->setExpectedException('RuntimeException');
        }

        $manager->loginUser('username', 'password');

        $container = ReflectionHelper::getValue($manager, 'container');

        $this->assertNotEmpty(ReflectionHelper::getValue($manager, 'currentUser'), sprintf($msg, 'Failed to cache the current user'));
        $this->assertEquals(1, $container->segment->get('user_id', 0), sprintf($msg, 'Failed to save the user id in the session'));
    }

    /**
     * @covers          Awf\User\Manager::logoutUser
     */
    public function testLogoutUser()
    {
        $manager = new Manager();

        ReflectionHelper::setValue($manager, 'currentUser', new User());

        $manager->logoutUser();

        $container = ReflectionHelper::getValue($manager, 'container');
        $data      = ReflectionHelper::getValue($container->segment, 'data');

        $this->assertNull(ReflectionHelper::getValue($manager, 'currentUser'), 'Manager::logoutUser Failed to clear current user cache');
        $this->assertEmpty($data, 'Manager::logoutUser Failed to clear session cache');
    }

    /**
     * @covers          Awf\User\Manager::saveUser
     * @dataProvider    ManagerDataprovider::getTestSaveUser
     */
    public function testSaveUser($test, $check)
    {
        $msg = 'Manager::saveUser %s - Case: '.$check['case'];
        $db  = static::$container->db;

        // First of all let's get the default data
        $query = $db->getQuery(true)
                    ->select('*')
                    ->from($db->qn('#__users'))
                    ->where($db->qn('id').' = '.$db->q(1));
        $data = $db->setQuery($query)->loadObject();

        $user = $this->getMock('Awf\User\User', array('triggerEvent'));
        $user->bind($data);

        // Then let's bind our data
        $user->bind($test['data']);

        $manager = new Manager();
        $manager->saveUser($user);

        $query = $db->getQuery(true)
                    ->select('*')
                    ->from($db->qn('#__users'))
                    ->where($db->qn('id').' = '.$db->q($check['user_id']));
        $userCheck = $db->setQuery($query)->loadAssoc();

        $this->assertEquals($check['user'], $userCheck, sprintf($msg, 'Failed to save the user'));
    }

    /**
     * @covers          Awf\User\Manager::deleteUser
     * @dataProvider    ManagerDataprovider::getTestDeleteUser
     */
    public function testDeleteUser($test, $check)
    {
        $db      = static::$container->db;
        $msg     = 'Manager::deleteUser %s - Case: '.$check['case'];
        $manager = new Manager();

        // First of all let's add a new user inside the table
        $query = $db->getQuery(true)
            ->insert($db->qn('#__users'))
            ->columns(array(
                $db->qn('id'),
                $db->qn('username'),
                $db->qn('name'),
                $db->qn('email'),
                $db->qn('password'),
                $db->qn('parameters'),
            ))->values(
                $db->q(100) .','.
                $db->q('new') . ', ' .
                $db->q('new') . ', ' .
                $db->q('new@example.com') . ', ' .
                $db->q('password') . ', ' .
                $db->q('{}')
            );
        $db->setQuery($query)->execute();

        $result = $manager->deleteUser($test['id']);

        $query = $db->getQuery(true)
                    ->select('COUNT(*)')
                    ->from($db->qn('#__users'))
                    ->where($db->qn('id').' = '.$db->q(100));
        $user_id = $db->setQuery($query)->loadResult();

        if($check['delete'])
        {
            $this->assertEmpty($user_id, sprintf($msg, 'Failed to correctly delete the record'));
        }
        else
        {
            $this->assertEquals($user_id, 1, sprintf($msg, 'Failed to correctly delete the record'));
        }

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @covers          Awf\User\Manager::registerPrivilegePlugin
     */
    public function testRegisterPrivilegePlugin()
    {
        $manager = new Manager();

        $manager->registerPrivilegePlugin('foobar', 'test');

        $this->assertEquals(array('foobar' => 'test'), ReflectionHelper::getValue($manager, 'privileges'), 'Manager::registerPrivilegePlugin Failed to register the plugin');
    }

    /**
     * @covers          Awf\User\Manager::unregisterPrivilegePlugin
     */
    public function testUnregisterPrivilegePlugin()
    {
        $manager = new Manager();

        ReflectionHelper::setValue($manager, 'privileges', array('foobar' => 'test'));

        $manager->unregisterPrivilegePlugin('foobar');

        $privileges = ReflectionHelper::getValue($manager, 'privileges');

        $this->assertArrayNotHasKey('foobar', $privileges, 'Manager::unregisterPrivilegePlugin Failed to remove the plugin');
    }

    /**
     * @covers          Awf\User\Manager::registerAuthenticationPlugin
     */
    public function testRegisterAuthenticationPlugin()
    {
        $manager = new Manager();

        $manager->registerAuthenticationPlugin('foobar', 'test');

        $this->assertEquals(array('foobar' => 'test'), ReflectionHelper::getValue($manager, 'authentications'), 'Manager::registerAuthenticationPlugin Failed to register the plugin');
    }

    /**
     * @covers          Awf\User\Manager::unregisterAuthenticationPlugin
     */
    public function testUnregisterAuthenticationPlugin()
    {
        $manager = new Manager();

        ReflectionHelper::setValue($manager, 'authentications', array('foobar' => 'test'));

        $manager->unregisterAuthenticationPlugin('foobar');

        $authentications = ReflectionHelper::getValue($manager, 'authentications');

        $this->assertArrayNotHasKey('foobar', $authentications, 'Manager::unregisterAuthenticationPlugin Failed to remove the plugin');
    }

}