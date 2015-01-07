<?php

class FtpDataprovider
{
    public static function getTest__destruct()
    {
        $data[] = array(
            array(
                'connection' => '',
            ),
            array(
                'case'  => 'Connection is not set',
                'count' => 0
            )
        );

        $data[] = array(
            array(
                'connection' => 'foobar',
            ),
            array(
                'case'  => 'Connection is set',
                'count' => 1
            )
        );

        return $data;
    }
}