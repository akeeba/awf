<?php
/**
 * @package        awf
 * @copyright      2014-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\DataModel;

use Awf\Mvc\DataModel\Behaviour\Filters;
use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Stubs\Fakeapp\Container;

require_once 'FiltersDataprovider.php';

/**
 * @covers      Awf\Mvc\DataModel\Behaviour\Filters::<protected>
 * @covers      Awf\Mvc\DataModel\Behaviour\Filters::<private>
 * @package     Awf\Tests\DataModel\Behaviour\Filters
 */
class FiltersTest extends DatabaseMysqliCase
{
    /**
     * @group           Behaviour
     * @group           FiltersOnAfterBuildQuery
     * @covers          Awf\Mvc\DataModel\Behaviour\Filters::onAfterBuildQuery
     * @dataProvider    FiltersDataprovider::getTestOnAfterBuildQuery
     */
    public function testOnAfterBuildQuery($test, $check)
    {
        //\PHPUnit_Framework_Error_Warning::$enabled = false;

        $msg = 'Filters::onAfterBuildQuery %s - Case: '.$check['case'];

        $query      = self::$driver->getQuery(true)->select('*')->from('test');
        $model      = $this->buildModel();
        $dispatcher = $model->getBehavioursDispatcher();
        $filter     = new Filters($dispatcher);

        foreach($test['mock']['state'] as $key => $state)
        {
            $model->setState($key, $state);
        }

        $filter->onAfterBuildQuery($model, $query);

        $this->assertEquals($check['query'], trim((string) $query), sprintf($msg, 'Failed to build the query'));
    }

    /**
     * @param   string    $class
     *
     * @return \Awf\Mvc\DataModel
     */
    protected function buildModel($class = null)
    {
        if(!$class)
        {
            $class = '\Awf\Tests\Stubs\Mvc\DataModelStub';
        }

        $container = new Container(array(
            'db' => self::$driver,
            'mvc_config' => array(
                'idFieldName' => 'id',
                'tableName'   => '#__dbtest'
            )
        ));

        return new $class($container);
    }
}
