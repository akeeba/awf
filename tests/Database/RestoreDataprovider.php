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

    public static function getTestStepRestoration()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'nextLine' => array(),
                    'timer'    => array(),
                    'running'  => 5,
                )
            ),
            array(
                'case'   => 'We immediately run out of time',
                'result' => array(
                    'percent'   => 0,
                    'restored'  => '0 b',
                    'total'     => '3.81 Kb',
                    'queries_restored' => 0,
                    'current_line'  => 0,
                    'current_part'  => 0,
                    'total_parts'   => 1,
                    'eta'           => '2 minutes',
                    'error'         => null,
                    'done'          => 0
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'nextLine' => array(),
                    'timer'    => array(5),
                    'running'  => 5,
                )
            ),
            array(
                'case'   => "We read all the parts",
                'result' => array(
                    'percent'   => 0,
                    'restored'  => '0 b',
                    'total'     => '3.81 Kb',
                    'queries_restored' => 0,
                    'current_line'  => 0,
                    'current_part'  => 0,
                    'total_parts'   => 1,
                    'eta'           => '2 minutes',
                    'error'         => null,
                    'done'          => 0
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'nextLine' => array(str_repeat('-', 100)),
                    'timer'    => array(5),
                    'running'  => 5,
                )
            ),
            array(
                'case'   => 'We process only one query',
                'result' => array(
                    'percent'   => 2.6,
                    'restored'  => '100 b',
                    'total'     => '3.81 Kb',
                    'queries_restored' => 1,
                    'current_line'  => 1,
                    'current_part'  => 0,
                    'total_parts'   => 1,
                    'eta'           => '3 minutes',
                    'error'         => null,
                    'done'          => 0
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'nextLine' => array(str_repeat('-', 100), str_repeat('+', 400)),
                    'timer'    => array(5, 2),
                    'running'  => 6,
                )
            ),
            array(
                'case'   => 'We process two queries',
                'result' => array(
                    'percent'   => 12.8,
                    'restored'  => '500 b',
                    'total'     => '3.81 Kb',
                    'queries_restored' => 2,
                    'current_line'  => 2,
                    'current_part'  => 0,
                    'total_parts'   => 1,
                    'eta'           => '41 seconds',
                    'error'         => null,
                    'done'          => 0
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'nextLine' => array(str_repeat('-', 100), 'exception', str_repeat('+', 400)),
                    'timer'    => array(5, 3, 2),
                    'running'  => 6,
                )
            ),
            array(
                'case'   => 'We process two queries, with a skip between them',
                'result' => array(
                    'percent'   => 12.8,
                    'restored'  => '500 b',
                    'total'     => '3.81 Kb',
                    'queries_restored' => 2,
                    'current_line'  => 2,
                    'current_part'  => 0,
                    'total_parts'   => 1,
                    'eta'           => '41 seconds',
                    'error'         => null,
                    'done'          => 0
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'nextLine' => array(str_repeat('-', 100), '', str_repeat('+', 400)),
                    'timer'    => array(5, 3, 2),
                    'running'  => 6,
                )
            ),
            array(
                'case'   => 'We process two queries, with an empty query between them',
                'result' => array(
                    'percent'   => 12.8,
                    'restored'  => '500 b',
                    'total'     => '3.81 Kb',
                    'queries_restored' => 2,
                    'current_line'  => 2,
                    'current_part'  => 0,
                    'total_parts'   => 1,
                    'eta'           => '41 seconds',
                    'error'         => null,
                    'done'          => 0
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'nextLine' => array(str_repeat('-', 100), str_repeat('+', 400), 'EOF'),
                    'timer'    => array(5, 3, 2),
                    'running'  => 6,
                )
            ),
            array(
                'case'   => 'We read the whole file',
                'result' => array(
                    'percent'   => 100,
                    'restored'  => '3.81 Kb',
                    'total'     => '3.81 Kb',
                    'queries_restored' => 3,
                    'current_line'  => 3,
                    'current_part'  => 0,
                    'total_parts'   => 1,
                    'eta'           => '0 seconds',
                    'error'         => null,
                    'done'          => 1
                )
            )
        );

        return $data;
    }
}
