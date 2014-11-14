<?php

class DataControllerDataprovider
{
    public static function getTestGetModel()
    {
        $data[] = array(
            array(
                'model' => 'datafoobars'
            ),
            array(
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'model' => null
            ),
            array(
                'exception' => true
            )
        );

        return $data;
    }

    public static function getTestGetIDsFromRequest()
    {
        $data[] = array(
            array(
                'load' => false,
                'mock' => array(
                    'cid' => array(),
                    'id'  => 0,
                    'kid' => 0
                )
            ),
            array(
                'case'   => 'Everything is empty, not asked for loading',
                'result' => array(),
                'load'   => false,
                'loadid' => null
            )
        );

        $data[] = array(
            array(
                'load' => true,
                'mock' => array(
                    'cid' => array(),
                    'id'  => 0,
                    'kid' => 0
                )
            ),
            array(
                'case'   => 'Everything is empty, asked for loading',
                'result' => array(),
                'load'   => false,
                'loadid' => null
            )
        );

        $data[] = array(
            array(
                'load' => false,
                'mock' => array(
                    'cid' => array(3,4,5),
                    'id'  => 0,
                    'kid' => 0
                )
            ),
            array(
                'case'   => 'Passed an array of id (cid), not asked for loading',
                'result' => array(3,4,5),
                'load'   => false,
                'loadid' => null
            )
        );

        $data[] = array(
            array(
                'load' => true,
                'mock' => array(
                    'cid' => array(3,4,5),
                    'id'  => 0,
                    'kid' => 0
                )
            ),
            array(
                'case'   => 'Passed an array of id (cid), asked for loading',
                'result' => array(3,4,5),
                'load'   => true,
                'loadid' => array('id' => 3)
            )
        );

        $data[] = array(
            array(
                'load' => false,
                'mock' => array(
                    'cid' => array(),
                    'id'  => 3,
                    'kid' => 0
                )
            ),
            array(
                'case'   => 'Passed a single id (id) , not asked for loading',
                'result' => array(3),
                'load'   => false,
                'loadid' => null
            )
        );

        $data[] = array(
            array(
                'load' => true,
                'mock' => array(
                    'cid' => array(),
                    'id'  => 3,
                    'kid' => 0
                )
            ),
            array(
                'case'   => 'Passed a single id (id), asked for loading',
                'result' => array(3),
                'load'   => true,
                'loadid' => array('id' => 3)
            )
        );

        $data[] = array(
            array(
                'load' => false,
                'mock' => array(
                    'cid' => array(),
                    'id'  => 0,
                    'kid' => 3
                )
            ),
            array(
                'case'   => 'Passed a single id (kid) , not asked for loading',
                'result' => array(3),
                'load'   => false,
                'loadid' => null
            )
        );

        $data[] = array(
            array(
                'load' => true,
                'mock' => array(
                    'cid' => array(),
                    'id'  => 0,
                    'kid' => 3
                )
            ),
            array(
                'case'   => 'Passed a single id (kid), asked for loading',
                'result' => array(3),
                'load'   => true,
                'loadid' => array('id' => 3)
            )
        );

        $data[] = array(
            array(
                'load' => false,
                'mock' => array(
                    'cid' => array(4,5,6),
                    'id'  => 3,
                    'kid' => 0
                )
            ),
            array(
                'case'   => 'Passing an array of id (cid) and a single id (id), not asked for loading',
                'result' => array(4,5,6),
                'load'   => false,
                'loadid' => null
            )
        );

        $data[] = array(
            array(
                'load' => false,
                'mock' => array(
                    'cid' => array(4,5,6),
                    'id'  => 0,
                    'kid' => 3
                )
            ),
            array(
                'case'   => 'Passing an array of id (cid) and a single id (kid), not asked for loading',
                'result' => array(4,5,6),
                'load'   => false,
                'loadid' => null
            )
        );

        $data[] = array(
            array(
                'load' => false,
                'mock' => array(
                    'cid' => array(),
                    'id'  => 4,
                    'kid' => 3
                )
            ),
            array(
                'case'   => 'Passing a single id (id and kid), not asked for loading',
                'result' => array(4),
                'load'   => false,
                'loadid' => null
            )
        );

        $data[] = array(
            array(
                'load' => false,
                'mock' => array(
                    'cid' => array(4,5,6),
                    'id'  => 3,
                    'kid' => 7
                )
            ),
            array(
                'case'   => 'Passing everything, not asked for loading',
                'result' => array(4,5,6),
                'load'   => false,
                'loadid' => null
            )
        );

        return $data;
    }
}