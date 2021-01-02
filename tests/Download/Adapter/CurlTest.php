<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Download\Adapter;


use Awf\Download\Adapter\Curl;
use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\Download\FakeCurl;
use Awf\Tests\Helpers\ReflectionHelper;

require_once __DIR__ . '/../../Helpers/Download/FakeCurlImporter.php';
require_once __DIR__ . '/CurlDataprovider.php';

/**
 * @covers  Awf\Download\Adapter\Curl::<protected>
 * @covers  Awf\Download\Adapter\Curl::<private>
 */
class CurlTest extends AwfTestCase
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
	 * @covers  Awf\Download\Adapter\Curl::__construct
	 */
	public function testConstructor()
	{
		$adapter = new Curl();

		$this->assertInstanceOf('Awf\\Download\\Adapter\\Curl', $adapter, 'Adapter must match correct object type');
		$this->assertEquals(110, ReflectionHelper::getValue($adapter, 'priority'), 'Adapter priority must match');
		$this->assertEquals(true, ReflectionHelper::getValue($adapter, 'supportsFileSize'), 'Adapter must support file size');
		$this->assertEquals(true, ReflectionHelper::getValue($adapter, 'supportsChunkDownload'), 'Adapter must support chunked download');
		$this->assertEquals('curl', ReflectionHelper::getValue($adapter, 'name'), 'Adapter must have the correct name');
		$this->assertEquals(true, ReflectionHelper::getValue($adapter, 'isSupported'), 'Adapter must be supported');
	}

	/**
	 * @covers          Awf\Download\Adapter\Curl::downloadAndReturn
	 * @dataProvider    Awf\Tests\Download\Adapter\CurlDataprovider::getTestDownloadAndReturn
	 */
	public function testDownloadAndReturn(array $config, array $test)
	{
		FakeCurl::setUp($config);
		$adapter = new Curl();

		if ($test['exception'] !== false)
		{
			$this->setExpectedException($test['exception']['name'], $test['exception']['message'], $test['exception']['code']);
		}

		$ret = $adapter->downloadAndReturn($test['url'], $test['from'], $test['to']);
		$retSize = 0;

		if (is_string($ret))
		{
			$retSize = strlen($ret);
		}

		$this->assertEquals($test['retSize'], $retSize, $test['message']);
	}

	/**
	 * @covers          Awf\Download\Adapter\Curl::getFileSize
	 * @dataProvider    Awf\Tests\Download\Adapter\CurlDataprovider::getTestGetFileSize
	 */
	public function testGetFileSize(array $config, array $test)
	{
		FakeCurl::setUp($config);
		$adapter = new Curl();

		$ret = $adapter->getFileSize($test['url']);

		$this->assertEquals($test['retSize'], $ret, $test['message']);
	}
}
