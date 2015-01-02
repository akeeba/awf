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

    public static function getTestRemoveById()
    {
        $data[] = array(
            array(
                'key'     => 1
            ),
            array(
                'case' => 'Removed using a key',
                'key'  => 1
            )
        );

        $data[] = array(
            array(
                'key'     => 'object'
            ),
            array(
                'case' => 'Removed using a model',
                'key'  => 2
            )
        );

        return $data;
    }

    public static function getTestContains()
    {
        $data[] = array(
            array(
                'key' => 1
            ),
            array(
                'case'   => 'Key is contained',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'key' => 100
            ),
            array(
                'case'   => 'Key is not contained',
                'result' => false
            )
        );

        return $data;
    }

    public static function getTest__call()
    {
        $data[] = array(
            array(
                'arguments' => 0,
                'load'      => 0
            ),
            array(
                'case' => 'Empty collection',
                'call' => null
            )
        );

        $data[] = array(
            array(
                'arguments' => 0,
                'load'      => 1
            ),
            array(
                'case' => 'Passing no arguments',
                'call' => array(
                    array()
                )
            )
        );

        $data[] = array(
            array(
                'arguments' => 1,
                'load'      => 1
            ),
            array(
                'case' => 'Passing 1 argument',
                'call' => array(
                    array(1)
                )
            )
        );

        $data[] = array(
            array(
                'arguments' => 2,
                'load'      => 1
            ),
            array(
                'case' => 'Passing 2 arguments',
                'call' => array(
                    array(1,1)
                )
            )
        );

        $data[] = array(
            array(
                'arguments' => 3,
                'load'      => 1
            ),
            array(
                'case' => 'Passing 3 arguments',
                'call' => array(
                    array(1,1,1)
                )
            )
        );

        $data[] = array(
            array(
                'arguments' => 4,
                'load'      => 1
            ),
            array(
                'case' => 'Passing 4 arguments',
                'call' => array(
                    array(1,1,1,1)
                )
            )
        );

        $data[] = array(
            array(
                'arguments' => 5,
                'load'      => 1
            ),
            array(
                'case' => 'Passing 5 arguments',
                'call' => array(
                    array(1,1,1,1,1)
                )
            )
        );

        $data[] = array(
            array(
                'arguments' => 6,
                'load'      => 1
            ),
            array(
                'case' => 'Passing no arguments',
                'call' => array(
                    array(1,1,1,1,1,1)
                )
            )
        );

        $data[] = array(
            array(
                'arguments' => 7,
                'load'      => 1
            ),
            array(
                'case' => 'Passing 7 arguments',
                'call' => array(
                    array(1,1,1,1,1,1,1)
                )
            )
        );

        return $data;
    }
}