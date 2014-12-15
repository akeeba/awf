<?php

class HasManyDataprovider
{
    public static function getTestConstruct()
    {
        $data[] = array(
            array(
                'local'   => null,
                'foreign' => null
            ),
            array(
                'case'    => 'Local and foreign keys not supplied',
                'local'   => 'fakeapp_parent_id',
                'foreign' => 'fakeapp_parent_id'
            )
        );

        $data[] = array(
            array(
                'local'   => 'local',
                'foreign' => null
            ),
            array(
                'case'    => 'Local key supplied',
                'local'   => 'local',
                'foreign' => 'local'
            )
        );

        $data[] = array(
            array(
                'local'   => null,
                'foreign' => 'foreign'
            ),
            array(
                'case'    => 'Foreign key supplied',
                'local'   => 'fakeapp_parent_id',
                'foreign' => 'foreign'
            )
        );

        $data[] = array(
            array(
                'local'   => 'local',
                'foreign' => 'foreign'
            ),
            array(
                'case'    => 'Local and foreign keys supplied',
                'local'   => 'local',
                'foreign' => 'foreign'
            )
        );

        return $data;
    }
}