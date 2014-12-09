<?php

namespace Awf\Tests\DataModel;

use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\DataModelStub;

require_once 'PublishDataprovider.php';

/**
 * @covers      Awf\Mvc\DataModel::<protected>
 * @covers      Awf\Mvc\DataModel::<private>
 * @package     Awf\Tests\DataModel
 */
class DataModelPublishTest extends DatabaseMysqliCase
{
    /**
     * @group           DataModel
     * @group           DataModelArchive
     * @covers          Awf\Mvc\DataModel::archive
     * @dataProvider    PublishDataprovider::getTestArchive
     */
    public function testArchive($test, $check)
    {
        $msg     = 'DataModel::getFieldValue %s - Case: '.$check['case'];
        $methods = array();

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => $test['table']
            )
        ));

        if($test['mock']['before'])
        {
            $methods['onBeforeArchive'] = $test['mock']['before'];
        }

        if($test['mock']['after'])
        {
            $methods['onAfterArchive'] = $test['mock']['after'];
        }

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('save', 'getId'), array($container, $methods));
        $model->expects($this->any())->method('getId')->willReturn(1);
        $model->expects($check['save'] ? $this->once() : $this->never())->method('save')->willReturn(null);

        $dispatcher = $this->getMock('\\Awf\\Event\\Dispatcher', array('trigger'), array($container));
        $dispatcher->expects($this->exactly($check['dispatcher']))->method('trigger')->withConsecutive(
            array($this->equalTo('onBeforeArchive')),
            array($this->equalTo('onAfterArchive'))
        );

        ReflectionHelper::setValue($model, 'behavioursDispatcher', $dispatcher);
        ReflectionHelper::setValue($model, 'aliasFields', $test['mock']['alias']);

        if($check['exception'])
        {
            $this->setExpectedException('Exception');
        }

        $result = $model->archive();

        if($check['save'])
        {
            $enabled = $model->getFieldAlias('enabled');
            $value   = $model->$enabled;

            $this->assertEquals(2, $value, sprintf($msg, 'Should set the value of the enabled field to 2'));
        }

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an istance of itself'));
    }

    /**
     * @group           DataModel
     * @group           DataModelArchive
     * @covers          Awf\Mvc\DataModel::archive
     */
    public function testArchiveException()
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
        $model->archive();
    }

    /**
     * @group           DataModel
     * @group           DataModelTrash
     * @covers          Awf\Mvc\DataModel::trash
     * @dataProvider    PublishDataprovider::getTestTrash
     */
    public function testTrash($test, $check)
    {
        $before = 0;
        $after  = 0;
        $msg    = 'DataModel::trash %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest_extended'
            )
        ));

        // I am passing those methods so I can double check if the method is really called
        $methods = array(
            'onBeforeTrash' => function() use(&$before){
                $before++;
            },
            'onAfterTrash' => function() use(&$after){
                $after++;
            }
        );

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('save', 'getId', 'findOrFail'), array($container, $methods));
        $model->expects($this->any())->method('save')->willReturn(null);
        $model->expects($this->any())->method('getId')->willReturn(1);
        $model->expects($check['find'] ? $this->once() : $this->never())->method('findOrFail')->willReturn(null);

        // Let's mock the dispatcher, too. So I can check if events are really triggered
        $dispatcher = $this->getMock('\\Awf\\Event\\Dispatcher', array('trigger'), array($container));
        $dispatcher->expects($this->exactly($check['dispatcher']))->method('trigger')->withConsecutive(
            array($this->equalTo('onBeforeTrash')),
            array($this->equalTo('onAfterTrash'))
        );

        ReflectionHelper::setValue($model, 'behavioursDispatcher', $dispatcher);

        $result = $model->trash($test['id']);

        $enabled = $model->getFieldValue('enabled');

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals($check['before'], $before, sprintf($msg, 'Failed to call the onBefore method'));
        $this->assertEquals($check['after'], $after, sprintf($msg, 'Failed to call the onAfter method'));
        $this->assertSame($check['enabled'], $enabled, sprintf($msg, 'Failed to set the enabled field'));
    }

    /**
     * @group           DataModel
     * @group           DataModelTrash
     * @covers          Awf\Mvc\DataModel::trash
     * @dataProvider    PublishDataprovider::getTestTrashException
     */
    public function testTrashException($test, $check)
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => $test['table']
            )
        ));

        $this->setExpectedException($check['exception']);

        $model = new DataModelStub($container);
        $model->trash($test['id']);
    }

    /**
     * @group           DataModel
     * @group           DataModelPublish
     * @covers          Awf\Mvc\DataModel::publish
     * @dataProvider    PublishDataprovider::getTestPublish
     */
    public function testPublish($test, $check)
    {
        $before = 0;
        $after  = 0;
        $msg    = 'DataModel::publish %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => $test['table']
            )
        ));

        // I am passing those methods so I can double check if the method is really called
        $methods = array(
            'onBeforePublish' => function() use(&$before){
                $before++;
            },
            'onAfterPublish' => function() use(&$after){
                $after++;
            }
        );

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('save', 'getId'), array($container, $methods));
        $model->expects($this->any())->method('save')->willReturn(null);
        $model->expects($this->any())->method('getId')->willReturn(1);

        // Let's mock the dispatcher, too. So I can check if events are really triggered
        $dispatcher = $this->getMock('\\Awf\\Event\\Dispatcher', array('trigger'), array($container));
        $dispatcher->expects($this->exactly($check['dispatcher']))->method('trigger')->withConsecutive(
            array($this->equalTo('onBeforePublish')),
            array($this->equalTo('onAfterPublish'))
        );

        ReflectionHelper::setValue($model, 'behavioursDispatcher', $dispatcher);

        $result = $model->publish($test['state']);

        $enabled = $model->getFieldValue('enabled');

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals($check['before'], $before, sprintf($msg, 'Failed to call the onBefore method'));
        $this->assertEquals($check['after'], $after, sprintf($msg, 'Failed to call the onAfter method'));
        $this->assertEquals($check['enabled'], $enabled, sprintf($msg, 'Failed to set the enabled field'));
    }

    /**
     * @group           DataModel
     * @group           DataModelPublish
     * @covers          Awf\Mvc\DataModel::publish
     */
    public function testPublishException()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        $this->setExpectedException('Awf\Mvc\DataModel\Exception\RecordNotLoaded');

        $model = new DataModelStub($container);
        $model->publish();
    }

    /**
     * @group           DataModel
     * @group           DataModelRestore
     * @covers          Awf\Mvc\DataModel::restore
     * @dataProvider    PublishDataprovider::getTestrestore
     */
    public function testRestore($test, $check)
    {
        $before = 0;
        $after  = 0;
        $msg    = 'DataModel::restore %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => $test['table']
            )
        ));

        // I am passing those methods so I can double check if the method is really called
        $methods = array(
            'onBeforeRestore' => function() use(&$before){
                $before++;
            },
            'onAfterRestore' => function() use(&$after){
                $after++;
            }
        );

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('save', 'getId', 'findOrFail'), array($container, $methods));
        $model->expects($this->any())->method('save')->willReturn(null);
        $model->expects($this->any())->method('getId')->willReturn(1);
        $model->expects($check['find'] ? $this->once() : $this->never())->method('findOrFail')->willReturn(null);

        // Let's mock the dispatcher, too. So I can check if events are really triggered
        $dispatcher = $this->getMock('\\Awf\\Event\\Dispatcher', array('trigger'), array($container));
        $dispatcher->expects($this->exactly($check['dispatcher']))->method('trigger')->withConsecutive(
            array($this->equalTo('onBeforeRestore')),
            array($this->equalTo('onAfterRestore'))
        );

        ReflectionHelper::setValue($model, 'behavioursDispatcher', $dispatcher);

        $result = $model->restore($test['id']);

        $enabled = $model->getFieldValue('enabled');

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals($check['before'], $before, sprintf($msg, 'Failed to call the onBefore method'));
        $this->assertEquals($check['after'], $after, sprintf($msg, 'Failed to call the onAfter method'));
        $this->assertSame($check['enabled'], $enabled, sprintf($msg, 'Failed to set the enabled field'));
    }

    /**
     * @group           DataModel
     * @group           DataModelRestore
     * @covers          Awf\Mvc\DataModel::restore
     */
    public function testRestoreException()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest_extended'
            )
        ));

        $this->setExpectedException('Awf\Mvc\DataModel\Exception\RecordNotLoaded');

        $model = new DataModelStub($container);
        $model->restore();
    }

    /**
     * @group           DataModel
     * @group           DataModelUnpublish
     * @covers          Awf\Mvc\DataModel::unpublish
     * @dataProvider    PublishDataprovider::getTestUnpublish
     */
    public function testUnpublish($test, $check)
    {
        $before = 0;
        $after  = 0;
        $msg    = 'DataModel::unpublish %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => $test['table']
            )
        ));

        // I am passing those methods so I can double check if the method is really called
        $methods = array(
            'onBeforeUnpublish' => function() use(&$before){
                $before++;
            },
            'onAfterUnpublish' => function() use(&$after){
                $after++;
            }
        );

        $model = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\DataModelStub', array('save', 'getId'), array($container, $methods));
        $model->expects($this->any())->method('save')->willReturn(null);
        $model->expects($this->any())->method('getId')->willReturn(1);

        // Let's mock the dispatcher, too. So I can check if events are really triggered
        $dispatcher = $this->getMock('\\Awf\\Event\\Dispatcher', array('trigger'), array($container));
        $dispatcher->expects($this->exactly($check['dispatcher']))->method('trigger')->withConsecutive(
            array($this->equalTo('onBeforeUnpublish')),
            array($this->equalTo('onAfterUnpublish'))
        );

        ReflectionHelper::setValue($model, 'behavioursDispatcher', $dispatcher);

        $result = $model->unpublish();

        $enabled = $model->getFieldValue('enabled');

        $this->assertInstanceOf('\\Awf\\Mvc\\DataModel', $result, sprintf($msg, 'Should return an instance of itself'));
        $this->assertEquals($check['before'], $before, sprintf($msg, 'Failed to call the onBefore method'));
        $this->assertEquals($check['after'], $after, sprintf($msg, 'Failed to call the onAfter method'));
        $this->assertSame($check['enabled'], $enabled, sprintf($msg, 'Failed to set the enabled field'));
    }

    /**
     * @group           DataModel
     * @group           DataModelUnpublish
     * @covers          Awf\Mvc\DataModel::unpublish
     */
    public function testUnpublishException()
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
        $model->unpublish();
    }
}
