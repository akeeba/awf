<?php

class ControllerDataprovider
{
    public static function getTestRegisterTask()
    {
        $data[] = array(
            array(
                'task'   => 'dummy',
                'method' => 'Foobar',
                'mock'   => array(
                    'methods' => array('foobar')
                )
            ),
            array(
                'case'     => 'Method is mapped inside the controller',
                'register' => true
            )
        );

        $data[] = array(
            array(
                'task'   => 'dummy',
                'method' => 'Foobar',
                'mock'   => array(
                    'methods' => array()
                )
            ),
            array(
                'case'     => 'Method is not mapped inside the controller',
                'register' => false
            )
        );

        return $data;
    }

    public static function getTestSetMessage()
    {
        $data[] = array(
            array(
                'message' => 'foo',
                'type'    => null,
                'mock' => array(
                    'previous' => 'bar'
                )
            ),
            array(
                'case'      => '$type argument is null',
                'result'    => 'bar',
                'message'   => 'foo',
                'type'      => 'message'
            )
        );

        $data[] = array(
            array(
                'message' => 'foo',
                'type'    => 'warning',
                'mock' => array(
                    'previous' => 'bar'
                )
            ),
            array(
                'case'      => 'Message type is defined',
                'result'    => 'bar',
                'message'   => 'foo',
                'type'      => 'warning'
            )
        );

        return $data;
    }
}