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
}