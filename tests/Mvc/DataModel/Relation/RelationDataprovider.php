<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

class RelationDataprovider
{
    public function getTestGetData()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'filter' => true,
                    'data'   => null
                )
            ),
            array(
                'case' => 'Data is filtered',
                'applyCallback' => true,
                'count' => 3
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filter' => false,
                    'data'   => null
                )
            ),
            array(
                'case' => 'Data is not filtered',
                'applyCallback' => false,
                'count' => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filter' => true,
                    'data'   => new \Awf\Mvc\DataModel\Collection(array(1))
                )
            ),
            array(
                'case' => 'Data fetched from the internal cache',
                'applyCallback' => false,
                'count' => 1
            )
        );

        return $data;
    }
}
