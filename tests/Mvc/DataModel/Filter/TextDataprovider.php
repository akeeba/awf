<?php
/**
 * @package        awf
 * @copyright      2014-2016 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

class TextDataprovider
{
    public static function getTestPartial()
    {
        $data[] = array(
            array(
                'value' => ''
            ),
            array(
                'case'   => 'Empty value',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'value' => 'foobar'
            ),
            array(
                'case'   => 'Valid value',
                'result' => "(`test` LIKE '%foobar%')"
            )
        );

        return $data;
    }

    public static function getTestExact()
    {
        $data[] = array(
            array(
                'value' => ''
            ),
            array(
                'case'   => 'Empty value',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'value' => 'foobar'
            ),
            array(
                'case'   => 'Valid value',
                'result' => "(`test` LIKE 'foobar')"
            )
        );

        return $data;
    }
}