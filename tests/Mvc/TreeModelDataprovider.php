<?php

class TreeModelDataprovider
{
    public static function getTestMakeLastChildOf()
    {
        // Moving a single node
        $data[] = array(
            array(
                'loadid'   => 13,
                'parentid' => 2
            ),
            array(
                'case'   => 'Moving a single node',
                'table'  => array('lft' => 15, 'rgt' => 16),
                'parent' => array('lft' => 2, 'rgt' => 17)
            )
        );

        // Moving an entire subtree
        $data[] = array(
            array(
                'loadid'   => 10,
                'parentid' => 2
            ),
            array(
                'case'   => 'Moving an entire subtree',
                'table'  => array('lft' => 15, 'rgt' => 20),
                'parent' => array('lft' => 2, 'rgt' => 21)
            )
        );

        // Moving a single node under the same parent
        $data[] = array(
            array(
                'loadid'   => 13,
                'parentid' => 9
            ),
            array(
                'case'   => 'Moving a single node under the same parent',
                'table'  => array('lft' => 29, 'rgt' => 30),
                'parent' => array('lft' => 16, 'rgt' => 31)
            )
        );

        return $data;
    }

    public static function getTestMakeLastChildOfException()
    {
        $data[] = array(
            'loadid'   => 0,
            'parentid' => 0
        );

        $data[] = array(
            'loadid'   => 1,
            'parentid' => 0
        );

        $data[] = array(
            'loadid'   => 0,
            'parentid' => 1
        );

        return $data;
    }
}