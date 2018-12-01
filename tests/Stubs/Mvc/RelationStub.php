<?php
/**
 * @package        awf
 * @subpackage     tests.stubs
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Mvc;

use Awf\Database\Query;
use Awf\Mvc\DataModel;
use Awf\Mvc\DataModel\Collection;
use Awf\Mvc\DataModel\Relation;

class RelationStub extends Relation
{
    private $mockedMethods = array();

    public function setupMocks(array $methods = array())
    {
        foreach($methods as $method => $callback)
        {
            $this->mockedMethods[$method] = $callback;
        }
    }

    /**
     * Returns a new item of the foreignModel type, pre-initialised to fulfil this relation
     *
     * @return DataModel
     *
     * @throws DataModel\Relation\Exception\NewNotSupported when it's not supported
     */
    public function getNew()
    {
        if(isset($this->mockedMethods['getNew']))
        {
            $func = $this->mockedMethods['getNew'];

            return call_user_func_array($func, array());
        }
    }

    /**
     * Returns the count subquery for DataModel's has() and whereHas() methods.
     *
     * @return Query
     */
    public function getCountSubquery()
    {
        if(isset($this->mockedMethods['getCountSubquery']))
        {
            $func = $this->mockedMethods['getCountSubquery'];

            return call_user_func_array($func, array());
        }
    }

    /**
     * Applies the relation filters to the foreign model when getData is called
     *
     * @param DataModel $foreignModel The foreign model you're operating on
     * @param Collection $dataCollection If it's an eager loaded relation, the collection of loaded parent records
     *
     * @return boolean Return false to force an empty data collection
     */
    protected function filterForeignModel(DataModel $foreignModel, Collection $dataCollection = null)
    {
        if(isset($this->mockedMethods['filterForeignModel']))
        {
            $func = $this->mockedMethods['filterForeignModel'];

            return call_user_func_array($func, array());
        }

        return false;
    }
}
