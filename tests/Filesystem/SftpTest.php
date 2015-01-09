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