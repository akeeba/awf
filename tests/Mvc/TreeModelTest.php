<?php
/**
 * @package        awf
 * @subpackage     tests.date.date
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 *
 * This class is adapted from Joomla! Framework
 */

namespace Awf\Tests\TreeModel;

use Awf\Tests\Database\DatabaseMysqlCase;
use Awf\Database\Driver;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Mvc\TreeModelStub;

require_once 'TreeModelDataprovider.php';

class TreeModelTest extends DatabaseMysqlCase
{
    /**
     * @group           TreeModel
     * @group           TreeModelCheck
     * @covers          TreeModel::check
     * @dataProvider    TreeModelDataprovider::getTestCheck
     */
    public function testCheck($test, $check)
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => $test['id'],
                'tableName'   => $test['table']
            )
        ));

        $table = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\TreeModelStub', array('resetTreeCache'), array($container));
        $table->expects($this->any())->method('resetTreeCache')->willReturn(null);

        foreach($test['fields'] as $field => $value)
        {
            $table->$field = $value;
        }

        $return = $table->check();

        $this->assertInstanceOf('\\Awf\Mvc\\TreeModel', $return, 'TreeModel::check should return an instance of itself - Case: '.$check['case']);

        foreach($check['fields'] as $field => $expected)
        {
            if(is_null($expected))
            {
                $this->assertObjectNotHasAttribute($field, $table, 'TreeModel::check set the field '.$field.' even if it should not - Case: '.$check['case']);
            }
            else
            {
                $this->assertEquals($expected, $table->$field, 'TreeModel::check failed to set the field '.$field.' - Case: '.$check['case']);
            }
        }
    }

    /**
     * @group               TreeModelForceDelete
     * @group               TreeModel
     * @covers              TreeModel::forceDelete
     * @dataProvider        TreeModelDataprovider::getTestForceDelete
     */
    public function testForceDelete($test, $check)
    {
        $msg = 'TreeModel::forceDelete %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $db = self::$driver;

        $table = new TreeModelStub($container, array(
            'onBeforeDelete' => $test['mock']['before']
        ));

        if($test['loadid'])
        {
            $table->findOrFail($test['loadid']);
        }

        $return = $table->forceDelete($test['delete']);

        if($check['return'])
        {
            $this->assertInstanceOf('\\Awf\\Mvc\\TreeModel', $return, sprintf($msg, 'Should return an instance of itself'));
        }
        else
        {
            $this->assertFalse($return, sprintf($msg, 'Should return false'));
        }


        $pk    = $table->getIdFieldName();
        $query = $db->getQuery(true)->select($pk)->from($table->getTableName());
        $items = $db->setQuery($query)->loadColumn();

        $this->assertEmpty(array_intersect($check['deleted'], $items), sprintf($msg, 'Faiiled to delete all the items'));

        $query = $db->getQuery(true)
                    ->select('*')
                    ->from($table->getTableName())
                    ->where($db->qn($pk).' IN('.implode(',', array_keys($check['nodes'])).')');
        $nodes = $db->setQuery($query)->loadObjectList();

        foreach($nodes as $node)
        {
            $this->assertEquals($check['nodes'][$node->$pk]['lft'], $node->lft, sprintf($msg, 'Failed to update the lft value of the node with id '.$node->$pk));
            $this->assertEquals($check['nodes'][$node->$pk]['rgt'], $node->rgt, sprintf($msg, 'Failed to update the rgt value of the node with id '.$node->$pk));
        }
    }

    /**
     * @group               TreeModelReorder
     * @group               TreeModel
     * @covers              TreeModel::reorder
     */
    public function testReorder()
    {
        $this->setExpectedException('RuntimeException');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table = new TreeModelStub($container);
        $table->reorder();
    }

    /**
     * @group               TreeModelMove
     * @group               TreeModel
     * @covers              TreeModel::move
     */
    public function testMove()
    {
        $this->setExpectedException('RuntimeException');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table = new TreeModelStub($container);
        $table->move(-1);
    }

    /**
     * @group               TreeModelCreate
     * @group               TreeModel
     * @covers              TreeModel::create
     * @dataProvider        TreeModelDataprovider::getTestCreate
     */
    public function testCreate($test)
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $matcher = $this->never();

        if(!$test['root'])
        {
            $matcher = $this->once();
        }

        $table = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\TreeModelStub', array('insertAsChildOf', 'getParent'), array($container));

        // This is just a little trick, so insertAsChildOf won't complain about the argument passed
        \PHPUnit_Framework_Error_Notice::$enabled = false;
        $table->expects($this->once())->method('insertAsChildOf')->willReturnSelf();
        $table->expects($matcher)->method('getParent')->willReturnSelf();

        $table->findOrFail($test['loadid']);
        $table->create($test['data']);

        \PHPUnit_Framework_Error_Notice::$enabled = true;
    }

    /**
     * @group               TreeModelInsertAsRoot
     * @group               TreeModel
     * @covers              TreeModel::insertAsRoot
     */
    public function testInsertAsRoot()
    {
        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table = new TreeModelStub($container);

        $table->title = 'New root';
        $table->insertAsRoot();

        $this->assertTrue($table->isRoot(), 'TreeModel::insertAsRoot failed to create a new root');
    }

    /**
     * @group               TreeModelInsertAsRoot
     * @group               TreeModel
     * @covers              TreeModel::insertAsRoot
     */
    public function testInsertAsRootException()
    {
        $this->setExpectedException('RuntimeException');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table = new TreeModelStub($container);

        $table->findOrFail(1);
        $table->insertAsRoot();
    }

    /**
     * @group               TreeModelInsertAsFirstChildOf
     * @group               TreeModel
     * @covers              TreeModel::insertAsFirstChildOf
     * @dataProvider        TreeModelDataprovider::getTestInsertAsFirstChildOf
     */
    public function testInsertAsFirstChildOf($test, $check)
    {
        /** @var TreeModelStub $table */
        /** @var TreeModelStub $parent */

        $msg = 'TreeModel::insertAsFirstChildOf %s - Case: '.$check['case'];
        $db  = self::$driver;

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table  = new TreeModelStub($container);
        $parent = $table->getClone();

        if($test['loadid'])
        {
            $table->findOrFail($test['loadid']);
        }

        if($test['title'])
        {
            $table->title = $test['title'];
        }

        $parent->findOrFail($test['parentid']);
        $parentLft = $parent->lft;
        $parentRgt = $parent->rgt;

        $return = $table->insertAsFirstChildOf($parent);

        $this->assertInstanceOf('\\Awf\\Mvc\\TreeModel', $return, sprintf($msg, 'Should return an instance of itself'));

        // Assertions on the objects
        $this->assertNotEquals($test['loadid'], $table->getId(), sprintf($msg, 'Should always create a new node'));

        $this->assertEquals($parentLft, $parent->lft, sprintf($msg, 'Should not touch the lft value of the parent'));
        $this->assertEquals($parentRgt + 2, $parent->rgt, sprintf($msg, 'Should increase the rgt value by 2'));
        $this->assertEquals(1, $table->rgt - $table->lft, sprintf($msg, 'Should insert the node as leaf'));
        $this->assertEquals(1, $table->lft - $parent->lft, sprintf($msg, 'Should insert the node as first child'));

        // Great, the returned objects are ok, what about the ACTUAL data saved inside the db?
        $query = $db->getQuery(true)
                    ->select('*')
                    ->from('#__dbtest_nestedsets')
                    ->where('dbtest_nestedset_id = '.$table->dbtest_nestedset_id);
        $nodeDb = $db->setQuery($query)->loadObject();

        $query = $db->getQuery(true)
                    ->select('*')
                    ->from('#__dbtest_nestedsets')
                    ->where('dbtest_nestedset_id = '.$parent->dbtest_nestedset_id);
        $parentDb = $db->setQuery($query)->loadObject();

        $this->assertEquals($table->lft, $nodeDb->lft, sprintf($msg, 'Children object and database lft values are not the same'));
        $this->assertEquals($table->rgt, $nodeDb->rgt, sprintf($msg, 'Children object and database rgt values are not the same'));
        $this->assertEquals($parent->lft, $parentDb->lft, sprintf($msg, 'Parent object and database lft values are not the same'));
        $this->assertEquals($parent->rgt, $parentDb->rgt, sprintf($msg, 'Parent object and database rgt values are not the same'));
    }

    /**
     * @group               TreeModelInsertAsFirstChildOf
     * @group               TreeModel
     * @covers              TreeModel::insertAsFirstChildOf
     */
    public function testInsertAsFirstChildOfException()
    {
        $this->setExpectedException('RuntimeException');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table  = new TreeModelStub($container);
        $parent = $table->getClone();

        $table->insertAsFirstChildOf($parent);
    }

    /**
     * @group               TreeModelInsertAsLastChildOf
     * @group               TreeModel
     * @covers              TreeModel::insertAsLastChildOf
     * @dataProvider        TreeModelDataprovider::getTestInsertAsLastChildOf
     */
    public function testInsertAsLastChildOf($test, $check)
    {
        /** @var TreeModelStub $table */
        /** @var TreeModelStub $parent */

        $msg = 'TreeModel::insertAsLastChildOf %s - Case: '.$check['case'];
        $db  = self::$driver;

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table  = new TreeModelStub($container);
        $parent = $table->getClone();

        if($test['loadid'])
        {
            $table->findOrFail($test['loadid']);
        }

        if($test['title'])
        {
            $table->title = $test['title'];
        }

        $parent->findOrFail($test['parentid']);
        $parentLft = $parent->lft;
        $parentRgt = $parent->rgt;

        $return = $table->insertAsLastChildOf($parent);

        $this->assertInstanceOf('\\Awf\\Mvc\\TreeModel', $return, sprintf($msg, 'Should return an instance of itself'));

        // Assertions on the objects
        $this->assertNotEquals($test['loadid'], $table->getId(), sprintf($msg, 'Should always create a new node'));

        $this->assertEquals($parentLft, $parent->lft, sprintf($msg, 'Should not touch the lft value of the parent'));
        $this->assertEquals($parentRgt + 2, $parent->rgt, sprintf($msg, 'Should increase the rgt value by 2'));
        $this->assertEquals(1, $table->rgt - $table->lft, sprintf($msg, 'Should insert the node as leaf'));
        $this->assertEquals(1, $parent->rgt - $table->rgt, sprintf($msg, 'Should insert the node as last child'));

        // Great, the returned objects are ok, what about the ACTUAL data saved inside the db?
        $query = $db->getQuery(true)
                    ->select('*')
                    ->from('#__dbtest_nestedsets')
                    ->where('dbtest_nestedset_id = '.$table->dbtest_nestedset_id);
        $nodeDb = $db->setQuery($query)->loadObject();

        $query = $db->getQuery(true)
                    ->select('*')
                    ->from('#__dbtest_nestedsets')
                    ->where('dbtest_nestedset_id = '.$parent->dbtest_nestedset_id);
        $parentDb = $db->setQuery($query)->loadObject();

        $this->assertEquals($table->lft, $nodeDb->lft, sprintf($msg, 'Children object and database lft values are not the same'));
        $this->assertEquals($table->rgt, $nodeDb->rgt, sprintf($msg, 'Children object and database rgt values are not the same'));
        $this->assertEquals($parent->lft, $parentDb->lft, sprintf($msg, 'Parent object and database lft values are not the same'));
        $this->assertEquals($parent->rgt, $parentDb->rgt, sprintf($msg, 'Parent object and database rgt values are not the same'));
    }

    /**
     * @group               TreeModelInsertAsLastChildOf
     * @group               TreeModel
     * @covers              TreeModel::insertAsLastChildOf
     */
    public function testInsertAsLastChildOfException()
    {
        $this->setExpectedException('RuntimeException');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table  = new TreeModelStub($container);
        $parent = $table->getClone();

        $table->insertAsLastChildOf($parent);
    }

    /**
     * @group               TreeModelInsertLeftOf
     * @group               TreeModel
     * @covers              TreeModel::insertLeftOf
     * @dataProvider        TreeModelDataprovider::getTestInsertLeftOf
     */
    public function testInsertLeftOf($test, $check)
    {
        /** @var TreeModelStub $table */
        /** @var TreeModelStub $sibling */

        $msg = 'TreeModel::insertLeftOf %s - Case: '.$check['case'];
        $db  = self::$driver;

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table  = new TreeModelStub($container);
        $sibling = $table->getClone();

        if($test['loadid'])
        {
            $table->findOrFail($test['loadid']);
        }

        if($test['title'])
        {
            $table->title = $test['title'];
        }

        $sibling->findOrFail($test['siblingid']);
        $siblingLft = $sibling->lft;
        $siblingRgt = $sibling->rgt;

        $return = $table->insertLeftOf($sibling);

        $this->assertInstanceOf('\\Awf\\Mvc\\TreeModel', $return, sprintf($msg, 'Should return an instance of itself for chaining'));

        // Assertions on the objects
        $this->assertNotEquals($test['loadid'], $table->getId(), sprintf($msg, 'Should always create a new node'));
        $this->assertEquals($siblingLft + 2, $sibling->lft, sprintf($msg, 'Should increase the lft value by 2'));
        $this->assertEquals($siblingRgt + 2, $sibling->rgt, sprintf($msg, 'Should increase the rgt value by 2'));
        $this->assertEquals(1, $table->rgt - $table->lft, sprintf($msg, 'Should insert the node as leaf'));
        $this->assertEquals(1, $sibling->lft - $table->rgt, sprintf($msg, 'Should insert the node on the left of the sibling'));

        // Great, the returned objects are ok, what about the ACTUAL data saved inside the db?
        $query = $db->getQuery(true)
                    ->select('*')
                    ->from('#__dbtest_nestedsets')
                    ->where('dbtest_nestedset_id = '.$table->dbtest_nestedset_id);
        $nodeDb = $db->setQuery($query)->loadObject();

        $query = $db->getQuery(true)
                    ->select('*')
                    ->from('#__dbtest_nestedsets')
                    ->where('dbtest_nestedset_id = '.$sibling->dbtest_nestedset_id);
        $siblingDb = $db->setQuery($query)->loadObject();

        $this->assertEquals($table->lft, $nodeDb->lft, sprintf($msg, 'Node object and database lft values are not the same'));
        $this->assertEquals($table->rgt, $nodeDb->rgt, sprintf($msg, 'Node object and database rgt values are not the same'));
        $this->assertEquals($sibling->lft, $siblingDb->lft, sprintf($msg, 'Sibling object and database lft values are not the same'));
        $this->assertEquals($sibling->rgt, $siblingDb->rgt, sprintf($msg, 'Sibling object and database rgt values are not the same'));
    }

    /**
     * @group               TreeModelInsertLeftOf
     * @group               TreeModel
     * @covers              TreeModel::insertLeftOf
     */
    public function testInsertLeftOfException()
    {
        $this->setExpectedException('RuntimeException');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table  = new TreeModelStub($container);
        $sibling = $table->getClone();

        $table->insertLeftOf($sibling);
    }

    /**
     * @group               TreeModelInsertRightOf
     * @group               TreeModel
     * @covers              TreeModel::insertRightOf
     * @dataProvider        TreeModelDataprovider::getTestInsertRightOf
     */
    public function testInsertRightOf($test, $check)
    {
        /** @var TreeModelStub $table */
        /** @var TreeModelStub $sibling */

        $msg = 'TreeModel::insertRightOf %s - Case: '.$check['case'];
        $db  = self::$driver;

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table  = new TreeModelStub($container);
        $sibling = $table->getClone();

        if($test['loadid'])
        {
            $table->findOrFail($test['loadid']);
        }

        if($test['title'])
        {
            $table->title = $test['title'];
        }

        $sibling->findOrFail($test['siblingid']);
        $siblingLft = $sibling->lft;
        $siblingRgt = $sibling->rgt;

        $return = $table->insertRightOf($sibling);

        $this->assertInstanceOf('\\Awf\\Mvc\\TreeModel', $return, sprintf($msg, 'Should return an instance of itself'));

        // Assertions on the objects
        $this->assertNotEquals($test['loadid'], $table->getId(), sprintf($msg, 'Should always create a new node'));
        $this->assertEquals($siblingLft, $sibling->lft, sprintf($msg, 'Should not modify the lft value'));
        $this->assertEquals($siblingRgt, $sibling->rgt, sprintf($msg, 'Should not modify the rgt value'));
        $this->assertEquals(1, $table->rgt - $table->lft, sprintf($msg, 'Should insert the node as leaf'));
        $this->assertEquals(1, $table->lft - $sibling->rgt, sprintf($msg, 'Should insert the node on the right of the sibling'));

        // Great, the returned objects are ok, what about the ACTUAL data saved inside the db?
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__dbtest_nestedsets')
            ->where('dbtest_nestedset_id = '.$table->dbtest_nestedset_id);
        $nodeDb = $db->setQuery($query)->loadObject();

        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__dbtest_nestedsets')
            ->where('dbtest_nestedset_id = '.$sibling->dbtest_nestedset_id);
        $siblingDb = $db->setQuery($query)->loadObject();

        $this->assertEquals($table->lft, $nodeDb->lft, sprintf($msg, 'Node object and database lft values are not the same'));
        $this->assertEquals($table->rgt, $nodeDb->rgt, sprintf($msg, 'Node object and database rgt values are not the same'));
        $this->assertEquals($sibling->lft, $siblingDb->lft, sprintf($msg, 'Sibling object and database lft values are not the same'));
        $this->assertEquals($sibling->rgt, $siblingDb->rgt, sprintf($msg, 'Sibling object and database rgt values are not the same'));
    }

    /**
     * @group               TreeModelInsertRightOf
     * @group               TreeModel
     * @covers              TreeModel::insertRightOf
     */
    public function testInsertRightOfException()
    {
        $this->setExpectedException('RuntimeException');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table  = new TreeModelStub($container);
        $sibling = $table->getClone();

        $table->insertRightOf($sibling);
    }

    /**
     * @group               TreeModelMoveLeft
     * @group               TreeModel
     * @covers              TreeModel::moveLeft
     * @dataProvider        TreeModelDataprovider::getTestMoveLeft
     */
    public function testMoveLeft($test, $check)
    {
        $counter = 0;
        $sibling = null;
        $msg     = 'TreeModel::moveLeft %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\TreeModelStub', array('moveToLeftOf'), array($container));
        $table->expects($this->any())->method('moveToLeftOf')->willReturnCallback(
            function($leftSibling) use (&$counter, &$sibling){
                $counter++;
                $sibling = $leftSibling->dbtest_nestedset_id;
            }
        );

        $table->findOrFail($test['loadid']);

        $table->moveLeft();

        $this->assertEquals($check['counter'], $counter, sprintf($msg, "Invoked moveToLefOf the wrong number of time"));
        $this->assertEquals($check['sibling'], $sibling, sprintf($msg, "Invoked moveToLefOf with the wrong sibling"));
    }

    /**
     * @group               TreeModelMoveLeft
     * @group               TreeModel
     * @covers              TreeModel::moveLeft
     */
    public function testMoveLeftException()
    {
        $this->setExpectedException('RuntimeException');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table  = new TreeModelStub($container);

        $table->moveLeft();
    }

    /**
     * @group               TreeModelMoveRight
     * @group               TreeModel
     * @covers              TreeModel::moveRight
     * @dataProvider        TreeModelDataprovider::getTestMoveRight
     */
    public function testMoveRight($test, $check)
    {
        $counter = 0;
        $sibling = null;
        $msg     = 'TreeModel::moveRight %s - Case: '.$check['case'];

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\TreeModelStub', array('moveToRightOf'), array($container));
        $table->expects($this->any())->method('moveToRightOf')->willReturnCallback(
            function($rightSibling) use (&$counter, &$sibling){
                $counter++;
                $sibling = $rightSibling->dbtest_nestedset_id;
            }
        );

        $table->findOrFail($test['loadid']);

        $table->moveRight();

        $this->assertEquals($check['counter'], $counter, sprintf($msg, "Invoked moveToRightOf the wrong number of time"));
        $this->assertEquals($check['sibling'], $sibling, sprintf($msg, "Invoked moveToRightOf with the wrong sibling"));
    }

    /**
     * @group               TreeModelMoveRight
     * @group               TreeModel
     * @covers              TreeModel::moveRight
     */
    public function testMoveRightException()
    {
        $this->setExpectedException('RuntimeException');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table  = new TreeModelStub($container);

        $table->moveRight();
    }

    /**
     * @group               TreeModelMoveToLeftOf
     * @group               TreeModel
     * @covers              TreeModel::moveToLeftOf
     * @dataProvider        TreeModelDataprovider::getTestMoveToLeftOf
     */
    public function testMoveToLeftOf($test, $check)
    {
        /** @var TreeModelStub $table */
        /** @var TreeModelStub $sibling */

        $msg = 'TreeModel::moveToLeftOf %s - Case: '.$check['case'];
        $db  = self::$driver;

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table   = new TreeModelStub($container);
        $sibling = $table->getClone();

        // Am I request to create a different root?
        if($test['newRoot'])
        {
            $root = $table->getClone();
            $root->title = 'New root';
            $root->insertAsRoot();

            $child = $table->getClone();
            $child->title = 'First child 2nd root';
            $child->insertAsChildOf($root);

            $child->reset();

            $child->title = 'Second child 2nd root';
            $child->insertAsChildOf($root);
        }

        $table->findOrFail($test['loadid']);
        $sibling->findOrFail($test['siblingid']);

        $return = $table->moveToLeftOf($sibling);

        $this->assertInstanceOf('\\Awf\\Mvc\\TreeModel', $return, sprintf($msg, 'Should return an instance of itself'));

        // Assertions on the objects
        $this->assertEquals($check['table']['lft'], $table->lft, sprintf($msg, 'Failed to assign the correct lft value to the node'));
        $this->assertEquals($check['table']['rgt'], $table->rgt, sprintf($msg, 'Failed to assign the correct rgt value to the node'));

        // Great, the returned objects are ok, what about the ACTUAL data saved inside the db?
        $query = $db->getQuery(true)
                    ->select('*')
                    ->from('#__dbtest_nestedsets')
                    ->where('dbtest_nestedset_id = '.$table->dbtest_nestedset_id);
        $nodeDb = $db->setQuery($query)->loadObject();

        $query = $db->getQuery(true)
                    ->select('*')
                    ->from('#__dbtest_nestedsets')
                    ->where('dbtest_nestedset_id = '.$sibling->dbtest_nestedset_id);
        $siblingDb = $db->setQuery($query)->loadObject();

        $this->assertEquals($table->lft, $nodeDb->lft, sprintf($msg, 'Node object and database lft values are not the same'));
        $this->assertEquals($table->rgt, $nodeDb->rgt, sprintf($msg, 'Node object and database rgt values are not the same'));
        $this->assertEquals($check['sibling']['lft'], $siblingDb->lft, sprintf($msg, 'Saved the wrong lft value for the sibling'));
        $this->assertEquals($check['sibling']['rgt'], $siblingDb->rgt, sprintf($msg, 'Saved the wrong rgt value for the sibling'));
    }

    /**
     * @group               TreeModelMoveToLeftOf
     * @group               TreeModel
     * @covers              TreeModel::moveToLeftOf
     * @dataProvider        TreeModelDataprovider::getTestMoveToLeftOfException
     */
    public function testMoveToLeftOfException($test)
    {
        $this->setExpectedException('RuntimeException');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table   = new TreeModelStub($container);
        $sibling = $table->getClone();

        if($test['loadid'])
        {
            $table->findOrFail($test['loadid']);
        }

        if($test['siblingid'])
        {
            $sibling->findOrFail($test['siblingid']);
        }

        $table->moveToLeftOf($sibling);
    }

    /**
     * @group               TreeModelMoveToRightOf
     * @group               TreeModel
     * @covers              TreeModel::moveToRightOf
     * @dataProvider        TreeModelDataprovider::getTestMoveToRightOf
     */
    public function testMoveToRightOf($test, $check)
    {
        /** @var TreeModelStub $table */
        /** @var TreeModelStub $sibling */

        $msg = 'TreeModel::moveToRightOf %s - Case: '.$check['case'];
        $db  = self::$driver;

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table   = new TreeModelStub($container);
        $sibling = $table->getClone();

        // Am I request to create a different root?
        if($test['newRoot'])
        {
            $root = $table->getClone();
            $root->title = 'New root';
            $root->insertAsRoot();

            $child = $table->getClone();
            $child->title = 'First child 2nd root';
            $child->insertAsChildOf($root);

            $child->reset();

            $child->title = 'Second child 2nd root';
            $child->insertAsChildOf($root);
        }

        $table->findOrFail($test['loadid']);
        $sibling->findOrFail($test['siblingid']);

        $return = $table->moveToRightOf($sibling);

        $this->assertInstanceOf('\\Awf\\Mvc\\TreeModel', $return, sprintf($msg, 'Should return an instance of itself'));

        // Assertions on the objects
        $this->assertEquals($check['table']['lft'], $table->lft, sprintf($msg, 'Failed to assign the correct lft value to the node'));
        $this->assertEquals($check['table']['rgt'], $table->rgt, sprintf($msg, 'Failed to assign the correct rgt value to the node'));

        // Great, the returned objects are ok, what about the ACTUAL data saved inside the db?
        $query = $db->getQuery(true)
                    ->select('*')
                    ->from('#__dbtest_nestedsets')
                    ->where('dbtest_nestedset_id = '.$table->dbtest_nestedset_id);
        $nodeDb = $db->setQuery($query)->loadObject();

        $query = $db->getQuery(true)
                    ->select('*')
                    ->from('#__dbtest_nestedsets')
                    ->where('dbtest_nestedset_id = '.$sibling->dbtest_nestedset_id);
        $siblingDb = $db->setQuery($query)->loadObject();

        $this->assertEquals($table->lft, $nodeDb->lft, sprintf($msg, 'Node object and database lft values are not the same'));
        $this->assertEquals($table->rgt, $nodeDb->rgt, sprintf($msg, 'Node object and database rgt values are not the same'));
        $this->assertEquals($check['sibling']['lft'], $siblingDb->lft, sprintf($msg, 'Saved the wrong lft value for the sibling'));
        $this->assertEquals($check['sibling']['rgt'], $siblingDb->rgt, sprintf($msg, 'Saved the wrong rgt value for the sibling'));
    }

    /**
     * @group               TreeModelMoveToRightOf
     * @group               TreeModel
     * @covers              TreeModel::moveToRightOf
     * @dataProvider        TreeModelDataprovider::getTestMoveToRightOfException
     */
    public function testMoveToRightOfException($test)
    {
        $this->setExpectedException('RuntimeException');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table   = new TreeModelStub($container);
        $sibling = $table->getClone();

        if($test['loadid'])
        {
            $table->findOrFail($test['loadid']);
        }

        if($test['siblingid'])
        {
            $sibling->findOrFail($test['siblingid']);
        }

        $table->moveToRightOf($sibling);
    }

    /**
     * @group               TreeModelMakeFirstChildOf
     * @group               TreeModel
     * @covers              TreeModel::makeFirstChildOf
     * @dataProvider        TreeModelDataprovider::getTestMakeFirstChildOf
     */
    public function testMakeFirstChildOf($test, $check)
    {
        /** @var TreeModelStub $table */
        /** @var TreeModelStub $parent */

        $msg = 'TreeModel::makeFirstChildOf %s - Case: '.$check['case'];
        $db  = self::$driver;

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table   = new TreeModelStub($container);
        $parent = $table->getClone();

        $table->findOrFail($test['loadid']);
        $parent->findOrFail($test['parentid']);

        $return = $table->makeFirstChildOf($parent);

        $this->assertInstanceOf('\\Awf\\Mvc\\TreeModel', $return, sprintf($msg, 'Should return an instance of itself'));

        // Assertions on the objects
        $this->assertEquals($check['table']['lft'], $table->lft, sprintf($msg, 'Failed to assign the correct lft value to the node'));
        $this->assertEquals($check['table']['rgt'], $table->rgt, sprintf($msg, 'Failed to assign the correct rgt value to the node'));

        // Great, the returned objects are ok, what about the ACTUAL data saved inside the db?
        $query = $db->getQuery(true)
                    ->select('*')
                    ->from('#__dbtest_nestedsets')
                    ->where('dbtest_nestedset_id = '.$table->dbtest_nestedset_id);
        $nodeDb = $db->setQuery($query)->loadObject();

        $query = $db->getQuery(true)
                    ->select('*')
                    ->from('#__dbtest_nestedsets')
                    ->where('dbtest_nestedset_id = '.$parent->dbtest_nestedset_id);
        $parentDb = $db->setQuery($query)->loadObject();

        $this->assertEquals($table->lft, $nodeDb->lft, sprintf($msg, 'Node object and database lft values are not the same'));
        $this->assertEquals($table->rgt, $nodeDb->rgt, sprintf($msg, 'Node object and database rgt values are not the same'));
        $this->assertEquals($check['parent']['lft'], $parentDb->lft, sprintf($msg, 'Saved the wrong lft value for the parent'));
        $this->assertEquals($check['parent']['rgt'], $parentDb->rgt, sprintf($msg, 'Saved the wrong rgt value for the parent'));
    }

    /**
     * @group               TreeModelMakeFirstChildOf
     * @group               TreeModel
     * @covers              TreeModel::makeFirstChildOf
     * @dataProvider        TreeModelDataprovider::getTestMakeFirstChildOfException
     */
    public function testMakeFirstChildOfException($test)
    {
        $this->setExpectedException('RuntimeException');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table   = new TreeModelStub($container);
        $parent = $table->getClone();

        if($test['loadid'])
        {
            $table->findOrFail($test['loadid']);
        }

        if($test['siblingid'])
        {
            $parent->findOrFail($test['parentid']);
        }

        $table->makeFirstChildOf($parent);
    }

    /**
     * @group               TreeModelMakeLastChildOf
     * @group               TreeModel
     * @covers              TreeModel::makeLastChildOf
     * @dataProvider        TreeModelDataprovider::getTestMakeLastChildOf
     */
    public function testMakeLastChildOf($test, $check)
    {
        /** @var TreeModelStub $table */
        /** @var TreeModelStub $parent */

        $msg = 'TreeModel::makeLastChildOf %s - Case: '.$check['case'];
        $db  = self::$driver;

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table   = new TreeModelStub($container);
        $parent = $table->getClone();

        $table->findOrFail($test['loadid']);
        $parent->findOrFail($test['parentid']);

        $return = $table->makeLastChildOf($parent);

        $this->assertInstanceOf('\\Awf\\Mvc\\TreeModel', $return, sprintf($msg, 'Should return an instance of itself'));

        // Assertions on the objects
        $this->assertEquals($check['table']['lft'], $table->lft, sprintf($msg, 'Failed to assign the correct lft value to the node'));
        $this->assertEquals($check['table']['rgt'], $table->rgt, sprintf($msg, 'Failed to assign the correct rgt value to the node'));

        // Great, the returned objects are ok, what about the ACTUAL data saved inside the db?
        $query = $db->getQuery(true)
                    ->select('*')
                    ->from('#__dbtest_nestedsets')
                    ->where('dbtest_nestedset_id = '.$table->dbtest_nestedset_id);
        $nodeDb = $db->setQuery($query)->loadObject();

        $query = $db->getQuery(true)
                    ->select('*')
                    ->from('#__dbtest_nestedsets')
                    ->where('dbtest_nestedset_id = '.$parent->dbtest_nestedset_id);
        $parentDb = $db->setQuery($query)->loadObject();

        $this->assertEquals($table->lft, $nodeDb->lft, sprintf($msg, 'Node object and database lft values are not the same'));
        $this->assertEquals($table->rgt, $nodeDb->rgt, sprintf($msg, 'Node object and database rgt values are not the same'));
        $this->assertEquals($check['parent']['lft'], $parentDb->lft, sprintf($msg, 'Saved the wrong lft value for the parent'));
        $this->assertEquals($check['parent']['rgt'], $parentDb->rgt, sprintf($msg, 'Saved the wrong rgt value for the parent'));
    }

    /**
     * @group               TreeModelMakeLastChildOf
     * @group               TreeModel
     * @covers              TreeModel::makeLastChildOf
     * @dataProvider        TreeModelDataprovider::getTestMakeLastChildOfException
     */
    public function testMakeLastChildOfException($test)
    {
        $this->setExpectedException('RuntimeException');

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table   = new TreeModelStub($container);
        $parent = $table->getClone();

        if($test['loadid'])
        {
            $table->findOrFail($test['loadid']);
        }

        if($test['siblingid'])
        {
            $parent->findOrFail($test['parentid']);
        }

        $table->makeLastChildOf($parent);
    }

    /**
     * @group               TreeModelMakeRoot
     * @group               TreeModel
     * @covers              TreeModel::makeRoot
     * @dataProvider        TreeModelDataprovider::getTestMakeRoot
     */
    public function testMakeRoot($test, $check)
    {
        $msg     = 'TreeModel::makeRoot %s - Case: '.$check['case'];
        $counter = 0;

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'autoChecks'  => false,
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\TreeModelStub', array('moveToRightOf', 'isRoot', 'getRoot', 'equals'), array($container));
        $table->expects($this->any())->method('isRoot')->willReturn($test['mock']['isRoot']);
        $table->expects($this->any())->method('getRoot')->willReturnSelf();
        $table->expects($this->any())->method('equals')->willReturn($test['mock']['equals']);
        $table->expects($this->any())->method('moveToRightOf')->willReturnCallback(
            function() use (&$counter) {
                $counter++;
                return true;
            }
        );

        $return = $table->makeRoot();

        $this->assertEquals($check['move'], $counter, sprintf($msg, 'Invoke the moveToRightOf method the wrong number of times'));
        $this->assertInstanceOf('\\Awf\\Mvc\\TreeModel', $return, 'TreeModel::makeRoot should return an instance of itself');
    }
}