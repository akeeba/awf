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

class TreeModelTest extends DatabaseMysqlCase
{
    /**
     * @group           TreeModel
     * @group           TreeModelCheck
     * @covers          TreeModel::check
     * @dataProvider    getTestCheck
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
        $table->expects($this->any())->method('resetTreeCache')->willReturn($this->returnValue(null));

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
     * @dataProvider        getTestForceDelete
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
     * @dataProvider        getTestCreate
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
     * @dataProvider        getTestInsertAsFirstChildOf
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

    public function getTestForceDelete()
    {
        /*
         * At the moment I can only test when onBeforeDelete return false in the first level only.
         * That's because the iterator is spawning a new class every time, so the mock we setup is not used
         * and the check if performed vs the "real" object, which of course returns false.
         */

        // Delete a single leaf item
        $data[] = array(
            array(
                'loadid'    => null,
                'delete'    => 15,
                'mock'      => array(
                    'before'    => function(){return true; }
                )
            ),
            array(
                'case'    => 'Delete a single leaf item',
                'return'  => true,
                'deleted' => array(15),
                // Associative array where the index is the node id, so I can double check if the lft rgt values
                // are correctly updated
                'nodes'   => array(
                    1  => array('lft' => 1, 'rgt' => 30),
                    9  => array('lft' => 16, 'rgt' => 29),
                    14 => array('lft' => 25, 'rgt' => 28)
                )
            )
        );

        // Delete a single leaf item (loaded table)
        $data[] = array(
            array(
                'loadid'    => 15,
                'delete'    => null,
                'mock'      => array(
                    'before'    => function(){return true; }
                )
            ),
            array(
                'case'    => 'Delete a single leaf item (loaded table)',
                'return'  => true,
                'deleted' => array(15),
                // Associative array where the index is the node id, so I can double check if the lft rgt values
                // are correctly updated
                'nodes'   => array(
                    1  => array('lft' => 1, 'rgt' => 30),
                    9  => array('lft' => 16, 'rgt' => 29),
                    14 => array('lft' => 25, 'rgt' => 28)
                )
            )
        );

        // Delete a single leaf item - prevented
        $data[] = array(
            array(
                'loadid'    => null,
                'delete'    => 15,
                'mock'      => array(
                    'before'    => function($self){
                        $k = $self->getIdFieldName();
                        if($self->$k == 15){
                            return false;
                        }

                        return true;
                    }
                )
            ),
            array(
                'case'    => 'Delete a single leaf item - prevented',
                'return'  => false,
                'deleted' => array(),
                // Associative array where the index is the node id, so I can double check if the lft rgt values
                // are correctly updated
                'nodes'   => array(
                    1  => array('lft' => 1, 'rgt' => 32),
                    9  => array('lft' => 16, 'rgt' => 31),
                    14 => array('lft' => 25, 'rgt' => 30)
                )
            )
        );

        // Delete a single trunk item
        $data[] = array(
            array(
                'loadid'    => null,
                'delete'    => 14,
                'mock'      => array(
                    'before'    => function(){return true; }
                )
            ),
            array(
                'case'    => 'Delete a single trunk item',
                'return'  => true,
                'deleted' => array(14, 15, 16),
                // Associative array where the index is the node id, so I can double check if the lft rgt values
                // are correctly updated
                'nodes'   => array(
                    1 => array('lft' =>  1, 'rgt' => 26),
                    9 => array('lft' => 16, 'rgt' => 25)
                )
            )
        );

        // Delete a single trunk item (loaded table)
        $data[] = array(
            array(
                'loadid'    => 14,
                'delete'    => null,
                'mock'      => array(
                    'before'    => function(){return true; }
                )
            ),
            array(
                'case'    => 'Delete a single trunk item (loaded table)',
                'return'  => true,
                'deleted' => array(14, 15, 16),
                // Associative array where the index is the node id, so I can double check if the lft rgt values
                // are correctly updated
                'nodes'   => array(
                    1 => array('lft' =>  1, 'rgt' => 26),
                    9 => array('lft' => 16, 'rgt' => 25)
                )
            )
        );

        // Delete a single trunk item - prevented
        $data[] = array(
            array(
                'loadid'    => null,
                'delete'    => 14,
                'mock'      => array(
                    'before'    => function($self){
                        $k = $self->getIdFieldName();
                        if($self->$k == 14){
                            return false;
                        }

                        return true;
                    }
                )
            ),
            array(
                'case'    => 'Delete a single trunk item - prevented',
                'return'  => false,
                'deleted' => array(),
                // Associative array where the index is the node id, so I can double check if the lft rgt values
                // are correctly updated
                'nodes'   => array(
                    1 => array('lft' =>  1, 'rgt' => 32),
                    9 => array('lft' => 16, 'rgt' => 31)
                )
            )
        );

        return $data;
    }

    public function getTestCheck()
    {
        $data[] = array(
            array(
                'table' => '#__dbtest_nestedsets',
                'id'    => 'dbtest_nestedset_id',
                'fields' => array(
                    'title' => 'Test title',
                    'slug'  => ''
                )
            ),
            array(
                'case' => 'Title is set and slug is empty',
                'fields' => array(
                    'slug'   => 'test-title',
                    'hash'   => sha1('test-title')
                ),
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_nestedsets',
                'id'    => 'dbtest_nestedset_id',
                'fields' => array(
                    'title' => 'Test title',
                    'slug'  => 'old-slug'
                )
            ),
            array(
                'case'   => 'Title and slug are set',
                'fields' => array(
                    'slug'   => 'old-slug',
                    'hash'   => sha1('old-slug')
                ),
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_nestedbares',
                'id'    => 'id',
                'fields' => array()
            ),
            array(
                'case' => 'Bare table without hash nor slug fields',
                'fields' => array(
                    'slug' => null,
                    'hash' => null
                ),
            )
        );

        return $data;
    }

    public function getTestCreate()
    {
        // Create a node under the root
        $data[] = array(
            array(
                'root'   => true,
                'loadid' => 1,
                'data'   => array(
                    'title' => 'Created node'
                )
            )
        );

        // Create a node in any other position
        $data[] = array(
            array(
                'root'   => false,
                'loadid' => 2,
                'data'   => array(
                    'title' => 'Created node'
                )
            )
        );

        return $data;
    }

    public function getTestInsertAsFirstChildOf()
    {
        // Creating a new node
        $data[] = array(
            array(
                'loadid'   => 0,
                'parentid' => 14,
                'title'    => 'First child'
            ),
            array(
                'case' => 'Creating a new node'
            )
        );

        // Copying an existing node of the same parent (it's not the first child)
        $data[] = array(
            array(
                'loadid'   => 16,
                'parentid' => 14,
                'title'    => ''
            ),
            array(
                'case' => "Copying an existing node of the same parent (it's not the first child)"
            )
        );

        // Copying an existing node of the same parent (it's the first child)
        $data[] = array(
            array(
                'loadid'   => 15,
                'parentid' => 14,
                'title'    => ''
            ),
            array(
                'case' => "Copying an existing node of the same parent (it's the first child)"
            )
        );

        // Copying an existing node of another parent
        $data[] = array(
            array(
                'loadid'   => 4,
                'parentid' => 14,
                'title'    => ''
            ),
            array(
                'case' => 'Copying an existing node of another parent'
            )
        );

        return $data;
    }
}