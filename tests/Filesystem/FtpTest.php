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
    protected function setUp()
    {
        parent::setUp(false);
    }

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

function ftp_put()
{
    global $mockFilesystem, $stackFilesystem;

    if(isset($mockFilesystem['ftp_put']))
    {
        return call_user_func_array($mockFilesystem['ftp_put'], func_get_args());
    }

    isset($stackFilesystem['ftp_put']) ? $stackFilesystem['ftp_put']++ : $stackFilesystem['ftp_put'] = 1;
}