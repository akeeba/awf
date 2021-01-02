<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Hal\Render;

use Awf\Hal\Document;
use Awf\Hal\Link;
use Awf\Hal\Render\Json;
use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\ReflectionHelper;

class JsonTest extends AwfTestCase
{
	/** @var  Document  The document used in the renderer tests */
	protected $document;

	/**
	 * Creating the HAL Document fixture
	 */
	protected function setUp($resetContainer = true)
	{
		parent::setUp(false);

		$data = array(
			'key1' => 'val1',
			'key2' => 'val2',
		);

		$this->document = new Document($data);

		$data = array(
			'detail1_1' => 'val1_1',
			'detail1_2' => 'val1_2',
		);
		$subDocument1 = new Document($data);
		$this->document->addEmbedded('detail', $subDocument1, false);

		$data = array(
			'detail2_1' => 'val2_1',
			'detail2_2' => 'val2_2',
		);
		$subDocument2 = new Document($data);
		$this->document->addEmbedded('detail', $subDocument2, false);

		$prev = new Link('http://www.example.com/test.json?page=1', false);
		$this->document->addLink('prev', $prev);

		$next = new Link('http://www.example.com/test.json?page=3', false);
		$this->document->addLink('next', $next);
	}

	/**
	 * Test the __construct contructor
	 *
	 * @covers Awf\Hal\Render\Json::__construct
	 *
	 * @return Json
	 */
	public function testConstruct()
	{
		$renderer = new Json($this->document);

		$this->assertEquals(
			$this->document,
			$this->getObjectAttribute($renderer, '_document'),
			'Line: ' . __LINE__ . '.'
		);

		return $renderer;
	}

	/**
	 * Test the protected _getLink method
	 *
	 * @param Link  $link     The link to test with
	 * @param array $expected The expected return array
	 *
	 * @dataProvider getTestGetLink
	 *
	 * @covers       Awf\Hal\Render\Json::_getLink
	 *
	 * @return Json
	 */
	public function testGetLink(Link $link, array $expected)
	{
		$renderer = new Json($this->document);

		$result = ReflectionHelper::invoke($renderer, '_getLink', $link);

		$this->assertEquals(
			$expected,
			(array)$result,
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * @covers Awf\Hal\Render\Json::render
	 */
	public function testRender()
	{
		$renderer = new Json($this->document);

		// Full render
		$expected = '{"_links":{"prev":{"href":"http:\/\/www.example.com\/test.json?page=1"},"next":{"href":"http:\/\/www.example.com\/test.json?page=3"}},"_embedded":{"detail":["{\"_links\":{},\"_list\":{\"detail1_1\":\"val1_1\",\"detail1_2\":\"val1_2\"}}","{\"_links\":{},\"_list\":{\"detail2_1\":\"val2_1\",\"detail2_2\":\"val2_2\"}}"]},"_list":{"key1":"val1","key2":"val2"}}';
		$rendered = $renderer->render();

		$this->assertEquals(
			$expected,
			$rendered,
			'Line: ' . __LINE__ . '.'
		);

		// Without embedded documents
		$document = $this->getObjectAttribute($renderer, '_document');
		ReflectionHelper::setValue($document, '_embedded', null);
		ReflectionHelper::setValue($renderer, '_document', $document);

		$expected = '{"_links":{"prev":{"href":"http:\/\/www.example.com\/test.json?page=1"},"next":{"href":"http:\/\/www.example.com\/test.json?page=3"}},"_list":{"key1":"val1","key2":"val2"}}';
		$rendered = $renderer->render();

		$this->assertEquals(
			$expected,
			$rendered,
			'Line: ' . __LINE__ . '.'
		);

		// With list of links
		$foo = new Link('http://www.example.com/foo.json?arg=1', false);
		$document->addLink('foo', $foo);
		$foo = new Link('http://www.example.com/foo.json?arg=1', false);
		$document->addLink('foo', $foo);
		ReflectionHelper::setValue($renderer, '_document', $document);

		$expected = '{"_links":{"prev":{"href":"http:\/\/www.example.com\/test.json?page=1"},"next":{"href":"http:\/\/www.example.com\/test.json?page=3"},"foo":{"href":"http:\/\/www.example.com\/foo.json?arg=1"}},"_list":{"key1":"val1","key2":"val2"}}';
		$rendered = $renderer->render();

		$this->assertEquals(
			$expected,
			$rendered,
			'Line: ' . __LINE__ . '.'
		);

	}

	/**
	 * Data provider for testGetLink
	 */
	public function getTestGetLink()
	{
		return array(
			array(
				new Link(
					'http://www.example.com/foo.json'
				),
				array(
					'href' => 'http://www.example.com/foo.json'
				)
			),
			array(
				new Link(
					'http://www.example.com/foo{?id}.json', true
				),
				array(
					'href'      => 'http://www.example.com/foo{?id}.json',
					'templated' => 'true'
				)
			),
			array(
				new Link(
					'http://www.example.com/foo.json', false, 'foo'
				),
				array(
					'href' => 'http://www.example.com/foo.json',
					'name' => 'foo'
				)
			),
			array(
				new Link(
					'http://www.example.com/foo{?id}.json', true, 'foo'
				),
				array(
					'href'      => 'http://www.example.com/foo{?id}.json',
					'templated' => 'true',
					'name'      => 'foo'
				)
			),
			array(
				new Link(
					'http://www.example.com/foo.json', false, null, 'en-GB'
				),
				array(
					'href'     => 'http://www.example.com/foo.json',
					'hreflang' => 'en-GB'
				)
			),
			array(
				new Link(
					'http://www.example.com/foo.json', false, null, null, 'foobar'
				),
				array(
					'href'  => 'http://www.example.com/foo.json',
					'title' => 'foobar'
				)
			),
			array(
				new Link(
					'http://www.example.com/foo{?id}.json', true, 'foo', 'en-GB', 'foobar'
				),
				array(
					'href'      => 'http://www.example.com/foo{?id}.json',
					'templated' => 'true',
					'name'      => 'foo',
					'hreflang'  => 'en-GB',
					'title'     => 'foobar'
				)
			),
		);
	}
}
