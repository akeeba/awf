<?php
/**
 * @package    awf
 * @copyright  Copyright (c)2014-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU GPL version 3 or later
 */

class FiltersDataprovider
{
    public static function getTestOnAfterBuildQuery()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'state' => array(
                        'id' => 1,
                    )
                )
            ),
            array(
                'case'  => 'Searching vs primary key',
                'query' => "SELECT *
FROM test
WHERE (`id` = '1')"
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'state' => array(
                        'title' => 'test'
                    )
                )
            ),
            array(
                'case'  => 'Searching vs text field',
                'query' => "SELECT *
FROM test
WHERE (`title` LIKE '%test%')"
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'state' => array(
                        'description' => array(
                            'value' => 'one'
                        )
                    )
                )
            ),
            array(
                'case'  => 'Searching using an array',
                'query' => "SELECT *
FROM test
WHERE (`description` LIKE '%one%')"
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'state' => array(
                        'start_date' => array(
                            'from' => '1979-01-01',
                            'to'   => '1981-12-31'
                        ),
                    )
                )
            ),
            array(
                'case'  => 'Searching using an array, value key not present',
                'query' => "SELECT *
FROM test"
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'state' => array(
                        'start_date' => array(
                            'method' => 'between',
                            'from' => '1979-01-01',
                            'to'   => '1981-12-31'
                        ),
                    )
                )
            ),
            array(
                'case'  => 'Searching using an array, passing the method in the state - 1',
                'query' => "SELECT *
FROM test
WHERE ((`start_date` >= '1979-01-01') AND (`start_date` <= '1981-12-31'))"
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'state' => array(
                        'start_date' => array(
                            'method' => 'between',
                            'to'   => '1981-12-31'
                        ),
                    )
                )
            ),
            array(
                'case'  => 'Searching using an array, passing the method in the state - 2',
                'query' => "SELECT *
FROM test"
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'state' => array(
                        'start_date' => array(
                            'method' => 'between',
                            'from' => '1979-01-01',
                        ),
                    )
                )
            ),
            array(
                'case'  => 'Searching using an array, passing the method in the state - 3',
                'query' => "SELECT *
FROM test"
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'state' => array(
                        'start_date' => array(
                            'method' => 'outside',
                            'from' => '1979-01-01',
                            'to'   => '1981-12-31'
                        ),
                    )
                )
            ),
            array(
                'case'  => 'Searching using an array, passing the method in the state - 4',
                'query' => "SELECT *
FROM test
WHERE ((`start_date` < '1979-01-01') AND (`start_date` > '1981-12-31'))"
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'state' => array(
                        'start_date' => array(
                            'method' => 'interval',
                            'value' => '1979-01-01',
                            'interval' => '+1 year'
                        ),
                    )
                )
            ),
            array(
                'case'  => 'Searching using an array, passing the method in the state - 5',
                'query' => "SELECT *
FROM test
WHERE (`start_date` >= DATE_ADD(`start_date`, INTERVAL 1 year))"
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'state' => array(
                        'start_date' => array(
                            'method' => 'search',
                            'value' => '1979-01-01',
                        ),
                    )
                )
            ),
            array(
                'case'  => 'Searching using an array, passing the method in the state - 6',
                'query' => "SELECT *
FROM test
WHERE (`start_date` = '1979-01-01')"
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'state' => array(
                        'id' => array(
                            'method' => 'wrong',
                            'value' => '32',
                        ),
                    )
                )
            ),
            array(
                'case'  => 'Searching using an array, passing a wrong method in the state',
                'query' => "SELECT *
FROM test
WHERE (`id` = '32')"
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'state' => array(
                        'start_date' => array(
                            'method' => 'search',
                            'operator' => '>',
                            'value' => '1979-01-01',
                        ),
                    )
                )
            ),
            array(
                'case'  => 'Searching using an array, passing the method and operator in the state',
                'query' => "SELECT *
FROM test
WHERE (`start_date` > '1979-01-01')"
            )
        );



        $data[] = array(
            array(
                'mock' => array(
                    'state' => array(
                        'start_date' => (object) array(
                            'method' => 'search',
                            'operator' => '>',
                            'value' => '1979-01-01',
                        ),
                    )
                )
            ),
            array(
                'case'  => 'Searching using an object, passing the method and operator in the state',
                'query' => "SELECT *
FROM test
WHERE (`start_date` > '1979-01-01')"
            )
        );

        return $data;
    }
}
