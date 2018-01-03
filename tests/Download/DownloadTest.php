<?php
/**
 * @package     awf
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license     GNU GPL version 2 or later
 */

namespace Awf\Tests\Download\Adapter;

use Awf\Download\Download;
use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\Download\FakeCurl;
use Awf\Tests\Helpers\ReflectionHelper;

require_once __DIR__ . '/../Helpers/Download/FakeCurlImporter.php';
require_once __DIR__ . '/DownloadDataprovider.php';

/**
 * @covers  Awf\Download\Download::<protected>
 * @covers  Awf\Download\Download::<private>
 */
class DownloadTest extends AwfTestCase
{
	public static function setUpBeforeClass()
	{
		global $awfTest_FakeCurl_Active;
        $awfTest_FakeCurl_Active = true;

		parent::setUpBeforeClass();
	}

	public static function tearDownAfterClass()
	{
		global $awfTest_FakeCurl_Active;
        $awfTest_FakeCurl_Active = false;

		parent::tearDownAfterClass();
	}

	/**
	 * @covers Awf\Download\Download::__construct
	 * @covers Awf\Download\Download::getFiles
	 * @covers Awf\Download\Download::scanDirectory
	 */
	public function testConstructor()
	{
		$download = new Download(static::$container);

		$this->assertInstanceOf('\\Awf\\Download\\Download', $download, 'Download object must be an instance of Awf\\Download\\Download');
	}

	/**
	 * @covers          Awf\Download\Download::setAdapter
	 * @dataProvider    DownloadDataprovider::getTestSetAdapter
	 */
	public function testSetAdapter($className, $shouldChange = true)
	{
		$download = new Download(static::$container);
		$download->setAdapter('curl');
		$this->assertInstanceOf('\\Awf\\Download\\Adapter\\Curl', ReflectionHelper::getValue($download, 'adapter'), 'Initially forced adapter should be cURL');
		$download->setAdapter($className);

		if ($shouldChange)
		{
			$this->assertNotInstanceOf('\\Awf\\Download\\Adapter\\Curl', ReflectionHelper::getValue($download, 'adapter'), 'Forced adapter should be NOT still be cURL');
		}
		else
		{
			$this->assertInstanceOf('\\Awf\\Download\\Adapter\\Curl', ReflectionHelper::getValue($download, 'adapter'), 'Forced adapter should still be cURL');
		}
	}

	/**
	 * @covers          Awf\Download\Download::getAdapterName
	 * @dataProvider    DownloadDataprovider::getTestGetAdapterName
	 */
	public function testGetAdapterName($className = null, $expected = null)
	{
		$download = new Download(static::$container);
		$download->setAdapter($className);

		$actual = $download->getAdapterName();

		$this->assertEquals($expected, $actual, "Download adapter $actual does not match $expected");
	}

	/**
	 * @covers          Awf\Download\Download::getFromUrl
	 * @dataProvider    DownloadDataprovider::getTestGetFromUrl
	 */
	public function testGetFromUrl(array $config, array $test)
	{
		FakeCurl::setUp($config);

		$download = new Download(static::$container);
		$download->setAdapter('curl');

		$ret = $download->getFromURL($test['url']);

		if ($test['false'])
		{
			$this->assertFalse($ret);
		}
		else
		{
			$retSize = 0;

			if (is_string($ret))
			{
				$retSize = strlen($ret);
			}

			$this->assertEquals($test['retSize'], $retSize, $test['message']);
		}
	}


	/**
	 * @covers          Awf\Download\Download::importFromUrl
	 * @dataProvider    DownloadDataprovider::getTestImportFromUrl
	 */
	public function testImportFromUrl(array $config, array $params, array $test)
	{
		// Set up the FakeCurl simulator
		FakeCurl::setUp($config);

		// Get the download class
		$download = new Download(static::$container);
		$download->setAdapter('curl');

		// Initialise
		$loopAllowed = $test['loop'];

		// Get the output file name
        $tmpDir = static::$container['temporaryPath'];
		$localFilename = $tmpDir . '/test.dat';

		// Set up the download parameters
		$params['localFilename'] = $localFilename;
		#$params['maxExecTime'] = $test['loop'] ? 10000 : 0;
		$params['maxExecTime'] = 0;

		if (isset($test['localfile']))
		{
			if (empty($test['localfile']))
			{
				unset($params['localFilename']);
			}
			else
			{
				$params['localFilename'] = $test['localfile'];
			}
		}

		// Remove the local filename if it's still there
		@unlink($localFilename);

		do
		{
			$ret = $download->importFromURL($params);

			if ($loopAllowed)
			{
				$loopAllowed = !(($ret['frag'] == -1) || ($ret['error']));
			}

			$params = array_merge($params, $ret);

			if (isset($params['localFilename']) && !empty($params['localFilename']))
			{
				$localFilename = $params['localFilename'];
			}
		}
		while ($loopAllowed);

		foreach ($test['expect'] as $k => $v)
		{
			// Validate expected parameters
			$this->assertEquals($v, $ret[$k], $test['message'] . " (returned $k does not match)");
		}

		// Check the return size
		if (!$test['expect']['error'])
		{
			$fileSize = @filesize($localFilename);
			$this->assertEquals($test['retSize'], $fileSize, $test['message'] . " (size doesn't match {$test['retSize']})");
		}

		// Remove the local filename if it's still there
		@unlink($localFilename);
	}
}
