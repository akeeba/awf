<?php

class RelationManagerDataprovider
{
    public static function getTest__call()
    {
        $data[] = array(
            array(
                'method' => 'foobar',
                'arguments' => 0
            ),
            array(
                'case' => 'Method is not magic',
                'exception' => 'Awf\Mvc\DataModel\Relation\Exception\RelationTypeNotFound',
                'get'  => false,
                'add'  => false,
                'name' => ''
            )
        );

        $data[] = array(
            array(
                'method' => 'hasMany',
                'arguments' => 0
            ),
            array(
                'case' => 'Method is a standard relation, 0 argument passed',
                'exception' => 'InvalidArgumentException',
                'get'  => false,
                'add'  => false,
                'name' => 'hasMany'
            )
        );

        $data[] = array(
            array(
                'method' => 'hasMany',
                'arguments' => 1
            ),
            array(
                'case' => 'Method is a standard relation, 1 argument passed',
                'exception' => '',
                'get'  => false,
                'add'  => true,
                'name' => 'hasMany'
            )
        );

        $data[] = array(
            array(
                'method' => 'hasMany',
                'arguments' => 2
            ),
            array(
                'case' => 'Method is a standard relation, 2 argument passed',
                'exception' => '',
                'get'  => false,
                'add'  => true,
                'name' => 'hasMany'
            )
        );

        $data[] = array(
            array(
                'method' => 'hasMany',
                'arguments' => 3
            ),
            array(
                'case' => 'Method is a standard relation, 3 argument passed',
                'exception' => '',
                'get'  => false,
                'add'  => true,
                'name' => 'hasMany'
            )
        );

        $data[] = array(
            array(
                'method' => 'hasMany',
                'arguments' => 4
            ),
            array(
                'case' => 'Method is a standard relation, 4 argument passed',
                'exception' => '',
                'get'  => false,
                'add'  => true,
                'name' => 'hasMany'
            )
        );

        $data[] = array(
            array(
                'method' => 'hasMany',
                'arguments' => 5
            ),
            array(
                'case' => 'Method is a standard relation, 5 argument passed',
                'exception' => '',
                'get'  => false,
                'add'  => true,
                'name' => 'hasMany'
            )
        );

        $data[] = array(
            array(
                'method' => 'hasMany',
                'arguments' => 6
            ),
            array(
                'case' => 'Method is a standard relation, 6 argument passed',
                'exception' => '',
                'get'  => false,
                'add'  => true,
                'name' => 'hasMany'
            )
        );

        $data[] = array(
            array(
                'method' => 'hasMany',
                'arguments' => 7
            ),
            array(
                'case' => 'Method is a standard relation, 7 argument passed',
                'exception' => '',
                'get'  => false,
                'add'  => true,
                'name' => 'hasMany'
            )
        );

        $data[] = array(
            array(
                'method' => 'hasMany',
                'arguments' => 8
            ),
            array(
                'case' => 'Method is a standard relation, 8 argument passed',
                'exception' => '',
                'get'  => false,
                'add'  => true,
                'name' => 'hasMany'
            )
        );

        $data[] = array(
            array(
                'method' => 'getPhone',
                'arguments' => 0
            ),
            array(
                'case' => 'Method is a "get" one, 0 argument passed',
                'exception' => '',
                'get'  => true,
                'add'  => false,
                'name' => 'phone'
            )
        );

        $data[] = array(
            array(
                'method' => 'getPhone',
                'arguments' => 1
            ),
            array(
                'case' => 'Method is a "get" one, 1 argument passed',
                'exception' => '',
                'get'  => true,
                'add'  => false,
                'name' => 'phone'
            )
        );

        $data[] = array(
            array(
                'method' => 'getPhone',
                'arguments' => 2
            ),
            array(
                'case' => 'Method is a "get" one, 2 argument passed',
                'exception' => '',
                'get'  => true,
                'add'  => false,
                'name' => 'phone'
            )
        );

        $data[] = array(
            array(
                'method' => 'getPhone',
                'arguments' => 3
            ),
            array(
                'case' => 'Method is a "get" one, 3 argument passed',
                'exception' => 'InvalidArgumentException',
                'get'  => false,
                'add'  => false,
                'name' => 'phone'
            )
        );

        return $data;
    }

    public static function getTestIsMagicMethod()
    {
        $data[] = array(
            array(
                'method' => 'hasMany'
            ),
            array(
                'case' => 'Method is the name of a standard type',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'method' => 'getFoobar'
            ),
            array(
                'case' => 'Method is get-NameOfTheRelation- and the relation is set',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'method' => 'getDummy'
            ),
            array(
                'case' => 'Method is get-NameOfTheRelation- and the relation is not set',
                'result' => false
            )
        );

        $data[] = array(
            array(
                'method' => 'wrong'
            ),
            array(
                'case' => 'Method is not magic',
                'result' => false
            )
        );

        return $data;
    }

    public static function getTestIsMagicProperty()
    {
        $data[] = array(
            array(
                'name' => 'foobar'
            ),
            array(
                'case'   => 'Property is magic',
                'result' => true
            )
        );

        $data[] = array(
            array(
                'name' => 'wrong'
            ),
            array(
                'case'   => 'Property is not magic',
                'result' => false
            )
        );

        return $data;
    }
}