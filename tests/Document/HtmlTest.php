<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Document;

use Awf\Document\Document;
use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\ReflectionHelper;

/**
 * @package Awf\Tests\Document
 *
 * @coversDefaultClass \Awf\Document\Html
 */
class HtmlTest extends AwfTestCase
{
	protected function setUp($resetContainer = true)
	{
		parent::setUp();

		// Reset the instances
		ReflectionHelper::setValue('\Awf\Document\Document', 'instances', array());
	}

	public function testRenderHtml()
	{
		$document = Document::getInstance('html', static::$container);
		$document->getApplication()->setTemplate('nada');
		$this->assertInstanceOf('\\Awf\\Document\\Html', $document);
		$document->render();

		$contentType = $document->getHTTPHeader('Content-Type');
		$this->assertEquals('text/html', $contentType);

		$contentDisposition = $document->getHTTPHeader('Content-Disposition');
		$this->assertNull($contentDisposition);
	}

	public function testRenderAttachment()
	{
		$document = Document::getInstance('html', static::$container);
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
