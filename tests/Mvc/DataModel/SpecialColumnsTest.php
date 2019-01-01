<?php
/**
 * @package    awf
 * @copyright  Copyright (c)2014-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU GPL version 3 or later
 */

namespace Awf\Tests\DataModel;

use Awf\Date\Date;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\DataModelStub;
use Awf\Tests\Stubs\Utils\ObserverClosure;
use Awf\Tests\Stubs\Utils\TestClosure;

require_once 'SpecialColumnsDataprovider.php';

/**
 * @covers      Awf\Mvc\DataModel::<protected>
 * @covers      Awf\Mvc\DataModel::<private>
 * @package     Awf\Tests\DataModel
 */
class DataModelSpecialColumnsTest extends DatabaseMysqliCase
{
	protected function setUp($resetContainer = true)
	{
		parent::setUp(false);
	}

    /**
     * @group           DataModel
     * @group           DataModelReorder
     * @covers          Awf\Mvc\DataModel::reorder
     * @dataProvider    SpecialColumnsDataprovider::getTestReorder
     */
    public function testReorder($test, $check)
    {
        // Please note that if you try to debug this test, you'll get a "Couldn't fetch mysqli_result" error
        // That's harmless and appears in debug only, you might want to suppress exception throwing
        //\PHPUnit_Framework_Error_Warning::$enabled = false;

        $before = 0;
        $after  = 0;
        $db     = self::$driver;
        $msg    = 'DataModel::reorder %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest_extended'
            )
        ));

        // I am passing those methods so I can double check if the method is really called
        $methods = array(
            'onBeforeReorder' => function() use(&$before){
                $before++;
            },
            'onAfterReorder' => function() use(&$after){
                $after++;
            }
        );

        // Let's mess up the records a little
        foreach($test['mock']['ordering'] as $id => $order)
        {
            $query = $db->getQuery(true)
                ->update($db->qn('#__dbtest_extended'))
                ->set($db->qn('ordering').' = '.$db->q($order))
                ->where($db->qn('id').' = '.$db->q($id));

            $db->setQuery($query)->execute();
        }

        $model = new DataModelStub($container, $methods);

        // Let's mock the dispatcher, too. So I can check if events are really triggered
        $dispatcher = $this->getMock('\\Awf\\Event\\Dispatcher', array('trigger'), array($container));
        $dispatcher->expects($this->exactly(2))->method('trigger')->withConsecutive(
            array($this->equalTo('onBeforeReorder')),
            array($this->equalTo('onAfterReorder'))
        );

        ReflectionHelper::setValue($model, 'behavioursDispatcher', $dispatcher);

        $result = $model->reorder($test['where']);

        // Now let's take a look at the updated records
        $query = $db->getQuery(true)
            ->select('ordering')
            ->from($db->qn('#__dbtest_extended'))
            ->order($db->qn($model->getIdFieldName()).' ASC');
        $ordering = $db->setQuery($query)->loadColumn();

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals(1, $before, sprintf($msg, 'Failed to invoke the onBefore method'));
        $this->assertEquals(1, $after, sprintf($msg, 'Failed to invoke the onAfter method'));
        $this->assertEquals($check['order'], $ordering, sprintf($msg, 'Failed to save the correct order'));
    }

    /**
     * @group           DataModel
     * @group           DataModelReorder
     * @covers          Awf\Mvc\DataModel::reorder
     */
    public function testReorderException()
    {
        $this->setExpectedException('\\Awf\\Mvc\\DataModel\\Exception\\SpecialColumnMissing');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $model = new DataModelStub($container);
        $model->reorder();
    }

    /**
     * @group           DataModel
     * @group           DataModelMove
     * @covers          Awf\Mvc\DataModel::move
     * @dataProvider    SpecialColumnsDataprovider::getTestMove
     */
    public function testMove($test, $check)
    {
        // Please note that if you try to debug this test, you'll get a "Couldn't fetch mysqli_result" error
        // That's harmless and appears in debug only, you might want to suppress exception thowing
        //\PHPUnit_Framework_Error_Warning::$enabled = false;

        $before     = 0;
        $beforeDisp = 0;
        $after      = 0;
        $afterDisp  = 0;
        $db         = self::$driver;
        $msg        = 'DataModel::move %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest_extended'
            )
        ));

        // I am passing those methods so I can double check if the method is really called
        $methods = array(
            'onBeforeMove' => function() use(&$before){
                $before++;
            },
            'onAfterMove' => function() use(&$after){
                $after++;
            }
        );

        $model      = new DataModelStub($container, $methods);
        $dispatcher = $model->getBehavioursDispatcher();

        // Let's attach a custom observer, so I can mock and check all the calls performed by the dispatcher
        // P.A. The object is immediatly attached to the dispatcher, so I don't need to manually do that
        new ObserverClosure($dispatcher, array(
            'onBeforeMove' => function(&$subject, &$delta, &$where) use ($test, &$beforeDisp){
                if(!is_null($test['mock']['find'])){
                    $subject->find($test['mock']['find']);
                }

                if(!is_null($test['mock']['delta'])){
                    $delta = $test['mock']['delta'];
                }

                if(!is_null($test['mock']['where'])){
                    $where = $test['mock']['where'];
                }

                $beforeDisp++;
            },
            'onAfterMove' => function() use(&$afterDisp){
                $afterDisp++;
            }
        ));

        if($test['id'])
        {
            $model->find($test['id']);
        }

        $result = $model->move($test['delta'], $test['where']);

        // Now let's take a look at the updated records
        $query = $db->getQuery(true)
            ->select('ordering')
            ->from($db->qn('#__dbtest_extended'))
            ->order($db->qn($model->getIdFieldName()).' ASC');
        $ordering = $db->setQuery($query)->loadColumn();

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals(1, $before, sprintf($msg, 'Failed to invoke the onBefore method'));
        $this->assertEquals(1, $beforeDisp, sprintf($msg, 'Failed to invoke the onBeforeMove event'));
        $this->assertEquals(1, $after, sprintf($msg, 'Failed to invoke the onAfter method'));
        $this->assertEquals(1, $afterDisp, sprintf($msg, 'Failed to invoke the onAfterMove event'));
        $this->assertEquals($check['order'], $ordering, sprintf($msg, 'Failed to save the correct order'));
    }

    /**
     * @group           DataModel
     * @group           DataModelMove
     * @covers          Awf\Mvc\DataModel::move
     * @dataProvider    SpecialColumnsDataprovider::getTestMoveException
     */
    public function testMoveException($test, $check)
    {
        $this->setExpectedException($check['exception']);

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => $test['table']
            )
        ));

        $model = new DataModelStub($container);
        $model->move(-1);
    }

    /**
     * @group           DataModel
     * @group           DataModelLock
     * @covers          Awf\Mvc\DataModel::lock
     * @dataProvider    SpecialColumnsDataprovider::getTestLock
     */
    public function testLock($test, $check)
    {
        $before = 0;
        $after  = 0;
        $msg    = 'DataModel::lock %s - Case: '.$check['case'];

        $fakeUserManager = new TestClosure(array(
            'getUser' => function() use ($test){
                return new TestClosure(array(
                    'getId' => function() use ($test){
                        return $test['mock']['user_id'];
                    }
                ));
            }
        ));

        $container = new Container(array(
            'db' => self::$driver,
            'userManager' => $fakeUserManager,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => $test['table']
            )
        ));

        // I am passing those methods so I can double check if the method is really called
        $methods = array(
            'onBeforeLock' => function() use(&$before){
                $before++;
            },
            'onAfterLock' => function() use(&$after){
                $after++;
            }
        );

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('save', 'getId'), array($container, $methods));
        $model->expects($this->any())->method('save')->willReturn(null);
        $model->expects($this->any())->method('getId')->willReturn(1);

        // Let's mock the dispatcher, too. So I can check if events are really triggered
        $dispatcher = $this->getMock('\\Awf\\Event\\Dispatcher', array('trigger'), array($container));
        $dispatcher->expects($this->exactly($check['dispatcher']))->method('trigger')->withConsecutive(
            array($this->equalTo('onBeforeLock')),
            array($this->equalTo('onAfterLock'))
        );

        ReflectionHelper::setValue($model, 'behavioursDispatcher', $dispatcher);

        $result = $model->lock($test['user_id']);

        $locked_by = $model->getFieldValue('locked_by');
        $locked_on = $model->getFieldValue('locked_on');

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals($check['before'], $before, sprintf($msg, 'Failed to call the onBefore method'));
        $this->assertEquals($check['after'], $after, sprintf($msg, 'Failed to call the onAfter method'));
        $this->assertEquals($check['locked_by'], $locked_by, sprintf($msg, 'Failed to set the locking user'));

        // The time is calculated on the fly, so I can only check if it's null or not
        if($check['locked_on'])
        {
            $this->assertNotNull($locked_on, sprintf($msg, 'Failed to set the locking time'));
        }
        else
        {
            $this->assertNull($locked_on, sprintf($msg, 'Failed to set the locking time'));
        }
    }

    /**
     * @group           DataModel
     * @group           DataModelLock
     * @covers          Awf\Mvc\DataModel::lock
     */
    public function testLockException()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $this->setExpectedException('RuntimeException');

        $model = new DataModelStub($container);
        $model->lock();
    }

    /**
     * @group           DataModel
     * @group           DataModelUnlock
     * @covers          Awf\Mvc\DataModel::unlock
     * @dataProvider    SpecialColumnsDataprovider::getTestUnlock
     */
    public function testUnlock($test, $check)
    {
        $before = 0;
        $after  = 0;
        $msg    = 'DataModel::unlock %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => $test['table']
            )
        ));

        // I am passing those methods so I can double check if the method is really called
        $methods = array(
            'onBeforeUnlock' => function() use(&$before){
                $before++;
            },
            'onAfterUnlock' => function() use(&$after){
                $after++;
            }
        );

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('save', 'getId'), array($container, $methods));
        $model->expects($this->any())->method('save')->willReturn(null);
        $model->expects($this->any())->method('getId')->willReturn(1);

        // Let's mock the dispatcher, too. So I can check if events are really triggered
        $dispatcher = $this->getMock('\\Awf\\Event\\Dispatcher', array('trigger'), array($container));
        $dispatcher->expects($this->exactly($check['dispatcher']))->method('trigger')->withConsecutive(
            array($this->equalTo('onBeforeUnlock')),
            array($this->equalTo('onAfterUnlock'))
        );

        ReflectionHelper::setValue($model, 'behavioursDispatcher', $dispatcher);

        if($model->hasField('locked_on'))
        {
            $now = new Date();
            $model->setFieldValue('locked_on', $now->toSql());
        }

        $result = $model->unlock();

        $locked_by = $model->getFieldValue('locked_by');
        $locked_on = $model->getFieldValue('locked_on');

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals($check['before'], $before, sprintf($msg, 'Failed to call the onBefore method'));
        $this->assertEquals($check['after'], $after, sprintf($msg, 'Failed to call the onAfter method'));
        $this->assertEquals($check['locked_by'], $locked_by, sprintf($msg, 'Failed to set the locking user'));

        // The time is calculated on the fly, so I can only check if it's null or not
        if($check['locked_on'])
        {
            $this->assertEquals(self::$driver->getNullDate(), $locked_on, sprintf($msg, 'Failed to set the locking time'));
        }
        else
        {
            $this->assertNull($locked_on, sprintf($msg, 'Failed to set the locking time'));
        }
    }

    /**
     * @group           DataModel
     * @group           DataModelUnlock
     * @covers          Awf\Mvc\DataModel::unlock
     */
    public function testUnlockException()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $this->setExpectedException('Awf\Mvc\DataModel\Exception\RecordNotLoaded');

        $model = new DataModelStub($container);
        $model->unlock();
    }

    /**
     * @group           DataModel
     * @group           DataModelTouch
     * @covers          Awf\Mvc\DataModel::touch
     * @dataProvider    SpecialColumnsDataprovider::getTestTouch
     */
    public function testTouch($test, $check)
    {
        $msg    = 'DataModel::touch %s - Case: '.$check['case'];

        $fakeUserManager = new TestClosure(array(
            'getUser' => function() use ($test){
                return new TestClosure(array(
                    'getId' => function() use ($test){
                        return $test['mock']['user_id'];
                    }
                ));
            }
        ));

        $container = new Container(array(
            'db' => self::$driver,
            'userManager' => $fakeUserManager,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => $test['table']
            )
        ));

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('save', 'getId'), array($container));
        $model->expects($this->any())->method('save')->willReturn(null);
        $model->expects($this->any())->method('getId')->willReturn(1);

        $result = $model->touch($test['user_id']);

        $modified_by = $model->getFieldValue('modified_by');
        $modified_on = $model->getFieldValue('modified_on');

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals($check['modified_by'], $modified_by, sprintf($msg, 'Failed to set the modifying user'));

        // The time is calculated on the fly, so I can only check if it's null or not
        if($check['modified_on'])
        {
            $this->assertNotNull($modified_on, sprintf($msg, 'Failed to set the modifying time'));
        }
        else
        {
            $this->assertNull($modified_on, sprintf($msg, 'Failed to set the modifying time'));
        }
    }

    /**
     * @group           DataModel
     * @group           DataModelTouch
     * @covers          Awf\Mvc\DataModel::touch
     */
    public function testTouchException()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $this->setExpectedException('Awf\Mvc\DataModel\Exception\RecordNotLoaded');

        $model = new DataModelStub($container);
        $model->touch();
    }
}
