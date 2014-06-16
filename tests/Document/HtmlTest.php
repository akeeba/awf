<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Document;

use Awf\Application\Application;
use Awf\Document\Document;
use Awf\Document\Html;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container as FakeContainer;

/**
 * @package Awf\Tests\Document
 *
 * @coversDefaultClass \Awf\Document\Html
 */
class HtmlTest extends \PHPUnit_Framework_TestCase
{
	/** @var FakeContainer A container suitable for unit testing */
	public static $container = null;

	public function __construct($name = null, array $data = array(), $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		// We can't use setUpBeforeClass or setUp because PHPUnit will not run these methods before
		// getting the data from the data provider of each test :(

		ReflectionHelper::setValue('\\Awf\\Application\\Application', 'instances', array());

		// Convince the autoloader about our default app and its container
		static::$container = new FakeContainer();
		$app = \Awf\Application\Application::getInstance('Fakeapp', static::$container);

		$app->setTemplate('nada');
	}

	public function testRenderHtml()
	{
		$document = Document::getInstance('html', Application::getInstance('Fakeapp'));
		$document->getApplication()->setTemplate('nada');
		$this->assertInstanceOf('\\Awf\\Document\\Html', $document);
		$document->render();

		$contentType = $document->getHTTPHeader('Content-Type');
		$this->assertEquals('text/html', $contentType);

		$contentDisposition = $document->getHTTPHeader('Content-Disposition');
		$this->assertNull($contentDisposition);

		return $document;
	}

	public function testRenderAttachment()
	{
		$document = Document::getInstance('html', Application::getInstance('Fakeapp'));
		$document->getApplication()->setTemplate('nada');
		$this->assertInstanceOf('\\Awf\\Document\\Html', $document);
		$document->setMimeType('application/pdf');
		$document->setName('foobar.pdf');
		$document->render();

		$contentType = $document->getHTTPHeader('Content-Type');
		$this->assertEquals('application/pdf', $contentType);

		$contentDisposition = $document->getHTTPHeader('Content-Disposition');
		$this->assertEquals('attachment; filename="foobar.pdf.html"', $contentDisposition);
	}
}
 