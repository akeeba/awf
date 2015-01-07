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
        global $mockFilesystem;

        parent::tearDown();

        $mockFilesystem = array();
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
}
