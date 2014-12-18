<?php

class BelongsToManyDataprovider
{
    public function getTestConstruct()
    {
        $data[] = array(
            array(
                'local'     => 'local',
                'foreign'   => 'foreign',
                'pvTable'   => 'pvTable',
                'pvLocal'   => 'pvLocal',
                'pvForeign' => 'pvForeign'
            ),
            array(
                'case'      => 'Passing all the info',
                'local'     => 'local',
                'foreign'   => 'foreign',
                'pvTable'   => 'pvTable',
                'pvLocal'   => 'pvLocal',
                'pvForeign' => 'pvForeign',
            )
        );

        $data[] = array(
            array(
                'local'     => 'local',
                'foreign'   => 'foreign',
                'pvTable'   => null,
                'pvLocal'   => 'pvLocal',
                'pvForeign' => 'pvForeign'
            ),
            array(
                'case'      => 'Passing all the info, except for the pivot table name',
                'local'     => 'local',
                'foreign'   => 'foreign',
                'pvTable'   => '#__fakeapp_parts_groups',
                'pvLocal'   => 'pvLocal',
                'pvForeign' => 'pvForeign',
            )
        );

        $data[] = array(
            array(
                'local'     => 'local',
                'foreign'   => null,
                'pvTable'   => 'pvTable',
                'pvLocal'   => 'pvLocal',
                'pvForeign' => 'pvForeign'
            ),
            array(
                'case'      => 'Passing all the info, except for foreign key',
                'local'     => 'local',
                'foreign'   => 'fakeapp_part_id',
                'pvTable'   => 'pvTable',
                'pvLocal'   => 'pvLocal',
                'pvForeign' => 'pvForeign',
            )
        );

        $data[] = array(
            array(
                'local'     => 'local',
                'foreign'   => null,
                'pvTable'   => 'pvTable',
                'pvLocal'   => 'pvLocal',
                'pvForeign' => null
            ),
            array(
                'case'      => 'Passing all the info, except for foreign and pivot foreign keys',
                'local'     => 'local',
                'foreign'   => 'fakeapp_part_id',
                'pvTable'   => 'pvTable',
                'pvLocal'   => 'pvLocal',
                'pvForeign' => 'fakeapp_part_id',
            )
        );

        $data[] = array(
            array(
                'local'     => null,
                'foreign'   => 'foreign',
                'pvTable'   => 'pvTable',
                'pvLocal'   => 'pvLocal',
                'pvForeign' => 'pvForeign'
            ),
            array(
                'case'      => 'Passing all the info, except for local key',
                'local'     => 'fakeapp_group_id',
                'foreign'   => 'foreign',
                'pvTable'   => 'pvTable',
                'pvLocal'   => 'pvLocal',
                'pvForeign' => 'pvForeign',
            )
        );

        $data[] = array(
            array(
                'local'     => null,
                'foreign'   => 'foreign',
                'pvTable'   => 'pvTable',
                'pvLocal'   => null,
                'pvForeign' => 'pvForeign'
            ),
            array(
                'case'      => 'Passing all the info, except for local and pivot local keys',
                'local'     => 'fakeapp_group_id',
                'foreign'   => 'foreign',
                'pvTable'   => 'pvTable',
                'pvLocal'   => 'fakeapp_group_id',
                'pvForeign' => 'pvForeign',
            )
        );

        return $data;
    }
}
