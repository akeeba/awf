<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Document;

use Awf\Document\Document;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container as FakeContainer;

/**
 * Class DocumentTest
 *
 * @package Awf\Tests\Document
 *
 * @coversDefaultClass \Awf\Document\Document
 */
class DocumentTest extends \Awf\Tests\Helpers\ApplicationTestCase
{
	/**
	 * @covers Awf\Document\Document::__construct
	 * @covers Awf\Document\Document::getInstance
	 *
	 * @throws \Awf\Exception\App
	 */
	public function testGetInstance()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$this->assertInstanceOf('\\Awf\\Tests\\Stubs\\Document\\Fake', $doc);
		$docApp = ReflectionHelper::getValue($doc, 'container');
		$this->assertEquals(static::$container, $docApp);
	}

	public function testSetBuffer()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$myBuffer = 'The quick brown fox jumped over the lazy dog';
		$doc->setBuffer($myBuffer);
		$actual = ReflectionHelper::getValue($doc, 'buffer');

		$this->assertEquals($myBuffer, $actual);
	}

	public function testGetBuffer()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$myBuffer = 'The quick brown fox jumped over the lazy dog';
		ReflectionHelper::setValue($doc, 'buffer', $myBuffer);
		$actual = $doc->getBuffer($myBuffer);

		$this->assertEquals($myBuffer, $actual);
	}

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

		return $doc;
	}

	/**
	 * @depends testAddScriptPlain
	 *
	 * @param Document $doc
	 *
	 * @return Document
	 */
	public function testAddScriptReplaceExisting(Document $doc)
	{
		$url = 'http://www.example.com/foo.js';

		$doc->addScript($url, true, 'text/ecmascript');
		$scripts = ReflectionHelper::getValue($doc, 'scripts');

		$this->assertInternalType('array', $scripts);
		$this->assertCount(1, $scripts);
		$this->assertEquals('text/ecmascript', $scripts[$url]['mime']);
		$this->assertTrue($scripts[$url]['before']);

		return $doc;
	}

	/**
	 * @depends testAddScriptReplaceExisting
	 *
	 * @param Document $doc
	 *
	 * @return Document
	 */
	public function testAddScriptAppend(Document $doc)
	{
		$url = 'http://www.example.com/bar.js';

		$doc->addScript($url);
		$scripts = ReflectionHelper::getValue($doc, 'scripts');

		$this->assertInternalType('array', $scripts);
		$this->assertCount(2, $scripts);
		$this->assertArrayHasKey($url, $scripts);

		return $doc;
	}

	/**
	 * @throws \Awf\Exception\App
	 *
	 * @return  Document
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

		return $doc;
	}

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

		return $doc;
	}

	/**
	 * @depends testAddStyleSheetPlain
	 *
	 * @param Document $doc
	 *
	 * @return Document
	 */
	public function testAddStyleSheetReplaceExisting(Document $doc)
	{
		$url = 'http://www.example.com/foo.css';

		$doc->addStyleSheet($url, true, 'css3', 'screen');
		$styles = ReflectionHelper::getValue($doc, 'styles');

		$this->assertInternalType('array', $styles);
		$this->assertCount(1, $styles);
		$this->assertEquals('css3', $styles[$url]['mime']);
		$this->assertEquals('screen', $styles[$url]['media']);
		$this->assertTrue($styles[$url]['before']);

		return $doc;
	}

	/**
	 * @depends testAddStyleSheetReplaceExisting
	 *
	 * @param Document $doc
	 *
	 * @return Document
	 */
	public function testAddStyleSheetAppend(Document $doc)
	{
		$url = 'http://www.example.com/bar.css';

		$doc->addStyleSheet($url);
		$styles = ReflectionHelper::getValue($doc, 'styles');

		$this->assertInternalType('array', $styles);
		$this->assertCount(2, $styles);
		$this->assertArrayHasKey($url, $styles);

		return $doc;
	}

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

		return $doc;
	}

	/**
	 * @depends testAddScriptAppend
	 */
	public function testGetScripts(Document $doc)
	{
		$expected = ReflectionHelper::getValue($doc, 'scripts');
		$actual = $doc->getScripts();

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @depends testAddScriptDeclaration
	 *
	 * @param Document $doc
	 */
	public function testGetScriptDeclarations(Document $doc)
	{
		$expected = ReflectionHelper::getValue($doc, 'scriptDeclarations');
		$actual = $doc->getScriptDeclarations();

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @depends testAddStyleSheetAppend
	 */
	public function testGetStyles(Document $doc)
	{
		$expected = ReflectionHelper::getValue($doc, 'styles');
		$actual = $doc->getStyles();

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @depends testAddStyleDeclaration
	 *
	 * @param Document $doc
	 */
	public function testGetStyleDeclarations(Document $doc)
	{
		$expected = ReflectionHelper::getValue($doc, 'styleDeclarations');
		$actual = $doc->getStyleDeclarations();

		$this->assertEquals($expected, $actual);
	}

	public function testGetMenu()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$expected = ReflectionHelper::getValue($doc, 'menu');
		$actual = $doc->getMenu();

		$this->assertEquals($expected, $actual);
		$this->assertInstanceOf('\\Awf\\Document\\Menu\\MenuManager', $actual);
	}

	public function testGetToolbar()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$expected = ReflectionHelper::getValue($doc, 'toolbar');
		$actual = $doc->getToolbar();

		$this->assertEquals($expected, $actual);
		$this->assertInstanceOf('\\Awf\\Document\\Toolbar\\Toolbar', $actual);
	}

	public function testGetApplication()
	{
		$app = \Awf\Application\Application::getInstance('Fakeapp', static::$container);
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$actual = $doc->getApplication();

		$this->assertEquals($app, $actual);
	}

	public function testGetContainer()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$actual = $doc->getContainer();

		$this->assertEquals(static::$container, $actual);
	}

	public function testSetMimeType()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');
		$mime = 'text/foobar';

		$doc->setMimeType($mime);
		$actual = ReflectionHelper::getValue($doc, 'mimeType');
		$this->assertEquals($mime, $actual);
	}

	public function testGetMimeType()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');
		$mime = 'text/foobar';

		ReflectionHelper::setValue($doc, 'mimeType', $mime);
		$actual = $doc->getMimeType($mime);

		$this->assertEquals($mime, $actual);
	}

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
	 * @depends testAddHTTPHeader
	 *
	 * @param Document $doc
	 *
	 * @return Document
	 */
	public function testRemoveHTTPHeader(Document $doc)
	{
		$headers = ReflectionHelper::getValue($doc, 'HTTPHeaders');
		$this->assertCount(2, $headers);
		$this->assertArrayHasKey('Kot', $headers);

		$doc->removeHTTPHeader('Kot');

		$headers = ReflectionHelper::getValue($doc, 'HTTPHeaders');
		$this->assertCount(1, $headers);
		$this->assertArrayNotHasKey('Kot', $headers);

		return $doc;
	}

	/**
	 * @depends testRemoveHTTPHeader
	 *
	 * @param Document $doc
	 */
	public function testGetHTTPHeader(Document $doc)
	{
		// Get a header which exists
		$actual = $doc->getHTTPHeader('Foo');
		$this->assertEquals('Bad', $actual);

		// Get the default value of a header which doesn't exist
		$actual = $doc->getHTTPHeader('Kot', 'Not');
		$this->assertEquals('Not', $actual);

		// Make sure we didn't add the header which doesn't exist by accident
		$headers = ReflectionHelper::getValue($doc, 'HTTPHeaders');
		$this->assertCount(1, $headers);
		$this->assertArrayNotHasKey('Kot', $headers);
	}

	public function testSetName()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		$doc->setName('foobar');
		$actual = ReflectionHelper::getValue($doc, 'name');
		$this->assertEquals('foobar', $actual);
	}

	public function testGetName()
	{
		$doc = Document::getInstance('fake', static::$container, '\\Awf\\Tests\\Stubs');

		ReflectionHelper::setValue($doc, 'name', 'foobar');

		$actual = $doc->getName();
		$this->assertEquals('foobar', $actual);
	}
}
 