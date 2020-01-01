<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Database;

use Awf\Database\Restore;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Utils\TestClosure;
use Fakeapp\Application;

require_once 'RestoreDataprovider.php';

/**
 * @covers      Awf\Database\Restore::<protected>
 * @covers      Awf\Database\Restore::<private>
 */
class RestoreTest extends DatabaseMysqliCase
{
    /**
     * @covers          Awf\Database\Restore::__construct
     * @dataProvider    RestoreDataprovider::getTest__construct
     */
    public function test__construct($test, $check)
    {
        $msg       = 'Restore::__construct %s - Case: '.$check['case'];
        $container = Application::getInstance()->getContainer();

        if($test['dbrestore'])
        {
            $container['dbrestore'] = array(
                'maxexectime' => $test['maxexectime'],
                'runtimebias' => $test['runtimebias'],
                'dbkey'       => $test['dbkey']
            );
        }

        if($check['exception'])
        {
            $this->setExpectedException('Exception');
        }

        $restore = $this->getMock('Awf\Tests\Stubs\Database\RestoreMock', array('populatePartsMap'), array(), '', false);
        $restore->__construct($container);

        // Let's check if the timer was correctly set
        $timer    = ReflectionHelper::getValue($restore, 'timer');
        $max_exec = ReflectionHelper::getValue($timer, 'max_exec_time');

        $this->assertEquals($check['max_exec'], $max_exec, sprintf($msg, 'Failed to set Timer max execution time'));
    }

    /**
     * @covers          Awf\Database\Restore::getInstance
     * @dataProvider    RestoreDataprovider::getTestGetInstance
     */
    public function testGetInstance($test, $check)
    {
        $msg       = 'Restore::__construct %s - Case: '.$check['case'];
        $container = Application::getInstance()->getContainer();

        ReflectionHelper::setValue('Awf\Database\Restore', 'instances', array());

        if($test['cache'])
        {
            $cache = $this->getMock('Awf\Tests\Stubs\Database\RestoreMock', array('populatePartsMap'), array(), '', false);
            ReflectionHelper::setValue('Awf\Database\Restore', 'instances', array('cache' => $cache));
        }

        if($test['dbrestore'])
        {
            $container['dbrestore'] = array(
                'maxexectime' => $test['maxexectime'],
                'runtimebias' => $test['runtimebias'],
                'dbkey'       => $test['dbkey'],
                'dbtype'      => $test['dbtype'],
                // I have to pass the keys required by Restore::populatePartsMap, since the class is automatically created
                // I am using the same connection details of the parent class, so it should work
                'sqlfile'       => 'test.sql',
                'dbhost'		=> self::$options['host'],
                'dbuser'		=> self::$options['user'],
                'dbpass'		=> self::$options['password'],
                'dbname'		=> self::$options['database'],
                'prefix'		=> 'awf_',
            );
        }

        if($check['exception'])
        {
            $this->setExpectedException('Exception');
        }

        $result = Restore::getInstance($container);

        $this->assertInstanceOf($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @covers          Awf\Database\Restore::stepRestoration
     * @dataProvider    RestoreDataprovider::getTestStepRestoration
     */
    public function testStepRestoration($test, $check)
    {
        $msg       = 'Restore::stepRestoration %s - Case: '.$check['case'];
        $container = Application::getInstance()->getContainer();

        $container['dbrestore'] = array(
            'maxexectime' => 30,
            'runtimebias' => 75,
            'dbkey'       => 'awftest',
            'sqlfile'     => 'fakerestore.sql'
        );

        $restore = $this->getMock('Awf\Tests\Stubs\Database\RestoreMock', array('readNextLine'), array($container));
        $restore->expects($this->any())->method('readNextLine')->willReturnCallback(function() use(&$test, $restore){
            if($test['mock']['nextLine'])
            {
                $result = array_shift($test['mock']['nextLine']);

                if($result == 'exception')
                {
                    throw new \Exception('', 201);
                }

                // Let's get a reference to the current file, so I can move the pointer
                $file = ReflectionHelper::getValue($restore, 'file');

                // Do I want to be at the end of the file or just move the pointer?
                if($result == 'EOF')
                {
                    @fseek($file, 0, SEEK_END);
                }
                else
                {
                    @fseek($file, strlen($result), SEEK_CUR);
                }

                return $result;
            }

            throw new \Exception('', 200);
        });

        $timer = new TestClosure(array(
            'getTimeLeft' => function() use (&$test){
                $result = 0;

                if($test['mock']['timer'])
                {
                    $result = array_shift($test['mock']['timer']);
                }

                return $result;
            },
            'getRunningTime' => function() use($test){ return $test['mock']['running'];}
        ));

        ReflectionHelper::setValue($restore, 'timer', $timer);

        $result = $restore->stepRestoration();

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }
}
