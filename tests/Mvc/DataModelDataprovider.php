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
}