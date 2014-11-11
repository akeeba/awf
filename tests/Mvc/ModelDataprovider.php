<?php

class ModelDataprovider
{
    public static function getTestSavestate()
    {
        $data[] = array(
            array(
                'state' => true
            ),
            array(
                'case'  => 'New state is boolean true',
                'state' => true
            )
        );

        $data[] = array(
            array(
                'state' => false
            ),
            array(
                'case'  => 'New state is boolean false',
                'state' => false
            )
        );

        $data[] = array(
            array(
                'state' => 1
            ),
            array(
                'case'  => 'New state is int 1',
                'state' => true
            )
        );

        $data[] = array(
            array(
                'state' => 0
            ),
            array(
                'case'  => 'New state is int 0',
                'state' => false
            )
        );

        return $data;
    }

    public static function getTestPopulatesavestate()
    {
        // Savestate is -999 => we are going to save the state
        $data[] = array(
            array(
                'state' => -999,
                'mock'  => array(
                    'state' => null
                )
            ),
            array(
                'savestate' => 1,
                'state'     => true
            )
        );

        // We already saved the state, nothing happens
        $data[] = array(
            array(
                'state' => -999,
                'mock'  => array(
                    'state' => true
                )
            ),
            array(
                'savestate' => 0,
                'state'     => null
            )
        );

        // Savestate is 1 => we are going to save the state
        $data[] = array(
            array(
                'state' => 1,
                'mock'  => array(
                    'state' => null
                )
            ),
            array(
                'savestate' => 1,
                'state'     => 1
            )
        );

        // Savestate is -1 => we are NOT going to save the state
        $data[] = array(
            array(
                'state' => -1,
                'mock'  => array(
                    'state' => null
                )
            ),
            array(
                'savestate' => 1,
                'state'     => -1
            )
        );

        return $data;
    }
}