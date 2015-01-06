<?php
/**
 * @package        awf
 * @subpackage     tests.pagination.object
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

// We will use the same namespace as the SUT, so when PHP will try to look for the native function, he will look inside
// this one before continuing
namespace Awf\Filesystem;

use Awf\Tests\Helpers\AwfTestCase;

global $mockFilesystem;

require_once 'FileDataprovider.php';

function file_put_contents()
{
    global $mockFilesystem;

    if(isset($mockFilesystem['file_put_contents']))
    {
        return call_user_func_array($mockFilesystem['file_put_contents'], func_get_args());
    }

    return call_user_func_array('\file_put_contents', func_get_args());
}

/**
 * @covers      Awf\Filesystem\File::<protected>
 * @covers      Awf\Filesystem\File::<private>
 * @package     Awf\Tests\Filesystem\File
 */
class FileTest extends AwfTestCase
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

        $this->assertEquals($check['result'], $result, sprintf($msg, ''));
    }
}
