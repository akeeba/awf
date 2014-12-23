<?php

class DateDataprovider
{
    public static function getTestBetween()
    {
        $data[] = array(
            array(
                'from'    => '',
                'to'      => '',
                'include' => false
            ),
            array(
                'case'   => 'From and to are empty',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'from'    => '1980-01-01',
                'to'      => '',
                'include' => false
            ),
            array(
                'case'   => 'To is empty',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'from'    => '',
                'to'      => '1980-01-01',
                'include' => false
            ),
            array(
                'case'   => 'From is empty',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'from'    => '1980-01-01',
                'to'      => '1980-02-01',
                'include' => false
            ),
            array(
                'case'   => 'From/to are set, not inclusive',
                'result' => "((`test` > '1980-01-01') AND (`test` < '1980-02-01'))"
            )
        );

        $data[] = array(
            array(
                'from'    => '1980-01-01',
                'to'      => '1980-02-01',
                'include' => true
            ),
            array(
                'case'   => 'From/to are set, inclusive',
                'result' => "((`test` >= '1980-01-01') AND (`test` <= '1980-02-01'))"
            )
        );

        return $data;
    }
}