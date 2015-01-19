<?php
/**
 * @package        awf
 * @copyright      2014-2015 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 *
 * This class is adapted from Joomla! Framework
 */

namespace Awf\Tests\Database;

use Awf\Database\Restore;
use Awf\Tests\Helpers\ReflectionHelper;
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
                'sqlfile'     => 'test.sql',
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
}