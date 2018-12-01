<?php
/**
 * @package		awf
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Utils;

use Awf\Session\Exception;
use Awf\Utils\Path;
use Awf\Tests\Stubs\Fakeapp\Container;
use Awf\Tests\Stubs\Fakeapp\Application;

/**
 * Class PathTest
 *
 * @package Awf\Tests\Utils
 *
 * @coversDefaultClass Awf\Utils\Path
 */
class PathTest extends \PHPUnit_Framework_TestCase
{
	/** @var Container A container suitable for unit testing */
	protected $container = null;

	public function __construct($name = null, array $data = array(), $dataName = '')
	{
		parent::__construct($name, $data, $dataName); // TODO: Change the autogenerated stub

		// Convince the autoloader about our default app and its container
		$this->container = new Container();
		\Awf\Application\Application::getInstance('Fakeapp', $this->container);
	}


	/**
	 * @dataProvider getTestClean
	 *
	 * @covers Awf\Utils\Path::clean
	 */
	public function testClean($input, $ds, $expected)
	{
		$this->assertEquals(
			$expected,
			Path::clean($input, $ds)
		);
	}

	public function getTestClean()
	{
		$path = $this->container->filesystemBase;

		return array(
			// Input Path, Directory Separator, Expected Output
			'Nothing to do.' => array('/var/www/foo/bar/baz', '/', '/var/www/foo/bar/baz'),
			'One backslash.' => array('/var/www/foo\\bar/baz', '/', '/var/www/foo/bar/baz'),
			'Two and one backslashes.' => array('/var/www\\\\foo\\bar/baz', '/', '/var/www/foo/bar/baz'),
			'Mixed backslashes and double forward slashes.' => array('/var\\/www//foo\\bar/baz', '/', '/var/www/foo/bar/baz'),
			'UNC path.' => array('\\\\www\\docroot', '\\', '\\\\www\\docroot'),
			'UNC path with forward slash.' => array('\\\\www/docroot', '\\', '\\\\www\\docroot'),
			'UNC path with UNIX directory separator.' => array('\\\\www/docroot', '/', '/www/docroot'),
			'Default path from null' => array(null, DIRECTORY_SEPARATOR, $path),
			'Default path from empty string' => array('', DIRECTORY_SEPARATOR, $path),
			'Default path from spaces' => array('        ', DIRECTORY_SEPARATOR, $path)
		);
	}

	/**
	 * @dataProvider getTestCheck
	 *
	 * @covers Awf\Utils\Path::check
	 */
	public function testCheck($path, $expected)
	{
		if ($expected === false)
		{
			$this->setExpectedException('\\Exception');
		}

		$result = Path::check($path);

		if ($expected !== false)
		{
			$this->assertEquals($expected, $result);
		}
	}

	public function getTestCheck()
	{
		$path = $this->container->filesystemBase;
		$cleanPath = Path::clean($path);

		return array(
			// Input Path, Result (false for exception)
			array($path . '/../Fakeapp', false), // double dot not permitted
			array('../../../../../../../../../etc/passwd', false), // double dot not permitted (nasty!)
			array('/etc/passwd', false), // snooping
			array($path . '/foo', $cleanPath . '/foo'), // clean path
			array($path . '//////foo', $cleanPath . '/foo'), // dirty path
		);
	}

	/**
	 * @dataProvider getTestFind
	 *
	 * @covers Awf\Utils\Path::find
	 */
	public function testFind($file, $path, $expected)
	{
		$result = Path::find($path, $file);
		$this->assertEquals($expected, $result);
	}

	public function getTestFind()
	{
		$path = $this->container->filesystemBase;
		$cleanPath = Path::clean($path);

		return array(
			// file, paths, expected
			array('kot.txt', array($path . '/path_find/bar', $path . '/path_find/foo'), $cleanPath . '/path_find/bar/kot.txt'),
			array('kot.txt', array($path . '/path_find/bar'), $cleanPath . '/path_find/bar/kot.txt'),
			array('kot.txt', $path . '/path_find/bar', $cleanPath . '/path_find/bar/kot.txt'),
			array('foo.txt', $path . '/path_find/bar', false),
			array('foo.txt', $path . '/../Fakeapp/path_find/bar', false),
			array('foo.txt', '../../whatever', false),
			array('foo.txt', '/etc', false),
			array('kot.txt', $path . '//////path_find/bar', $cleanPath . '/path_find/bar/kot.txt'),
		);
	}
}
