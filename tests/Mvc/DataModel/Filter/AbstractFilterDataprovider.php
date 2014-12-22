<?php

class AbstractFilterDataprovider
{
    public static function  getTest__constructException()
    {
        // Invalid type
        $data[] = array(
            array(
                'field' => null
            )
        );

        // Invalid type
        $data[] = array(
            array(
                'field' => 1
            )
        );

        // Invalid type
        $data[] = array(
            array(
                'field' => true
            )
        );

        // Invalid type
        $data[] = array(
            array(
                'field' => 'asd'
            )
        );

        // Invalid type
        $data[] = array(
            array(
                'field' => array(1)
            )
        );

        // Missing fields
        $data[] = array(
            array(
                'field' => (object)array()
            )
        );

        // Missing fields
        $data[] = array(
            array(
                'field' => (object)array(
                    'name' => null
                )
            )
        );

        // Missing fields
        $data[] = array(
            array(
                'field' => (object)array(
                    'type' => null
                )
            )
        );

        // Missing fields
        $data[] = array(
            array(
                'field' => (object)array(
                    'name' => 'test'
                )
            )
        );

        // Missing fields
        $data[] = array(
            array(
                'field' => (object)array(
                    'field' => 'test'
                )
            )
        );

        // Missing fields
        $data[] = array(
            array(
                'field' => (object)array(
                    'name' => 'test',
                    'field' => null
                )
            )
        );

        // Missing fields
        $data[] = array(
            array(
                'field' => (object)array(
                    'name' => null,
                    'field' => 'test'
                )
            )
        );

        return $data;
    }
}