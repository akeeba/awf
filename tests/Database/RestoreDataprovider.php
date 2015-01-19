<?php

class RestoreDataprovider
{
    public static function getTest__construct()
    {
        $data[] = array(
            array(
                'dbrestore'   => false,
                'dbkey'       => null,
                'maxexectime' => null,
                'runtimebias' => null
            ),
            array(
                'case'      => 'Missing required key',
                'exception' => true,
                'max_exec'  => ''
            )
        );

        $data[] = array(
            array(
                'dbrestore'   => true,
                'dbkey'       => null,
                'maxexectime' => null,
                'runtimebias' => null
            ),
            array(
                'case'      => 'Missing dbkey in the container',
                'exception' => true,
                'max_exec'  => ''
            )
        );

        $data[] = array(
            array(
                'dbrestore'   => true,
                'dbkey'       => 'awftest',
                'maxexectime' => null,
                'runtimebias' => null
            ),
            array(
                'case'      => 'No max execution time nor runtime bias set',
                'exception' => false,
                'max_exec'  => 3.75
            )
        );

        $data[] = array(
            array(
                'dbrestore'   => true,
                'dbkey'       => 'awftest',
                'maxexectime' => 20,
                'runtimebias' => null
            ),
            array(
                'case'      => 'Max execution time set, no runtime bias set',
                'exception' => false,
                'max_exec'  => 15
            )
        );

        $data[] = array(
            array(
                'dbrestore'   => true,
                'dbkey'       => 'awftest',
                'maxexectime' => null,
                'runtimebias' => 50
            ),
            array(
                'case'      => 'No max execution time, runtime bias set',
                'exception' => false,
                'max_exec'  => 2.5
            )
        );

        $data[] = array(
            array(
                'dbrestore'   => true,
                'dbkey'       => 'awftest',
                'maxexectime' => 0,
                'runtimebias' => 5
            ),
            array(
                'case'      => 'Execution time and runtime bias lower than the limit',
                'exception' => false,
                'max_exec'  => 0.1
            )
        );

        return $data;
    }

    public static function getTestGetInstance()
    {
        $data[] = array(
            array(
                'cache'       => false,
                'dbrestore'   => false,
                'dbkey'       => null,
                'dbtype'      => null,
                'maxexectime' => null,
                'runtimebias' => null
            ),
            array(
                'case'      => 'Missing dbrestore key',
                'result'    => '',
                'exception' => true
            )
        );

        $data[] = array(
            array(
                'cache'       => false,
                'dbrestore'   => true,
                'dbkey'       => null,
                'dbtype'      => null,
                'maxexectime' => null,
                'runtimebias' => null
            ),
            array(
                'case'      => 'Missing dbkey key',
                'result'    => '',
                'exception' => true
            )
        );

        $data[] = array(
            array(
                'cache'       => false,
                'dbrestore'   => true,
                'dbkey'       => 'awftest',
                'dbtype'      => null,
                'maxexectime' => null,
                'runtimebias' => null
            ),
            array(
                'case'      => 'Missing dbtype key',
                'result'    => '',
                'exception' => true
            )
        );

        $data[] = array(
            array(
                'cache'       => false,
                'dbrestore'   => true,
                'dbkey'       => 'awftest',
                'dbtype'      => null,
                'maxexectime' => null,
                'runtimebias' => null
            ),
            array(
                'case'      => 'Missing dbtype key',
                'result'    => '',
                'exception' => true
            )
        );

        $data[] = array(
            array(
                'cache'       => false,
                'dbrestore'   => true,
                'dbkey'       => 'awftest',
                'dbtype'      => 'wrong',
                'maxexectime' => null,
                'runtimebias' => null
            ),
            array(
                'case'      => 'Restore class does not exist',
                'result'    => '',
                'exception' => true
            )
        );

        $data[] = array(
            array(
                'cache'       => false,
                'dbrestore'   => true,
                'dbkey'       => 'awftest',
                'dbtype'      => 'mysqli',
                'maxexectime' => null,
                'runtimebias' => null
            ),
            array(
                'case'      => 'Restore class exists',
                'result'    => 'Awf\Database\Restore\Mysqli',
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'cache'       => true,
                'dbrestore'   => true,
                'dbkey'       => 'awftest',
                'dbtype'      => 'mysqli',
                'maxexectime' => null,
                'runtimebias' => null
            ),
            array(
                'case'      => 'Cache is populated, but we are using another dbkey',
                'result'    => 'Awf\Database\Restore\Mysqli',
                'exception' => false
            )
        );

        $data[] = array(
            array(
                'cache'       => true,
                'dbrestore'   => true,
                'dbkey'       => 'cache',
                'dbtype'      => 'mysqli',
                'maxexectime' => null,
                'runtimebias' => null
            ),
            array(
                'case'      => 'Cache is populated, and we need the same instance',
                'result'    => 'Awf\Tests\Stubs\Database\RestoreMock',
                'exception' => false
            )
        );

        return $data;
    }
}
