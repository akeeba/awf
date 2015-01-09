<?php
/**
 * @package        awf
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

// We will use the same namespace as the SUT, so when PHP will try to look for the native function, he will look inside
// this one before continuing
namespace Awf\Filesystem;

use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\ReflectionHelper;
use org\bovigo\vfs\vfsStream;

global $mockFilesystem;
global $stackFilesystem;

require_once 'SftpDataprovider.php';

/**
 * @covers      Awf\Filesystem\Sftp::<protected>
 * @covers      Awf\Filesystem\Sftp::<private>
 * @package     Awf\Tests\Filesystem\Sftp
 */
class SftpTest extends AwfTestCase
{
    protected function tearDown()
    {
        global $mockFilesystem, $stackFilesystem;

        parent::tearDown();

        $mockFilesystem = array();
        $stackFilesystem = array();
    }

    /**
     * @covers          Awf\Filesystem\Sftp::__construct
     */
    public function test__construct()
    {
        $options = array(
            'host'       => 'localhost',
            'port'       => '22',
            'username'   => 'test',
            'password'   => 'test',
            'directory'  => 'foobar/ ',
            'privateKey' => 'foo',
            'publicKey'  => 'bar'
        );

        $sftp = $this->getMock('Awf\Filesystem\Sftp', array('connect'), array(), '', false);

        $sftp->__construct($options);

        $this->assertSame('localhost', ReflectionHelper::getValue($sftp, 'host'));
        $this->assertSame(22, ReflectionHelper::getValue($sftp, 'port'));
        $this->assertSame('test', ReflectionHelper::getValue($sftp, 'username'));
        $this->assertSame('test', ReflectionHelper::getValue($sftp, 'password'));
        $this->assertSame('/foobar/', ReflectionHelper::getValue($sftp, 'directory'));
        $this->assertSame('foo', ReflectionHelper::getValue($sftp, 'privateKey'));
        $this->assertSame('bar', ReflectionHelper::getValue($sftp, 'publicKey'));
    }

    /**
     * @covers          Awf\Filesystem\Sftp::__destruct
     * @dataProvider    SftpDataprovider::getTest__destruct
     */
    public function test__destruct($test, $check)
    {
        global $stackFilesystem;

        $msg     = 'Sftp::__destruct %s - Case: '.$check['case'];
        $count   = 0;
        $options = array(
            'host'       => 'localhost',
            'port'       => '22',
            'username'   => 'test',
            'password'   => 'test',
            'directory'  => 'foobar/ ',
            'privateKey' => 'foo',
            'publicKey'  => 'bar'
        );

        $sftp = $this->getMock('Awf\Filesystem\Sftp', array('connect'), array(), '', false);

        $sftp->__construct($options);

        ReflectionHelper::setValue($sftp, 'connection', $test['connection']);

        $sftp->__destruct();

        if(isset($stackFilesystem['ssh2_exec']))
        {
            $count = (int) (array_search('exit;', $stackFilesystem['ssh2_exec']) !== false);
        }

        $this->assertEquals($check['count'], $count, sprintf($msg, 'Failed to close the connection'));
    }

    /**
     * @covers          Awf\Filesystem\Sftp::connect
     * @dataProvider    SftpDataprovider::getTestConnect
     */
    public function testConnect($test, $check)
    {
        global $mockFilesystem;

        $options = array(
            'host'       => 'localhost',
            'port'       => '22',
            'username'   => 'test',
            'password'   => 'test',
            'directory'  => 'foobar/ ',
            'privateKey' => $test['private'],
            'publicKey'  => $test['public']
        );

        if($check['exception'])
        {
            $this->setExpectedException('RuntimeException');
        }

        $mockFilesystem['function_exists'] = function($function) use ($test)
        {
            if($function != 'ssh2_connect')
            {
                return '__awf_continue__';
            }

            return $test['mock']['function_exists'];
        };

        $mockFilesystem['ssh2_connect']          = function() use ($test){ return $test['mock']['ssh2_connect']; };
        $mockFilesystem['ssh2_auth_pubkey_file'] = function() use ($test){ return $test['mock']['ssh2_auth_pubkey_file']; };
        $mockFilesystem['ssh2_auth_password']    = function() use ($test){ return $test['mock']['ssh2_auth_password']; };
        $mockFilesystem['ssh2_sftp']             = function() use ($test){ return $test['mock']['ssh2_sftp']; };
        $mockFilesystem['ssh2_sftp_stat']        = function() use ($test){ return $test['mock']['ssh2_sftp_stat']; };

        $sftp = new Sftp($options);

        $this->assertNotNull(ReflectionHelper::getValue($sftp, 'connection'));
        $this->assertNotNull(ReflectionHelper::getValue($sftp, 'sftpHandle'));
    }

    /**
     * @covers          Awf\Filesystem\Sftp::write
     * @dataProvider    SftpDataprovider::getTestWrite
     */
    public function testWrite($test, $check)
    {
        global $mockFilesystem;

        $msg = 'Sftp::write %s - Case: '.$check['case'];
        $options = array(
            'host'       => 'localhost',
            'port'       => '22',
            'username'   => 'test',
            'password'   => 'test',
            'directory'  => 'foobar/ ',
            'privateKey' => 'foo',
            'publicKey'  => 'bar'
        );

        $mockFilesystem['fopen']   = function() use ($test){ return $test['mock']['fopen']; };
        $mockFilesystem['fwrite']  = function() use ($test){ return $test['mock']['fwrite']; };
        $mockFilesystem['fclose']  = function() { return true; };

        $sftp = $this->getMock('Awf\Filesystem\Sftp', array('connect'), array(), '', false);

        $sftp->__construct($options);

        $result = $sftp->write('foobar.txt', 'dummy');

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @covers          Awf\Filesystem\Sftp::delete
     * @dataProvider    SftpDataprovider::getTestDelete
     */
    public function testDelete($test, $check)
    {
        global $mockFilesystem;

        $msg = 'Sftp::delete %s - Case: '.$check['case'];
        $options = array(
            'host'       => 'localhost',
            'port'       => '22',
            'username'   => 'test',
            'password'   => 'test',
            'directory'  => 'foobar/ ',
            'privateKey' => 'foo',
            'publicKey'  => 'bar'
        );

        $mockFilesystem['ssh2_sftp_unlink']   = function() use ($test){
            if($test['mock']['ssh2_sftp_unlink'] === 'exception')
            {
                throw new \Exception();
            }

            return $test['mock']['ssh2_sftp_unlink'];
        };

        $sftp = $this->getMock('Awf\Filesystem\Sftp', array('connect'), array(), '', false);

        $sftp->__construct($options);

        $result = $sftp->delete('foobar.txt');

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }
}

// Let's be sure that the mocked function is created only once
if(!function_exists('Awf\Filesystem\function_exists'))
{
    function function_exists()
    {
        global $mockFilesystem, $stackFilesystem;

        isset($stackFilesystem['function_exists']) ? $stackFilesystem['function_exists']++ : $stackFilesystem['function_exists'] = 1;

        if(isset($mockFilesystem['function_exists']))
        {
            $result = call_user_func_array($mockFilesystem['function_exists'], func_get_args());

            if($result !== '__awf_continue__')
            {
                return $result;
            }
        }

        return call_user_func_array('\function_exists', func_get_args());
    }
}

function fopen()
{
    global $mockFilesystem, $stackFilesystem;

    isset($stackFilesystem['fopen']) ? $stackFilesystem['fopen']++ : $stackFilesystem['fopen'] = 1;

    if(isset($mockFilesystem['fopen']))
    {
        return call_user_func_array($mockFilesystem['fopen'], func_get_args());
    }

    return call_user_func_array('\fopen', func_get_args());
}

function fwrite()
{
    global $mockFilesystem, $stackFilesystem;

    isset($stackFilesystem['fwrite']) ? $stackFilesystem['fwrite']++ : $stackFilesystem['fwrite'] = 1;

    if(isset($mockFilesystem['fwrite']))
    {
        return call_user_func_array($mockFilesystem['fwrite'], func_get_args());
    }

    return call_user_func_array('\fwrite', func_get_args());
}

function fclose()
{
    global $mockFilesystem, $stackFilesystem;

    isset($stackFilesystem['fclose']) ? $stackFilesystem['fclose']++ : $stackFilesystem['fclose'] = 1;

    if(isset($mockFilesystem['fclose']))
    {
        return call_user_func_array($mockFilesystem['fclose'], func_get_args());
    }

    return call_user_func_array('\fclose', func_get_args());
}

function ssh2_connect()
{
    global $mockFilesystem, $stackFilesystem;

    isset($stackFilesystem['ssh2_connect']) ? $stackFilesystem['ssh2_connect']++ : $stackFilesystem['ssh2_connect'] = 1;

    if(isset($mockFilesystem['ssh2_connect']))
    {
        return call_user_func_array($mockFilesystem['ssh2_connect'], func_get_args());
    }
}

function ssh2_auth_pubkey_file()
{
    global $mockFilesystem, $stackFilesystem;

    isset($stackFilesystem['ssh2_auth_pubkey_file']) ? $stackFilesystem['ssh2_auth_pubkey_file']++ : $stackFilesystem['ssh2_auth_pubkey_file'] = 1;

    if(isset($mockFilesystem['ssh2_auth_pubkey_file']))
    {
        return call_user_func_array($mockFilesystem['ssh2_auth_pubkey_file'], func_get_args());
    }
}

function ssh2_auth_password()
{
    global $mockFilesystem, $stackFilesystem;

    isset($stackFilesystem['ssh2_auth_password']) ? $stackFilesystem['ssh2_auth_password']++ : $stackFilesystem['ssh2_auth_password'] = 1;

    if(isset($mockFilesystem['ssh2_auth_password']))
    {
        return call_user_func_array($mockFilesystem['ssh2_auth_password'], func_get_args());
    }
}

function ssh2_sftp()
{
    global $mockFilesystem, $stackFilesystem;

    isset($stackFilesystem['ssh2_sftp']) ? $stackFilesystem['ssh2_sftp']++ : $stackFilesystem['ssh2_sftp'] = 1;

    if(isset($mockFilesystem['ssh2_sftp']))
    {
        return call_user_func_array($mockFilesystem['ssh2_sftp'], func_get_args());
    }
}

function ssh2_sftp_stat()
{
    global $mockFilesystem, $stackFilesystem;

    isset($stackFilesystem['ssh2_sftp_stat']) ? $stackFilesystem['ssh2_sftp_stat']++ : $stackFilesystem['ssh2_sftp_stat'] = 1;

    if(isset($mockFilesystem['ssh2_sftp_stat']))
    {
        return call_user_func_array($mockFilesystem['ssh2_sftp_stat'], func_get_args());
    }
}

function ssh2_exec()
{
    global $mockFilesystem, $stackFilesystem;

    $args = func_get_args();

    // First argument is always the connection one, we're not interested into
    $stackFilesystem['ssh2_exec'][] = $args[1];

    if(isset($mockFilesystem['ssh2_exec']))
    {
        return call_user_func_array($mockFilesystem['ssh2_exec'], func_get_args());
    }
}

function ssh2_sftp_unlink()
{
    global $mockFilesystem, $stackFilesystem;

    isset($stackFilesystem['ssh2_sftp_unlink']) ? $stackFilesystem['ssh2_sftp_unlink']++ : $stackFilesystem['ssh2_sftp_unlink'] = 1;

    if(isset($mockFilesystem['ssh2_sftp_unlink']))
    {
        return call_user_func_array($mockFilesystem['ssh2_sftp_unlink'], func_get_args());
    }
}