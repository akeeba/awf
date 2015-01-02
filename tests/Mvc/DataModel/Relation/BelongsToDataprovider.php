<?php

class BelongsToDataprovider
{
    public static function getTestConstruct()
    {
        $data[] = array(
            array(
                'local'   => null,
                'foreign' => null
            ),
            array(
                'case'    => 'Local and foreign keys not passed',
                'local'   => 'fakeapp_parent_id',
                'foreign' => 'fakeapp_parent_id'
            )
        );

        $data[] = array(
            array(
                'local'   => 'local',
                'foreign' => 'foreign'
            ),
            array(
                'case'    => 'Local and foreign keys passed',
                'local'   => 'local',
                'foreign' => 'foreign'
            )
        );

        $data[] = array(
            array(
                'local'   => 'local',
                'foreign' => null
            ),
            array(
                'case'    => 'Local key passed, foreign key not passed',
                'local'   => 'local',
                'foreign' => 'fakeapp_parent_id'
            )
        );

        $data[] = array(
            array(
                'local'   => null,
                'foreign' => 'foreign'
            ),
            array(
                'case'    => 'Local key not passed, foreign key passed',
                'local'   => 'fakeapp_parent_id',
                'foreign' => 'foreign'
            )
        );

        return $data;
    }
}