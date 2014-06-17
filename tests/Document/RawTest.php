<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Document;

use Awf\Application\Application;
use Awf\Document\Document;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container as FakeContainer;

/**
 * @package Awf\Tests\Document
 *
 * @coversDefaultClass \Awf\Document\Raw
 */
class RawTest extends \Awf\Tests\Helpers\ApplicationTestCase
{
	public function testRenderRaw()
	{
		$document = Document::getInstance('raw', Application::getInstance('Fakeapp'));
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
		$document = Document::getInstance('raw', Application::getInstance('Fakeapp'));
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
 