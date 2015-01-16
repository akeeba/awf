<?php

class PrivilegeDataprovider
{
    public static function getTestGetPrivilege()
    {
        $data[] = array(
            array(
                'privilege' => 'foobar'
            ),
            array(
                'case'   => 'Privilege found',
                'result' => 'test'
            )
        );

        $data[] = array(
            array(
                'privilege' => 'nothere'
            ),
            array(
                'case'   => 'Privilege not found',
                'result' => 'default'
            )
        );

        return $data;
    }
}