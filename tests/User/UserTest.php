<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

// We will use the same namespace as the SUT, so when PHP will try to look for the native function, he will look inside
// this one before continuing
namespace Awf\User;

use Awf\Tests\Stubs\User\AuthenticationMock;
use Awf\Tests\Stubs\User\PrivilegeMock;
use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Utils\TestClosure;

global $mockUser;
global $stackUser;

require_once 'UserDataprovider.php';

/**
 * @covers      Awf\User\User::<private>
 * @covers      Awf\User\User::<protected>
 */
class UserTest extends AwfTestCase
{
    protected function setUp($resetContainer = true)
    {
        parent::setUp(false);
    }

    protected function tearDown()
    {
        global $mockUser, $stackUser;

        parent::tearDown();

        $mockUser  = array();
        $stackUser = array();
    }

    /**
     * @covers          Awf\User\User::bind
     * @dataProvider    UserDataprovider::getTestBind
     */
    public function testBind($test, $check)
    {
        $msg  = 'User::bind %s - Case: '.$check['case'];
        $counter = array(
            'before' => 0,
            'after'  => 0
        );
        $user = new User();

        if($test['privileges'])
        {
            $privilege = new TestClosure(array(
                'onBeforeLoad' => function($self, &$data) use ($test, &$counter){
                    $counter['before']++;

                    // Let's check when data is changed in the onBefore event
                    if(isset($test['mock']['data']))
                    {
                        foreach ($test['mock']['data'] as $key => $value)
                        {
                            $data->$key = $value;
                        }
                    }
                },
                'onAfterLoad' => function($self) use ($test, &$counter){
                    $counter['after']++;
                }
            ));

            ReflectionHelper::setValue($user, 'privileges', array('test' => $privilege));
        }

        $user->bind($test['data']);

        $this->assertEquals($check['events'], $counter, sprintf($msg, 'Failed to correctly invoke events'));
        $this->assertObjectNotHasAttribute('notHere', $user, sprintf($msg, 'Should not set non declared properties'));
        $this->assertInstanceOf('Awf\Registry\Registry', ReflectionHelper::getValue($user, 'parameters'), sprintf($msg, 'Failed to set the parameters'));

        foreach($check['user'] as $property => $value)
        {
            $this->assertEquals($value, ReflectionHelper::getValue($user, $property), sprintf($msg, 'Failed to set property "'.$property.'"'));
        }
    }

    /**
     * @covers          Awf\User\User::getId
     */
    public function testGetId()
    {
        $id   = 30;
        $user = new User();

        ReflectionHelper::setValue($user, 'id', $id);

        $this->assertSame($id, $user->getId(), 'User::getId Returned the wrong value');
    }

    /**
     * @covers          Awf\User\User::getUsername
     */
    public function testGetUsername()
    {
        $username = 'test';
        $user = new User();

        ReflectionHelper::setValue($user, 'username', $username);

        $this->assertSame($username, $user->getUsername(), 'User::getUsername Returned the wrong value');
    }

    /**
     * @covers          Awf\User\User::setUsername
     */
    public function testSetUsername()
    {
        $email = 'test';
        $user  = new User();

        $user->setUsername($email);

        $this->assertSame($email, ReflectionHelper::getValue($user, 'username'), 'User::setUsername Returned the wrong value');
    }

    /**
     * @covers          Awf\User\User::getName
     */
    public function testGetName()
    {
        $name = 'test';
        $user = new User();

        ReflectionHelper::setValue($user, 'name', $name);

        $this->assertSame($name, $user->getName(), 'User::getName Returned the wrong value');
    }

    /**
     * @covers          Awf\User\User::setName
     */
    public function testSetName()
    {
        $name = 'test';
        $user = new User();

        $user->setName($name);

        $this->assertSame($name, ReflectionHelper::getValue($user, 'name'), 'User::setName Returned the wrong value');
    }

    /**
     * @covers          Awf\User\User::getEmail
     */
    public function testGetEmail()
    {
        $email = 'user@example.com';
        $user  = new User();

        ReflectionHelper::setValue($user, 'email', $email);

        $this->assertSame($email, $user->getEmail(), 'User::getEmail Returned the wrong value');
    }

    /**
     * @covers          Awf\User\User::setEmail
     */
    public function testSetEmail()
    {
        $email = 'user@example.com';
        $user  = new User();

        $user->setEmail($email);

        $this->assertSame($email, ReflectionHelper::getValue($user, 'email'), 'User::setEmail Returned the wrong value');
    }

    /**
     * @covers          Awf\User\User::setPassword
     * @dataProvider    UserDataprovider::getTestSetPassword
     */
    public function testSetPassword($test, $check)
    {
        global $mockUser, $stackUser;

        $msg  = 'User::setPassword %s - Case: '.$check['case'];
        $user = new User();

        $mockUser['function_exists'] = function($function) use ($test)
        {
            if(isset($test['mock']['function_exists'][$function]))
            {
                return $test['mock']['function_exists'][$function];
            }

            return '__awf_continue__';
        };

        $mockUser['hash_algos'] = function() use ($test) { return $test['mock']['hash_algos']; };

        if($check['exception'])
        {
            $this->setExpectedException('RuntimeException');
        }

        $user->setPassword('test');

        $password = $user->getPassword();

        if($check['encType'] == 'bcrypt')
        {
            $count = isset($stackUser['password_hash']) ? $stackUser['password_hash'] : 0;
            $this->assertStringStartsWith('$2y$', $password, sprintf($msg, 'Failed to use the correct encryption type'));
        }
        else
        {
            list($algo, $pwd, $salt) = explode(':', $password);

            // Which function has been used? Hash or another native one?
            $count = isset($stackUser['hash'][$check['encType']]) ? $stackUser['hash'][$check['encType']] : 0;

            if(!$count)
            {
                $count = isset($stackUser[$check['encType']]) ? $stackUser[$check['encType']] : 0;
            }

            $this->assertEquals(strtoupper($check['encType']), $algo, sprintf($msg,'Failed to use the correct algorithm'));
            $this->assertNotEmpty($salt, sprintf($msg, 'Failed to create password salt'));
        }

        $this->assertEquals(1, $count, sprintf($msg, 'Failed to invoke the correct algorithm function'));
    }

    /**
     * @covers          Awf\User\User::getPassword
     */
    public function testGetPassword()
    {
        $pwd  = 'test';
        $user = new User();

        ReflectionHelper::setValue($user, 'password', $pwd);

        $this->assertSame($pwd, $user->getPassword(), 'User::getPassword Returned the wrong value');
    }

    /**
     * @covers          Awf\User\User::verifyPassword
     */
    public function testVerifyPassword()
    {
        $user = $this->getMock('Awf\User\User', array('triggerAuthenticationEvent'));
        $user->expects($this->once())->method('triggerAuthenticationEvent')->willReturn(true);

        $this->assertTrue($user->verifyPassword('test'));
    }

    /**
     * @covers          Awf\User\User::getParameters
     * @dataProvider    UserDataprovider::getTestGetparameters
     */
    public function testGetParameters($test, $check)
    {
        $msg  = 'User::getParameters %s - Case: '.$check['case'];
        $user = new User();

        ReflectionHelper::setValue($user, 'parameters', $test['parameters']);

        $this->assertInstanceOf('Awf\Registry\Registry', $user->getParameters(), sprintf($msg, 'Parameters should always be a Registry'));
    }

    /**
     * @covers          Awf\User\User::attachPrivilegePlugin
     */
    public function testAttachPrivilegePlugin()
    {
        $msg     = 'User::attachPrivilegePlugin %s';
        $user    = new User();

        $privilege = new PrivilegeMock();

        $user->attachPrivilegePlugin('test', $privilege);

        $privileges = ReflectionHelper::getValue($user, 'privileges');

        $this->assertArrayHasKey('test', $privileges, sprintf($msg, 'Failed to attach the privilege'));
        $this->assertSame($privilege, $privileges['test']);
    }

    /**
     * @covers          Awf\User\User::detachPrivilegePlugin
     */
    public function testDetachPrivilegePlugin()
    {
        $user = new User();

        ReflectionHelper::setValue($user, 'privileges', array('test' => 'dummy'));

        $user->detachPrivilegePlugin('test');

        $privileges = ReflectionHelper::getValue($user, 'privileges');

        $this->assertArrayNotHasKey('test', $privileges);
    }

    /**
     * @covers          Awf\User\User::getPrivilege
     * @dataProvider    UserDataprovider::getTestGetPrivilege
     */
    public function testGetPrivilege($test, $check)
    {
        $msg  = 'User::getPrivilege %s - Case: '.$check['case'];
        $user = new User();

        $privileges = array(
            'foobar' => new TestClosure(array(
                'getPrivilege' => function(){
                    return 'closure';
                }
            ))
        );

        ReflectionHelper::setValue($user, 'privileges', $privileges);

        $result = $user->getPrivilege($test['privilege'], 'default');

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @covers          Awf\User\User::setPrivilege
     * @dataProvider    UserDataprovider::getTestSetPrivilege
     */
    public function testSetPrivilege($test, $check)
    {
        $msg  = 'User::setPrivilege %s - Case: '.$check['case'];
        $user = new User();

        $privileges = array(
            'foobar' => new TestClosure(array(
                'setPrivilege' => function(){
                    return 'closure';
                }
            ))
        );

        ReflectionHelper::setValue($user, 'privileges', $privileges);

        $result = $user->setPrivilege($test['privilege'], 'value');

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong value'));
    }

    /**
     * @covers          Awf\User\User::triggerEvent
     */
    public function testTriggerEvent()
    {
        $user = new User();

        $privilege = new PrivilegeMock();

        ReflectionHelper::setValue($user, 'privileges', array('test' => $privilege));

        $user->triggerEvent('onAfterSave');

        $counter = $privilege->methodCounter;

        $this->assertArrayHasKey('onAfterSave', $counter, 'User::triggerEvent Failed to trigger privilege event');
        $this->assertEquals(1, $counter['onAfterSave'], 'User::triggerEvent Failed to trigger privilege event');
    }

    /**
     * @covers          Awf\User\User::triggerAuthenticationEvent
     * @dataProvider    UserDataprovider::getTestTriggerAuthenticationEvent
     */
    public function testTriggerAuthenticationEvent($test, $check)
    {
        $msg     = 'User::triggerAuthenticationEvent %s - Case: '.$check['case'];
        $user    = new User();
        $counter = 0;

        if($test['auth'])
        {
            $auth = array(
                'foo' => new AuthenticationMock(array(
                    'onAuthentication' => function() use($test){
                        return $test['mock']['auth1'];
                    }
                )),
                'bar' => new AuthenticationMock(array(
                    'onAuthentication' => function() use($test){
                        return $test['mock']['auth2'];
                    }
                ))
            );

            ReflectionHelper::setValue($user, 'authentications', $auth);
        }

        $result = $user->triggerAuthenticationEvent('onAuthentication');

        $objects = ReflectionHelper::getValue($user, 'authentications');

        // Ok let's check if everything was called correctly
        foreach($objects as $object)
        {
            $counter += isset($object->methodCounter['onAuthentication']) ? $object->methodCounter['onAuthentication'] : $object->methodCounter['onAuthentication'] = 0;
        }

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
        $this->assertEquals($check['counter'], $counter, sprintf($msg, 'Invoked the authentication plugins the wrong amount of times'));
    }

    /**
     * @covers          Awf\User\User::attachAuthenticationPlugin
     */
    public function testAttachAuthenticationPlugin()
    {
        $msg     = 'User::attachAuthenticationPlugin %s';
        $user    = new User();

        $auth = new AuthenticationMock();

        $user->attachAuthenticationPlugin('test', $auth);

        $auths = ReflectionHelper::getValue($user, 'authentications');

        $this->assertArrayHasKey('test', $auths, sprintf($msg, 'Failed to attach the authentication'));
        $this->assertSame($auth, $auths['test']);
    }

    /**
     * @covers          Awf\User\User::detachAuthenticationPlugin
     */
    public function testDetachAuthenticationPlugin()
    {
        $user = new User();

        ReflectionHelper::setValue($user, 'authentications', array('test' => 'dummy'));

        $user->detachAuthenticationPlugin('test');

        $authentications = ReflectionHelper::getValue($user, 'authentications');

        $this->assertArrayNotHasKey('test', $authentications);
    }
}

// Let's be sure that the mocked function is created only once
if(!function_exists('Awf\User\User\function_exists'))
{
    function function_exists()
    {
        global $mockUser, $stackUser;

        isset($stackUser['function_exists']) ? $stackUser['function_exists']++ : $stackUser['function_exists'] = 1;

        if (isset($mockUser['function_exists'])) {
            $result = call_user_func_array($mockUser['function_exists'], func_get_args());

            if ($result !== '__awf_continue__') {
                return $result;
            }
        }

        return call_user_func_array('\function_exists', func_get_args());
    }
}

function password_hash()
{
    global $mockUser, $stackUser;

    isset($stackUser['password_hash']) ? $stackUser['password_hash']++ : $stackUser['password_hash'] = 1;

    if(isset($mockUser['password_hash']))
    {
        return call_user_func_array($mockUser['password_hash'], func_get_args());
    }

    return call_user_func_array('\password_hash', func_get_args());
}

function hash()
{
    global $mockUser, $stackUser;

    if(!isset($stackUser['hash']))
    {
        $stackUser['hash'] = array();
    }

    $args = func_get_args();

    isset($stackUser['hash'][$args[0]]) ? $stackUser['hash'][$args[0]]++ : $stackUser['hash'][$args[0]] = 1;

    if(isset($mockUser['hash']))
    {
        return call_user_func_array($mockUser['hash'], func_get_args());
    }

    return call_user_func_array('\hash', func_get_args());
}

function hash_algos()
{
    global $mockUser, $stackUser;

    isset($stackUser['hash_algos']) ? $stackUser['hash_algos']++ : $stackUser['hash_algos'] = 1;

    if(isset($mockUser['hash_algos']))
    {
        return call_user_func_array($mockUser['hash_algos'], func_get_args());
    }

    return call_user_func_array('\hash_algos', func_get_args());
}

function sha1()
{
    global $mockUser, $stackUser;

    isset($stackUser['sha1']) ? $stackUser['sha1']++ : $stackUser['sha1'] = 1;

    if(isset($mockUser['sha1']))
    {
        return call_user_func_array($mockUser['sha1'], func_get_args());
    }

    return call_user_func_array('\sha1', func_get_args());
}

function md5()
{
    global $mockUser, $stackUser;

    isset($stackUser['md5']) ? $stackUser['md5']++ : $stackUser['md5'] = 1;

    if(isset($mockUser['md5']))
    {
        return call_user_func_array($mockUser['md5'], func_get_args());
    }

    return call_user_func_array('\md5', func_get_args());
}
