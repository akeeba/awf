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

    public static function getTestOutside()
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
                'result' => "((`test` < '1980-01-01') AND (`test` > '1980-02-01'))"
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
                'result' => "((`test` <= '1980-01-01') AND (`test` >= '1980-02-01'))"
            )
        );

        return $data;
    }

    public static function getTestInterval()
    {
        $data[] = array(
            array(
                'value'    => '',
                'interval' => '',
                'include'  => false
            ),
            array(
                'case'   => 'Value/interval are empty',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'value'    => '',
                'interval' => '+1 MONTH',
                'include'  => false
            ),
            array(
                'case'   => 'Value is empty',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'value'    => '2014-31-23',
                'interval' => '',
                'include'  => false
            ),
            array(
                'case'   => 'Interval is empty',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'value'    => '2014-31-23',
                'interval' => '+1 MONTH',
                'include'  => false
            ),
            array(
                'case'   => 'Value and interval are set, non inclusive',
                'result' => '(`test` > DATE_ADD(`test`, INTERVAL 1 MONTH))'
            )
        );

        $data[] = array(
            array(
                'value'    => '2014-31-23',
                'interval' => '+1 MONTH',
                'include'  => true
            ),
            array(
                'case'   => 'Value and interval are set, inclusive',
                'result' => '(`test` >= DATE_ADD(`test`, INTERVAL 1 MONTH))'
            )
        );

        $data[] = array(
            array(
                'value'    => '2014-31-23',
                'interval' => array(
                    'value' => 1,
                    'unit'  => 'MONTH',
                    'sign'  => '-'
                ),
                'include'  => true
            ),
            array(
                'case'   => 'Interval is an array',
                'result' => '(`test` >= DATE_SUB(`test`, INTERVAL 1 MONTH))'
            )
        );

        $data[] = array(
            array(
                'value'    => '2014-31-23',
                'interval' => (object) array(
                    'value' => 1,
                    'unit'  => 'MONTH',
                    'sign'  => '-'
                ),
                'include'  => true
            ),
            array(
                'case'   => 'Interval is an object',
                'result' => '(`test` >= DATE_SUB(`test`, INTERVAL 1 MONTH))'
            )
        );

        $data[] = array(
            array(
                'value'    => '2014-31-23',
                'interval' => array(
                    'unit'  => 'MONTH',
                    'sign'  => '-'
                ),
                'include'  => true
            ),
            array(
                'case'   => 'Incomplete interval array',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'value'    => '2014-31-23',
                'interval' => array(
                    'value' => 1,
                    'sign'  => '-'
                ),
                'include'  => true
            ),
            array(
                'case'   => 'Incomplete interval array',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'value'    => '2014-31-23',
                'interval' => array(
                    'value' => 1,
                    'unit'  => 'MONTH',
                ),
                'include'  => true
            ),
            array(
                'case'   => 'Incomplete interval array',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'value'    => '2014-31-23',
                'interval' => '-1',
                'include'  => true
            ),
            array(
                'case'   => 'Incomplete interval string',
                'result' => '(`test` >= DATE_ADD(`test`, INTERVAL 1 MONTH))'
            )
        );

        $data[] = array(
            array(
                'value'    => '2014-31-23',
                'interval' => '1',
                'include'  => true
            ),
            array(
                'case'   => 'Incomplete interval string',
                'result' => '(`test` >= DATE_ADD(`test`, INTERVAL 1 MONTH))'
            )
        );

        return $data;
    }
}