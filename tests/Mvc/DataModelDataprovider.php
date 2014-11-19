<?php

class DataModelDataprovider
{
    public static function getTestSetFieldValue()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'alias' => array()
                ),
                'name'  => 'foo',
                'value' => 'bar'
            ),
            array(
                'case'  => 'Setting a method, no alias nor specific setter',
                'method' => 'SetFooAttribute',
                'count' => 0,
                'set'   => true,
                'key'   => 'foo',
                'value' => 'bar'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'alias' => array(
                        'foo' => 'test'
                    )
                ),
                'name'  => 'foo',
                'value' => 'bar'
            ),
            array(
                'case'  => 'Setting a method, with alias and no specific setter',
                'method' => 'SetFooAttribute',
                'count' => 0,
                'set'   => true,
                'key'   => 'test',
                'value' => 'bar'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'alias' => array()
                ),
                'name'  => 'dummy',
                'value' => 'bar'
            ),
            array(
                'case'  => 'Setting a method, no alias and with a specific setter',
                'method' => 'SetDummyAttribute',
                'count' => 1,
                'set'   => false,
                'key'   => '',
                'value' => ''
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'alias' => array(
                        'dummy' => 'foo'
                    )
                ),
                'name'  => 'dummy',
                'value' => 'bar'
            ),
            array(
                'case'  => 'Setting a method, method with a specific setter AND a different alias',
                'method' => 'SetFooAttribute',
                'count' => 0,
                'set'   => true,
                'key'   => 'foo',
                'value' => 'bar'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'alias' => array(
                        'foo' => 'dummy'
                    )
                ),
                'name'  => 'foo',
                'value' => 'bar'
            ),
            array(
                'case'  => 'Setting a method, with an alias pointing to a specific setter',
                'method' => 'SetDummyAttribute',
                'count' => 1,
                'set'   => false,
                'key'   => '',
                'value' => ''
            )
        );

        return $data;
    }
}