<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Uri;

use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Uri\Uri;

/**
 * Class UriTest
 *
 * @package Awf\Tests\Uri
 *
 * @coversDefaultClass \Awf\Uri\Uri
 */
class UriTest extends AwfTestCase
{
	/**
	 * @var    Uri
	 */
	protected $object;

	protected function setUp()
	{
        parent::setUp();

		$this->object = new Uri('http://someuser:somepass@www.example.com:80/path/file.html?var=value#fragment');
	}

	/**
	 * Test the __toString method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::__toString
	 */
	public function test__toString()
	{
		$this->assertThat(
			$this->object->__toString(),
			$this->equalTo('http://someuser:somepass@www.example.com:80/path/file.html?var=value#fragment')
		);
	}

	/**
	 * @covers  Awf\Uri\Uri::getInstance
	 */
	public function testGetInstance()
	{
        // TODO Rewrite this test to use a dataProvider
		ReflectionHelper::setValue($this->object, 'instances', array());
		$_SERVER['HTTPS'] = 'off';
		$_SERVER['HTTP_HOST'] = 'www.example.com';
		$_SERVER['REQUEST_URI'] = '/foo/bar/baz.html?q=1';
		$uri = Uri::getInstance();

		$this->assertEquals('http://www.example.com/foo/bar/baz.html?q=1', $uri->toString());

		ReflectionHelper::setValue($this->object, 'instances', array());
		$_SERVER['HTTPS'] = 'on';
		$_SERVER['HTTP_HOST'] = 'www.example.com';
		$_SERVER['REQUEST_URI'] = '/foo/bar/baz.html?q=1';
		$uri = Uri::getInstance();

		$this->assertEquals('https://www.example.com/foo/bar/baz.html?q=1', $uri->toString());

		ReflectionHelper::setValue($this->object, 'instances', array());
		$_SERVER['HTTPS'] = 'off';
		$_SERVER['HTTP_HOST'] = 'www.example.com';
		$_SERVER['REQUEST_URI'] = '';
		$_SERVER['SCRIPT_NAME'] = '/foo/bar/baz.html';
		$_SERVER['QUERY_STRING'] = 'q=1';
		$uri = Uri::getInstance();

		$this->assertEquals('http://www.example.com/foo/bar/baz.html?q=1', $uri->toString());

		$uri = Uri::getInstance('http://www.google.com');

		$this->assertEquals('http://www.google.com', $uri->toString());
	}

	/**
	 * @covers  Awf\Uri\Uri::isInternal
	 */
	public function testIsInternal()
	{
		$_SERVER['HTTPS'] = 'off';
		$_SERVER['HTTP_HOST'] = 'www.example.com';
		$_SERVER['REQUEST_URI'] = '/foo/bar/baz.html?q=1';

		$actual = Uri::isInternal('http://www.example.com/something/something/test.html');
		$this->assertTrue($actual);

		$actual = Uri::isInternal('http://www.akeebabackup.com/something/something/test.html');
		$this->assertFalse($actual);
	}

	/**
	 * @covers  Awf\Uri\Uri::root
	 */
	public function testRoot()
	{
		$_SERVER['HTTPS'] = 'off';
		$_SERVER['HTTP_HOST'] = 'www.example.com';
		$_SERVER['REQUEST_URI'] = '/foo/bar/baz.html?q=1';

		$actual = Uri::root(false);
		$this->assertEquals('http://www.example.com/', $actual);

		$actual = Uri::root(true);
		$this->assertEquals('', $actual);

		$actual = Uri::root(false, '/foo');
		$this->assertEquals('http://www.example.com/foo/', $actual);

		$actual = Uri::root(true, '/foo');
		$this->assertEquals('/foo', $actual);
	}

	/**
	 * @covers  Awf\Uri\Uri::current
	 * @covers  Awf\Uri\Uri::reset
	 */
	public function testCurrent()
	{
		Uri::reset();
		$_SERVER['HTTPS'] = 'off';
		$_SERVER['HTTP_HOST'] = 'www.example.com';
		$_SERVER['REQUEST_URI'] = '/foo/bar/baz.html?q=1';

		$actual = Uri::current();
		$this->assertEquals('http://www.example.com/foo/bar/baz.html', $actual);
	}

	/**
	 * @covers  Awf\Uri\Uri::rebase
	 */
	public function testRebase()
	{
		$actual = Uri::rebase('?foo=bar', static::$container);
		$this->assertEquals('http://www.example.com/index.php?foo=bar', $actual);

		$actual = Uri::rebase('bummer.php?foo=bar', static::$container);
		$this->assertEquals('http://www.example.com/index.php?foo=bar', $actual);

		$actual = Uri::rebase('?view=something#foobar', static::$container);
		$this->assertEquals('http://www.example.com/index.php?view=something#foobar', $actual);

		$actual = Uri::rebase('?view=something#foobar', static::$container, '/foobar/index.php?view=other&baz=1');
		$this->assertEquals('http://www.example.com/foobar/index.php?view=something&baz=1#foobar', $actual);

	}

	/**
	 * Test the parse method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::parse
	 * @covers  Awf\Uri\Uri::__construct
	 */
	public function testConstruct()
	{
		$object = new Uri('http://someuser:somepass@www.example.com:80/path/file.html?var=value&amp;test=true#fragment');

		$this->assertThat(
			$object->getHost(),
			$this->equalTo('www.example.com')
		);

		$this->assertThat(
			$object->getPath(),
			$this->equalTo('/path/file.html')
		);

		$this->assertThat(
			$object->getScheme(),
			$this->equalTo('http')
		);
	}

	/**
	 * Test the toString method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::toString
	 */
	public function testToString()
	{
		$this->assertThat(
			$this->object->toString(),
			$this->equalTo('http://someuser:somepass@www.example.com:80/path/file.html?var=value#fragment')
		);

		$this->object->setQuery('somevar=somevalue');
		$this->object->setVar('somevar2', 'somevalue2');
		$this->object->setScheme('ftp');
		$this->object->setUser('root');
		$this->object->setPass('secret');
		$this->object->setHost('www.example.org');
		$this->object->setPort('8888');
		$this->object->setFragment('someFragment');
		$this->object->setPath('/this/is/a/path/to/a/file');

		$this->assertThat(
			$this->object->toString(),
			$this->equalTo('ftp://root:secret@www.example.org:8888/this/is/a/path/to/a/file?somevar=somevalue&somevar2=somevalue2#someFragment')
		);
	}

	/**
	 * Test the setVar method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::setVar
	 */
	public function testSetVar()
	{
		$this->object->setVar('somevariable', 'somevalue');

		$this->assertThat(
			$this->object->getVar('somevariable'),
			$this->equalTo('somevalue')
		);

		$this->object->setVar('somevariable', null);

		$this->assertThat(
			$this->object->getVar('somevariable'),
			$this->isNull()
		);

	}

	/**
	 * Test the hasVar method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::hasVar
	 */
	public function testHasVar()
	{
		$this->assertThat(
			$this->object->hasVar('somevariable'),
			$this->equalTo(false)
		);

		$this->assertThat(
			$this->object->hasVar('var'),
			$this->equalTo(true)
		);
	}

	/**
	 * Test the getVar method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::getVar
	 */
	public function testGetVar()
	{
		$this->assertThat(
			$this->object->getVar('var'),
			$this->equalTo('value')
		);

		$this->assertThat(
			$this->object->getVar('var2'),
			$this->equalTo('')
		);

		$this->assertThat(
			$this->object->getVar('var2', 'default'),
			$this->equalTo('default')
		);
	}

	/**
	 * Test the delVar method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::delVar
	 */
	public function testDelVar()
	{
		$this->assertThat(
			$this->object->getVar('var'),
			$this->equalTo('value')
		);

		$this->object->delVar('var');

		$this->assertThat(
			$this->object->getVar('var'),
			$this->equalTo('')
		);
	}

	/**
	 * Test the setQuery method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::setQuery
	 */
	public function testSetQuery()
	{
		$this->object->setQuery('somevar=somevalue');

		$this->assertThat(
			$this->object->getQuery(),
			$this->equalTo('somevar=somevalue')
		);

		$this->object->setQuery('somevar=somevalue&amp;test=true');

		$this->assertThat(
			$this->object->getQuery(),
			$this->equalTo('somevar=somevalue&test=true')
		);

		$this->object->setQuery(array('somevar' => 'somevalue', 'test' => 'true'));

		$this->assertThat(
			$this->object->getQuery(),
			$this->equalTo('somevar=somevalue&test=true')
		);
	}

	/**
	 * Test the getQuery method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::getQuery
	 * @covers  Awf\Uri\Uri::buildQuery
	 */
	public function testGetQuery()
	{
		$this->assertThat(
			$this->object->getQuery(),
			$this->equalTo('var=value')
		);

		$this->assertThat(
			$this->object->getQuery(true),
			$this->equalTo(array('var' => 'value'))
		);

		ReflectionHelper::setValue($this->object, 'query', null);

		$this->assertThat(
			$this->object->getQuery(),
			$this->equalTo('var=value')
		);

		ReflectionHelper::setValue($this->object, 'query', null);

		$this->assertThat(
			$this->object->getQuery(true),
			$this->equalTo(array('var' => 'value'))
		);
	}

	/**
	 * Test the getScheme method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::getScheme
	 */
	public function testGetScheme()
	{
		$this->assertThat(
			$this->object->getScheme(),
			$this->equalTo('http')
		);
	}

	/**
	 * Test the setScheme method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::setScheme
	 */
	public function testSetScheme()
	{
		$this->object->setScheme('ftp');

		$this->assertThat(
			$this->object->getScheme(),
			$this->equalTo('ftp')
		);
	}

	/**
	 * Test the getUser method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::getUser
	 */
	public function testGetUser()
	{
		$this->assertThat(
			$this->object->getUser(),
			$this->equalTo('someuser')
		);
	}

	/**
	 * Test the setUser method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::setUser
	 */
	public function testSetUser()
	{
		$this->object->setUser('root');

		$this->assertThat(
			$this->object->getUser(),
			$this->equalTo('root')
		);
	}

	/**
	 * Test the getPass method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::getPass
	 */
	public function testGetPass()
	{
		$this->assertThat(
			$this->object->getPass(),
			$this->equalTo('somepass')
		);
	}

	/**
	 * Test the setPass method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::setPass
	 */
	public function testSetPass()
	{
		$this->object->setPass('secret');

		$this->assertThat(
			$this->object->getPass(),
			$this->equalTo('secret')
		);
	}

	/**
	 * Test the getHost method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::getHost
	 */
	public function testGetHost()
	{
		$this->assertThat(
			$this->object->getHost(),
			$this->equalTo('www.example.com')
		);
	}

	/**
	 * Test the setHost method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::setHost
	 */
	public function testSetHost()
	{
		$this->object->setHost('www.example.org');

		$this->assertThat(
			$this->object->getHost(),
			$this->equalTo('www.example.org')
		);
	}

	/**
	 * Test the getPort method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::getPort
	 */
	public function testGetPort()
	{
		$this->assertThat(
			$this->object->getPort(),
			$this->equalTo('80')
		);
	}

	/**
	 * Test the setPort method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::setPort
	 */
	public function testSetPort()
	{
		$this->object->setPort('8888');

		$this->assertThat(
			$this->object->getPort(),
			$this->equalTo('8888')
		);
	}

	/**
	 * Test the getPath method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::getPath
	 */
	public function testGetPath()
	{
		$this->assertThat(
			$this->object->getPath(),
			$this->equalTo('/path/file.html')
		);
	}

	/**
	 * Test the setPath method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::setPath
	 * @covers  Awf\Uri\Uri::_cleanPath
	 */
	public function testSetPath()
	{
		$this->object->setPath('/this/is/a/path/to/a/file.htm');

		$this->assertThat(
			$this->object->getPath(),
			$this->equalTo('/this/is/a/path/to/a/file.htm')
		);
	}

	/**
	 * Test the getFragment method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::getFragment
	 */
	public function testGetFragment()
	{
		$this->assertThat(
			$this->object->getFragment(),
			$this->equalTo('fragment')
		);
	}

	/**
	 * Test the setFragment method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::setFragment
	 */
	public function testSetFragment()
	{
		$this->object->setFragment('someFragment');

		$this->assertThat(
			$this->object->getFragment(),
			$this->equalTo('someFragment')
		);
	}

	/**
	 * Test the isSSL method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Uri\Uri::isSSL
	 */
	public function testIsSSL()
	{
		$object = new Uri('https://someuser:somepass@www.example.com:80/path/file.html?var=value#fragment');

		$this->assertThat(
			$object->isSSL(),
			$this->equalTo(true)
		);

		$object = new Uri('http://someuser:somepass@www.example.com:80/path/file.html?var=value#fragment');

		$this->assertThat(
			$object->isSSL(),
			$this->equalTo(false)
		);
	}
}
