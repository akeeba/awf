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
 * @coversDefaultClass \Awf\Document\Raw
 */
class RawTest extends AwfTestCase
{
	protected function setUp($resetContainer = true)
	{
		parent::setUp();

		// Reset the instances
		ReflectionHelper::setValue('\Awf\Document\Document', 'instances', array());
	}

	public function testRenderRaw()
	{
		$document = Document::getInstance('raw', static::$container);
		$this->assertInstanceOf('\\Awf\\Document\\Raw', $document);
		$document->setBuffer('test');

		$this->expectOutputString('test');
		$document->render();

		$contentType = $document->getHTTPHeader('Content-Type');
		$this->assertEquals('text/plain', $contentType);

		$contentDisposition = $document->getHTTPHeader('Content-Disposition');
		$this->assertNull($contentDisposition);
	}

	public function testRenderRawAttachment()
	{
		$document = Document::getInstance('raw', static::$container);
		$this->assertInstanceOf('\\Awf\\Document\\Raw', $document);
		$document->setBuffer('test');
		$document->setMimeType('application/pdf');
		$document->setName('foobar.pdf');

		$this->expectOutputString('test');
		$document->render();

		$contentType = $document->getHTTPHeader('Content-Type');
		$this->assertEquals('application/pdf', $contentType);

		$contentDisposition = $document->getHTTPHeader('Content-Disposition');
		$this->assertEquals('attachment; filename="foobar.pdf"', $contentDisposition);
	}

}
