<?php
namespace Awf\Tests\DataModel;

use Awf\Mvc\DataModel\Behaviour\RelationFilters;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container;

require_once 'RelationFiltersDataprovider.php';

/**
 * @covers      Awf\Mvc\DataModel\Behaviour\RelationFilters::<protected>
 * @covers      Awf\Mvc\DataModel\Behaviour\RelationFilters::<private>
 * @package     Awf\Tests\DataModel\Behaviour\RelationFilters
 */
class RelationFiltersTest extends DatabaseMysqliCase
{
	protected function setUp($resetContainer = true)
	{
		parent::setUp(false);
	}


	/**
     * @group           Behaviour
     * @group           RelationFiltersOnAfterBuildQuery
     * @covers          Awf\Mvc\DataModel\Behaviour\RelationFilters::onAfterBuildQuery
     * @dataProvider    RelationFiltersDataprovider::getTestOnAfterBuildQuery
     */
    public function testOnAfterBuildQuery($test, $check)
    {
        \PHPUnit_Framework_Error_Warning::$enabled = false;

        $msg = 'RelationFilters::onAfterBuildQuery %s - Case: '.$check['case'];

        //$class = $test['class'];
        $class = '\Fakeapp\Model\Parents';

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'relations'   => array(
                    'children' => array(
                        'type' => 'hasMany',
                        'foreignModelClass' => 'Fakeapp\Model\Children',
                        'localKey' => 'fakeapp_parent_id',
                        'foreignKey' => 'fakeapp_parent_id'
                    )
                )
            )
        ));

        /** @var \Awf\Mvc\DataModel $model */
        $model = new $class($container);

        $query      = self::$driver->getQuery(true)->select('*')->from('test');
        $dispatcher = $model->getBehavioursDispatcher();
        $filter     = new RelationFilters($dispatcher);

        // I have to setup a filter
        $model->has('children', $test['operator'], $test['value']);

        $filter->onAfterBuildQuery($model, $query);

        $this->assertEquals($check['query'], trim((string) $query), sprintf($msg, 'Failed to build the search query'));
    }
}

