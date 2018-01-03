<?php
/**
 * @package        awf
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

// We will use the same namespace as the SUT, so when PHP will try to look for the native function, he will look inside
// this one before continuing
namespace Awf\Filesystem;

use Awf\Tests\Helpers\AwfTestCase;
use org\bovigo\vfs\vfsStream;

global $mockFilesystem;

require_once 'FileDataprovider.php';

/**
 * @covers      Awf\Filesystem\File::<protected>
 * @covers      Awf\Filesystem\File::<private>
 * @package     Awf\Tests\Filesystem\File
 */
class FileTest extends AwfTestCase
{
    protected function setUp($resetContainer = true)
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
     * @group           File
     * @group           FileWrite
     * @covers          Awf\Filesystem\File::write
     * @dataProvider    FileDataprovider::getTestWrite
     */
    public function testWrite($test, $check)
    {
        global $mockFilesystem;

        $msg  = 'File::write %s - Case: '.$check['case'];
        $file = new File(array());

        $mockFilesystem['file_put_contents'] = function() use ($test){
            return $test['mock']['file_put_contents'];
        };

        $result = $file->write(__DIR__.'/test.txt', 'test');

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @group           File
     * @group           FileDelete
     * @covers          Awf\Filesystem\File::delete
     * @dataProvider    FileDataprovider::getTestDelete
     */
    public function testDelete($test, $check)
    {
        global $mockFilesystem;

        $msg  = 'File::delete %s - Case: '.$check['case'];
        $file = new File(array());

        $mockFilesystem['unlink'] = function() use ($test){
            return $test['mock']['unlink'];
        };

        $result = $file->delete(__DIR__.'/test.txt');

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @group           File
     * @group           FileCopy
     * @covers          Awf\Filesystem\File::copy
     * @dataProvider    FileDataprovider::getTestCopy
     */
    public function testCopy($test, $check)
    {
        global $mockFilesystem;

        $msg  = 'File::copy %s - Case: '.$check['case'];
        $file = new File(array());

        $mockFilesystem['copy'] = function() use ($test){
            return $test['mock']['copy'];
        };

        $result = $file->copy(__DIR__.'/test.txt', __DIR__.'/test2.txt');

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @group           File
     * @group           FileMove
     * @covers          Awf\Filesystem\File::move
     * @dataProvider    FileDataprovider::getTestMove
     */
    public function testMove($test, $check)
    {
        global $mockFilesystem;

        $msg  = 'File::move %s - Case: '.$check['case'];
        $file = new File(array());

        $mockFilesystem['rename'] = function() use ($test){
            return $test['mock']['rename'];
        };

        $result = $file->move(__DIR__.'/test.txt', __DIR__.'/test2.txt');

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @group           File
     * @group           FileChmod
     * @covers          Awf\Filesystem\File::chmod
     * @dataProvider    FileDataprovider::getTestChmod
     */
    public function testChmod($test, $check)
    {
        global $mockFilesystem;

        $msg  = 'File::chmod %s - Case: '.$check['case'];
        $file = new File(array());

        $mockFilesystem['chmod'] = function() use ($test){
            return $test['mock']['chmod'];
        };

        $result = $file->chmod(__DIR__.'/test', 0777);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @group           File
     * @group           FileMkdir
     * @covers          Awf\Filesystem\File::mkdir
     * @dataProvider    FileDataprovider::getTestMkdir
     */
    public function testMkdir($test, $check)
    {
        global $mockFilesystem;

        $msg  = 'File::mkdir %s - Case: '.$check['case'];
        $file = new File(array());

        $mockFilesystem['mkdir'] = function() use ($test){
            return $test['mock']['mkdir'];
        };

        $result = $file->mkdir(__DIR__.'/test', 0777);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @group           File
     * @group           FileRmdir
     * @covers          Awf\Filesystem\File::rmdir
     * @dataProvider    FileDataprovider::getTestRmdir
     */
    public function testRmdir($test, $check)
    {
        vfsStream::setup('root', null, $test['filesystem']);

        $msg  = 'File::rmdir %s - Case: '.$check['case'];
        $file = new File(array());

        $result = $file->rmdir(vfsStream::url($test['path']), $test['recursive']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @group           File
     * @group           FileTranslatePath
     * @covers          Awf\Filesystem\File::translatePath
     */
    public function testTranslatePath()
    {
        $path = __DIR__;
        $file = new File(array());

        $result = $file->translatePath($path);

        $this->assertEquals($path, $result, 'File::translatePath Returned the wrong result');
    }

    /**
     * @group           File
     * @group           FileListFolders
     * @covers          Awf\Filesystem\File::listFolders
     * @dataProvider    FileDataprovider::getTestListFolders
     */
    public function testListFolders($test, $check)
    {
        global $mockFilesystem;

        $msg  = 'File::listFolders %s - Case: '.$check['case'];
        $file = new File(array());

        vfsStream::setup('root', null, $test['filesystem']);

        if($test['mock']['getcwd'])
        {
            $mockFilesystem['getcwd'] = function() use ($test){
                return $test['mock']['getcwd'];
            };
        }

        $result = $file->listFolders($test['path']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @group           File
     * @group           FileDirectoryFiles
     * @covers          Awf\Filesystem\File::directoryFiles
     * @dataProvider    FileDataprovider::getTestDirectoryFiles
     */
    public function testDirectoryFiles($test, $check)
    {
        $msg  = 'File::directoryFiles %s - Case: '.$check['case'];
        $file = new File(array());

        vfsStream::setup('root', null, $test['filesystem']);

        $result = $file->directoryFiles(vfsStream::url('root'), $test['filter'], $test['recurse'], $test['full'],
                                        $test['exclude'], $test['excludeFilter'], $test['natsort']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @group           File
     * @group           FileDirectoryFiles
     * @covers          Awf\Filesystem\File::directoryFiles
     */
    public function testDirectoryFilesException()
    {
        $this->setExpectedException('InvalidArgumentException');

        $file = new File(array());

        $root = vfsStream::setup('root');
        vfsStream::newFile('foobar.txt')->at($root);

        $file->directoryFiles(vfsStream::url('root/foobar.txt'));
    }
}

function file_put_contents()
{
    global $mockFilesystem;

    if(isset($mockFilesystem['file_put_contents']))
    {
        return call_user_func_array($mockFilesystem['file_put_contents'], func_get_args());
    }

    return call_user_func_array('\file_put_contents', func_get_args());
}

function unlink()
{
    global $mockFilesystem;

    if(isset($mockFilesystem['unlink']))
    {
        return call_user_func_array($mockFilesystem['unlink'], func_get_args());
    }

    return call_user_func_array('\unlink', func_get_args());
}

function copy()
{
    global $mockFilesystem;

    if(isset($mockFilesystem['copy']))
    {
        return call_user_func_array($mockFilesystem['copy'], func_get_args());
    }

    return call_user_func_array('\copy', func_get_args());
}

function rename()
{
    global $mockFilesystem;

    if(isset($mockFilesystem['rename']))
    {
        return call_user_func_array($mockFilesystem['rename'], func_get_args());
    }

    return call_user_func_array('\rename', func_get_args());
}

function chmod()
{
    global $mockFilesystem;

    if(isset($mockFilesystem['chmod']))
    {
        return call_user_func_array($mockFilesystem['chmod'], func_get_args());
    }

    return call_user_func_array('\chmod', func_get_args());
}

function mkdir()
{
    global $mockFilesystem;

    if(isset($mockFilesystem['mkdir']))
    {
        return call_user_func_array($mockFilesystem['mkdir'], func_get_args());
    }

    return call_user_func_array('\mkdir', func_get_args());
}

function getcwd()
{
    global $mockFilesystem;

    if(isset($mockFilesystem['getcwd']))
    {
        return call_user_func_array($mockFilesystem['getcwd'], func_get_args());
    }

    return call_user_func_array('\getcwd', func_get_args());
}
