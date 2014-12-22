<?php

class NumberDataprovider
{
    public static function getTestBetween()
    {
        $data[] = array(
            array(
                'from' => null,
                'to'   => null,
                'inclusive' => false
            ),
            array(
                'case'   => 'From and to are null',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'from' => null,
                'to'   => 5,
                'inclusive' => false
            ),
            array(
                'case'   => 'From is null',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'from' => 5,
                'to'   => null,
                'inclusive' => false
            ),
            array(
                'case'   => 'To is null',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'from' => 1,
                'to'   => 5,
                'inclusive' => false
            ),
            array(
                'case'   => 'From and to are set, not inclusive ',
                'result' => '((`test` > 1) AND (`test` < 5))'
            )
        );

        $data[] = array(
            array(
                'from' => 1,
                'to'   => 5,
                'inclusive' => true
            ),
            array(
                'case'   => 'From and to are set, inclusive ',
                'result' => '((`test` >= 1) AND (`test` <= 5))'
            )
        );

        $data[] = array(
            array(
                'from' => -5,
                'to'   => -1,
                'inclusive' => true
            ),
            array(
                'case'   => 'From and to are set and they are negative, inclusive ',
                'result' => '((`test` >= -5) AND (`test` <= -1))'
            )
        );

        $data[] = array(
            array(
                'from' => -5,
                'to'   => 0,
                'inclusive' => true
            ),
            array(
                'case'   => 'From is negative and to is 0, inclusive ',
                'result' => ''
            )
        );

        return $data;
    }

    public static function getTestOutside()
    {
        $data[] = array(
            array(
                'from' => null,
                'to'   => null,
                'inclusive' => false
            ),
            array(
                'case'   => 'From and to are null',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'from' => null,
                'to'   => 5,
                'inclusive' => false
            ),
            array(
                'case'   => 'From is null',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'from' => 5,
                'to'   => null,
                'inclusive' => false
            ),
            array(
                'case'   => 'To is null',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'from' => 1,
                'to'   => 5,
                'inclusive' => false
            ),
            array(
                'case'   => 'From and to are set, not inclusive ',
                'result' => '((`test` < 1) AND (`test` > 5))'
            )
        );

        $data[] = array(
            array(
                'from' => 1,
                'to'   => 5,
                'inclusive' => true
            ),
            array(
                'case'   => 'From and to are set, inclusive ',
                'result' => '((`test` <= 1) AND (`test` >= 5))'
            )
        );

        $data[] = array(
            array(
                'from' => -5,
                'to'   => -1,
                'inclusive' => true
            ),
            array(
                'case'   => 'From and to are set and they are negative, inclusive ',
                'result' => '((`test` <= -5) AND (`test` >= -1))'
            )
        );

        $data[] = array(
            array(
                'from' => -5,
                'to'   => 0,
                'inclusive' => true
            ),
            array(
                'case'   => 'From is negative and to is 0, inclusive ',
                'result' => ''
            )
        );

        return $data;
    }

    public static function getTestInterval()
    {
        $data[] = array(
            array(
                'value'     => null,
                'interval'  => 1,
                'inclusive' => false
            ),
            array(
                'case'   => 'Value is empty',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'value'     => 5,
                'interval'  => 1,
                'inclusive' => false
            ),
            array(
                'case'   => 'Value is not empty, not inclusive',
                'result' => '((`test` > 4) AND (`test` < 6))'
            )
        );

        $data[] = array(
            array(
                'value'     => 5,
                'interval'  => 1,
                'inclusive' => true
            ),
            array(
                'case'   => 'Value is not empty, inclusive',
                'result' => '((`test` >= 4) AND (`test` <= 6))'
            )
        );

        $data[] = array(
            array(
                'value'     => 3.2,
                'interval'  => 1.2,
                'inclusive' => true
            ),
            array(
                'case'   => 'Float values provided',
                'result' => '((`test` >= 2) AND (`test` <= 4.4))'
            )
        );

        $data[] = array(
            array(
                'value'     => 3.2,
                'interval'  => 'test',
                'inclusive' => true
            ),
            array(
                'case'   => 'Wrong interval type',
                'result' => '((`test` >= 3.2) AND (`test` <= 3.2))'
            )
        );

        return $data;
    }
}