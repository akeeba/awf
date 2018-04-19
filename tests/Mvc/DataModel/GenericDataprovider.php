<?php
/**
 * @package        awf
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

class DataModelGenericDataprovider
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
                        'Type' => 'varchar(255)',
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
                        'Type' => 'varchar(255)',
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
                        'Type' => 'varchar(255)',
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
                        'Type' => 'varchar(255)',
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
                        'Type' => 'varchar(255)',
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
                        'Type' => 'varchar(255)',
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

    public static function getTestGet()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'limitstart' => 10,
                    'limit'      => 10
                ),
                'override'   => false,
                'limitstart' => 0,
                'limit'      => 0
            ),
            array(
                'case'       => 'Not overriding the limits',
                'limitstart' => 10,
                'limit'      => 10
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'limitstart' => 10,
                    'limit'      => 10
                ),
                'override'   => true,
                'limitstart' => 5,
                'limit'      => 5
            ),
            array(
                'case'       => 'Overriding the limits',
                'limitstart' => 5,
                'limit'      => 5
            )
        );

        return $data;
    }

    public static function getTestAddBehaviour()
    {
        $data[] = array(
            array(
                'class' => 'Foofilters'
            ),
            array(
                'case'     => 'Adding behaviour from Fakeapp\\Model\\DataModel\\Behaviour',
                'class'    => 'Fakeapp\\Model\\DataModel\\Behaviour\\Foofilters',
                'attached' => true
            )
        );

        $data[] = array(
            array(
                'class' => 'NamedBehaviour'
            ),
            array(
                'case'     => 'Adding behaviour from Fakeapp\\Model\\Nestedset\\Behaviour\\NamedBehaviour',
                'class'    => 'Fakeapp\Model\Nestedset\Behaviour\NamedBehaviour',
                'attached' => true
            )
        );

        $data[] = array(
            array(
                'class' => 'Filters'
            ),
            array(
                'case'     => 'Adding behaviour from Awf\\Mvc\\DataModel\\Behaviour',
                'class'    => 'Awf\\Mvc\\DataModel\\Behaviour\\Filters',
                'attached' => true
            )
        );

        $data[] = array(
            array(
                'class' => 'Wrong'
            ),
            array(
                'case'     => 'Trying to add non-existant behaviour',
                'class'    => 'Awf\\Mvc\\DataModel\\Behaviour\\Wrong',
                'attached' => false
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

    public static function getTestToJson()
    {
        $data[] = array(
            array(
                'pretty' => false
            )
        );

        $data[] = array(
            array(
                'pretty' => true
            )
        );

        return $data;
    }

    public static function getTestWhere()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'foobar',
                    'hasClass' => false
                ),
                'field'  => 'foobar',
                'method' => '=',
                'values' => null
            ),
            array(
                'case'    => 'Behaviors not loaded, field is the primary key',
                'add'     => true,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => null,
                    'operator' => '='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'dummy',
                    'hasClass' => true
                ),
                'field'  => 'foobar',
                'method' => '=',
                'values' => null
            ),
            array(
                'case'    => 'Behaviors loaded, field is not the primary key',
                'add'     => false,
                'field'   => 'foobar',
                'options' => array(
                    'method'   => 'search',
                    'value'    => null,
                    'operator' => '='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '<>',
                'values' => 12
            ),
            array(
                'case'    => '<> method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => 'lt',
                'values' => 12
            ),
            array(
                'case'    => 'lt method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '<'
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => 'le',
                'values' => 12
            ),
            array(
                'case'    => 'le method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '<='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => 'gt',
                'values' => 12
            ),
            array(
                'case'    => 'gt method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '>'
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => 'ge',
                'values' => 12
            ),
            array(
                'case'    => 'ge method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '>='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => 'eq',
                'values' => 12
            ),
            array(
                'case'    => 'eq method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => 'neq',
                'values' => 12
            ),
            array(
                'case'    => 'neq method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => 'ne',
                'values' => 12
            ),
            array(
                'case'    => 'ne method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '<',
                'values' => 12
            ),
            array(
                'case'    => '< method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '<'
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '!<',
                'values' => 12
            ),
            array(
                'case'    => '!< method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!<'
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '<=',
                'values' => 12
            ),
            array(
                'case'    => '<= method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '<='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '!<=',
                'values' => 12
            ),
            array(
                'case'    => '!<= method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!<='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '>',
                'values' => 12
            ),
            array(
                'case'    => '> method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '>'
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '!>',
                'values' => 12
            ),
            array(
                'case'    => '!> method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!>'
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '>=',
                'values' => 12
            ),
            array(
                'case'    => '>= method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '>='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '!>=',
                'values' => 12
            ),
            array(
                'case'    => '!>= method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!>='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '!=',
                'values' => 12
            ),
            array(
                'case'    => '!= method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '=',
                'values' => 12
            ),
            array(
                'case'    => '= method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => 'like',
                'values' => 'foobar'
            ),
            array(
                'case'    => 'like method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'partial',
                    'value'    => 'foobar'
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '~',
                'values' => 'foobar'
            ),
            array(
                'case'    => '~ method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'partial',
                    'value'    => 'foobar'
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '%',
                'values' => 'foobar'
            ),
            array(
                'case'    => '%% method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'partial',
                    'value'    => 'foobar'
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '==',
                'values' => 12
            ),
            array(
                'case'    => '== method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'exact',
                    'value'    => 12
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '=[]',
                'values' => 12
            ),
            array(
                'case'    => '=[] method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'exact',
                    'value'    => 12
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '=()',
                'values' => 12
            ),
            array(
                'case'    => '=() method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'exact',
                    'value'    => 12
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => 'in',
                'values' => 12
            ),
            array(
                'case'    => 'in method, values passed',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'exact',
                    'value'    => 12
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '()',
                'values' => 12
            ),
            array(
                'case'    => 'between method, values is not an array',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '[]',
                'values' => 12
            ),
            array(
                'case'    => '[] method, values is not an array',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '[)',
                'values' => 12
            ),
            array(
                'case'    => '[) method, values is not an array',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '(]',
                'values' => 12
            ),
            array(
                'case'    => '(] method, values is not an array',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '()',
                'values' => array(12)
            ),
            array(
                'case'    => 'between method, values is an array with a single element',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '()',
                'values' => array(12, 22)
            ),
            array(
                'case'    => 'between method, values is an array, but no from/to keys',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'between',
                    'from'     => 12,
                    'to'       => 22
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '()',
                'values' => array(12, 22, 'from' => 5)
            ),
            array(
                'case'    => 'between method, values is an array, but no "from" key',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'between',
                    'from'     => 12,
                    'to'       => 22
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '()',
                'values' => array(12, 22, 'to' => 5)
            ),
            array(
                'case'    => 'between method, values is an array, but no "to" key',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'between',
                    'from'     => 12,
                    'to'       => 22
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '()',
                'values' => array(12, 22, 'from' => 5, 'to' => 7)
            ),
            array(
                'case'    => 'between method, values is an array, with "from/to" keys',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'between',
                    'from'     => 5,
                    'to'       => 7
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => ')(',
                'values' => 12
            ),
            array(
                'case'    => 'outside method, values is not an array',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => ')[',
                'values' => 12
            ),
            array(
                'case'    => ')[ method, values is not an array',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '](',
                'values' => 12
            ),
            array(
                'case'    => ']( method, values is not an array',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '][',
                'values' => 12
            ),
            array(
                'case'    => '][ method, values is not an array',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => ')(',
                'values' => array(12)
            ),
            array(
                'case'    => 'outside method, values is an array with a single element',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => ')(',
                'values' => array(12, 22)
            ),
            array(
                'case'    => 'outside method, values is an array, but no from/to keys',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'outside',
                    'from'     => 12,
                    'to'       => 22
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => ')(',
                'values' => array(12, 22, 'from' => 5)
            ),
            array(
                'case'    => 'outside method, values is an array, but no "from" key',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'outside',
                    'from'     => 12,
                    'to'       => 22
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => ')(',
                'values' => array(12, 22, 'to' => 5)
            ),
            array(
                'case'    => 'outside method, values is an array, but no "to" key',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'outside',
                    'from'     => 12,
                    'to'       => 22
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => ')(',
                'values' => array(12, 22, 'from' => 5, 'to' => 7)
            ),
            array(
                'case'    => 'outside method, values is an array, with "from/to" keys',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'outside',
                    'from'     => 5,
                    'to'       => 7
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => 'every',
                'values' => 12
            ),
            array(
                'case'    => 'every (interval) method, values is not an array',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '*=',
                'values' => 12
            ),
            array(
                'case'    => 'interval method, values is not an array',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '*=',
                'values' => array(12)
            ),
            array(
                'case'    => 'interval method, values is an array with a single item',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '*=',
                'values' => array(12, 22)
            ),
            array(
                'case'    => 'interval method, values is an array, but no value/interval keys',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'interval',
                    'value'    => 12,
                    'interval' => 22
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '*=',
                'values' => array(12, 22, 'value' => 5)
            ),
            array(
                'case'    => 'interval method, values is an array, but no "value" key',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'interval',
                    'value'    => 12,
                    'interval' => 22
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '*=',
                'values' => array(12, 22, 'interval' => 5)
            ),
            array(
                'case'    => 'interval method, values is an array, but no "interval" key',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'interval',
                    'value'    => 12,
                    'interval' => 22
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '*=',
                'values' => array(12, 22, 'value' => 5, 'interval' => 7)
            ),
            array(
                'case'    => 'interval method, values is an array, with "value/interval" keys',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'interval',
                    'value'    => 5,
                    'interval' => 7
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '?=',
                'values' => 12
            ),
            array(
                'case'    => '?= method, values is not an array',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => 12
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '?=',
                'values' => array(12)
            ),
            array(
                'case'    => '?= method, values is an array with a single item',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'value'    => array(12)
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '?=',
                'values' => array(12, 22)
            ),
            array(
                'case'    => '?= method, values is an array with no "operator/value" keys',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'operator' => 12,
                    'value'    => 22
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '?=',
                'values' => array(12, 22, 'operator' => 'XX')
            ),
            array(
                'case'    => '?= method, values is an array with no "value" key',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'operator' => 12,
                    'value'    => 22
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '?=',
                'values' => array(12, 22, 'value' => 'XX')
            ),
            array(
                'case'    => '?= method, values is an array with no "operator" key',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'operator' => 12,
                    'value'    => 22
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'id_field' => 'id',
                    'hasClass' => true
                ),
                'field'  => 'id',
                'method' => '?=',
                'values' => array(12, 22, 'value' => 5, 'operator' => 'XX')
            ),
            array(
                'case'    => '?= method, values is an array with "operator/value" keys',
                'add'     => false,
                'field'   => 'id',
                'options' => array(
                    'method'   => 'search',
                    'operator' => 'XX',
                    'value'    => 5
                )
            )
        );

        return $data;
    }

    public static function getTestWith()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'relNames' => array('foobar')
                ),
                'relations' => array('foobar' => function(){})
            ),
            array(
                'case' => 'Relation known, callback applied',
                'eager' => array(
                    'foobar' => function(){}
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'relNames' => array('foobar')
                ),
                'relations' => array('foobar')
            ),
            array(
                'case' => 'Relation known, no callback',
                'eager' => array(
                    'foobar' => null
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'relNames' => array('foobar')
                ),
                'relations' => array('dummy')
            ),
            array(
                'case' => 'Relation not known',
                'eager' => array()
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'relNames' => array('foobar')
                ),
                'relations' => array()
            ),
            array(
                'case' => 'Reset the eager relations array',
                'eager' => array()
            )
        );

        return $data;
    }
}
