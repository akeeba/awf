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

    public static function getTestGetPagesCounter()
    {
        $data[] = array(
            array(
                'current' => 0,
                'total'   => 0
            ),
            array(
                'case'   => 'No pages',
                'result' => null
            )
        );

        $data[] = array(
            array(
                'current' => 3,
                'total'   => 10
            ),
            array(
                'case'   => 'There are some pages',
                'result' => 'Page 3 of 10'
            )
        );

        return $data;
    }

    public static function getTestGetResultsCounter()
    {
        $data[] = array(
            array(
                'mock' => array(
                    'start' => 0,
                    'limit' => 5,
                    'total' => 20
                )
            ),
            array(
                'case'   => 'There are results, we did not reach the end',
                'result' => "\nResults 1-5 of 20"
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'start' => 3,
                    'limit' => 5,
                    'total' => 20
                )
            ),
            array(
                'case'   => 'There are results, we did not reach the end',
                'result' => "\nResults 4-8 of 20"
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'start' => 0,
                    'limit' => 5,
                    'total' => 0
                )
            ),
            array(
                'case'   => 'No results',
                'result' => "\nAWF_PAGINATION_LBL_NO_RESULTS"
            )
        );

        $data[] = array(
            array(
                'mock' => array(
                    'start' => 16,
                    'limit' => 5,
                    'total' => 20
                )
            ),
            array(
                'case'   => 'There are results, we reached the end',
                'result' => "\nResults 17-20 of 20"
            )
        );

        return $data;
    }

    public static function getTestGetPagesLinks()
    {
        $data[] = array(
            array(
                'total'     => 10,
                'start'     => 0,
                'limit'     => 5,
                'displayed' => 10,
            ),
            array(
                'case'   => '10 links, 5 per pages, starting from 0',
                'result' => '<ul class="pagination"><li class="disabled"><span>&lsaquo;</span></li><li class="active"><a href="">1</a></li><li><a href="http://www.example.com/index.php?limitstart=5">2</a></li><li><a href="http://www.example.com/index.php?limitstart=5">&rsaquo;</a></li></ul>'
            )
        );

        $data[] = array(
            array(
                'total'     => 10,
                'start'     => 3,
                'limit'     => 5,
                'displayed' => 10,
            ),
            array(
                'case'   => '10 links, 5 per pages, starting from 3',
                'result' => '<ul class="pagination"><li class="disabled"><span>&lsaquo;</span></li><li class="active"><a href="">1</a></li><li><a href="http://www.example.com/index.php?limitstart=5">2</a></li><li><a href="http://www.example.com/index.php?limitstart=5">&rsaquo;</a></li></ul>'
            )
        );

        $data[] = array(
            array(
                'total'     => 10,
                'start'     => 6,
                'limit'     => 5,
                'displayed' => 10,
            ),
            array(
                'case'   => '10 links, 5 per pages, starting from 6',
                'result' => '<ul class="pagination"><li><a href="http://www.example.com/index.php?limitstart=0">&lsaquo;</a></li><li><a href="http://www.example.com/index.php?limitstart=0">1</a></li><li class="active"><a href="">2</a></li><li class="disabled"><span>&rsaquo;</span></li></ul>'
            )
        );

        $data[] = array(
            array(
                'total'     => 20,
                'start'     => 6,
                'limit'     => 5,
                'displayed' => 10,
            ),
            array(
                'case'   => '20 links, 5 per pages, starting from 6',
                'result' => '<ul class="pagination"><li><a href="http://www.example.com/index.php?limitstart=0">&lsaquo;</a></li><li><a href="http://www.example.com/index.php?limitstart=0">1</a></li><li class="active"><a href="">2</a></li><li><a href="http://www.example.com/index.php?limitstart=10">3</a></li><li><a href="http://www.example.com/index.php?limitstart=15">4</a></li><li><a href="http://www.example.com/index.php?limitstart=10">&rsaquo;</a></li></ul>'
            )
        );

        $data[] = array(
            array(
                'total'     => 20,
                'start'     => 6,
                'limit'     => 0,
                'displayed' => 10,
            ),
            array(
                'case'   => '20 links, no limit',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'total'     => 20,
                'start'     => 6,
                'limit'     => 100,
                'displayed' => 10,
            ),
            array(
                'case'   => 'Limit is bigger than the total',
                'result' => ''
            )
        );

        $data[] = array(
            array(
                'total'     => 200,
                'start'     => 32,
                'limit'     => 5,
                'displayed' => 10,
            ),
            array(
                'case'   => 'Displaying several pages of pagination',
                'result' => '<ul class="pagination"><li><a href="http://www.example.com/index.php?limitstart=0">&laquo;</a></li><li><a href="http://www.example.com/index.php?limitstart=25">&lsaquo;</a></li><li><a href="http://www.example.com/index.php?limitstart=5">2</a></li><li><a href="http://www.example.com/index.php?limitstart=10">3</a></li><li><a href="http://www.example.com/index.php?limitstart=15">4</a></li><li><a href="http://www.example.com/index.php?limitstart=20">5</a></li><li><a href="http://www.example.com/index.php?limitstart=25">6</a></li><li class="active"><a href="">7</a></li><li><a href="http://www.example.com/index.php?limitstart=35">8</a></li><li><a href="http://www.example.com/index.php?limitstart=40">9</a></li><li><a href="http://www.example.com/index.php?limitstart=45">10</a></li><li><a href="http://www.example.com/index.php?limitstart=50">11</a></li><li><a href="http://www.example.com/index.php?limitstart=35">&rsaquo;</a></li><li><a href="http://www.example.com/index.php?limitstart=195">&raquo;</a></li></ul>'
            )
        );

        $data[] = array(
            array(
                'total'     => 200,
                'start'     => 32,
                'limit'     => 5,
                'displayed' => 5,
            ),
            array(
                'case'   => 'Displaying several pages of pagination',
                'result' => '<ul class="pagination"><li><a href="http://www.example.com/index.php?limitstart=0">&laquo;</a></li><li><a href="http://www.example.com/index.php?limitstart=25">&lsaquo;</a></li><li><a href="http://www.example.com/index.php?limitstart=17.5">4.5</a></li><li><a href="http://www.example.com/index.php?limitstart=22.5">5.5</a></li><li><a href="http://www.example.com/index.php?limitstart=27.5">6.5</a></li><li class="active"><a href="http://www.example.com/index.php?limitstart=32.5">7.5</a></li><li><a href="http://www.example.com/index.php?limitstart=37.5">8.5</a></li><li><a href="http://www.example.com/index.php?limitstart=35">&rsaquo;</a></li><li><a href="http://www.example.com/index.php?limitstart=195">&raquo;</a></li></ul>'
            )
        );

        $data[] = array(
            array(
                'total'     => 200,
                'start'     => 190,
                'limit'     => 5,
                'displayed' => 50,
            ),
            array(
                'case'   => 'Display more pages than the available ones',
                'result' => '<ul class="pagination"><li><a href="http://www.example.com/index.php?limitstart=185">&lsaquo;</a></li><li><a href="http://www.example.com/index.php?limitstart=0">1</a></li><li><a href="http://www.example.com/index.php?limitstart=5">2</a></li><li><a href="http://www.example.com/index.php?limitstart=10">3</a></li><li><a href="http://www.example.com/index.php?limitstart=15">4</a></li><li><a href="http://www.example.com/index.php?limitstart=20">5</a></li><li><a href="http://www.example.com/index.php?limitstart=25">6</a></li><li><a href="http://www.example.com/index.php?limitstart=30">7</a></li><li><a href="http://www.example.com/index.php?limitstart=35">8</a></li><li><a href="http://www.example.com/index.php?limitstart=40">9</a></li><li><a href="http://www.example.com/index.php?limitstart=45">10</a></li><li><a href="http://www.example.com/index.php?limitstart=50">11</a></li><li><a href="http://www.example.com/index.php?limitstart=55">12</a></li><li><a href="http://www.example.com/index.php?limitstart=60">13</a></li><li><a href="http://www.example.com/index.php?limitstart=65">14</a></li><li><a href="http://www.example.com/index.php?limitstart=70">15</a></li><li><a href="http://www.example.com/index.php?limitstart=75">16</a></li><li><a href="http://www.example.com/index.php?limitstart=80">17</a></li><li><a href="http://www.example.com/index.php?limitstart=85">18</a></li><li><a href="http://www.example.com/index.php?limitstart=90">19</a></li><li><a href="http://www.example.com/index.php?limitstart=95">20</a></li><li><a href="http://www.example.com/index.php?limitstart=100">21</a></li><li><a href="http://www.example.com/index.php?limitstart=105">22</a></li><li><a href="http://www.example.com/index.php?limitstart=110">23</a></li><li><a href="http://www.example.com/index.php?limitstart=115">24</a></li><li><a href="http://www.example.com/index.php?limitstart=120">25</a></li><li><a href="http://www.example.com/index.php?limitstart=125">26</a></li><li><a href="http://www.example.com/index.php?limitstart=130">27</a></li><li><a href="http://www.example.com/index.php?limitstart=135">28</a></li><li><a href="http://www.example.com/index.php?limitstart=140">29</a></li><li><a href="http://www.example.com/index.php?limitstart=145">30</a></li><li><a href="http://www.example.com/index.php?limitstart=150">31</a></li><li><a href="http://www.example.com/index.php?limitstart=155">32</a></li><li><a href="http://www.example.com/index.php?limitstart=160">33</a></li><li><a href="http://www.example.com/index.php?limitstart=165">34</a></li><li><a href="http://www.example.com/index.php?limitstart=170">35</a></li><li><a href="http://www.example.com/index.php?limitstart=175">36</a></li><li><a href="http://www.example.com/index.php?limitstart=180">37</a></li><li><a href="http://www.example.com/index.php?limitstart=185">38</a></li><li class="active"><a href="">39</a></li><li><a href="http://www.example.com/index.php?limitstart=195">40</a></li><li><a href="http://www.example.com/index.php?limitstart=195">&rsaquo;</a></li></ul>'
            )
        );

        $data[] = array(
            array(
                'total'     => 200,
                'start'     => 190,
                'limit'     => 5,
                'displayed' => 40,
            ),
            array(
                'case'   => 'Long list of pages, we are on the end',
                'result' => '<ul class="pagination"><li><a href="http://www.example.com/index.php?limitstart=185">&lsaquo;</a></li><li><a href="http://www.example.com/index.php?limitstart=0">1</a></li><li><a href="http://www.example.com/index.php?limitstart=5">2</a></li><li><a href="http://www.example.com/index.php?limitstart=10">3</a></li><li><a href="http://www.example.com/index.php?limitstart=15">4</a></li><li><a href="http://www.example.com/index.php?limitstart=20">5</a></li><li><a href="http://www.example.com/index.php?limitstart=25">6</a></li><li><a href="http://www.example.com/index.php?limitstart=30">7</a></li><li><a href="http://www.example.com/index.php?limitstart=35">8</a></li><li><a href="http://www.example.com/index.php?limitstart=40">9</a></li><li><a href="http://www.example.com/index.php?limitstart=45">10</a></li><li><a href="http://www.example.com/index.php?limitstart=50">11</a></li><li><a href="http://www.example.com/index.php?limitstart=55">12</a></li><li><a href="http://www.example.com/index.php?limitstart=60">13</a></li><li><a href="http://www.example.com/index.php?limitstart=65">14</a></li><li><a href="http://www.example.com/index.php?limitstart=70">15</a></li><li><a href="http://www.example.com/index.php?limitstart=75">16</a></li><li><a href="http://www.example.com/index.php?limitstart=80">17</a></li><li><a href="http://www.example.com/index.php?limitstart=85">18</a></li><li><a href="http://www.example.com/index.php?limitstart=90">19</a></li><li><a href="http://www.example.com/index.php?limitstart=95">20</a></li><li><a href="http://www.example.com/index.php?limitstart=100">21</a></li><li><a href="http://www.example.com/index.php?limitstart=105">22</a></li><li><a href="http://www.example.com/index.php?limitstart=110">23</a></li><li><a href="http://www.example.com/index.php?limitstart=115">24</a></li><li><a href="http://www.example.com/index.php?limitstart=120">25</a></li><li><a href="http://www.example.com/index.php?limitstart=125">26</a></li><li><a href="http://www.example.com/index.php?limitstart=130">27</a></li><li><a href="http://www.example.com/index.php?limitstart=135">28</a></li><li><a href="http://www.example.com/index.php?limitstart=140">29</a></li><li><a href="http://www.example.com/index.php?limitstart=145">30</a></li><li><a href="http://www.example.com/index.php?limitstart=150">31</a></li><li><a href="http://www.example.com/index.php?limitstart=155">32</a></li><li><a href="http://www.example.com/index.php?limitstart=160">33</a></li><li><a href="http://www.example.com/index.php?limitstart=165">34</a></li><li><a href="http://www.example.com/index.php?limitstart=170">35</a></li><li><a href="http://www.example.com/index.php?limitstart=175">36</a></li><li><a href="http://www.example.com/index.php?limitstart=180">37</a></li><li><a href="http://www.example.com/index.php?limitstart=185">38</a></li><li class="active"><a href="">39</a></li><li><a href="http://www.example.com/index.php?limitstart=195">40</a></li><li><a href="http://www.example.com/index.php?limitstart=195">&rsaquo;</a></li></ul>'
            )
        );

        return $data;
    }

    public static function getTestGetLimitBox()
    {
        $data[] = array(
            array(
                'total'   => 20,
                'start'   => 0,
                'limit'   => 5,
                'attribs' => null
            ),
            array(
                'case'   => 'Default case',
                'result' =>
'<select id="limit" name="limit" class="input-sm" size="1" onchange="this.form.submit()">
	<option value="5" selected="selected">5</option>
	<option value="10">10</option>
	<option value="15">15</option>
	<option value="20">20</option>
	<option value="25">25</option>
	<option value="30">30</option>
	<option value="50">AWF_50</option>
	<option value="100">AWF_100</option>
	<option value="0">AWF_ALL</option>
</select>
'
            )
        );

        $data[] = array(
            array(
                'total'   => 20,
                'start'   => 0,
                'limit'   => 0,
                'attribs' => null
            ),
            array(
                'case'   => 'Displaying the whole list',
                'result' =>
'<select id="limit" name="limit" class="input-sm" size="1" onchange="this.form.submit()">
	<option value="5">5</option>
	<option value="10">10</option>
	<option value="15">15</option>
	<option value="20">20</option>
	<option value="25">25</option>
	<option value="30">30</option>
	<option value="50">AWF_50</option>
	<option value="100">AWF_100</option>
	<option value="0" selected="selected">AWF_ALL</option>
</select>
'
            )
        );

        $data[] = array(
            array(
                'total'   => 20,
                'start'   => 0,
                'limit'   => 5,
                'attribs' => array('class' => 'foobar')
            ),
            array(
                'case'   => 'Passing additional attribs',
                'result' =>
                    '<select id="limit" name="limit" class="foobar">
	<option value="5" selected="selected">5</option>
	<option value="10">10</option>
	<option value="15">15</option>
	<option value="20">20</option>
	<option value="25">25</option>
	<option value="30">30</option>
	<option value="50">AWF_50</option>
	<option value="100">AWF_100</option>
	<option value="0">AWF_ALL</option>
</select>
'
            )
        );

        return $data;
    }
}