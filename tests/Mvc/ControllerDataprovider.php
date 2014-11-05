<?php

class ControllerDataprovider
{
    public static function getTestRedirect()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'redirect' => 'index.php'
                )
            ),
            array(
                'case'     => 'A redirect as been set',
                'result'   => null,
                'redirect' => 1
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'redirect' => null
                )
            ),
            array(
                'case'     => 'No redirection set',
                'result'   => false,
                'redirect' => 0
            )
        );

        return $data;
    }

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

    public static function getTestSetRedirect()
    {
        $data[] = array(
            array(
                'url'  => 'index.php',
                'msg'  => null,
                'type' => null,
                'mock' => array(
                    'type' => null
                )
            ),
            array(
                'case'     => 'Url is set, message and type are null; controller messageType is null',
                'redirect' => 'index.php',
                'message'  => null,
                'type'     => 'info'
            )
        );

        $data[] = array(
            array(
                'url'  => 'index.php',
                'msg'  => null,
                'type' => null,
                'mock' => array(
                    'type' => 'warning'
                )
            ),
            array(
                'case'     => 'Url is set, message and type are null; controller messageType is not null',
                'redirect' => 'index.php',
                'message'  => null,
                'type'     => 'warning'
            )
        );

        $data[] = array(
            array(
                'url'  => 'index.php',
                'msg'  => null,
                'type' => 'info',
                'mock' => array(
                    'type' => 'warning'
                )
            ),
            array(
                'case'     => 'Url and type are set, message is null; controller messageType is not null',
                'redirect' => 'index.php',
                'message'  => null,
                'type'     => 'info'
            )
        );

        $data[] = array(
            array(
                'url'  => 'index.php',
                'msg'  => 'Foobar',
                'type' => 'info',
                'mock' => array(
                    'type' => 'warning'
                )
            ),
            array(
                'case'     => 'Url, type and message are set, controller messageType is not null',
                'redirect' => 'index.php',
                'message'  => 'Foobar',
                'type'     => 'info'
            )
        );

        return $data;
    }
}