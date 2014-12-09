<?php

class DataModelRelationDataprovider
{
    public static function getTestPush()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'names' => array('test1', 'test2'),
                    'touches' => array()
                ),
                'relations' => null
            ),
            array(
                'case' => 'No touches, saving all relations',
                'save' => array('test1', 'test2'),
                'touches' => array(),
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'names' => array('test1', 'test2'),
                    'touches' => array()
                ),
                'relations' => array('test1')
            ),
            array(
                'case' => 'No touches, saving some relations',
                'save' => array('test1'),
                'touches' => array(),
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'names' => array('test1', 'test2', 'children'),
                    'touches' => array('children')
                ),
                'relations' => null
            ),
            array(
                'case' => 'With touches, saving all relations',
                'save' => array('test1', 'test2', 'children'),
                'touches' => array('children'),
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'names' => array('test1', 'test2', 'children'),
                    'touches' => array('children')
                ),
                'relations' => array('test1')
            ),
            array(
                'case' => 'With touches, saving some relations',
                'save' => array('test1'),
                'touches' => array('children'),
            )
        );

        return $data;
    }

    public static function getTestEagerLoad()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'eager' => array()
                ),
                'items'     => true,
                'relations' => array(
                    'test' => function(){}
                )
            ),
            array(
                'case' => 'Passing a relation with callable callback, collection is not empty',
                'getData' => array(
                    'relation' => 'test',
                    'callback' => 'function'
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'eager' => array()
                ),
                'items'     => true,
                'relations' => array(
                    'test' => 'dummy'
                )
            ),
            array(
                'case' => 'Passing a relation without a callable callback, collection is not empty',
                'getData' => array(
                    'relation' => 'dummy',
                    'callback' => null
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'eager' => array(
                        'test' => function(){}
                    )
                ),
                'items'     => true,
                'relations' => null
            ),
            array(
                'case' => 'Using the relation defined inside the object, collection is not empty',
                'getData' => array(
                    'relation' => 'test',
                    'callback' => 'function'
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'eager' => array(
                        'test' => function(){}
                    )
                ),
                'items'     => false,
                'relations' => null
            ),
            array(
                'case' => 'Collection is empty',
                'getData' => array()
            )
        );

        return $data;
    }

    public static function getTestHas()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => false
                ),
                'relation' => 'posts',
                'method' => '<>',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => 'Behaviors not loaded',
                'add'     => true,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(array(
                        'relation' => 'posts',
                        'method'   => 'search',
                        'value'    => 1,
                        'operator' => '='
                    )),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '<>',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => 'Filter already set, not replacing it',
                'add'     => false,
                'filters' => array(
                    array(
                        'relation' => 'posts',
                        'method'   => 'search',
                        'value'    => 1,
                        'operator' => '='
                    ),
                    array(
                        'relation' => 'posts',
                        'method'   => 'search',
                        'value'    => 12,
                        'operator' => '!='
                    )
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(array(
                        'relation' => 'posts',
                        'method'   => 'search',
                        'value'    => 1,
                        'operator' => '='
                    )),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '<>',
                'values' => 12,
                'replace' => true
            ),
            array(
                'case'    => 'Filter already set, replacing it',
                'add'     => false,
                'filters' => array(
                    1 => array(
                        'relation' => 'posts',
                        'method'   => 'search',
                        'operator' => '!=',
                        'value'    => 12,
                    )
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '<>',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '<> method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => 'lt',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => 'lt method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '<'
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => 'le',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => 'le method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '<='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => 'gt',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => 'gt method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '>'
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => 'ge',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => 'ge method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '>='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => 'eq',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => 'eq method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => 'neq',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => 'neq method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => 'ne',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => 'ne method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '<',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '< method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '<'
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '!<',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '!< method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!<'
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '<=',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '<= method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '<='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '!<=',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '!<= method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!<='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '>',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '> method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '>'
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '!>',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '!> method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!>'
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '>=',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '>= method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '>='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '!>=',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '!>= method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!>='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '!=',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '!= method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '=',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '= method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => 'like',
                'values' => 'foobar',
                'replace' => false
            ),
            array(
                'case'    => 'like method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'partial',
                    'operator' => 'like',
                    'value'    => 'foobar'
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '~',
                'values' => 'foobar',
                'replace' => false
            ),
            array(
                'case'    => '~ method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'partial',
                    'operator' => '~',
                    'value'    => 'foobar'
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '%',
                'values' => 'foobar',
                'replace' => false
            ),
            array(
                'case'    => '%% method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'partial',
                    'operator' => '%',
                    'value'    => 'foobar'
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '==',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '== method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'exact',
                    'operator' => '==',
                    'value'    => 12
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '=[]',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '=[] method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'exact',
                    'operator' => '=[]',
                    'value'    => 12
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '=()',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '=() method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'exact',
                    'operator' => '=()',
                    'value'    => 12
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => 'in',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => 'in method, values passed',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'exact',
                    'operator' => 'in',
                    'value'    => 12
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '()',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => 'between method, values is not an array',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '[]',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '[] method, values is not an array',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '[)',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '[) method, values is not an array',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '(]',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '(] method, values is not an array',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '()',
                'values' => array(12),
                'replace' => false
            ),
            array(
                'case'    => 'between method, values is an array with a single element',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '()',
                'values' => array(12, 22),
                'replace' => false
            ),
            array(
                'case'    => 'between method, values is an array, but no from/to keys',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'between',
                    'operator' => '()',
                    'from'     => 12,
                    'to'       => 22
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '()',
                'values' => array(12, 22, 'from' => 5),
                'replace' => false
            ),
            array(
                'case'    => 'between method, values is an array, but no "from" key',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'between',
                    'operator' => '()',
                    'from'     => 12,
                    'to'       => 22
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '()',
                'values' => array(12, 22, 'to' => 5),
                'replace' => false
            ),
            array(
                'case'    => 'between method, values is an array, but no "to" key',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'between',
                    'operator' => '()',
                    'from'     => 12,
                    'to'       => 22
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '()',
                'values' => array(12, 22, 'from' => 5, 'to' => 7),
                'replace' => false
            ),
            array(
                'case'    => 'between method, values is an array, with "from/to" keys',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'between',
                    'operator' => '()',
                    'from'     => 5,
                    'to'       => 7
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => ')(',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => 'outside method, values is not an array',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => ')[',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => ')[ method, values is not an array',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '](',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => ']( method, values is not an array',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '][',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '][ method, values is not an array',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => ')(',
                'values' => array(12),
                'replace' => false
            ),
            array(
                'case'    => 'outside method, values is an array with a single element',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '!='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => ')(',
                'values' => array(12, 22),
                'replace' => false
            ),
            array(
                'case'    => 'outside method, values is an array, but no from/to keys',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'outside',
                    'operator' => ')(',
                    'from'     => 12,
                    'to'       => 22
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => ')(',
                'values' => array(12, 22, 'from' => 5),
                'replace' => false
            ),
            array(
                'case'    => 'outside method, values is an array, but no "from" key',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'outside',
                    'operator' => ')(',
                    'from'     => 12,
                    'to'       => 22
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => ')(',
                'values' => array(12, 22, 'to' => 5),
                'replace' => false
            ),
            array(
                'case'    => 'outside method, values is an array, but no "to" key',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'outside',
                    'operator' => ')(',
                    'from'     => 12,
                    'to'       => 22
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => ')(',
                'values' => array(12, 22, 'from' => 5, 'to' => 7),
                'replace' => false
            ),
            array(
                'case'    => 'outside method, values is an array, with "from/to" keys',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'outside',
                    'operator' => ')(',
                    'from'     => 5,
                    'to'       => 7
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => 'every',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => 'every (interval) method, values is not an array',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '*=',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => 'interval method, values is not an array',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '*=',
                'values' => array(12),
                'replace' => false
            ),
            array(
                'case'    => 'interval method, values is an array with a single item',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'value'    => 12,
                    'operator' => '='
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '*=',
                'values' => array(12, 22),
                'replace' => false
            ),
            array(
                'case'    => 'interval method, values is an array, but no value/interval keys',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'interval',
                    'operator' => '*=',
                    'value'    => 12,
                    'interval' => 22
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '*=',
                'values' => array(12, 22, 'value' => 5),
                'replace' => false
            ),
            array(
                'case'    => 'interval method, values is an array, but no "value" key',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'interval',
                    'operator' => '*=',
                    'value'    => 12,
                    'interval' => 22
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '*=',
                'values' => array(12, 22, 'interval' => 5),
                'replace' => false
            ),
            array(
                'case'    => 'interval method, values is an array, but no "interval" key',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'interval',
                    'operator' => '*=',
                    'value'    => 12,
                    'interval' => 22
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '*=',
                'values' => array(12, 22, 'value' => 5, 'interval' => 7),
                'replace' => false
            ),
            array(
                'case'    => 'interval method, values is an array, with "value/interval" keys',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'interval',
                    'operator' => '*=',
                    'value'    => 5,
                    'interval' => 7
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '?=',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => '?= method, values is not an array',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'operator' => '?=',
                    'value'    => 12
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '?=',
                'values' => array(12),
                'replace' => false
            ),
            array(
                'case'    => '?= method, values is an array with a single item',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'operator' => '?=',
                    'value'    => array(12)
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => '?=',
                'values' => array(12, 22),
                'replace' => false
            ),
            array(
                'case'    => '?= method, values is an array with no "operator/value" keys',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'operator' => '?=',
                    'value'    => array(12,22)
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => 'callback',
                'values' => function(){},
                'replace' => false
            ),
            array(
                'case'    => 'callback method, values is a callable function',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'callback',
                    'operator' => 'callback',
                    'value'    => function(){}
                ))
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'filters' => array(),
                    'hasClass' => true
                ),
                'relation' => 'posts',
                'method' => 'callback',
                'values' => 12,
                'replace' => false
            ),
            array(
                'case'    => 'callback method, values is NOT a callable function',
                'add'     => false,
                'filters' => array(array(
                    'relation' => 'posts',
                    'method'   => 'search',
                    'operator' => '=',
                    'value'    => 1
                ))
            )
        );

        return $data;
    }
}