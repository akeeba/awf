<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Filesystem\Hybrid;

use Awf\Filesystem\Hybrid;
use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Utils\TestClosure;

require_once 'HybridDataprovider.php';

// Pretty ugly require, but I have to create a file with a fixed namespace and obviously I can't out it inside the SRC folder
require_once __DIR__.'/../Stubs/Filesystem/Fake.php';

/**
 * @covers      Awf\Filesystem\Hybrid::<protected>
 * @covers      Awf\Filesystem\Hybrid::<private>
 * @package     Awf\Tests\Filesystem\Hybrid
 */
class HybridTest extends AwfTestCase
{
    /**
     * @covers          Awf\Filesystem\Hybrid::__construct
     * @dataProvider    HybridDataprovider::getTest__construct
     */
    public function test__construct($test, $check)
    {
        $msg     = 'Hybrid::__construct %s - Case: '.$check['case'];
        $options = array_merge(array(), $test['options']);

        $hybrid = new Hybrid($options);

        $file     = ReflectionHelper::getValue($hybrid, 'fileAdapter');
        $abstract = ReflectionHelper::getValue($hybrid, 'abstractionAdapter');

        $this->assertInstanceOf('\Awf\Filesystem\File', $file, sprintf($msg, 'Wrong class for the file adapter'));

        if($check['adapter'])
        {
            $this->assertInstanceOf($check['adapter'], $abstract, sprintf($msg, 'Wrong class for the abstraction adapter'));
        }
        else
        {
            $this->assertNull($abstract, sprintf($msg, 'Abstraction adapter should be null'));
        }
    }

    /**
     * @covers          Awf\Filesystem\Hybrid::write
     * @dataProvider    HybridDataprovider::getTestWrite
     */
    public function testWrite($test, $check)
    {
        $msg     = 'Hybrid::write %s - Case: '.$check['case'];
        $counter = 0;
        $hybrid  = new Hybrid(array());

        // Let's replace the adapter with our mocks
        $file = new TestClosure(array(
            'write' => function() use($test){ return $test['mock']['file']; }
        ));

        ReflectionHelper::setValue($hybrid, 'fileAdapter', $file);

        if($test['adapter'])
        {
            $adapter = new TestClosure(array(
                'write' => function() use ($test, &$counter){
                    $counter++;
                    return $test['mock']['adapter'];
                }
            ));

            ReflectionHelper::setValue($hybrid, 'abstractionAdapter', $adapter);
        }

        $result = $hybrid->write('foobar.txt', 'dummy');

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
        $this->assertEquals($check['count'], $counter, sprintf($msg, 'Failed to correctly invoke the adapter'));
    }

    /**
     * @covers          Awf\Filesystem\Hybrid::delete
     * @dataProvider    HybridDataprovider::getTestDelete
     */
    public function testDelete($test, $check)
    {
        $msg     = 'Hybrid::delete %s - Case: '.$check['case'];
        $counter = 0;
        $hybrid  = new Hybrid(array());

        // Let's replace the adapter with our mocks
        $file = new TestClosure(array(
            'delete' => function() use($test){ return $test['mock']['file']; }
        ));

        ReflectionHelper::setValue($hybrid, 'fileAdapter', $file);

        if($test['adapter'])
        {
            $adapter = new TestClosure(array(
                'delete' => function() use ($test, &$counter){
                    $counter++;
                    return $test['mock']['adapter'];
                }
            ));

            ReflectionHelper::setValue($hybrid, 'abstractionAdapter', $adapter);
        }

        $result = $hybrid->delete('foobar.txt');

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
        $this->assertEquals($check['count'], $counter, sprintf($msg, 'Failed to correctly invoke the adapter'));
    }

    /**
     * @covers          Awf\Filesystem\Hybrid::copy
     * @dataProvider    HybridDataprovider::getTestCopy
     */
    public function testCopy($test, $check)
    {
        $msg     = 'Hybrid::copy %s - Case: '.$check['case'];
        $counter = 0;
        $hybrid  = new Hybrid(array());

        // Let's replace the adapter with our mocks
        $file = new TestClosure(array(
            'copy' => function() use($test){ return $test['mock']['file']; }
        ));

        ReflectionHelper::setValue($hybrid, 'fileAdapter', $file);

        if($test['adapter'])
        {
            $adapter = new TestClosure(array(
                'copy' => function() use ($test, &$counter){
                    $counter++;
                    return $test['mock']['adapter'];
                }
            ));

            ReflectionHelper::setValue($hybrid, 'abstractionAdapter', $adapter);
        }

        $result = $hybrid->copy('foobar.txt', 'dummy.txt');

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
        $this->assertEquals($check['count'], $counter, sprintf($msg, 'Failed to correctly invoke the adapter'));
    }

    /**
     * @covers          Awf\Filesystem\Hybrid::move
     * @dataProvider    HybridDataprovider::getTestMove
     */
    public function testMove($test, $check)
    {
        $msg     = 'Hybrid::move %s - Case: '.$check['case'];
        $counter = 0;
        $hybrid  = new Hybrid(array());

        // Let's replace the adapter with our mocks
        $file = new TestClosure(array(
            'move' => function() use($test){ return $test['mock']['file']; }
        ));

        ReflectionHelper::setValue($hybrid, 'fileAdapter', $file);

        if($test['adapter'])
        {
            $adapter = new TestClosure(array(
                'move' => function() use ($test, &$counter){
                    $counter++;
                    return $test['mock']['adapter'];
                }
            ));

            ReflectionHelper::setValue($hybrid, 'abstractionAdapter', $adapter);
        }

        $result = $hybrid->move('foobar.txt', 'dummy.txt');

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
        $this->assertEquals($check['count'], $counter, sprintf($msg, 'Failed to correctly invoke the adapter'));
    }

    /**
     * @covers          Awf\Filesystem\Hybrid::chmod
     * @dataProvider    HybridDataprovider::getTestChmod
     */
    public function testChmod($test, $check)
    {
        $msg     = 'Hybrid::chmod %s - Case: '.$check['case'];
        $counter = 0;
        $hybrid  = new Hybrid(array());

        // Let's replace the adapter with our mocks
        $file = new TestClosure(array(
            'chmod' => function() use($test){ return $test['mock']['file']; }
        ));

        ReflectionHelper::setValue($hybrid, 'fileAdapter', $file);

        if($test['adapter'])
        {
            $adapter = new TestClosure(array(
                'chmod' => function() use ($test, &$counter){
                    $counter++;
                    return $test['mock']['adapter'];
                }
            ));

            ReflectionHelper::setValue($hybrid, 'abstractionAdapter', $adapter);
        }

        $result = $hybrid->chmod('foobar.txt', 0777);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
        $this->assertEquals($check['count'], $counter, sprintf($msg, 'Failed to correctly invoke the adapter'));
    }

    /**
     * @covers          Awf\Filesystem\Hybrid::mkdir
     * @dataProvider    HybridDataprovider::getTestMkdir
     */
    public function testMkdir($test, $check)
    {
        $msg     = 'Hybrid::chmod %s - Case: '.$check['case'];
        $counter = 0;
        $hybrid  = new Hybrid(array());

        // Let's replace the adapter with our mocks
        $file = new TestClosure(array(
            'mkdir' => function() use($test){ return $test['mock']['file']; }
        ));

        ReflectionHelper::setValue($hybrid, 'fileAdapter', $file);

        if($test['adapter'])
        {
            $adapter = new TestClosure(array(
                'mkdir' => function() use ($test, &$counter){
                    $counter++;
                    return $test['mock']['adapter'];
                }
            ));

            ReflectionHelper::setValue($hybrid, 'abstractionAdapter', $adapter);
        }

        $result = $hybrid->mkdir('foobar.txt', 0777);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
        $this->assertEquals($check['count'], $counter, sprintf($msg, 'Failed to correctly invoke the adapter'));
    }

    /**
     * @covers          Awf\Filesystem\Hybrid::rmdir
     * @dataProvider    HybridDataprovider::getTestRmdir
     */
    public function testRmdir($test, $check)
    {
        $msg     = 'Hybrid::chmod %s - Case: '.$check['case'];
        $counter = 0;
        $hybrid  = new Hybrid(array());

        // Let's replace the adapter with our mocks
        $file = new TestClosure(array(
            'rmdir' => function() use($test){ return $test['mock']['file']; }
        ));

        ReflectionHelper::setValue($hybrid, 'fileAdapter', $file);

        if($test['adapter'])
        {
            $adapter = new TestClosure(array(
                'rmdir' => function() use ($test, &$counter){
                    $counter++;
                    return $test['mock']['adapter'];
                }
            ));

            ReflectionHelper::setValue($hybrid, 'abstractionAdapter', $adapter);
        }

        $result = $hybrid->rmdir('foobar.txt', true);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
        $this->assertEquals($check['count'], $counter, sprintf($msg, 'Failed to correctly invoke the adapter'));
    }

    /**
     * @covers          Awf\Filesystem\Hybrid::translatePath
     * @dataProvider    HybridDataprovider::getTestTranslatePath
     */
    public function testTranslatePath($test, $check)
    {
        $msg     = 'Hybrid::translatePath %s - Case: '.$check['case'];
        $counter = 0;
        $hybrid  = new Hybrid(array());

        // Let's replace the adapter with our mocks
        $file = new TestClosure(array(
            'translatePath' => function() use($test){ return $test['mock']['file']; }
        ));

        ReflectionHelper::setValue($hybrid, 'fileAdapter', $file);

        if($test['adapter'])
        {
            $adapter = new TestClosure(array(
                'translatePath' => function() use ($test, &$counter){
                    $counter++;
                    return $test['mock']['adapter'];
                }
            ));

            ReflectionHelper::setValue($hybrid, 'abstractionAdapter', $adapter);
        }

        $result = $hybrid->translatePath('foobar.txt');

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
        $this->assertEquals($check['count'], $counter, sprintf($msg, 'Failed to correctly invoke the adapter'));
    }

    /**
     * @covers          Awf\Filesystem\Hybrid::listFolders
     * @dataProvider    HybridDataprovider::getTestListFolders
     */
    public function testListFolders($test, $check)
    {
        $msg     = 'Hybrid::listFolders %s - Case: '.$check['case'];
        $counter = 0;
        $hybrid  = new Hybrid(array());

        // Let's replace the adapter with our mocks
        $file = new TestClosure(array(
            'listFolders' => function() use($test){
                if($test['mock']['file'] === 'exception'){
                    throw new \RuntimeException();
                }

                return $test['mock']['file'];
            }
        ));

        ReflectionHelper::setValue($hybrid, 'fileAdapter', $file);

        if($test['adapter'])
        {
            $adapter = new TestClosure(array(
                'listFolders' => function() use ($test, &$counter){
                    $counter++;

                    if($test['mock']['adapter'] === 'exception'){
                        throw new \RuntimeException();
                    }

                    return $test['mock']['adapter'];
                }
            ));

            ReflectionHelper::setValue($hybrid, 'abstractionAdapter', $adapter);
        }

        if($check['exception'])
        {
            $this->setExpectedException('RuntimeException');
        }

        $result = $hybrid->listFolders('foobar');

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
        $this->assertEquals($check['count'], $counter, sprintf($msg, 'Failed to correctly invoke the adapter'));
    }
}
