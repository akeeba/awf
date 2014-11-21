<?php

class DataModelDataprovider
{
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
}