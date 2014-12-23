<?php

class JsonDataprovider
{
    public static function getTest__construct()
    {
        $data[] = array(
            array(
                'hyper' => null
            ),
            array(
                'case'  => 'Hypermedia flag not set',
                'hyper' => false
            )
        );

        $data[] = array(
            array(
                'hyper' => false
            ),
            array(
                'case'  => 'Hypermedia flag set to false',
                'hyper' => false
            )
        );

        $data[] = array(
            array(
                'hyper' => true
            ),
            array(
                'case'  => 'Hypermedia flag set to true',
                'hyper' => true
            )
        );

        return $data;
    }
}