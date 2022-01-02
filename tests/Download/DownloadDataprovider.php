<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

class DownloadDataprovider
{
	public static function getTestSetAdapter()
	{
		return array(
			array('Fopen', true),
			array('FOPEN', true),
			array('fopen', true),
			array('\\Awf\\Download\\Adapter\\Fopen', true),
			array('Curl', false),
			array('CURL', false),
			array('curl', false),
			array('\\Awf\\Download\\Adapter\\Curl', false),
			array('Spike', false),
			array('\\JRegistry', false),
			array(null, false),
		);
	}

	public static function getTestGetAdapterName()
	{
		return array(
			array('Fopen', 'fopen'),
			array('FOPEN', 'fopen'),
			array('fopen', 'fopen'),
			array('Curl', 'curl'),
			array('CURL', 'curl'),
			array('curl', 'curl'),
			array('\\Awf\\Download\\Adapter\\Fopen', 'fopen'),
			array('\\Awf\\Download\\Adapter\\Curl', 'curl'),
			array('Spike', 'curl'),
			array('\\JRegistry', 'curl'),
			array(null, 'curl'),
		);
	}

	public static function getTestGetFromUrl()
	{
		return array(
			array(
				'setup' => array(
					'httpstatus' => 200,
				),
				'test'  => array(
					'url'     => 'http://www.example.com/donwload.dat',
					'from'    => 0,
					'to'      => 0,
					'retSize' => 1048576,
					'false'   => false,
					'message' => 'Download a simple 1M file'
				)
			),

			array(
				'setup' => array(),
				'test'  => array(
					'url'     => 'http://www.example.com/IDoNotExist.dat',
					'from'    => 0,
					'to'      => 0,
					'retSize' => 0,
					'false'   => true,
					'message' => '404 on non-existent file results in Exception'
				)
			),

			array(
				'setup' => array(
					'httpstatus' => 403,
				),
				'test'  => array(
					'url'     => 'http://www.example.com/donwload.dat',
					'from'    => 0,
					'to'      => 0,
					'retSize' => 0,
					'false'   => true,
					'message' => '403 Forbidden results in Exception'
				)
			),

			array(
				'setup' => array(
					'errno' => 999,
					'error' => 'Foobar',
				),
				'test'  => array(
					'url'     => 'http://www.example.com/donwload.dat',
					'from'    => 0,
					'to'      => 0,
					'retSize' => 0,
					'false'   => true,
					'message' => '403 Forbidden'
				)
			),
		);
	}

	public static function getTestImportFromUrl()
	{
		return array(
			array(
				'setup'  => array(
					'httpstatus' => 200,
				),
				'params' => array(
					'url'    => 'http://www.example.com/donwload.dat',
					'length' => 1048576,
				),
				'test'   => array(
					'retSize' => 1048576,
					'loop'    => false,
					'expect'  => array(
						'status'    => true,
						'error'     => '',
						'frag'      => -1, // Done file
						'totalSize' => 1048576,
						'doneSize'  => 1048576,
						'percent'   => 100
					),
					'message' => 'Download a simple 1M file'
				)
			),

			array(
				'setup'  => array(
					'httpstatus' => 200,
				),
				'params' => array(
					'url'    => 'http://www.example.com/donwload.dat',
					'length' => 1048576,
				),
				'test'   => array(
					'retSize' => 1048576,
					'loop'    => false,
					'localfile' => null,
					'expect'  => array(
						'status'    => true,
						'error'     => '',
						'frag'      => -1, // Done file
						'totalSize' => 1048576,
						'doneSize'  => 1048576,
						'percent'   => 100
					),
					'message' => 'Download a simple 1M file without specifying a local file'
				)
			),

			array(
				'setup'  => array(
					'returnSize'	=> 5242880,
					'reportedSize'	=> 5242880,
					'httpstatus' => 200,
				),
				'params' => array(
					'url'    => 'http://www.example.com/donwload.dat',
					'length' => 1048576,
				),
				'test'   => array(
					'retSize' => 5242880,
					'loop'    => true,
					'expect'  => array(
						'status'    => true,
						'error'     => '',
						'frag'      => -1, // Done file
						'totalSize' => 5242880,
						'doneSize'  => 5242880,
						'percent'   => 100
					),
					'message' => 'Fully download a staggered 5M file'
				)
			),

			array(
				'setup'  => array(
					'returnSize'	=> 5242880,
					'reportedSize'	=> 5242880,
					'httpstatus' => 200,
				),
				'params' => array(
					'url'    => 'http://www.example.com/donwload.dat',
					'length' => 1048576,
				),
				'test'   => array(
					'retSize' => 1048576,
					'loop'    => false,
					'expect'  => array(
						'status'    => true,
						'error'     => '',
						'frag'      => 1,
						'totalSize' => 5242880,
						'doneSize'  => 1048576,
						'percent'   => 20
					),
					'message' => 'Download the first 1M chunk of a staggered 5M file'
				)
			),


			array(
				'setup'  => array(
					'returnSize'	=> 5242880,
					'reportedSize'	=> -1,
					'httpstatus' => 200,
				),
				'params' => array(
					'url'    => 'http://www.example.com/donwload.dat',
					'length' => 1048576,
				),
				'test'   => array(
					'retSize' => 1048576,
					'loop'    => false,
					'expect'  => array(
						'status'    => true,
						'error'     => '',
						'frag'      => 1,
						'totalSize' => 0,
						'doneSize'  => 1048576,
						'percent'   => 0
					),
					'message' => 'Download the first 1M chunk of a staggered 5M file which does not return its size'
				)
			),

			array(
				'setup'  => array(
					'returnSize'	=> 5242880,
					'reportedSize'	=> -1,
					'httpstatus' => 200,
				),
				'params' => array(
					'url'    => 'http://www.example.com/donwload.dat',
					'length' => 1048576,
				),
				'test'   => array(
					'retSize' => 5242880,
					'loop'    => true,
					'expect'  => array(
						'status'    => true,
						'error'     => '',
						'frag'      => -1, // Done file
						'totalSize' => 5242880,
						'doneSize'  => 5242880,
						'percent'   => 100
					),
					'message' => 'Fully download a staggered 5M file which doesn\'t return its size'
				)
			),

			array(
				'setup'  => array(
					'httpstatus' => 200,
				),
				'params' => array(
					'url'    => 'http://www.example.com/donwload.dat',
					'length' => 1048576,
				),
				'test'   => array(
					'retSize' => 1048576,
					'loop'    => false,
					'localfile' => '/foo/bar/baz.dat',
					'expect'  => array(
						'status'    => false,
						'error'     => 'AWF_DOWNLOAD_ERR_LIB_COULDNOTWRITELOCALFILE',
					),
					'message' => 'Unwritable local file leads to error'
				)
			),

			array(
				'setup' => array(),
				'params' => array(
					'url'    => 'http://www.example.com/IDoNotExist.dat',
					'length' => 1048576,
				),
				'test'  => array(
					'retSize' => 0,
					'loop'    => false,
					'expect'  => array(
						'status'    => false,
						'error'     => 'AWF_DOWNLOAD_ERR_LIB_HTTPERROR',
					),
					'message' => 'HTTP 404 results in error'
				)
			),

			array(
				'setup' => array(
					'httpstatus' => 403,
				),
				'params' => array(
					'url'    => 'http://www.example.com/IDoNotExist.dat',
					'length' => 1048576,
				),
				'test'  => array(
					'retSize' => 0,
					'loop'    => false,
					'expect'  => array(
						'status'    => false,
						'error'     => 'AWF_DOWNLOAD_ERR_LIB_HTTPERROR',
					),
					'message' => 'HTTP 403 results in error'
				)
			),

			array(
				'setup' => array(
					'errno' => 999,
					'error' => 'Foobar',
				),
				'params' => array(
					'url'    => 'http://www.example.com/IDoNotExist.dat',
					'length' => 1048576,
				),
				'test'  => array(
					'retSize' => 0,
					'loop'    => false,
					'expect'  => array(
						'status'    => false,
						'error'     => 'AWF_DOWNLOAD_ERR_LIB_CURL_ERROR',
					),
					'message' => 'cURL error results in error returned'
				)
			),
		);
	}
}
