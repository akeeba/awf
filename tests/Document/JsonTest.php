<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Document;

use Awf\Document\Document;
use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\ReflectionHelper;

/**
 * @package Awf\Tests\Document
 *
 * @coversDefaultClass \Awf\Document\Json
 */
class JsonTest extends AwfTestCase
{
	protected function setUp($resetContainer = true)
	{
		parent::setUp();

		// Reset the instances
		ReflectionHelper::setValue('\Awf\Document\Document', 'instances', array());
	}

	public function testSetAndGetUseHashes()
	{
		/** @var \Awf\Document\Json $document */
		$document = Document::getInstance('json', static::$container);
		$this->assertInstanceOf('\\Awf\\Document\\Json', $document);

		$document->setUseHashes(true);
		$this->assertTrue(ReflectionHelper::getValue($document, 'useHashes'));
		$this->assertTrue($document->getUseHashes());

		$document->setUseHashes(false);
		$this->assertFalse(ReflectionHelper::getValue($document, 'useHashes'));
		$this->assertFalse($document->getUseHashes());

		$document->setUseHashes(1);
		$this->assertTrue(ReflectionHelper::getValue($document, 'useHashes'));
		$this->assertTrue($document->getUseHashes());

		$document->setUseHashes(0);
		$this->assertFalse(ReflectionHelper::getValue($document, 'useHashes'));
		$this->assertFalse($document->getUseHashes());
	}

	public function testRenderJsonPlain()
	{
		/** @var \Awf\Document\Json $document */
		$document = Document::getInstance('json', static::$container);
		$this->assertInstanceOf('\\Awf\\Document\\Json', $document);
		$document->setUseHashes(false);
		$document->setBuffer("{test: true}");

		$this->expectOutputString('{test: true}');
		$document->render();

		$contentType = $document->getHTTPHeader('Content-Type');
		$this->assertEquals('application/json', $contentType);

		$contentDisposition = $document->getHTTPHeader('Content-Disposition');
		$this->assertNull($contentDisposition);
	}

	public function testRenderJsonHashes()
	{
		/** @var \Awf\Document\Json $document */
		$document = Document::getInstance('json', static::$container);
		$this->assertInstanceOf('\\Awf\\Document\\Json', $document);
		$document->setUseHashes(true);
		$document->setBuffer("{test: true}");

		$this->expectOutputString('###{test: true}###');
		$document->render();

		$contentType = $document->getHTTPHeader('Content-Type');
		$this->assertEquals('application/json', $contentType);

		$contentDisposition = $document->getHTTPHeader('Content-Disposition');
		$this->assertNull($contentDisposition);
	}

	public function testRenderJsonAttachment()
	{
		/** @var \Awf\Document\Json $document */
		$document = Document::getInstance('json', static::$container);
		$this->assertInstanceOf('\\Awf\\Document\\Json', $document);
		$document->setBuffer('{test: true}');
		$document->setName('foobar');
		$document->setUseHashes(false);

		$this->expectOutputString('{test: true}');
		$document->render();

		$contentType = $document->getHTTPHeader('Content-Type');
		$this->assertEquals('application/json', $contentType);

		$contentDisposition = $document->getHTTPHeader('Content-Disposition');
		$this->assertEquals('attachment; filename="foobar.json"', $contentDisposition);
	}
}
