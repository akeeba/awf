<?php
/**
 * @package    awf
 * @copyright  Copyright (c)2014-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU GPL version 3 or later
 */

namespace Awf\Tests\Utils;

use Awf\Utils\Template;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container as FakeContainer;

class TemplateTest extends \Awf\Tests\Helpers\ApplicationTestCase
{
	public $mockDocument = null;

	public $mockApp = null;

	protected function setUp()
	{
		// Create a mock document object, mocking addStyleSheet and addScript
		$this->mockDocument = new \Awf\Tests\Stubs\Utils\MockDocument();

		// Create a mock application, mocking getContainer, getDocument, getTemplate
		$this->mockApp = $this->getMockBuilder('\\Awf\\Application\\Application')
			->setConstructorArgs(array(static::$container))
			->setMethods(array('getContainer', 'getDocument', 'getTemplate', 'initialise'))
			->getMock();
		$this->mockApp
			->expects($this->any())
			->method('getContainer')
			->willReturn(static::$container);
		$this->mockApp
			->expects($this->any())
			->method('getDocument')
			->willReturn($this->mockDocument);
		$this->mockApp
			->expects($this->any())
			->method('getTemplate')
			->willReturn('foobar');
	}

	/**
	 * @dataProvider getTestGetAltPaths
	 *
	 * @covers Awf\Utils\Template::getAltPaths
	 *
	 * @param $path
	 * @param $expected
	 */
	public function testGetAltPaths($path, array $expected)
	{
		$result = Template::getAltPaths($path, $this->mockApp);

		// Make sure we got an array
		$this->assertInternalType('array', $result);

		// Check each expected key
		foreach ($expected as $key => $expectedValue)
		{
			$this->assertArrayHasKey($key, $result, "Key $key does not exist in result");

			$this->assertEquals($expectedValue, $result[$key], "Path for $key does not match");
		}

		// Fail on unexpected key
		foreach ($result as $k => $v)
		{
			$this->assertTrue(array_key_exists($k, $expected));
		}
	}

	public function getTestGetAltPaths()
	{
		return array(
			array('media://css/foo.min.css', array(
				'normal' => 'media/css/foo.min.css',
				'debug' => 'media/css/foo.css',
				'alternate' => 'template/foobar/media/css/foo.min.css',
			)),
			array('media://css/foo.css', array(
				'normal' => 'media/css/foo.css',
				'debug' => 'media/css/foo-uncompressed.css',
				'alternate' => 'template/foobar/media/css/foo.css',
			)),
			array('media://images/foo.jpg', array(
				'normal' => 'media/images/foo.jpg',
				'alternate' => 'template/foobar/media/images/foo.jpg'
			)),
			array('site://assets/foo.jpg', array(
				'normal' => 'assets/foo.jpg',
			)),
		);
	}

	/**
	 * @dataProvider getTestParsePath
	 *
	 * @covers Awf\Utils\Template::parsePath
	 *
	 * @param $path
	 * @param $local
	 * @param $expected
	 */
	public function testParsePath($path, $local, $expected)
	{
		$result = Template::parsePath($path, $local, $this->mockApp);

		$this->assertEquals($expected, $result);
	}

	public function getTestParsePath()
	{
		$root = realpath(__DIR__ . '/../Stubs/Fakeapp');

		return array(
			array('media://css/foo.min.css', true, $root . '/media/css/foo.min.css'),
			array('media://css/regular.css', true, $root . '/media/css/regular.css'),
			array('media://css/minimised.css', true, $root . '/media/css/minimised.min.css'),
			array('media://css/overridden.css', true, $root . '/template/foobar/media/css/overridden.css'),
		);
	}

	/**
	 * @dataProvider getTestAddCss
	 *
	 * @covers Awf\Utils\Template::addCss
	 *
	 * @param $path
	 * @param $expected
	 */
	public function testAddCss($path, $expected)
	{
		Template::addCss($path, $this->mockApp);

		$mockDocument = $this->mockDocument;
		$this->assertArrayHasKey('addStyleSheet', $mockDocument->calls);
		$this->assertCount(1, $mockDocument->calls['addStyleSheet']);
		$this->assertEquals($expected, $mockDocument->calls['addStyleSheet'][0][0]);
	}

	public function getTestAddCss()
	{
		// Fancy path, URL
		return array(
			array('media://css/regular.css', 'http://www.example.com/media/css/regular.css'),
			array('media://css/minimised.css', 'http://www.example.com/media/css/minimised.min.css'),
			array('media://css/overridden.css', 'http://www.example.com/template/foobar/media/css/overridden.css'),
			array('media://css/overriddenminimised.css', 'http://www.example.com/template/foobar/media/css/overriddenminimised.min.css'),
		);
	}

	/**
	 * @dataProvider getTestAddJs
	 *
	 * @covers Awf\Utils\Template::addJs
	 *
	 * @param $path
	 * @param $expected
	 */
	public function testAddJs($path, $expected)
	{
		Template::addJs($path, $this->mockApp);

		$mockDocument = $this->mockDocument;
		$this->assertArrayHasKey('addScript', $mockDocument->calls);
		$this->assertCount(1, $mockDocument->calls['addScript']);
		$this->assertEquals($expected, $mockDocument->calls['addScript'][0][0]);
	}

	public function getTestAddJs()
	{
		// Fancy path, URL
		return array(
			array('media://js/regular.js', 'http://www.example.com/media/js/regular.js'),
			array('media://js/minimised.js', 'http://www.example.com/media/js/minimised.min.js'),
			array('media://js/overridden.js', 'http://www.example.com/template/foobar/media/js/overridden.js'),
			array('media://js/overriddenminimised.js', 'http://www.example.com/template/foobar/media/js/overriddenminimised.min.js'),
		);
	}
}
