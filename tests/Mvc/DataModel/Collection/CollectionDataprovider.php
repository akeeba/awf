<?php

class CollectionDataprovider
{
    public static function getTestFind()
    {
        $data[] = array(
            array(
                'key'     => 1,
                'default' => null
            ),
            array(
                'case'   => 'Loading using a key, item found',
                'type'   => 'object',
                'result' => 1
            )
        );

        $data[] = array(
            array(
                'key'     => 100,
                'default' => 5
            ),
            array(
                'case'   => 'Loading using a key, item not found',
                'type'   => 'int',
                'result' => 5
            )
        );

        $data[] = array(
            array(
                'key'     => 'object',
                'default' => null
            ),
            array(
                'case'   => 'Loading using a model, item found',
                'type'   => 'object',
                'result' => 2
            )
        );

        return $data;
    }
}