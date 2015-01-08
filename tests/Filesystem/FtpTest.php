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

global $mockFilesystem;
global $stackFilesystem;

require_once 'FtpDataprovider.php';

/**
 * @covers      Awf\Filesystem\Ftp::<protected>
 * @covers      Awf\Filesystem\Ftp::<private>
 * @package     Awf\Tests\Filesystem\Ftp
 */
class FtpTest extends AwfTestCase
{
    protected function tearDown()
    {
        global $mockFilesystem, $stackFilesystem;

        parent::tearDown();

        $mockFilesystem = array();
        $stackFilesystem = array();
    }

    /**
     * @covers          Awf\Filesystem\Ftp::__construct
     */
    public function test__construct()
    {
        $options = array(
            'host'      => 'localhost',
            'port'      => '22',
            'username'  => 'test',
            'password'  => 'test',
            'directory' => 'foobar/ ',
            'ssl'       => true,
            'passive'   => false
        );

        $ftp = $this->getMock('Awf\Filesystem\Ftp', array('connect'), array(), '', false);

        $ftp->__construct($options);

        $this->assertSame('localhost', ReflectionHelper::getValue($ftp, 'host'));
        $this->assertSame(22, ReflectionHelper::getValue($ftp, 'port'));
        $this->assertSame('test', ReflectionHelper::getValue($ftp, 'username'));
        $this->assertSame('test', ReflectionHelper::getValue($ftp, 'password'));
        $this->assertSame('/foobar/', ReflectionHelper::getValue($ftp, 'directory'));
        $this->assertSame(true, ReflectionHelper::getValue($ftp, 'ssl'));
        $this->assertSame(false, ReflectionHelper::getValue($ftp, 'passive'));
    }

    /**
     * @covers          Awf\Filesystem\Ftp::connect
     * @dataProvider    FtpDataprovider::getTestConnect
     */
    public function testConnect($test, $check)
    {
        global $mockFilesystem, $stackFilesystem;

        $options = array(
            'host'      => 'localhost',
            'port'      => '22',
            'username'  => 'test',
            'password'  => 'test',
            'directory' => 'foobar/ ',
            'ssl'       => $test['ssl'],
            'passive'   => false
        );

        if($check['exception'])
        {
            $this->setExpectedException('RuntimeException');
        }

        $mockFilesystem['ftp_ssl_connect'] = function() use ($test){ return $test['mock']['ftp_ssl_connect']; };
        $mockFilesystem['ftp_connect']     = function() use ($test){ return $test['mock']['ftp_connect']; };
        $mockFilesystem['ftp_login']       = function() use ($test){ return $test['mock']['ftp_login']; };
        $mockFilesystem['ftp_chdir']       = function() use ($test){ return $test['mock']['ftp_chdir']; };

        $ftp = new Ftp($options);

        // If I'm here it means that no exception was thrown, so I can perform some checks
        $this->assertNotNull(ReflectionHelper::getValue($ftp, 'connection'));
    }

    /**
     * @covers          Awf\Filesystem\Ftp::__destruct
     * @dataProvider    FtpDataprovider::getTest__destruct
     */
    public function test__destruct($test, $check)
    {
        global $stackFilesystem;

        $msg     = 'Ftp::__destruct %s - Case: '.$check['case'];
        $options = array(
            'host'      => 'localhost',
            'port'      => '22',
            'username'  => 'test',
            'password'  => 'test',
            'directory' => 'foobar/ ',
            'ssl'       => true,
            'passive'   => false
        );

        $ftp = $this->getMock('Awf\Filesystem\Ftp', array('connect'), array(), '', false);

        $ftp->__construct($options);

        if($test['connection'])
        {
            ReflectionHelper::setValue($ftp, 'connection', 'test');
        }

        $ftp->__destruct();

        $this->assertEquals($check['count'], $stackFilesystem['ftp_close'], sprintf($msg, 'Failed to close the connection'));
    }

    /**
     * @covers          Awf\Filesystem\Ftp::write
     * @dataProvider    FtpDataprovider::getTestWrite
     */
    public function testWrite($test, $check)
    {
        global $mockFilesystem;

        $msg     = 'Ftp::write %s - Case: '.$check['case'];
        $options = array(
            'host'      => 'localhost',
            'port'      => '22',
            'username'  => 'test',
            'password'  => 'test',
            'directory' => 'foobar/ ',
            'ssl'       => false,
            'passive'   => false
        );

        $mockFilesystem['ftp_connect'] = function() use ($test){ return true; };
        $mockFilesystem['ftp_login']   = function() use ($test){ return true; };
        $mockFilesystem['ftp_chdir']   = function() use ($test){ return true; };
        $mockFilesystem['ftp_fput']    = function() use ($test){ return $test['mock']['ftp_fput']; };

        $ftp = new Ftp($options);

        $result = $ftp->write('foobar.txt', 'dummy');

        $this->assertEquals($check['result'], $result, sprintf($msg, ''));
    }

    /**
     * @covers          Awf\Filesystem\Ftp::delete
     * @dataProvider    FtpDataprovider::getTestDelete
     */
    public function testDelete($test, $check)
    {
        global $mockFilesystem;

        $msg     = 'Ftp::delete %s - Case: '.$check['case'];
        $options = array(
            'host'      => 'localhost',
            'port'      => '22',
            'username'  => 'test',
            'password'  => 'test',
            'directory' => 'foobar/ ',
            'ssl'       => false,
            'passive'   => false
        );

        $mockFilesystem['ftp_connect'] = function() use ($test){ return true; };
        $mockFilesystem['ftp_login']   = function() use ($test){ return true; };
        $mockFilesystem['ftp_chdir']   = function() use ($test){ return true; };
        $mockFilesystem['ftp_delete']  = function() use ($test){ return $test['mock']['ftp_delete']; };

        $ftp = new Ftp($options);

        $result = $ftp->delete('foobar.txt');

        $this->assertEquals($check['result'], $result, sprintf($msg, ''));
    }

    /**
     * @covers          Awf\Filesystem\Ftp::copy
     * @dataProvider    FtpDataprovider::getTestCopy
     */
    public function testCopy($test, $check)
    {
        global $mockFilesystem;

        $msg     = 'Ftp::copy %s - Case: '.$check['case'];
        $options = array(
            'host'      => 'localhost',
            'port'      => '22',
            'username'  => 'test',
            'password'  => 'test',
            'directory' => 'foobar/ ',
            'ssl'       => false,
            'passive'   => false
        );

        $mockFilesystem['ftp_connect'] = function() use ($test){ return true; };
        $mockFilesystem['ftp_login']   = function() use ($test){ return true; };
        $mockFilesystem['ftp_chdir']   = function() use ($test){ return true; };
        $mockFilesystem['ftp_fget']  = function() use ($test){ return $test['mock']['ftp_fget']; };
        $mockFilesystem['ftp_fput']  = function() use ($test){ return $test['mock']['ftp_fput']; };

        $ftp = new Ftp($options);

        $result = $ftp->copy('foobar.txt', 'dummy.txt');

        $this->assertEquals($check['result'], $result, sprintf($msg, ''));
    }

    /**
     * @covers          Awf\Filesystem\Ftp::move
     * @dataProvider    FtpDataprovider::getTestMove
     */
    public function testMove($test, $check)
    {
        global $mockFilesystem;

        $msg     = 'Ftp::move %s - Case: '.$check['case'];
        $options = array(
            'host'      => 'localhost',
            'port'      => '22',
            'username'  => 'test',
            'password'  => 'test',
            'directory' => 'foobar/ ',
            'ssl'       => false,
            'passive'   => false
        );

        $mockFilesystem['ftp_connect'] = function() use ($test){ return true; };
        $mockFilesystem['ftp_login']   = function() use ($test){ return true; };
        $mockFilesystem['ftp_chdir']   = function() use ($test){ return true; };
        $mockFilesystem['ftp_rename']  = function() use ($test){ return $test['mock']['ftp_rename']; };

        $ftp = new Ftp($options);

        $result = $ftp->move('foobar.txt', 'dummy.txt');

        $this->assertEquals($check['result'], $result, sprintf($msg, ''));
    }
}

function ftp_close()
{
    global $mockFilesystem, $stackFilesystem;

    if(isset($mockFilesystem['ftp_close']))
    {
        return call_user_func_array($mockFilesystem['ftp_close'], func_get_args());
    }

    isset($stackFilesystem['ftp_close']) ? $stackFilesystem['ftp_close']++ : $stackFilesystem['ftp_close'] = 1;
}

function ftp_ssl_connect()
{
    global $mockFilesystem, $stackFilesystem;

    if(isset($mockFilesystem['ftp_ssl_connect']))
    {
        return call_user_func_array($mockFilesystem['ftp_ssl_connect'], func_get_args());
    }

    isset($stackFilesystem['ftp_ssl_connect']) ? $stackFilesystem['ftp_ssl_connect']++ : $stackFilesystem['ftp_ssl_connect'] = 1;
}

function ftp_connect()
{
    global $mockFilesystem, $stackFilesystem;

    if(isset($mockFilesystem['ftp_connect']))
    {
        return call_user_func_array($mockFilesystem['ftp_connect'], func_get_args());
    }

    isset($stackFilesystem['ftp_connect']) ? $stackFilesystem['ftp_connect']++ : $stackFilesystem['ftp_connect'] = 1;
}

function ftp_login()
{
    global $mockFilesystem, $stackFilesystem;

    if(isset($mockFilesystem['ftp_login']))
    {
        return call_user_func_array($mockFilesystem['ftp_login'], func_get_args());
    }

    isset($stackFilesystem['ftp_login']) ? $stackFilesystem['ftp_login']++ : $stackFilesystem['ftp_login'] = 1;
}

function ftp_chdir()
{
    global $mockFilesystem, $stackFilesystem;

    if(isset($mockFilesystem['ftp_chdir']))
    {
        return call_user_func_array($mockFilesystem['ftp_chdir'], func_get_args());
    }

    isset($stackFilesystem['ftp_chdir']) ? $stackFilesystem['ftp_chdir']++ : $stackFilesystem['ftp_chdir'] = 1;
}

function ftp_pasv()
{
    global $mockFilesystem, $stackFilesystem;

    if(isset($mockFilesystem['ftp_pasv']))
    {
        return call_user_func_array($mockFilesystem['ftp_pasv'], func_get_args());
    }

    isset($stackFilesystem['ftp_pasv']) ? $stackFilesystem['ftp_pasv']++ : $stackFilesystem['ftp_pasv'] = 1;
}

function ftp_fget()
{
    global $mockFilesystem, $stackFilesystem;

    if(isset($mockFilesystem['ftp_fget']))
    {
        return call_user_func_array($mockFilesystem['ftp_fget'], func_get_args());
    }

    isset($stackFilesystem['ftp_fget']) ? $stackFilesystem['ftp_fget']++ : $stackFilesystem['ftp_fget'] = 1;
}

function ftp_fput()
{
    global $mockFilesystem, $stackFilesystem;

    if(isset($mockFilesystem['ftp_fput']))
    {
        return call_user_func_array($mockFilesystem['ftp_fput'], func_get_args());
    }

    isset($stackFilesystem['ftp_fput']) ? $stackFilesystem['ftp_fput']++ : $stackFilesystem['ftp_fput'] = 1;
}

function ftp_delete()
{
    global $mockFilesystem, $stackFilesystem;

    if(isset($mockFilesystem['ftp_delete']))
    {
        return call_user_func_array($mockFilesystem['ftp_delete'], func_get_args());
    }

    isset($stackFilesystem['ftp_delete']) ? $stackFilesystem['ftp_delete']++ : $stackFilesystem['ftp_delete'] = 1;
}

function ftp_rename()
{
    global $mockFilesystem, $stackFilesystem;

    if(isset($mockFilesystem['ftp_rename']))
    {
        return call_user_func_array($mockFilesystem['ftp_rename'], func_get_args());
    }

    isset($stackFilesystem['ftp_rename']) ? $stackFilesystem['ftp_rename']++ : $stackFilesystem['ftp_rename'] = 1;
}