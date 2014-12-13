<?php
/**
 * @package        awf
 * @subpackage     tests.stubs
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Mvc;

use Awf\Database\Query;
use Awf\Mvc\DataModel;
use Awf\Mvc\DataModel\Collection;
use Awf\Mvc\DataModel\Relation;

class RelationStub extends Relation
{
    /**
     * Returns a new item of the foreignModel type, pre-initialised to fulfil this relation
     *
     * @return DataModel
     *
     * @throws DataModel\Relation\Exception\NewNotSupported when it's not supported
     */
    public function getNew()
    {

    }

    /**
     * Returns the count subquery for DataModel's has() and whereHas() methods.
     *
     * @return Query
     */
    public function getCountSubquery()
    {

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
        return false;
    }
}