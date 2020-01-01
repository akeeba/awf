<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

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
