<?php

class FileDataprovider
{
    public static function getTestWrite()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'file_put_contents' => 48
                )
            ),
            array(
                'case'   => 'Write was successfully completed',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'file_put_contents' => false
                )
            ),
            array(
                'case'   => 'Write had an error',
                'result' => false
            )
        );

        return $data;
    }
}