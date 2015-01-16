<?php

class RestoreMysqliDataprovider
{
    public static function getTest__construct()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'foreign' => 0,
                    'check'   => false
                ),
            ),
            array(
                'case'  => "We don't disable foreign key checks",
                'query' => ''
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'foreign' => 1,
                    'check'   => false
                ),
            ),
            array(
                'case'  => "We disable foreign key checks",
                'query' => 'SET FOREIGN_KEY_CHECKS = 0'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'foreign' => 1,
                    'check'   => true
                ),
            ),
            array(
                'case'  => "We disable foreign key checks and an exception is raised",
                'query' => 'SET FOREIGN_KEY_CHECKS = 0'
            )
        );

        return $data;
    }
}