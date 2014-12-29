<?php

class JsonDataprovider
{
    public static function getTest__construct()
    {
        $data[] = array(
            array(
                'hyper' => null
            ),
            array(
                'case'  => 'Hypermedia flag not set',
                'hyper' => false
            )
        );

        $data[] = array(
            array(
                'hyper' => false
            ),
            array(
                'case'  => 'Hypermedia flag set to false',
                'hyper' => false
            )
        );

        $data[] = array(
            array(
                'hyper' => true
            ),
            array(
                'case'  => 'Hypermedia flag set to true',
                'hyper' => true
            )
        );

        return $data;
    }

    public static function getTestDisplay()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'before' => true,
                    'after'  => true
                ),
                'task' => 'nothere'
            ),
            array(
                'case'      => 'Task with no before/after hooks',
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => true,
                    'after'  => true
                ),
                'task' => 'foobar'
            ),
            array(
                'case'      => 'Task with before/after hooks',
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => false,
                    'after'  => true
                ),
                'task' => 'foobar'
            ),
            array(
                'case'      => 'Task with before/after hooks - before returns false',
                'exception' => true
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'before' => true,
                    'after'  => false
                ),
                'task' => 'foobar'
            ),
            array(
                'case'      => 'Task with before/after hooks - after returns false',
                'exception' => true
            )
        );

        return $data;
    }
}