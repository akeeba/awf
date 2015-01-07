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
     * @covers          Awf\Filesystem\Ftp::__desctruct
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
