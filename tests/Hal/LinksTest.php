<?php
/**
 * @package		awf
 * @copyright	2014-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Hal;

use Awf\Hal\Links;
use Awf\Hal\Link;
use Awf\Tests\Helpers\AwfTestCase;

class LinksTest extends AwfTestCase
{
	protected function setUp($resetContainer = true)
	{
		parent::setUp(false);
	}

	/**
	 * @covers Awf\Hal\Links::addLink
	 */
	function testAddLink()
	{
		// Create a sample link
		$link = new Link('http://www.example.com/nada.json');
		$linkset = new Links();

		// ==== Add a link to a link set ====
		$result = $linkset->addLink('custom', $link);

		$links = $this->readAttribute($linkset, '_links');

		$this->assertArrayHasKey('custom', $links, 'The link set must have an array key for our rel');

		$this->assertInternalType('object', $links['custom']);

		$this->assertEquals($link, $links['custom'], 'The link item is not present in the link set');

		// ==== Replace a link in the link set ====
		$newlink = new Link('http://www.example.com/yeah.json', false, 'Something');
		$result = $linkset->addLink('custom', $newlink, true);

		$links = $this->readAttribute($linkset, '_links');

		$this->assertArrayHasKey('custom', $links, 'The link set must have an array key for our replaced rel');

		$this->assertInternalType('object', $links['custom']);

		$this->assertEquals($newlink, $links['custom'], 'The replaced link item is not present in the link set');

		// ==== Add a link in the link set ====

		$anotherlink = new Link('http://www.example.com/another.json', false, 'Something else');
		$result = $linkset->addLink('custom', $anotherlink, false);

		$links = $this->readAttribute($linkset, '_links');

		$this->assertArrayHasKey('custom', $links, 'The link set must have an array key for our replaced rel');

		$this->assertInternalType('array', $links['custom']);

		$this->assertEquals($newlink, $links['custom'][0]);
		$this->assertEquals($anotherlink, $links['custom'][1]);
	}

	/**
	 * @covers Awf\Hal\Links::addLinks
	 */
	function testAddLinks()
	{
		// Create a sample link
		$link = new Link('http://www.example.com/nada.json');
		$linkset = new Links();

		// ==== Try to add an empty array ====
		$result = $linkset->addLinks('boz', array());

		$this->assertFalse($result);

		// ==== Add a link to a link set ====
		$result = $linkset->addLink('custom', $link);

		// ==== Replace the link in the link set ====
		$newlinks = array(
			new Link('http://www.example.com/yeah.json', false, 'Something'),
			new Link('http://www.example.com/another.json', false, 'Something else')
		);

		$result = $linkset->addLinks('custom', $newlinks, true);

		$links = $this->readAttribute($linkset, '_links');

		$this->assertArrayHasKey('custom', $links, 'The link set must have an array key for our replaced rel');

		$this->assertInternalType('array', $links['custom']);

		$this->assertEquals($newlinks[0], $links['custom'][0]);
		$this->assertEquals($newlinks[1], $links['custom'][1]);

		// ==== Append to an existing set ====
		$result = $linkset->addLink('custom', $link, true);

		$result = $linkset->addLinks('custom', $newlinks, false);

		$links = $this->readAttribute($linkset, '_links');

		$this->assertArrayHasKey('custom', $links, 'The link set must have an array key for our replaced rel');

		$this->assertInternalType('array', $links['custom']);

		$this->assertEquals($link, $links['custom'][0]);
		$this->assertEquals($newlinks[0], $links['custom'][1]);
		$this->assertEquals($newlinks[1], $links['custom'][2]);
	}

	/**
	 * @covers Awf\Hal\Links::getLinks
	 */
	function testGetLinks()
	{
		// Create a sample link
		$newlinks = array(
			'foo' => array(
				new Link('http://www.example.com/yeah.json', false, 'Something'),
				new Link('http://www.example.com/another.json', false, 'Something else')
			),
			'bar' => array(
				new Link('http://www.example.com/foo{?id}.json', true, 'Foo link'),
				new Link('http://www.example.com/bar{?id}.json', true, 'Bar link')
			),
		);

		$linkset = new Links();

		$linkset->addLinks('foo', $newlinks['foo']);
		$linkset->addLinks('bar', $newlinks['bar']);

		$links = $linkset->getLinks();

		$this->assertArrayHasKey('foo', $links);
		$this->assertArrayHasKey('bar', $links);

		$this->assertEquals($newlinks['foo'][0], $links['foo'][0]);
		$this->assertEquals($newlinks['foo'][1], $links['foo'][1]);
		$this->assertEquals($newlinks['bar'][0], $links['bar'][0]);
		$this->assertEquals($newlinks['bar'][1], $links['bar'][1]);

		$links = $linkset->getLinks('foo');
		$this->assertEquals($newlinks['foo'][0], $links[0]);
		$this->assertEquals($newlinks['foo'][1], $links[1]);

		$links = $linkset->getLinks('baz');
		$this->assertInternalType('array', $links);
		$this->assertEmpty($links);
	}
}
