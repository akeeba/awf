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

use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Database\Driver;
use Awf\Tests\Stubs\Fakeapp\Container;

class TreeModelTest extends DatabaseMysqliCase
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
                'idFieldName' => 'dbtest_nestedset_id',
                'tableName'   => '#__dbtest_nestedsets'
            )
        ));

        $table = $this->getMock('\\Awf\\Tests\\Stubs\\Mvc\\TreeModelStub', array('resetTreeCache'), array($container));
        $table->expects($this->any())->method('resetTreeCache')->willReturn($this->returnValue(null));

        foreach($test['fields'] as $field => $value)
        {
            $table->$field = $value;
        }

        $return = $table->check();

        $this->assertEquals($check['return'], $return, 'TreeModel::check returned the wrong value');

        foreach($check['fields'] as $field => $expected)
        {
            if(is_null($expected))
            {
                $this->assertObjectNotHasAttribute($field, $table, 'TreeModel::check set the field '.$field.' even if it should not');
            }
            else
            {
                $this->assertEquals($expected, $table->$field, 'TreeModel::check failed to set the field '.$field);
            }
        }
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
                'fields' => array(
                    'slug'   => 'test-title',
                    'hash'   => sha1('test-title')
                ),
                'return' => true
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
                'fields' => array(
                    'slug'   => 'old-slug',
                    'hash'   => sha1('old-slug')
                ),
                'return' => true
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_nestedbares',
                'id'    => 'id',
                'fields' => array()
            ),
            array(
                'fields' => array(
                    'slug' => null,
                    'hash' => null
                ),
                'return' => true
            )
        );

        return $data;
    }
}