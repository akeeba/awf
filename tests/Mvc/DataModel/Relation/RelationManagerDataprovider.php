<?php

class RelationManagerDataprovider
{
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
}