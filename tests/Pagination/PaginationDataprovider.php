<?php

use Awf\Text\Text;

class PaginationDataprovider
{
    public static function getTest__construct()
    {
        $data[] = array(
            array(
                'total'     => 10,
                'start'     => 0,
                'limit'     => 5,
                'displayed' => 10,
                'app'       => null
            ),
            array(
                'case'           => '10 links, 5 per pages, starting from 0',
                'limitStart'     => 0,
                'limit'          => 5,
                'total'          => 10,
                'pagesTotal'     => 2,
                'pagesCurrent'   => 1,
                'pagesDisplayed' => 10,
                'pagesStart'     => 1,
                'pagesStop'      => 2,
                'viewAll'        => false
            )
        );

        $data[] = array(
            array(
                'total'     => 10,
                'start'     => 3,
                'limit'     => 5,
                'displayed' => 10,
                'app'       => null
            ),
            array(
                'case'           => '10 links, 5 per pages, starting from 3',
                'limitStart'     => 3,
                'limit'          => 5,
                'total'          => 10,
                'pagesTotal'     => 2,
                'pagesCurrent'   => 1,
                'pagesDisplayed' => 10,
                'pagesStart'     => 1,
                'pagesStop'      => 2,
                'viewAll'        => false
            )
        );

        $data[] = array(
            array(
                'total'     => 10,
                'start'     => 6,
                'limit'     => 5,
                'displayed' => 10,
                'app'       => null
            ),
            array(
                'case'           => '10 links, 5 per pages, starting from 6',
                'limitStart'     => 5,
                'limit'          => 5,
                'total'          => 10,
                'pagesTotal'     => 2,
                'pagesCurrent'   => 2,
                'pagesDisplayed' => 10,
                'pagesStart'     => 1,
                'pagesStop'      => 2,
                'viewAll'        => false
            )
        );

        $data[] = array(
            array(
                'total'     => 20,
                'start'     => 6,
                'limit'     => 5,
                'displayed' => 10,
                'app'       => null
            ),
            array(
                'case'           => '20 links, 5 per pages, starting from 6',
                'limitStart'     => 6,
                'limit'          => 5,
                'total'          => 20,
                'pagesTotal'     => 4,
                'pagesCurrent'   => 2,
                'pagesDisplayed' => 10,
                'pagesStart'     => 1,
                'pagesStop'      => 4,
                'viewAll'        => false
            )
        );

        $data[] = array(
            array(
                'total'     => 20,
                'start'     => 6,
                'limit'     => 0,
                'displayed' => 10,
                'app'       => null
            ),
            array(
                'case'           => '20 links, no limit',
                'limitStart'     => 0,
                'limit'          => 20,
                'total'          => 20,
                'pagesTotal'     => 1,
                'pagesCurrent'   => 1,
                'pagesDisplayed' => 10,
                'pagesStart'     => 1,
                'pagesStop'      => 1,
                'viewAll'        => true
            )
        );

        $data[] = array(
            array(
                'total'     => 20,
                'start'     => 6,
                'limit'     => 100,
                'displayed' => 10,
                'app'       => null
            ),
            array(
                'case'           => 'Limit is bigger than the total',
                'limitStart'     => 0,
                'limit'          => 100,
                'total'          => 20,
                'pagesTotal'     => 1,
                'pagesCurrent'   => 1,
                'pagesDisplayed' => 10,
                'pagesStart'     => 1,
                'pagesStop'      => 1,
                'viewAll'        => false
            )
        );

        $data[] = array(
            array(
                'total'     => 200,
                'start'     => 32,
                'limit'     => 5,
                'displayed' => 10,
                'app'       => null
            ),
            array(
                'case'           => 'Displaying several pages of pagination',
                'limitStart'     => 32,
                'limit'          => 5,
                'total'          => 200,
                'pagesTotal'     => 40,
                'pagesCurrent'   => 7,
                'pagesDisplayed' => 10,
                'pagesStart'     => 2,
                'pagesStop'      => 11,
                'viewAll'        => false
            )
        );

        $data[] = array(
            array(
                'total'     => 200,
                'start'     => 32,
                'limit'     => 5,
                'displayed' => 5,
                'app'       => null
            ),
            array(
                'case'           => 'Displaying several pages of pagination',
                'limitStart'     => 32,
                'limit'          => 5,
                'total'          => 200,
                'pagesTotal'     => 40,
                'pagesCurrent'   => 7,
                'pagesDisplayed' => 5,
                'pagesStart'     => 4.5,
                'pagesStop'      => 8.5,
                'viewAll'        => false
            )
        );

        $data[] = array(
            array(
                'total'     => 200,
                'start'     => 190,
                'limit'     => 5,
                'displayed' => 50,
                'app'       => null
            ),
            array(
                'case'           => 'Display more pages than the available ones',
                'limitStart'     => 190,
                'limit'          => 5,
                'total'          => 200,
                'pagesTotal'     => 40,
                'pagesCurrent'   => 39,
                'pagesDisplayed' => 50,
                'pagesStart'     => 1,
                'pagesStop'      => 40,
                'viewAll'        => false
            )
        );

        $data[] = array(
            array(
                'total'     => 200,
                'start'     => 190,
                'limit'     => 5,
                'displayed' => 40,
                'app'       => null
            ),
            array(
                'case'           => 'Long list of pages, we are on the end',
                'limitStart'     => 190,
                'limit'          => 5,
                'total'          => 200,
                'pagesTotal'     => 40,
                'pagesCurrent'   => 39,
                'pagesDisplayed' => 40,
                'pagesStart'     => 1,
                'pagesStop'      => 40,
                'viewAll'        => false
            )
        );

        return $data;
    }

    public static function getTestSetAdditionalUrlParam()
    {
        $data[] = array(
            array(
                'mock'  => array(
                    'params' => array()
                ),
                'key'   => 'limit',
                'value' => ''
            ),
            array(
                'case'   => 'Trying to add limit as param',
                'result' => false,
                'params' => array()
            )
        );

        $data[] = array(
            array(
                'mock'  => array(
                    'params' => array()
                ),
                'key'   => 'limitstart',
                'value' => ''
            ),
            array(
                'case'   => 'Trying to add limitstart as param',
                'result' => false,
                'params' => array()
            )
        );

        $data[] = array(
            array(
                'mock'  => array(
                    'params' => array()
                ),
                'key'   => 'foo',
                'value' => 'bar'
            ),
            array(
                'case'   => 'Add a param that does not exist',
                'result' => null,
                'params' => array('foo' => 'bar')
            )
        );

        $data[] = array(
            array(
                'mock'  => array(
                    'params' => array(
                        'foo' => 'baz'
                    )
                ),
                'key'   => 'foo',
                'value' => 'bar'
            ),
            array(
                'case'   => 'Add a param that exists',
                'result' => 'baz',
                'params' => array('foo' => 'bar')
            )
        );

        $data[] = array(
            array(
                'mock'  => array(
                    'params' => array(
                        'foo' => 'bar'
                    )
                ),
                'key'   => 'foo',
                'value' => null
            ),
            array(
                'case'   => 'Unset a param',
                'result' => 'bar',
                'params' => array()
            )
        );

        return $data;
    }

    public static function getTestSetAdditionalUrlParamsFromInput()
    {
        // Getting the input from the application
        $data[] = array(
            array(
                'mock' => array(
                    'input' => true
                ),
                'input' => null
            )
        );

        // Passing the input
        $data[] = array(
            array(
                'mock' => array(
                    'input' => false
                ),
                'input' => array(
                    'foobar' => 'dummy'
                )
            )
        );

        // Passing the input with some unsupported params
        $data[] = array(
            array(
                'mock' => array(
                    'input' => false
                ),
                'input' => array(
                    'foobar' => 'dummy',
                    'array'  => array(1,2,3),
                    'object' => (object)array('foo', 'bar')
                )
            )
        );

        return $data;
    }

    public static function getTestGetAdditionalUrlParam()
    {
        $data[] = array(
            array(
                'key' => 'foo'
            ),
            array(
                'case'   => 'They key is set and is not null',
                'result' => 'bar'
            )
        );

        $data[] = array(
            array(
                'key' => 'empty'
            ),
            array(
                'case'   => 'They key is set and is null',
                'result' => null
            )
        );

        $data[] = array(
            array(
                'key' => 'nothere'
            ),
            array(
                'case'   => 'They key is not set',
                'result' => null
            )
        );

        return $data;
    }

    public static function getTestGetData()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'data'      => (object)array('foobar'),
                    'addParams' => array()
                ),
                'total'     => 10,
                'start'     => 0,
                'limit'     => 5,
                'displayed' => 10,
            ),
            array(
                'case'   => 'Data is cached',
                'result' => (object)array('foobar')
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'data'      => null,
                    'addParams' => array()
                ),
                'total'     => 10,
                'start'     => 0,
                'limit'     => 5,
                'displayed' => 10,
            ),
            array(
                'case'   => '10 links, 5 per pages, starting from 0',
                'result' => (object)array(
                    'all'      => new \Awf\Pagination\Object('AWF_PAGINATION_LBL_VIEW_ALL', '0', 'http://www.example.com/index.php?limitstart='),
                    'start'    => new \Awf\Pagination\Object('&laquo;'),
                    'previous' => new \Awf\Pagination\Object('&lsaquo;'),
                    'next'     => new \Awf\Pagination\Object('&rsaquo;', 5.0, 'http://www.example.com/index.php?limitstart=5'),
                    'end'      => new \Awf\Pagination\Object('&raquo;', 5.0, 'http://www.example.com/index.php?limitstart=5'),
                    'pages'    => array(
                        1 => new \Awf\Pagination\Object(1, null, null, true),
                        2 => new \Awf\Pagination\Object(2, 5, 'http://www.example.com/index.php?limitstart=5')
                    )
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'data'      => null,
                    'addParams' => array('foo' => 'bar')
                ),
                'total'     => 10,
                'start'     => 0,
                'limit'     => 5,
                'displayed' => 10,
            ),
            array(
                'case'   => 'Using additional params',
                'result' => (object)array(
                    'all'      => new \Awf\Pagination\Object('AWF_PAGINATION_LBL_VIEW_ALL', '0', 'http://www.example.com/index.php?foo=bar&limitstart='),
                    'start'    => new \Awf\Pagination\Object('&laquo;'),
                    'previous' => new \Awf\Pagination\Object('&lsaquo;'),
                    'next'     => new \Awf\Pagination\Object('&rsaquo;', 5.0, 'http://www.example.com/index.php?foo=bar&limitstart=5'),
                    'end'      => new \Awf\Pagination\Object('&raquo;', 5.0, 'http://www.example.com/index.php?foo=bar&limitstart=5'),
                    'pages'    => array(
                        1 => new \Awf\Pagination\Object(1, null, null, true),
                        2 => new \Awf\Pagination\Object(2, 5, 'http://www.example.com/index.php?foo=bar&limitstart=5')
                    )
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'data'      => null,
                    'addParams' => array()
                ),
                'total'     => 10,
                'start'     => 3,
                'limit'     => 5,
                'displayed' => 10,
            ),
            array(
                'case'           => '10 links, 5 per pages, starting from 3',
                'result' => (object)array(
                    'all'      => new \Awf\Pagination\Object('AWF_PAGINATION_LBL_VIEW_ALL', '0', 'http://www.example.com/index.php?limitstart='),
                    'start'    => new \Awf\Pagination\Object('&laquo;'),
                    'previous' => new \Awf\Pagination\Object('&lsaquo;'),
                    'next'     => new \Awf\Pagination\Object('&rsaquo;', 5.0, 'http://www.example.com/index.php?limitstart=5'),
                    'end'      => new \Awf\Pagination\Object('&raquo;', 5.0, 'http://www.example.com/index.php?limitstart=5'),
                    'pages'    => array(
                        1 => new \Awf\Pagination\Object(1, null, null, true),
                        2 => new \Awf\Pagination\Object(2, 5, 'http://www.example.com/index.php?limitstart=5')
                    )
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'data'      => null,
                    'addParams' => array()
                ),
                'total'     => 10,
                'start'     => 6,
                'limit'     => 5,
                'displayed' => 10,
            ),
            array(
                'case'           => '10 links, 5 per pages, starting from 6',
                'result' => (object)array(
                    'all'      => new \Awf\Pagination\Object('AWF_PAGINATION_LBL_VIEW_ALL', '0', 'http://www.example.com/index.php?limitstart='),
                    'start'    => new \Awf\Pagination\Object('&laquo;', '0', 'http://www.example.com/index.php?limitstart=0'),
                    'previous' => new \Awf\Pagination\Object('&lsaquo;', 0.0, 'http://www.example.com/index.php?limitstart=0'),
                    'next'     => new \Awf\Pagination\Object('&rsaquo;'),
                    'end'      => new \Awf\Pagination\Object('&raquo;'),
                    'pages'    => array(
                        1 => new \Awf\Pagination\Object(1, 0, 'http://www.example.com/index.php?limitstart=0', false),
                        2 => new \Awf\Pagination\Object(2, null, null, true)
                    )
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'data'      => null,
                    'addParams' => array()
                ),
                'total'     => 20,
                'start'     => 6,
                'limit'     => 5,
                'displayed' => 10,
            ),
            array(
                'case'           => '20 links, 5 per pages, starting from 6',
                'result' => (object)array(
                    'all'      => new \Awf\Pagination\Object('AWF_PAGINATION_LBL_VIEW_ALL', '0', 'http://www.example.com/index.php?limitstart='),
                    'start'    => new \Awf\Pagination\Object('&laquo;', '0', 'http://www.example.com/index.php?limitstart=0'),
                    'previous' => new \Awf\Pagination\Object('&lsaquo;', 0.0, 'http://www.example.com/index.php?limitstart=0'),
                    'next'     => new \Awf\Pagination\Object('&rsaquo;', 10.0, 'http://www.example.com/index.php?limitstart=10'),
                    'end'      => new \Awf\Pagination\Object('&raquo;', 15.0, 'http://www.example.com/index.php?limitstart=15'),
                    'pages'    => array(
                        1 => new \Awf\Pagination\Object(1, 0, 'http://www.example.com/index.php?limitstart=0', false),
                        2 => new \Awf\Pagination\Object(2, null, null, true),
                        3 => new \Awf\Pagination\Object(3, 10, 'http://www.example.com/index.php?limitstart=10'),
                        4 => new \Awf\Pagination\Object(4, 15, 'http://www.example.com/index.php?limitstart=15'),
                    )
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'data'      => null,
                    'addParams' => array()
                ),
                'total'     => 20,
                'start'     => 6,
                'limit'     => 0,
                'displayed' => 10,
            ),
            array(
                'case'           => '20 links, no limit',
                'result' => (object)array(
                    'all'      => new \Awf\Pagination\Object('AWF_PAGINATION_LBL_VIEW_ALL'),
                    'start'    => new \Awf\Pagination\Object('&laquo;'),
                    'previous' => new \Awf\Pagination\Object('&lsaquo;'),
                    'next'     => new \Awf\Pagination\Object('&rsaquo;'),
                    'end'      => new \Awf\Pagination\Object('&raquo;'),
                    'pages'    => array(
                        1 => new \Awf\Pagination\Object(1, 0, 'http://www.example.com/index.php?limitstart=0', false),
                    )
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'data'      => null,
                    'addParams' => array()
                ),
                'total'     => 20,
                'start'     => 6,
                'limit'     => 100,
                'displayed' => 10,
            ),
            array(
                'case'           => 'Limit is bigger than the total',
                'result' => (object)array(
                    'all'      => new \Awf\Pagination\Object('AWF_PAGINATION_LBL_VIEW_ALL', '0', 'http://www.example.com/index.php?limitstart='),
                    'start'    => new \Awf\Pagination\Object('&laquo;'),
                    'previous' => new \Awf\Pagination\Object('&lsaquo;'),
                    'next'     => new \Awf\Pagination\Object('&rsaquo;'),
                    'end'      => new \Awf\Pagination\Object('&raquo;'),
                    'pages'    => array(
                        1 => new \Awf\Pagination\Object(1, null, null, true),
                    )
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'data'      => null,
                    'addParams' => array()
                ),
                'total'     => 200,
                'start'     => 32,
                'limit'     => 5,
                'displayed' => 10,
            ),
            array(
                'case'           => 'Displaying several pages of pagination',
                'result' => (object)array(
                    'all'      => new \Awf\Pagination\Object('AWF_PAGINATION_LBL_VIEW_ALL', 0, 'http://www.example.com/index.php?limitstart='),
                    'start'    => new \Awf\Pagination\Object('&laquo;', '0', 'http://www.example.com/index.php?limitstart=0'),
                    'previous' => new \Awf\Pagination\Object('&lsaquo;', 25, 'http://www.example.com/index.php?limitstart=25'),
                    'next'     => new \Awf\Pagination\Object('&rsaquo;', 35, 'http://www.example.com/index.php?limitstart=35'),
                    'end'      => new \Awf\Pagination\Object('&raquo;', 195, 'http://www.example.com/index.php?limitstart=195'),
                    'pages'    => 10
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'data'      => null,
                    'addParams' => array()
                ),
                'total'     => 200,
                'start'     => 32,
                'limit'     => 5,
                'displayed' => 5,
            ),
            array(
                'case'           => 'Displaying several pages of pagination',
                'result' => (object)array(
                    'all'      => new \Awf\Pagination\Object('AWF_PAGINATION_LBL_VIEW_ALL', 0, 'http://www.example.com/index.php?limitstart='),
                    'start'    => new \Awf\Pagination\Object('&laquo;', '0', 'http://www.example.com/index.php?limitstart=0'),
                    'previous' => new \Awf\Pagination\Object('&lsaquo;', 25, 'http://www.example.com/index.php?limitstart=25'),
                    'next'     => new \Awf\Pagination\Object('&rsaquo;', 35, 'http://www.example.com/index.php?limitstart=35'),
                    'end'      => new \Awf\Pagination\Object('&raquo;', 195, 'http://www.example.com/index.php?limitstart=195'),
                    'pages'    => 5
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'data'      => null,
                    'addParams' => array()
                ),
                'total'     => 200,
                'start'     => 190,
                'limit'     => 5,
                'displayed' => 50,
            ),
            array(
                'case'           => 'Display more pages than the available ones',
                'result' => (object)array(
                    'all'      => new \Awf\Pagination\Object('AWF_PAGINATION_LBL_VIEW_ALL', 0, 'http://www.example.com/index.php?limitstart='),
                    'start'    => new \Awf\Pagination\Object('&laquo;', '0', 'http://www.example.com/index.php?limitstart=0'),
                    'previous' => new \Awf\Pagination\Object('&lsaquo;', 185, 'http://www.example.com/index.php?limitstart=185'),
                    'next'     => new \Awf\Pagination\Object('&rsaquo;', 195, 'http://www.example.com/index.php?limitstart=195'),
                    'end'      => new \Awf\Pagination\Object('&raquo;', 195, 'http://www.example.com/index.php?limitstart=195'),
                    'pages'    => 40
                )
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'data'      => null,
                    'addParams' => array()
                ),
                'total'     => 200,
                'start'     => 190,
                'limit'     => 5,
                'displayed' => 40,
            ),
            array(
                'case'           => 'Long list of pages, we are on the end',
                'result' => (object)array(
                    'all'      => new \Awf\Pagination\Object('AWF_PAGINATION_LBL_VIEW_ALL', 0, 'http://www.example.com/index.php?limitstart='),
                    'start'    => new \Awf\Pagination\Object('&laquo;', '0', 'http://www.example.com/index.php?limitstart=0'),
                    'previous' => new \Awf\Pagination\Object('&lsaquo;', 185, 'http://www.example.com/index.php?limitstart=185'),
                    'next'     => new \Awf\Pagination\Object('&rsaquo;', 195, 'http://www.example.com/index.php?limitstart=195'),
                    'end'      => new \Awf\Pagination\Object('&raquo;', 195, 'http://www.example.com/index.php?limitstart=195'),
                    'pages'    => 40
                )
            )
        );

        return $data;
    }
}