<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Document;

use Awf\Document\Document;
use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\ReflectionHelper;

/**
 * Class DocumentTest
 *
 * @package Awf\Tests\Document
 *
 * @coversDefaultClass \Awf\Document\Document
 */
class DocumentTest extends AwfTestCase
{
	protected function setUp($resetContainer = true)
	{
		parent::setUp();

		// Reset the instances
		ReflectionHelper::setValue('\Awf\Document\Document', 'instances', array());
	}

	/**
	 * @group   Document
	 * @covers  Awf\Document\Document::__construct
	 * @covers  Awf\Document\Document::getInstance
	 *
	 * @throws  \Awf\Exception\App
	 */
	public function testGetInstance()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$this->assertInstanceOf('\\Awf\\Tests\\Stubs\\Document\\Fake', $doc);
		$docApp = ReflectionHelper::getValue($doc, 'container');
		$this->assertEquals(static::$container, $docApp);
	}

	/**
	 * @group   Document
	 */
	public function testSetBuffer()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$myBuffer = 'The quick brown fox jumped over the lazy dog';
		$doc->setBuffer($myBuffer);
		$actual = ReflectionHelper::getValue($doc, 'buffer');

		$this->assertEquals($myBuffer, $actual);
	}

	/**
	 * @group   Document
	 */
	public function testGetBuffer()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$myBuffer = 'The quick brown fox jumped over the lazy dog';
		ReflectionHelper::setValue($doc, 'buffer', $myBuffer);
		$actual = $doc->getBuffer($myBuffer);

		$this->assertEquals($myBuffer, $actual);
	}

	/**
	 * @group   Document
	 */
	public function testAddScriptPlain()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$scripts = ReflectionHelper::getValue($doc, 'scripts');
		$this->assertInternalType('array', $scripts);

		$url = 'http://www.example.com/foo.js';

		ReflectionHelper::setValue($doc, 'scripts', array());
		$doc->addScript($url);
		$scripts = ReflectionHelper::getValue($doc, 'scripts');

		$this->assertInternalType('array', $scripts);
		$this->assertCount(1, $scripts);
		$this->assertEquals('text/javascript', $scripts[$url]['mime']);
		$this->assertFalse($scripts[$url]['before']);
	}

	/**
	 * @group   Document
	 */
	public function testAddScriptReplaceExisting()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$url = 'http://www.example.com/foo.js';

		$doc->addScript($url);
		$doc->addScript($url, true, 'text/ecmascript');
		$scripts = ReflectionHelper::getValue($doc, 'scripts');

		$this->assertInternalType('array', $scripts);
		$this->assertCount(1, $scripts);
		$this->assertEquals('text/ecmascript', $scripts[$url]['mime']);
		$this->assertTrue($scripts[$url]['before']);
	}

	/**
	 * @group   Document
	 */
	public function testAddScriptAppend()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$url = 'http://www.example.com/bar.js';

		$doc->addScript('http://www.example.com/foo.js');
		$doc->addScript($url);
		$scripts = ReflectionHelper::getValue($doc, 'scripts');

		$this->assertInternalType('array', $scripts);
		$this->assertCount(2, $scripts);
		$this->assertArrayHasKey($url, $scripts);
	}

	/**
	 * @group   Document
	 */
	public function testAddScriptDeclaration()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$line1 = 'foo';
		$line2 = 'bar';

		$scriptDeclarations = ReflectionHelper::getValue($doc, 'scriptDeclarations');
		$this->assertArrayNotHasKey('text/javascript', $scriptDeclarations);

		$doc->addScriptDeclaration($line1);
		$scriptDeclarations = ReflectionHelper::getValue($doc, 'scriptDeclarations');
		$this->assertArrayHasKey('text/javascript', $scriptDeclarations);
		$this->assertEquals($line1, $scriptDeclarations['text/javascript']);

		$doc->addScriptDeclaration($line2);
		$scriptDeclarations = ReflectionHelper::getValue($doc, 'scriptDeclarations');
		$this->assertEquals($line1 . chr(13) . $line2, $scriptDeclarations['text/javascript']);
	}

	/**
	 * @group   Document
	 */
	public function testAddStyleSheetPlain()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$styles = ReflectionHelper::getValue($doc, 'styles');
		$this->assertInternalType('array', $styles);

		$url = 'http://www.example.com/foo.css';

		ReflectionHelper::setValue($doc, 'styles', array());
		$doc->addStyleSheet($url);
		$styles = ReflectionHelper::getValue($doc, 'styles');

		$this->assertInternalType('array', $styles);
		$this->assertCount(1, $styles);
		$this->assertEquals('text/css', $styles[$url]['mime']);
		$this->assertNull($styles[$url]['media']);
		$this->assertFalse($styles[$url]['before']);
	}

	/**
	 * @group   Document
	 */
	public function testAddStyleSheetReplaceExisting()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');
		$url = 'http://www.example.com/foo.css';

		$doc->addStyleSheet('http://www.example.com/foo.css');
		$doc->addStyleSheet($url, true, 'css3', 'screen');
		$styles = ReflectionHelper::getValue($doc, 'styles');

		$this->assertInternalType('array', $styles);
		$this->assertCount(1, $styles);
		$this->assertEquals('css3', $styles[$url]['mime']);
		$this->assertEquals('screen', $styles[$url]['media']);
		$this->assertTrue($styles[$url]['before']);
	}

	/**
	 * @group   Document
	 */
	public function testAddStyleSheetAppend()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');
		$url = 'http://www.example.com/bar.css';

		$doc->addStyleSheet('http://www.example.com/foo.css');
		$doc->addStyleSheet($url);
		$styles = ReflectionHelper::getValue($doc, 'styles');

		$this->assertInternalType('array', $styles);
		$this->assertCount(2, $styles);
		$this->assertArrayHasKey($url, $styles);
	}

	/**
	 * @group   Document
	 */
	public function testAddStyleDeclaration()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$line1 = 'foo';
		$line2 = 'bar';

		$styleDeclarations = ReflectionHelper::getValue($doc, 'styleDeclarations');
		$this->assertArrayNotHasKey('text/css', $styleDeclarations);

		$doc->addStyleDeclaration($line1);
		$styleDeclarations = ReflectionHelper::getValue($doc, 'styleDeclarations');
		$this->assertArrayHasKey('text/css', $styleDeclarations);
		$this->assertEquals($line1, $styleDeclarations['text/css']);

		$doc->addStyleDeclaration($line2);
		$styleDeclarations = ReflectionHelper::getValue($doc, 'styleDeclarations');
		$this->assertEquals($line1 . chr(13) . $line2, $styleDeclarations['text/css']);
	}

	/**
	 * @group   Document
	 */
	public function testGetScripts()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$doc->addScript('http://www.example.com/foo.js');
		$doc->addScript('http://www.example.com/bar.js');

		$expected = ReflectionHelper::getValue($doc, 'scripts');
		$actual = $doc->getScripts();

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @group   Document
	 */
	public function testGetScriptDeclarations()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$doc->addScriptDeclaration('foo');
		$doc->addScriptDeclaration('bar');

		$expected = ReflectionHelper::getValue($doc, 'scriptDeclarations');
		$actual = $doc->getScriptDeclarations();

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @group   Document
	 */
	public function testGetStyles()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$doc->addStyleSheet('http://www.example.com/foo.css');
		$doc->addStyleSheet('http://www.example.com/bar.css');

		$expected = ReflectionHelper::getValue($doc, 'styles');
		$actual = $doc->getStyles();

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @group   Document
	 */
	public function testGetStyleDeclarations()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$doc->addStyleDeclaration('foo');
		$doc->addStyleDeclaration('bar');

		$expected = ReflectionHelper::getValue($doc, 'styleDeclarations');
		$actual = $doc->getStyleDeclarations();

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @group   Document
	 */
	public function testGetMenu()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$expected = ReflectionHelper::getValue($doc, 'menu');
		$actual = $doc->getMenu();

		$this->assertEquals($expected, $actual);
		$this->assertInstanceOf('\\Awf\\Document\\Menu\\MenuManager', $actual);
	}

	/**
	 * @group   Document
	 */
	public function testGetToolbar()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$expected = ReflectionHelper::getValue($doc, 'toolbar');
		$actual = $doc->getToolbar();

		$this->assertEquals($expected, $actual);
		$this->assertInstanceOf('\\Awf\\Document\\Toolbar\\Toolbar', $actual);
	}

	/**
	 * @group   Document
	 */
	public function testGetApplication()
	{
		$app = \Awf\Application\Application::getInstance('Fakeapp', static::$container);
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$actual = $doc->getApplication();

		$this->assertEquals($app, $actual);
	}

	/**
	 * @group   Document
	 */
	public function testGetContainer()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$actual = $doc->getContainer();

		$this->assertEquals(static::$container, $actual);
	}

	/**
	 * @group   Document
	 */
	public function testSetMimeType()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');
		$mime = 'text/foobar';

		$doc->setMimeType($mime);
		$actual = ReflectionHelper::getValue($doc, 'mimeType');
		$this->assertEquals($mime, $actual);
	}

	/**
	 * @group   Document
	 */
	public function testGetMimeType()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');
		$mime = 'text/foobar';

		ReflectionHelper::setValue($doc, 'mimeType', $mime);
		$actual = $doc->getMimeType($mime);

		$this->assertEquals($mime, $actual);
	}

	/**
	 * @group   Document
	 */
	public function testAddHTTPHeader()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		ReflectionHelper::setValue($doc, 'HTTPHeaders', array());

		// Can I set a header?
		$doc->addHTTPHeader('Foo', 'Bar');
		$headers = ReflectionHelper::getValue($doc, 'HTTPHeaders');
		$this->assertArrayHasKey('Foo', $headers);
		$this->assertEquals('Bar', $headers['Foo']);

		// Do not set the header when overwrite is false
		$doc->addHTTPHeader('Foo', 'Baz', false);
		$headers = ReflectionHelper::getValue($doc, 'HTTPHeaders');
		$this->assertArrayHasKey('Foo', $headers);
		$this->assertEquals('Bar', $headers['Foo']);

		// Overwrite the header when overwrite is not set
		$doc->addHTTPHeader('Foo', 'Baz');
		$headers = ReflectionHelper::getValue($doc, 'HTTPHeaders');
		$this->assertArrayHasKey('Foo', $headers);
		$this->assertEquals('Baz', $headers['Foo']);

		// Overwrite the header when overwrite is true
		$doc->addHTTPHeader('Foo', 'Bad');
		$headers = ReflectionHelper::getValue($doc, 'HTTPHeaders');
		$this->assertArrayHasKey('Foo', $headers);
		$this->assertEquals('Bad', $headers['Foo']);

		// Append headers
		$doc->addHTTPHeader('Kot', 'Lol');
		$headers = ReflectionHelper::getValue($doc, 'HTTPHeaders');
		$this->assertArrayHasKey('Kot', $headers);
		$this->assertEquals('Lol', $headers['Kot']);

		return $doc;
	}

	/**
	 * @group   Document
	 */
	public function testRemoveHTTPHeader()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		ReflectionHelper::setValue($doc, 'HTTPHeaders', array());

		$doc->addHTTPHeader('Foo', 'Bar');
		$doc->addHTTPHeader('Kot', 'Lol');

		$headers = ReflectionHelper::getValue($doc, 'HTTPHeaders');
		$this->assertCount(2, $headers);
		$this->assertArrayHasKey('Kot', $headers);

		$doc->removeHTTPHeader('Kot');

		$headers = ReflectionHelper::getValue($doc, 'HTTPHeaders');
		$this->assertCount(1, $headers);
		$this->assertArrayNotHasKey('Kot', $headers);
	}

	/**
	 * @group   Document
	 */
	public function testGetHTTPHeader()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$doc->addHTTPHeader('Foo', 'Bar');

		// Get a header which exists
		$actual = $doc->getHTTPHeader('Foo');
		$this->assertEquals('Bar', $actual);

		// Get the default value of a header which doesn't exist
		$actual = $doc->getHTTPHeader('Kot', 'Not');
		$this->assertEquals('Not', $actual);

		// Make sure we didn't add the header which doesn't exist by accident
		$headers = ReflectionHelper::getValue($doc, 'HTTPHeaders');
		$this->assertCount(1, $headers);
		$this->assertArrayNotHasKey('Kot', $headers);
	}

	/**
	 * @group   Document
	 */
	public function testSetName()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$doc->setName('foobar');
		$actual = ReflectionHelper::getValue($doc, 'name');
		$this->assertEquals('foobar', $actual);
	}

	/**
	 * @group   Document
	 */
	public function testGetName()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		ReflectionHelper::setValue($doc, 'name', 'foobar');

		$actual = $doc->getName();
		$this->assertEquals('foobar', $actual);
	}
}
