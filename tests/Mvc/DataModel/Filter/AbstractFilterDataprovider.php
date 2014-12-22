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

    public static function getTestSearch()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'isEmpty' => true
                ),
                'value'    => '',
                'operator' => '='
            ),
            array(
                'case'   => 'Value is empty',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'isEmpty' => false
                ),
                'value'    => 'dummy',
                'operator' => '='
            ),
            array(
                'case'   => 'Value is set',
                'result' => "(`test` = 'dummy')"
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'isEmpty' => false
                ),
                'value'    => 'dummy',
                'operator' => '!='
            ),
            array(
                'case'   => 'Value is set and should be different',
                'result' => "NOT (`test` = 'dummy')"
            )
        );

        return $data;
    }

    public static function getTestGetFieldType()
    {
        $data[] = array(
            array(
                'type' => 'int (10)'
            ),
            array(
                'case'   => 'Field: int (10)',
                'result' => 'Number'
            )
        );

        $data[] = array(
            array(
                'type' => 'tinyint (10)'
            ),
            array(
                'case'   => 'Field: tinyint (10)',
                'result' => 'Boolean'
            )
        );

        $data[] = array(
            array(
                'type' => 'smallint (10)'
            ),
            array(
                'case'   => 'Field: smallint (10)',
                'result' => 'Boolean'
            )
        );

        $data[] = array(
            array(
                'type' => 'date'
            ),
            array(
                'case'   => 'Field: date',
                'result' => 'Date'
            )
        );

        $data[] = array(
            array(
                'type' => 'datetime'
            ),
            array(
                'case'   => 'Field: datetime',
                'result' => 'Date'
            )
        );

        $data[] = array(
            array(
                'type' => 'time'
            ),
            array(
                'case'   => 'Field: time',
                'result' => 'Date'
            )
        );

        $data[] = array(
            array(
                'type' => 'year'
            ),
            array(
                'case'   => 'Field: year',
                'result' => 'Date'
            )
        );

        $data[] = array(
            array(
                'type' => 'timestamp'
            ),
            array(
                'case'   => 'Field: timestamp',
                'result' => 'Date'
            )
        );

        $data[] = array(
            array(
                'type' => 'timestamp without time zone'
            ),
            array(
                'case'   => 'Field: timestamp without time zone',
                'result' => 'Date'
            )
        );

        $data[] = array(
            array(
                'type' => 'timestamp with time zone'
            ),
            array(
                'case'   => 'Field: timestamp with time zone',
                'result' => 'Date'
            )
        );

        $data[] = array(
            array(
                'type' => 'varchar(10)'
            ),
            array(
                'case'   => 'Field: varchar(10)',
                'result' => 'Text'
            )
        );

        $data[] = array(
            array(
                'type' => 'text'
            ),
            array(
                'case'   => 'Field: text',
                'result' => 'Text'
            )
        );

        $data[] = array(
            array(
                'type' => 'smalltext'
            ),
            array(
                'case'   => 'Field: smalltext',
                'result' => 'Text'
            )
        );

        $data[] = array(
            array(
                'type' => 'longtext'
            ),
            array(
                'case'   => 'Field: longtext',
                'result' => 'Text'
            )
        );

        $data[] = array(
            array(
                'type' => 'char(10)'
            ),
            array(
                'case'   => 'Field: char(10)',
                'result' => 'Text'
            )
        );

        $data[] = array(
            array(
                'type' => 'mediumtext'
            ),
            array(
                'case'   => 'Field: mediumtext',
                'result' => 'Text'
            )
        );

        $data[] = array(
            array(
                'type' => 'character varying(10)'
            ),
            array(
                'case'   => 'Field: character varying(10)',
                'result' => 'Text'
            )
        );

        $data[] = array(
            array(
                'type' => 'nvarchar(10)'
            ),
            array(
                'case'   => 'Field: nvarchar(10)',
                'result' => 'Text'
            )
        );

        $data[] = array(
            array(
                'type' => 'nchar(10)'
            ),
            array(
                'case'   => 'Field: nchar(10)',
                'result' => 'Text'
            )
        );

        return $data;
    }

    public static function getTestGetFieldException()
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

        // Field ok, missing db object
        $data[] = array(
            array(
                'field' => (object)array(
                    'name'  => 'test',
                    'field' => 'int(10)'
                )
            )
        );

        return $data;
    }
}