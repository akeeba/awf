<?php
/**
 * @package		awf
 * @copyright	2014-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Application;

use Awf\Application\Configuration;
use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Application\MockFilesystem;
use Awf\Tests\Stubs\Application\MockPhpfuncConfig;

/**
 * Class ConfigurationTest
 *
 * @package Awf\Tests\Application
 *
 * @codeCoverageDefaultClass \Awf\Application\Configuration
 */
class ConfigurationTest extends AwfTestCase
{
	/** @var Configuration */
	protected $config;

	public function testConstructWithoutData()
	{
		$conf = new Configuration(static::$container);

		$this->assertEquals(
			static::$container,
			ReflectionHelper::getValue($conf, 'container')
		);
	}

	/**
	 * @param $data
	 *
	 * @dataProvider getTestConstructWithData
	 */
	public function testConstructWithData($data)
	{
		$conf = new Configuration(static::$container, $data);

		$this->assertEquals(
			'bar',
			$conf->get('foo')
		);
	}

	public function getTestConstructWithData()
	{
		return array(
			array(array('foo' => 'bar')),
			array((object)array('foo' => 'bar')),
			array('{"foo": "bar"}')
		);
	}

	public function testGetDefaultPath()
	{
		$this->assertEmpty(
			ReflectionHelper::getValue($this->config, 'defaultPath')
		);

		$path = $this->config->getDefaultPath();
		$defaultPath = static::$container->basePath . '/assets/private/config.php';

		$this->assertEquals(
			$defaultPath,
			$path
		);

		$this->assertEquals(
			$defaultPath,
			ReflectionHelper::getValue($this->config, 'defaultPath')
		);
	}

	public function testSetDefaultPath()
	{
		$this->assertEmpty(
			ReflectionHelper::getValue($this->config, 'defaultPath')
		);

		$this->config->setDefaultPath('/foo/bar');

		$this->assertEquals(
			'/foo/bar',
			ReflectionHelper::getValue($this->config, 'defaultPath')
		);

		$path = $this->config->getDefaultPath();

		$this->assertEquals(
			'/foo/bar',
			$path
		);
	}

	public function testLoadConfiguration()
	{
		$phpfunc = new MockPhpfuncConfig();

		$this->config->set('no', 'I said no');
		$this->config->loadConfiguration('/dev/false', $phpfunc);
		$this->assertEquals(new \stdClass(), ReflectionHelper::getValue($this->config, 'data'));

		$this->config->set('no', 'I said no');
		$this->config->loadConfiguration('/dev/trash', $phpfunc);
		$this->assertEquals(new \stdClass(), ReflectionHelper::getValue($this->config, 'data'));

		$this->config->set('no', 'I said no');
		$this->config->loadConfiguration('/dev/invalid', $phpfunc);
		$this->assertEquals(new \stdClass(), ReflectionHelper::getValue($this->config, 'data'));

		$this->config->set('no', 'I said no');
		$this->config->loadConfiguration('/dev/fake', $phpfunc);
		$this->assertEquals('bar', $this->config->get('foo'));
	}

	public function testSaveConfiguration()
	{
		static::$container->extend('fileSystem', function($fs, $c){
			return new MockFilesystem(array('ignore' => 'me'));
		});
		$this->config->set('foo', 'bar');
		$this->config->setDefaultPath('/dev/foobar');
		$this->config->saveConfiguration();

		$this->assertEquals('/dev/foobar', MockFilesystem::$outFilename);
		$data = str_replace(array("\n", "\t", ': ', '    '), array('', '', ':', ''), MockFilesystem::$writtenData);
		$this->assertEquals("<?php die; ?>{\"foo\":\"bar\"}", $data);
	}

	protected function setUp($resetContainer = true)
	{
		parent::setUp($resetContainer);

		$this->config = new Configuration(static::$container);
	}
}