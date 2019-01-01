<?php
/**
 * @package    awf
 * @copyright  Copyright (c)2014-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU GPL version 3 or later
 */

namespace Awf\Tests\DataModel\Filter\Relation;

use Awf\Mvc\DataModel\Filter\Relation;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;

/**
 * @covers      Awf\Mvc\DataModel\Filter\Relation::<protected>
 * @covers      Awf\Mvc\DataModel\Filter\Relation::<private>
 * @package     Awf\Tests\DataModel\Filter\Relation
 */
class RelationTest extends DatabaseMysqliCase
{
    protected function setUp($resetContainer = true)
    {
        parent::setUp(false);
    }

    /**
     * @group       RelationFilter
     * @group       RelationFilterConstruct
     * @covers      Awf\Mvc\DataModel\Filter\Relation::__construct
     */
    public function test__construct()
    {
        $subquery = self::$driver->getQuery(true);
        $subquery->select('*')->from('test');

        $filter = new Relation(self::$driver, 'foo', $subquery);

        $this->assertEquals('foo', ReflectionHelper::getValue($filter, 'name'), 'Relation::__construct Failed to set filter name');
        $this->assertEquals('relation', ReflectionHelper::getValue($filter, 'type'), 'Relation::__construct Failed to set filter type');
        $this->assertEquals($subquery, ReflectionHelper::getValue($filter, 'subQuery'), 'Relation::__construct Failed to set the subQuery field');
    }

    /**
     * @group       RelationFilter
     * @group       RelationFilterCallback
     * @covers      Awf\Mvc\DataModel\Filter\Relation::callback
     */
    public function testCallback()
    {
        $subquery = self::$driver->getQuery(true);
        $subquery->select('*')->from('test');

        $filter = new Relation(self::$driver, 'foo', $subquery);

        $result = $filter->callback(function($query){
            $query->where('bar = 1');

            return $query;
        });

        $check  = 'SELECT *
FROM test
WHERE bar = 1';

        $this->assertEquals($check, trim((string)$result), 'Relation::callback Returned the wrong result');
    }

    /**
     * @group       RelationFilter
     * @group       RelationFilterGetFieldName
     * @covers      Awf\Mvc\DataModel\Filter\Relation::getFieldName
     */
    public function testGetFieldName()
    {
        $subquery = self::$driver->getQuery(true);
        $subquery->select('*')->from('test');

        $filter = new Relation(self::$driver, 'foo', $subquery);

        $result = $filter->getFieldName();

        $check = '(
SELECT *
FROM test)';

        $this->assertEquals($check, $result, 'Relation::getFieldName Returned the wrong result');
    }
}
