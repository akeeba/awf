<?php

class DataModelDataprovider
{
    public static function getTestGetTableFields()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'tables'     => null,
                    'tableName'  => null
                ),
                'table' => '#__dbtest'
            ),
            array(
                'case' => 'Table exists, abstract name, loaded cache',
                'result' => array(
                    'id' => (object) array(
                        'Field' => 'id',
                        'Type' => 'int(10) unsigned',
                        'Collation' => null,
                        'Null' => 'NO',
                        'Key' => 'PRI',
                        'Default' => null,
                        'Extra' => 'auto_increment',
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'title' => (object) array(
                        'Field' => 'title',
                        'Type' => 'varchar(50)',
                        'Collation' => 'utf8_general_ci',
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' =>null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'start_date' => (object) array(
                        'Field' => 'start_date',
                        'Type' => 'datetime',
                        'Collation' => null,
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' => null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'description' => (object) array(
                        'Field' => 'description',
                        'Type' => 'text',
                        'Collation' => 'utf8_general_ci',
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' => null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    )
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'tables'     => null,
                    'tableName'  => '#__dbtest'
                ),
                'table' => null
            ),
            array(
                'case' => 'Table exists, abstract name, loaded cache, table name got from the object',
                'result' => array(
                    'id' => (object) array(
                        'Field' => 'id',
                        'Type' => 'int(10) unsigned',
                        'Collation' => null,
                        'Null' => 'NO',
                        'Key' => 'PRI',
                        'Default' => null,
                        'Extra' => 'auto_increment',
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'title' => (object) array(
                        'Field' => 'title',
                        'Type' => 'varchar(50)',
                        'Collation' => 'utf8_general_ci',
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' =>null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'start_date' => (object) array(
                        'Field' => 'start_date',
                        'Type' => 'datetime',
                        'Collation' => null,
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' => null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'description' => (object) array(
                        'Field' => 'description',
                        'Type' => 'text',
                        'Collation' => 'utf8_general_ci',
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' => null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    )
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'tables'     => null,
                    'tableName'  => null
                ),
                'table' => '#__wrong'
            ),
            array(
                'case' => 'Table does not exist, abstract name, loaded cache',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'tables'     => null,
                    'tableName'  => null
                ),
                'table' => 'awf_dbtest'
            ),
            array(
                'case' => 'Table exists, actual name, loaded cache',
                'result' => array(
                    'id' => (object) array(
                        'Field' => 'id',
                        'Type' => 'int(10) unsigned',
                        'Collation' => null,
                        'Null' => 'NO',
                        'Key' => 'PRI',
                        'Default' => null,
                        'Extra' => 'auto_increment',
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'title' => (object) array(
                        'Field' => 'title',
                        'Type' => 'varchar(50)',
                        'Collation' => 'utf8_general_ci',
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' =>null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'start_date' => (object) array(
                        'Field' => 'start_date',
                        'Type' => 'datetime',
                        'Collation' => null,
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' => null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'description' => (object) array(
                        'Field' => 'description',
                        'Type' => 'text',
                        'Collation' => 'utf8_general_ci',
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' => null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    )
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'tables'     => 'nuke',
                    'tableName'  => null
                ),
                'table' => '#__dbtest'
            ),
            array(
                'case' => 'Table exists, abstract name, clean cache',
                'result' => array(
                    'id' => (object) array(
                        'Field' => 'id',
                        'Type' => 'int(10) unsigned',
                        'Collation' => null,
                        'Null' => 'NO',
                        'Key' => 'PRI',
                        'Default' => null,
                        'Extra' => 'auto_increment',
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'title' => (object) array(
                        'Field' => 'title',
                        'Type' => 'varchar(50)',
                        'Collation' => 'utf8_general_ci',
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' =>null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'start_date' => (object) array(
                        'Field' => 'start_date',
                        'Type' => 'datetime',
                        'Collation' => null,
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' => null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'description' => (object) array(
                        'Field' => 'description',
                        'Type' => 'text',
                        'Collation' => 'utf8_general_ci',
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' => null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    )
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'tables'     => array(
                        '#__dbtest' => 'unset'
                    ),
                    'tableName'  => null
                ),
                'table' => '#__dbtest'
            ),
            array(
                'case' => 'Table exists, abstract name, table not inside the cache',
                'result' => array(
                    'id' => (object) array(
                        'Field' => 'id',
                        'Type' => 'int(10) unsigned',
                        'Collation' => null,
                        'Null' => 'NO',
                        'Key' => 'PRI',
                        'Default' => null,
                        'Extra' => 'auto_increment',
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'title' => (object) array(
                        'Field' => 'title',
                        'Type' => 'varchar(50)',
                        'Collation' => 'utf8_general_ci',
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' =>null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'start_date' => (object) array(
                        'Field' => 'start_date',
                        'Type' => 'datetime',
                        'Collation' => null,
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' => null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'description' => (object) array(
                        'Field' => 'description',
                        'Type' => 'text',
                        'Collation' => 'utf8_general_ci',
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' => null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    )
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'tables'     => array(
                        '#__dbtest' => false
                    ),
                    'tableName'  => null
                ),
                'table' => '#__dbtest'
            ),
            array(
                'case' => 'Table exists, abstract name, table had a false value inside the cache',
                'result' => array(
                    'id' => (object) array(
                        'Field' => 'id',
                        'Type' => 'int(10) unsigned',
                        'Collation' => null,
                        'Null' => 'NO',
                        'Key' => 'PRI',
                        'Default' => null,
                        'Extra' => 'auto_increment',
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'title' => (object) array(
                        'Field' => 'title',
                        'Type' => 'varchar(50)',
                        'Collation' => 'utf8_general_ci',
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' =>null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'start_date' => (object) array(
                        'Field' => 'start_date',
                        'Type' => 'datetime',
                        'Collation' => null,
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' => null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    ),
                    'description' => (object) array(
                        'Field' => 'description',
                        'Type' => 'text',
                        'Collation' => 'utf8_general_ci',
                        'Null' => 'NO',
                        'Key' => null,
                        'Default' => null,
                        'Extra' => null,
                        'Privileges' => 'select,insert,update,references',
                        'Comment' => null
                    )
                )
            )
        );

        return $data;
    }

    public static function getTestGetDbo()
    {
        $data[] = array(
            array(
                'nuke' => false
            ),
            array(
                'case' => 'The internal db pointer is an object',
                'dbCounter' => 0
            )
        );

        $data[] = array(
            array(
                'nuke' => true
            ),
            array(
                'case' => 'The internal db pointer is not an object, getting from the container',
                'dbCounter' => 1
            )
        );

        return $data;
    }

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

    public static function getTestReset()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'recordData'      => array('id' => null, 'title' => null, 'start_date' => null, 'description' => null),
                    'eagerRelations'  => array(),
                    'relationFilters' => array()
                ),
                'table'     => '#__dbtest',
                'default'   => true,
                'relations' => false
            ),
            array(
                'case'           => 'Table with no defaults, no relations nor filters. Resetting to default, not resetting the relations',
                'resetRelations' => false,
                'eager'          => array(),
                'data'           => array(
                    'id'          => null,
                    'title'       => null,
                    'start_date'  => null,
                    'description' => null
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'recordData'      => array('id' => null, 'title' => null, 'start_date' => null, 'description' => null, 'foobar' => 'test'),
                    'eagerRelations'  => array(),
                    'relationFilters' => array()
                ),
                'table'     => '#__dbtest',
                'default'   => true,
                'relations' => false
            ),
            array(
                'case'           => 'Table with no defaults, no relations nor filters. Resetting to default, not resetting the relations. Additional fields set',
                'resetRelations' => false,
                'eager'          => array(),
                'data'           => array(
                    'id'          => null,
                    'title'       => null,
                    'start_date'  => null,
                    'description' => null
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'recordData'      => array('id' => null, 'title' => null, 'start_date' => null, 'description' => null),
                    'eagerRelations'  => array(),
                    'relationFilters' => array()
                ),
                'table'     => '#__dbtest',
                'default'   => false,
                'relations' => false
            ),
            array(
                'case'           => 'Table with no defaults, no relations nor filters. Not resetting to default, not resetting the relations',
                'resetRelations' => false,
                'eager'          => array(),
                'data'           => array(
                    'id'          => null,
                    'title'       => null,
                    'start_date'  => null,
                    'description' => null
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'recordData'      => array('id' => null, 'title' => null, 'start_date' => null, 'description' => null),
                    'eagerRelations'  => array(),
                    'relationFilters' => array()
                ),
                'table'     => '#__dbtest_defaults',
                'default'   => true,
                'relations' => false
            ),
            array(
                'case'           => 'Table with defaults, no relations nor filters. Resetting to defaults, not resetting the relations',
                'resetRelations' => false,
                'eager'          => array(),
                'data'           => array(
                    'id'          => null,
                    'title'       => 'dummy',
                    'start_date'  => '0000-00-00 00:00:00',
                    'description' => null
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'recordData'      => array('id' => null, 'title' => null, 'start_date' => null, 'description' => null),
                    'eagerRelations'  => array(),
                    'relationFilters' => array()
                ),
                'table'     => '#__dbtest_defaults',
                'default'   => false,
                'relations' => false
            ),
            array(
                'case'           => 'Table with defaults, no relations nor filters. Not resetting to defaults, not resetting the relations',
                'resetRelations' => false,
                'eager'          => array(),
                'data'           => array(
                    'id'          => null,
                    'title'       => null,
                    'start_date'  => null,
                    'description' => null
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'recordData'      => array(),
                    'eagerRelations'  => array('foo' => 'bar'),
                    'relationFilters' => array('dummy')
                ),
                'table'     => '#__dbtest',
                'default'   => true,
                'relations' => false
            ),
            array(
                'case'           => 'Relations set, but we are not resetting them',
                'resetRelations' => false,
                'eager'          => array('foo' => 'bar'),
                'data'           => array(
                    'id'          => null,
                    'title'       => null,
                    'start_date'  => null,
                    'description' => null
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'recordData'      => array(),
                    'eagerRelations'  => array('foo' => 'bar'),
                    'relationFilters' => array('dummy')
                ),
                'table'     => '#__dbtest',
                'default'   => true,
                'relations' => true
            ),
            array(
                'case'           => 'Relations set, we are resetting them',
                'resetRelations' => true,
                'eager'          => array(),
                'data'           => array(
                    'id'          => null,
                    'title'       => null,
                    'start_date'  => null,
                    'description' => null
                )
            )
        );

        return $data;
    }

    public static function getTest__call()
    {
        $data[] = array(
            array(
                'method'   => 'dummyProperty',
                'argument' => null,
                'mock'     => array(
                    'magic' => false
                )
            ),
            array(
                'case'     => 'Property with a specific method, no argument passed',
                'method'   => 'scopeDummyProperty',
                'property' => 'dummyProperty',
                'value'    => 'default',
                'count'    => 1,
                'magic'    => false,
                'relationCall' => false
            )
        );

        $data[] = array(
            array(
                'method'   => 'dummyProperty',
                'argument' => array('test', null),
                'mock'     => array(
                    'magic' => false
                )
            ),
            array(
                'case'     => 'Property with a specific method, argument passed',
                'method'   => 'scopeDummyProperty',
                'property' => 'dummyProperty',
                'value'    => 'test',
                'count'    => 0,
                'magic'    => true,
                'relationCall' => false
            )
        );

        $data[] = array(
            array(
                'method'   => 'dummyPropertyNoFunction',
                'argument' => null,
                'mock'     => array(
                    'magic' => false
                )
            ),
            array(
                'case'     => 'Property without a specific method, no argument passed',
                'method'   => 'scopeDummyPropertyNoFunction',
                'property' => 'dummyPropertyNoFunction',
                'value'    => null,
                'count'    => 0,
                'magic'    => true,
                'relationCall' => false
            )
        );

        $data[] = array(
            array(
                'method'   => 'dummyPropertyNoFunction',
                'argument' => array('test', null),
                'mock'     => array(
                    'magic' => false
                )
            ),
            array(
                'case'     => 'Property without a specific method, argument passed',
                'method'   => 'scopeDummyPropertyNoFunction',
                'property' => 'dummyPropertyNoFunction',
                'value'    => 'test',
                'count'    => 0,
                'magic'    => true,
                'relationCall' => false
            )
        );

        $data[] = array(
            array(
                'method'   => 'dummyPropertyNoFunction',
                'argument' => array('test', null),
                'mock'     => array(
                    'magic' => true
                )
            ),
            array(
                'case'     => 'Property without a specific method, a magic method exists inside the relation manager',
                'method'   => 'scopeDummyPropertyNoFunction',
                'property' => 'dummyPropertyNoFunction',
                'value'    => 'default',
                'count'    => 0,
                'magic'    => true,
                'relationCall' => true
            )
        );

        return $data;
    }

    public static function getTest__isset()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'getField'  => 1,
                    'magic'     => '',
                    'alias'     => array(),
                    'relationGet' => null
                ),
                'property' => 'id'
            ),
            array(
                'case'          => 'Field is set and has a NOT NULL value',
                'getField'      => 'id',
                'magic'         => false,
                'relationGet'   => false,
                'isset'         => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getField'  => null,
                    'magic'     => '',
                    'alias'     => array(),
                    'relationGet' => null
                ),
                'property' => 'id'
            ),
            array(
                'case'          => 'Field is set and has a NULL value',
                'getField'      => 'id',
                'magic'         => false,
                'relationGet'   => false,
                'isset'         => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getField'  => 1,
                    'magic'     => '',
                    'alias'     => array(
                        'foobar' => 'id'
                    ),
                    'relationGet' => null
                ),
                'property' => 'foobar'
            ),
            array(
                'case'          => 'Field had an alias and has a NOT NULL value',
                'getField'      => 'id',
                'magic'         => false,
                'relationGet'   => false,
                'isset'         => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getField'  => null,
                    'magic'     => '',
                    'alias'     => array(
                        'foobar' => 'id'
                    ),
                    'relationGet' => null
                ),
                'property' => 'foobar'
            ),
            array(
                'case'          => 'Field had an alias and has a NULL value',
                'getField'      => 'id',
                'magic'         => false,
                'relationGet'   => false,
                'isset'         => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getField'  => null,
                    'magic'     => false,
                    'alias'     => array(),
                    'relationGet' => null
                ),
                'property' => 'foobar'
            ),
            array(
                'case'          => 'Field is not set and is not a magic property',
                'getField'      => false,
                'magic'         => 'foobar',
                'relationGet'  => false,
                'isset'         => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getField'  => null,
                    'magic'     => true,
                    'alias'     => array(),
                    'relationGet' => 1
                ),
                'property' => 'foobar'
            ),
            array(
                'case'          => 'Field is not set and is a magic property, returns NOT NULL',
                'getField'      => false,
                'magic'         => 'foobar',
                'relationGet'   => true,
                'isset'         => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getField'  => null,
                    'magic'     => true,
                    'alias'     => array(),
                    'relationGet' => null
                ),
                'property' => 'foobar'
            ),
            array(
                'case'          => 'Field is not set and is a magic property, returns NULL',
                'getField'      => false,
                'magic'         => 'foobar',
                'relationGet'   => true,
                'isset'         => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getField'  => false,
                    'magic'     => '',
                    'alias'     => array(),
                    'relationGet' => null
                ),
                'property' => 'fltState'
            ),
            array(
                'case'          => 'Field starts with flt, no magic property set',
                'getField'      => null,
                'magic'         => 'state',
                'relationGet'   => false,
                'isset'         => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getField'  => false,
                    'magic'     => true,
                    'alias'     => array(),
                    'relationGet' => null
                ),
                'property' => 'fltState'
            ),
            array(
                'case'          => 'Field starts with flt, magic property set and returns NULL',
                'getField'      => null,
                'magic'         => 'state',
                'relationGet'   => true,
                'isset'         => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getField'  => false,
                    'magic'     => true,
                    'alias'     => array(),
                    'relationGet' => 1
                ),
                'property' => 'fltState'
            ),
            array(
                'case'          => 'Field starts with flt, magic property set and returns NOT NULL',
                'getField'      => null,
                'magic'         => 'state',
                'relationGet'   => true,
                'isset'         => true
            )
        );

        return $data;
    }

    public static function getTest__get()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'getField'    => 1,
                    'getState'    => 0,
                    'magic'       => '',
                    'alias'       => array(),
                    'relationGet' => null
                ),
                'property' => 'id'
            ),
            array(
                'case'          => 'Standard field of the DataModel',
                'getField'      => 'id',
                'getState'      => false,
                'magic'         => false,
                'relationGet'   => false,
                'get'           => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getField'    => 1,
                    'getState'    => 0,
                    'magic'       => '',
                    'alias'       => array(
                        'foobar' => 'id'
                    ),
                    'relationGet' => null
                ),
                'property' => 'foobar'
            ),
            array(
                'case'          => 'Standard field with an alias of the DataModel',
                'getField'      => 'id',
                'getState'      => false,
                'magic'         => false,
                'relationGet'   => false,
                'get'           => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getField'    => 0,
                    'getState'    => 1,
                    'magic'       => false,
                    'alias'       => array(),
                    'relationGet' => null
                ),
                'property' => 'foobar'
            ),
            array(
                'case'          => 'Field with has not a magic property method inside the relation manager',
                'getField'      => false,
                'getState'      => 'foobar',
                'magic'         => 'foobar',
                'relationGet'   => false,
                'get'           => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getField'    => 0,
                    'getState'    => 0,
                    'magic'       => true,
                    'alias'       => array(),
                    'relationGet' => 1
                ),
                'property' => 'foobar'
            ),
            array(
                'case'          => 'Field has a magic property method inside the relation manager',
                'getField'      => false,
                'getState'      => false,
                'magic'         => 'foobar',
                'relationGet'   => 'foobar',
                'get'           => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getField'    => 0,
                    'getState'    => 1,
                    'magic'       => false,
                    'alias'       => array(),
                    'relationGet' => null
                ),
                'property' => 'fltFoobar'
            ),
            array(
                'case'          => 'Field with has not a magic property method inside the relation manager - Magic name',
                'getField'      => false,
                'getState'      => 'foobar',
                'magic'         => 'foobar',
                'relationGet'   => false,
                'get'           => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getField'    => 0,
                    'getState'    => 0,
                    'magic'       => true,
                    'alias'       => array(),
                    'relationGet' => 1
                ),
                'property' => 'fltFoobar'
            ),
            array(
                'case'          => 'Field has a magic property method inside the relation manager - Magic name',
                'getField'      => false,
                'getState'      => false,
                'magic'         => 'foobar',
                'relationGet'   => 'foobar',
                'get'           => 1
            )
        );

        return $data;
    }

    public static function getTest__set()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'alias'    => array()
                ),
                'property' => 'id',
                'value'    => 10
            ),
            array(
                'case'     => 'Setting a property that exists in the table',
                'call'     => false,
                'count'    => 0,
                'method'   => 'scopeId',
                'setField' => 'id',
                'setState' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'alias'    => array(
                        'foobar' => 'id'
                    )
                ),
                'property' => 'foobar',
                'value'    => 10
            ),
            array(
                'case'     => 'Setting a property that exists in the table using an alias',
                'call'     => false,
                'count'    => 0,
                'method'   => 'scopeId',
                'setField' => 'id',
                'setState' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'alias'    => array()
                ),
                'property' => 'foobar',
                'value'    => 10
            ),
            array(
                'case'     => 'Property does not exists, so we set the state',
                'call'     => false,
                'count'    => 0,
                'method'   => 'scopeFoobar',
                'setField' => false,
                'setState' => 'foobar'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'alias'    => array()
                ),
                'property' => 'dummyNoProperty',
                'value'    => 10
            ),
            array(
                'case'     => 'Property does not exists, but we have a magic method scope',
                'call'     => false,
                'count'    => 1,
                'method'   => 'scopeDummyNoProperty',
                'setField' => false,
                'setState' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'alias'    => array()
                ),
                'property' => 'fltFoobar',
                'value'    => 10
            ),
            array(
                'case'     => 'Property does not exists, but its name is magic for the state',
                'call'     => false,
                'count'    => 0,
                'method'   => 'scopeFoobar',
                'setField' => false,
                'setState' => 'foobar'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'alias'    => array()
                ),
                'property' => 'scopeFoobar',
                'value'    => 10
            ),
            array(
                'case'     => 'Property does not exists, but its name is magic for the state - Going to invoke the call method of the model',
                'call'     => true,
                'count'    => 0,
                'method'   => 'scopeFoobar',
                'setField' => false,
                'setState' => false
            )
        );

        return $data;
    }

    public static function getTestGetFieldValue()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'alias' => array()
                ),
                'find'     => 1,
                'property' => 'id',
                'default'  => null
            ),
            array(
                'case'   => 'Getting a property that exists',
                'method' => 'GetIdAttribute',
                'result' => 1,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'alias' => array()
                ),
                'find'     => null,
                'property' => 'id',
                'default'  => null
            ),
            array(
                'case'   => 'Getting a property that exists, record not loaded',
                'method' => 'GetIdAttribute',
                'result' => null,
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'alias' => array()
                ),
                'find'     => null,
                'property' => 'foobar',
                'default'  => 'test'
            ),
            array(
                'case'   => 'Getting a property that does not exist',
                'method' => 'GetFoobarAttribute',
                'result' => 'test',
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'alias' => array(
                        'foobar' => 'title'
                    )
                ),
                'find'     => 1,
                'property' => 'foobar',
                'default'  => null
            ),
            array(
                'case'   => 'Getting a property that exists using an alias',
                'method' => 'GetTitleAttribute',
                'result' => 'Testing',
                'count'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'alias' => array()
                ),
                'find'     => 1,
                'property' => 'dummy',
                'default'  => null
            ),
            array(
                'case'   => 'Getting a property that has a specific getter',
                'method' => 'GetDummyAttribute',
                'result' => null,
                'count'  => 1
            )
        );

        return $data;
    }

    public static function getTestArchive()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'before' => '',
                    'after'  => '',
                    'alias'  => array()
                ),
                'table' => '#__dbtest'
            ),
            array(
                'case'       => 'Table with no enabled field',
                'dispatcher' => 0,
                'save'       => false,
                'exception'  => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => '',
                    'after'  => '',
                    'alias'  => array(
                        'enabled' => 'xx_enabled'
                    )
                ),
                'table' => '#__dbtest_alias'
            ),
            array(
                'case'       => 'Table with enabled field (alias)',
                'dispatcher' => 2,
                'save'       => true,
                'exception'  => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => '',
                    'after'  => '',
                    'alias'  => array()
                ),
                'table' => '#__dbtest_extended'
            ),
            array(
                'case'       => 'Table with enabled field',
                'dispatcher' => 2,
                'save'       => true,
                'exception'  => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => function(){ return false;},
                    'after'  => '',
                    'alias'  => array()
                ),
                'table' => '#__dbtest_extended'
            ),
            array(
                'case'       => 'Table with enabled field, onBefore returns false',
                'dispatcher' => 2,
                'save'       => true,
                'exception'  => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => function(){ return true;},
                    'after'  => function(){ return false;},
                    'alias'  => array()
                ),
                'table' => '#__dbtest_extended'
            ),
            array(
                'case'       => 'Table with enabled field, onAfter returns false',
                'dispatcher' => 2,
                'save'       => true,
                'exception'  => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => function(){ throw new \Exception();},
                    'after'  => function(){ return false;},
                    'alias'  => array()
                ),
                'table' => '#__dbtest_extended'
            ),
            array(
                'case'       => 'Table with enabled field, onBefore throws an exception',
                'dispatcher' => 0,
                'save'       => false,
                'exception'  => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => function(){ return true;},
                    'after'  => function(){ throw new \Exception();},
                    'alias'  => array()
                ),
                'table' => '#__dbtest_extended'
            ),
            array(
                'case'       => 'Table with enabled field, onAfter throws an exception',
                'dispatcher' => 1,
                'save'       => true,
                'exception'  => true
            )
        );

        return $data;
    }

    public static function getTestHasField()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'getAlias' => 'id',
                    'fields'   => array(
                        'id' => 'dummy'
                    )
                ),
                'field' => 'id'
            ),
            array(
                'case'   => 'Field exists, no alias',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getAlias' => 'nothere',
                    'fields'   => array(
                        'id' => 'dummy'
                    )
                ),
                'field' => 'nothere'
            ),
            array(
                'case'   => 'Field does not exists, no alias',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getAlias' => 'foobar',
                    'fields'   => array(
                        'id' => 'dummy'
                    )
                ),
                'field' => 'id'
            ),
            array(
                'case'   => 'Field does no exists, has an alias',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getAlias' => 'foobar',
                    'fields'   => array(
                        'foobar' => 'dummy'
                    )
                ),
                'field' => 'id'
            ),
            array(
                'case'   => 'Field exists, has an alias',
                'result' => true
            )
        );

        return $data;
    }

    public static function getTestGetFieldAlias()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'alias' => array(
                        'foobar' => 'test'
                    )
                ),
                'field' => 'id'
            ),
            array(
                'case'   => 'Alias not set for the field',
                'result' => 'id'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'alias' => array(
                        'id' => 'foobar'
                    )
                ),
                'field' => 'id'
            ),
            array(
                'case'   => 'Alias set for the field',
                'result' => 'foobar'
            )
        );

        return $data;
    }

    public static function getTestBind()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'beforeDisp' => null
                ),
                'data' => array(
                    'id' => 1,
                    'title' => 'test'
                ),
                'ignore' => array()
            ),
            array(
                'case' => 'Data array contains properties that exists',
                'dispatcher' => 2,
                'bind' => array('id' => 1, 'title' => 'test')
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'beforeDisp' => null
                ),
                'data' => array(
                    'id' => 1,
                    'title' => 'test'
                ),
                'ignore' => array('title')
            ),
            array(
                'case' => 'Data array contains properties that exists, ignoring some of them (array format)',
                'dispatcher' => 2,
                'bind' => array('id' => 1)
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'beforeDisp' => null
                ),
                'data' => array(
                    'id' => 1,
                    'title' => 'test',
                    'description' => 'test'
                ),
                'ignore' => 'title description'
            ),
            array(
                'case' => 'Data array contains properties that exists, ignoring some of them (string format)',
                'dispatcher' => 2,
                'bind' => array('id' => 1)
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'beforeDisp' => null
                ),
                'data' => array(
                    'id' => 1,
                    'title' => 'test',
                    'foobar' => 'foo'
                ),
                'ignore' => array()
            ),
            array(
                'case' => 'Trying to bind a property that does not exist',
                'dispatcher' => 2,
                'bind' => array('id' => 1, 'title' => 'test')
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'beforeDisp' => array(
                        'id' => 1,
                        'title' => 'test',
                    )
                ),
                'data' => null,
                'ignore' => array()
            ),
            array(
                'case' => 'Passing invalid data, however the onBeforeBind converts it to a valid one',
                'dispatcher' => 2,
                'bind' => array('id' => 1, 'title' => 'test')
            )
        );

        return $data;
    }

    public static function getTestBindException()
    {
        $data[] = array(
            array(
                'data' => ''
            )
        );

        $data[] = array(
            array(
                'data' => 1
            )
        );

        $data[] = array(
            array(
                'data' => null
            )
        );

        $data[] = array(
            array(
                'data' => false
            )
        );

        return $data;
    }

    public static function getTestCheck()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'auto' => false
                ),
                'table' => '#__dbtest',
                'load'  => null
            ),
            array(
                'case' => 'No autochecks set',
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'auto' => true
                ),
                'table' => '#__dbtest',
                'load'  => 1
            ),
            array(
                'case' => 'Table loaded',
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'auto' => true
                ),
                'table' => '#__dbtest',
                'load'  => null
            ),
            array(
                'case' => 'Check failed',
                'exception' => 'FAKEAPP_NESTEDSET_ERR_TITLE_EMPTY'
            )
        );

        return $data;
    }

    public static function getTestChunk()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'count' => 0
                ),
                'chunksize' => 5
            ),
            array(
                'case' => 'Records not found',
                'get'  => 0
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'count' => 10
                ),
                'chunksize' => 5
            ),
            array(
                'case' => 'Records found they are a multiple of the chunksize',
                'get'  => 2
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'count' => 10
                ),
                'chunksize' => 4
            ),
            array(
                'case' => 'Records found they are not a multiple of the chunksize',
                'get'  => 3
            )
        );

        return $data;
    }

    public static function getTestBuildQuery()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'where' => array()
                ),
                'override' => false
            ),
            array(
                'case' => 'No limits override, no additional query, no order field or direction',
                'filter' => true,
                'where'  => array(),
                'order'  => array('`id` ASC')
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'where' => array(),
                    'order' => 'title'
                ),
                'override' => false
            ),
            array(
                'case' => 'No limits override, no additional query or direction, with (known) order field',
                'filter' => true,
                'where'  => array(),
                'order'  => array('`title` ASC')
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'where' => array(),
                    'order' => 'foobar'
                ),
                'override' => false
            ),
            array(
                'case' => 'No limits override, no additional query or direction, with (unknown) order field',
                'filter' => true,
                'where'  => array(),
                'order'  => array('`id` ASC')
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'where' => array(),
                    'order' => 'title',
                    'dir'   => 'asc'
                ),
                'override' => false
            ),
            array(
                'case' => 'No limits override, no additional query, with (known) order field and lowercase direction',
                'filter' => true,
                'where'  => array(),
                'order'  => array('`title` ASC')
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'where' => array(),
                    'order' => 'title',
                    'dir'   => 'DESC'
                ),
                'override' => false
            ),
            array(
                'case' => 'No limits override, no additional query, with (known) order field and uppercase direction',
                'filter' => true,
                'where'  => array(),
                'order'  => array('`title` DESC')
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'where' => array(),
                    'order' => 'title',
                    'dir'   => 'wrong'
                ),
                'override' => false
            ),
            array(
                'case' => 'No limits override, no additional query, with (known) order field and invalid direction',
                'filter' => true,
                'where'  => array(),
                'order'  => array('`title` ASC')
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'where' => array(
                        'foobar = 1'
                    ),
                    'order' => 'title',
                    'dir'   => 'DESC'
                ),
                'override' => true
            ),
            array(
                'case' => 'Limits override, additional query, with (known) order field and uppercase direction',
                'filter' => false,
                'where'  => array('foobar = 1'),
                'order'  => array()
            )
        );


        return $data;
    }

    public static function getTestDelete()
    {
        $data[] = array(
            array(
                'id'   => null,
                'soft' => true
            ),
            array(
                'case' => 'Id not provided, soft delete',
                'trash' => true,
                'force' => false
            )
        );

        $data[] = array(
            array(
                'id'   => null,
                'soft' => false
            ),
            array(
                'case' => 'Id not provided, db delete',
                'trash' => false,
                'force' => true
            )
        );

        $data[] = array(
            array(
                'id'   => 2,
                'soft' => true
            ),
            array(
                'case' => 'Id provided, soft delete',
                'trash' => true,
                'force' => false
            )
        );

        $data[] = array(
            array(
                'id'   => 2,
                'soft' => false
            ),
            array(
                'case' => 'Id provided, db delete',
                'trash' => false,
                'force' => true
            )
        );

        return $data;
    }

    public static function getTestTrash()
    {
        $data[] = array(
            array(
                'table' => '#__dbtest',
                'id' => ''
            ),
            array(
                'case'   => 'Table with no publish support',
                'before' => 0,
                'after'  => 0,
                'find'   => false,
                'dispatcher' => 0,
                'enabled' => null
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_extended',
                'id' => null
            ),
            array(
                'case'   => 'Table with publish support, already loaded',
                'before' => 1,
                'after'  => 1,
                'find'   => false,
                'dispatcher' => 2,
                'enabled' => -2
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_extended',
                'id' => 1
            ),
            array(
                'case'   => 'Table with publish support, not loaded',
                'before' => 1,
                'after'  => 1,
                'find'   => true,
                'dispatcher' => 2,
                'enabled' => -2
            )
        );

        return $data;
    }

    public static function getTestFindOrFail()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'getId' => 1
                ),
                'keys' => null
            ),
            array(
                'case' => 'Record found, not passing any keys',
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getId' => null
                ),
                'keys' => null
            ),
            array(
                'case' => 'Record not found, not passing any keys',
                'exception' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getId' => 1
                ),
                'keys' => 1
            ),
            array(
                'case' => 'Record found, passing keys',
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'getId' => null
                ),
                'keys' => 1
            ),
            array(
                'case' => 'Record not found, passing keys',
                'exception' => true
            )
        );

        return $data;
    }

    public static function getTestForceDelete()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'id' => 1
                ),
                'id' => 1
            ),
            array(
                'case' => 'Passing the id',
                'id'   => 1,
                'find' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id' => 1
                ),
                'id' => null
            ),
            array(
                'case' => 'Loaded record',
                'id'   => 1,
                'find' => false
            )
        );

        return $data;
    }

    public static function getTestFirstOrCreate()
    {
        $data[] = array(
            array(
                'mock' => array(
                    // I just need to return any value to flag the record as loaded
                    'first' => 'foobar'
                )
            ),
            array(
                'case' => 'I was able to get first record',
                'create' => false,
                'result' => 'foobar'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'first' => null
                )
            ),
            array(
                'case' => "I couldn't get the first record",
                'create' => true,
                'result' => 'object'
            )
        );

        return $data;
    }

    public static function getTestFirstOrFail()
    {
        $data[] = array(
            array(
                'mock' => array(
                    // I just need to return any value to flag the record as loaded
                    'first' => 'foobar'
                )
            ),
            array(
                'case' => 'I was able to get first record',
                'exception' => false,
                'result' => 'foobar'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'first' => null
                )
            ),
            array(
                'case' => "I couldn't get the first record",
                'exception' => true,
                'result' => ''
            )
        );

        return $data;
    }

    public static function getTestFirstOrNew()
    {
        $data[] = array(
            array(
                'mock' => array(
                    // I just need to return any value to flag the record as loaded
                    'first' => 'foobar'
                )
            ),
            array(
                'case' => 'I was able to get first record',
                'reset' => false,
                'result' => 'foobar'
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'first' => null
                )
            ),
            array(
                'case' => "I couldn't get the first record",
                'reset' => true,
                'result' => 'object'
            )
        );

        return $data;
    }

    public static function getTestLock()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'user_id' => ''
                ),
                'table' => '#__dbtest',
                'user_id' => ''
            ),
            array(
                'case' => 'Table without locking support',
                'before' => 0,
                'after'  => 0,
                'dispatcher' => 0,
                'locked_by' => null,
                'locked_on' => null
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'user_id' => ''
                ),
                'table' => '#__dbtest_extended',
                'user_id' => 90
            ),
            array(
                'case' => 'Table with locking support, user_id passed',
                'before' => 1,
                'after'  => 1,
                'dispatcher' => 2,
                'locked_by' => 90,
                'locked_on' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'user_id' => 88
                ),
                'table' => '#__dbtest_extended',
                'user_id' => null
            ),
            array(
                'case' => 'Table with locking support, user_id not passed',
                'before' => 1,
                'after'  => 1,
                'dispatcher' => 2,
                'locked_by' => 88,
                'locked_on' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'user_id' => ''
                ),
                'table' => '#__dbtest_lockedby',
                'user_id' => 90
            ),
            array(
                'case' => 'Table with only the locked_by field',
                'before' => 1,
                'after'  => 1,
                'dispatcher' => 2,
                'locked_by' => 90,
                'locked_on' => null
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'user_id' => ''
                ),
                'table' => '#__dbtest_lockedon',
                'user_id' => 90
            ),
            array(
                'case' => 'Table with only the locked_on field',
                'before' => 1,
                'after'  => 1,
                'dispatcher' => 2,
                'locked_by' => null,
                'locked_on' => true
            )
        );

        return $data;
    }

    public static function getTestOrderBy()
    {
        $data[] = array(
            array(
                'field' => 'foobar',
                'dir'   => 'asc'
            ),
            array(
                'case'  => 'Passing field and direction (lowercase)',
                'field' => 'foobar',
                'dir'   => 'ASC'
            )
        );

        $data[] = array(
            array(
                'field' => 'foobar',
                'dir'   => 'desc'
            ),
            array(
                'case'  => 'Passing field and direction (lowercase)',
                'field' => 'foobar',
                'dir'   => 'DESC'
            )
        );

        $data[] = array(
            array(
                'field' => 'foobar',
                'dir'   => ''
            ),
            array(
                'case'  => 'Passing field only',
                'field' => 'foobar',
                'dir'   => 'ASC'
            )
        );

        return $data;
    }

    public static function getTestPublish()
    {
        $data[] = array(
            array(
                'table' => '#__dbtest',
                'state' => 1
            ),
            array(
                'case'    => 'Table with no publish support',
                'dispatcher' => 0,
                'before'  => 0,
                'after'   => 0,
                'enabled' => null
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_extended',
                'state' => 1
            ),
            array(
                'case'    => 'Table with publish support (record enabling)',
                'dispatcher' => 2,
                'before'  => 1,
                'after'   => 1,
                'enabled' => 1
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_extended',
                'state' => 0
            ),
            array(
                'case'    => 'Table with publish support (record disabling)',
                'dispatcher' => 2,
                'before'  => 1,
                'after'   => 1,
                'enabled' => 0
            )
        );

        return $data;
    }

    public static function getTestRestore()
    {
        $data[] = array(
            array(
                'table' => '#__dbtest',
                'id' => ''
            ),
            array(
                'case'   => 'Table with no publish support',
                'before' => 0,
                'after'  => 0,
                'find'   => false,
                'dispatcher' => 0,
                'enabled' => null
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_extended',
                'id' => null
            ),
            array(
                'case'   => 'Table with publish support, already loaded',
                'before' => 1,
                'after'  => 1,
                'find'   => false,
                'dispatcher' => 2,
                'enabled' => 0
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_extended',
                'id' => 1
            ),
            array(
                'case'   => 'Table with publish support, not loaded',
                'before' => 1,
                'after'  => 1,
                'find'   => true,
                'dispatcher' => 2,
                'enabled' => 0
            )
        );

        return $data;
    }

    public static function getTestSkip()
    {
        $data[] = array(
            array(
                'limitstart' => 10
            ),
            array(
                'case' => 'Limitstart is positive',
                'limitstart' => 10
            )
        );

        $data[] = array(
            array(
                'limitstart' => null
            ),
            array(
                'case' => 'Limitstart is null',
                'limitstart' => 0
            )
        );

        $data[] = array(
            array(
                'limitstart' => -1
            ),
            array(
                'case' => 'Limitstart is negative',
                'limitstart' => 0
            )
        );

        $data[] = array(
            array(
                'limitstart' => array(1)
            ),
            array(
                'case' => 'Wrong type',
                'limitstart' => 0
            )
        );

        $data[] = array(
            array(
                'limitstart' => new stdClass()
            ),
            array(
                'case' => 'Wrong type',
                'limitstart' => 0
            )
        );

        $data[] = array(
            array(
                'limitstart' => true
            ),
            array(
                'case' => 'Wrong type',
                'limitstart' => 0
            )
        );

        return $data;
    }

    public static function getTestTake()
    {
        $data[] = array(
            array(
                'limit' => 10
            ),
            array(
                'case' => 'Limit is positive',
                'limit' => 10
            )
        );

        $data[] = array(
            array(
                'limit' => null
            ),
            array(
                'case' => 'Limit is null',
                'limit' => 0
            )
        );

        $data[] = array(
            array(
                'limit' => -1
            ),
            array(
                'case' => 'Limit is negative',
                'limit' => 0
            )
        );

        $data[] = array(
            array(
                'limit' => array(1)
            ),
            array(
                'case' => 'Wrong type',
                'limit' => 0
            )
        );

        $data[] = array(
            array(
                'limit' => new stdClass()
            ),
            array(
                'case' => 'Wrong type',
                'limit' => 0
            )
        );

        $data[] = array(
            array(
                'limit' => true
            ),
            array(
                'case' => 'Wrong type',
                'limit' => 0
            )
        );

        return $data;
    }

    public static function getTestTouch()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'user_id' => ''
                ),
                'table' => '#__dbtest',
                'user_id' => ''
            ),
            array(
                'case' => 'Table without modifying support',
                'modified_by' => null,
                'modified_on' => null
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'user_id' => ''
                ),
                'table' => '#__dbtest_extended',
                'user_id' => 90
            ),
            array(
                'case' => 'Table with modifying support, user_id passed',
                'modified_by' => 90,
                'modified_on' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'user_id' => 88
                ),
                'table' => '#__dbtest_extended',
                'user_id' => null
            ),
            array(
                'case' => 'Table with modifying support, user_id not passed',
                'modified_by' => 88,
                'modified_on' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'user_id' => ''
                ),
                'table' => '#__dbtest_modifiedby',
                'user_id' => 90
            ),
            array(
                'case' => 'Table with only the modified_by field',
                'modified_by' => 90,
                'modified_on' => null
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'user_id' => ''
                ),
                'table' => '#__dbtest_modifiedon',
                'user_id' => 90
            ),
            array(
                'case' => 'Table with only the modified_on field',
                'modified_by' => null,
                'modified_on' => true
            )
        );

        return $data;
    }

    public static function getTestUnlock()
    {
        $data[] = array(
            array(
                'table' => '#__dbtest',
            ),
            array(
                'case' => 'Table without locking support',
                'before' => 0,
                'after'  => 0,
                'dispatcher' => 0,
                'locked_by' => null,
                'locked_on' => null
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_extended',
            ),
            array(
                'case' => 'Table with locking support, user_id passed',
                'before' => 1,
                'after'  => 1,
                'dispatcher' => 2,
                'locked_by' => 0,
                'locked_on' => true
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_extended',
            ),
            array(
                'case' => 'Table with locking support, user_id not passed',
                'before' => 1,
                'after'  => 1,
                'dispatcher' => 2,
                'locked_by' => 0,
                'locked_on' => true
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_lockedby',
            ),
            array(
                'case' => 'Table with only the locked_by field',
                'before' => 1,
                'after'  => 1,
                'dispatcher' => 2,
                'locked_by' => 0,
                'locked_on' => null
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_lockedon',
            ),
            array(
                'case' => 'Table with only the locked_on field',
                'before' => 1,
                'after'  => 1,
                'dispatcher' => 2,
                'locked_by' => null,
                'locked_on' => true
            )
        );

        return $data;
    }

    public static function getTestUnpublish()
    {
        $data[] = array(
            array(
                'table' => '#__dbtest',
            ),
            array(
                'case'   => 'Table with no publish support',
                'before' => 0,
                'after'  => 0,
                'dispatcher' => 0,
                'enabled' => null
            )
        );

        $data[] = array(
            array(
                'table' => '#__dbtest_extended',
            ),
            array(
                'case'   => 'Table with publish support',
                'before' => 1,
                'after'  => 1,
                'dispatcher' => 2,
                'enabled' => 0
            )
        );

        return $data;
    }
}