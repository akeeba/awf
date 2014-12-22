<?php

class AbstractFilterDataprovider
{
    public static function  getTest__constructException()
    {
        // Invalid type
        $data[] = array(
            array(
                'field' => null
            )
        );

        // Invalid type
        $data[] = array(
            array(
                'field' => 1
            )
        );

        // Invalid type
        $data[] = array(
            array(
                'field' => true
            )
        );

        // Invalid type
        $data[] = array(
            array(
                'field' => 'asd'
            )
        );

        // Invalid type
        $data[] = array(
            array(
                'field' => array(1)
            )
        );

        // Missing fields
        $data[] = array(
            array(
                'field' => (object)array()
            )
        );

        // Missing fields
        $data[] = array(
            array(
                'field' => (object)array(
                    'name' => null
                )
            )
        );

        // Missing fields
        $data[] = array(
            array(
                'field' => (object)array(
                    'type' => null
                )
            )
        );

        // Missing fields
        $data[] = array(
            array(
                'field' => (object)array(
                    'name' => 'test'
                )
            )
        );

        // Missing fields
        $data[] = array(
            array(
                'field' => (object)array(
                    'field' => 'test'
                )
            )
        );

        // Missing fields
        $data[] = array(
            array(
                'field' => (object)array(
                    'name' => 'test',
                    'field' => null
                )
            )
        );

        // Missing fields
        $data[] = array(
            array(
                'field' => (object)array(
                    'name' => null,
                    'field' => 'test'
                )
            )
        );

        return $data;
    }

    public static function getTestIsEmpty()
    {
        $data[] = array(
            array(
                'null'  => null,
                'value' => null
            ),
            array(
                'case'   => 'Value: NULL, Null_value: NULL',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'null'  => null,
                'value' => 55
            ),
            array(
                'case'   => 'Value: 55, Null_value: NULL',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'null'  => -1,
                'value' => null
            ),
            array(
                'case'   => 'Value: NULL, Null_value: -1',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'null'  => -1,
                'value' => 'test'
            ),
            array(
                'case'   => 'Value: test, Null_value: -1',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'null'  => 'test',
                'value' => 'test'
            ),
            array(
                'case'   => 'Value: test, Null_value: test',
                'result' => true
            )
        );

        return $data;
    }

    public function getTestExact()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'isEmpty' => true
                ),
                'value' => ''
            ),
            array(
                'case'   => 'Passed value is empty',
                'name'   => false,
                'search' => false,
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'isEmpty' => false
                ),
                'value' => 'test'
            ),
            array(
                'case'   => 'Passed value is not empty',
                'name'   => false,
                'search' => true,
                'result' => 'search'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'isEmpty' => false
                ),
                'value' => array('foo', 'bar')
            ),
            array(
                'case'   => 'Passed value is an array',
                'name'   => true,
                'search' => false,
                'result' => "(`test` IN ('foo','bar'))"
            )
        );

        return $data;
    }
}