<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Hal;

use Awf\Hal\Link;
use Awf\Tests\Helpers\AwfTestCase;

class LinkTest extends AwfTestCase
{
	protected function setUp($resetContainer = true)
	{
		parent::setUp(false);
	}

	/**
	 * Provides the data for testCreateNoException
	 *
	 * @return array
	 */
	public function getTestCreateNoExceptionData()
	{
		return array(
			array('http://www.example.com/nada.json', false, null, null, null, 'Untemplated link without name should be created'),
			array('http://www.example.com/nada{?id}.json', true, null, null, null, 'Templated link without name should be created'),
			array('http://www.example.com/nada.json', false, 'Test name', null, null, 'Untemplated link with name should be created'),
			array('http://www.example.com/nada{?id}.json', true, 'Test name', null, null, 'Templated link with name should be created'),
			array('http://www.example.com/nada.json', false, 'Test name', 'en-GB', null, 'Untemplated link with hreflang should be created'),
			array('http://www.example.com/nada{?id}.json', true, 'Test name', 'en-GB', null, 'Templated link with hreflang should be created'),
			array('http://www.example.com/nada.json', false, 'Test name', 'en-GB', 'My title', 'Untemplated link with title should be created'),
			array('http://www.example.com/nada{?id}.json', true, 'Test name', 'en-GB', 'My title', 'Templated link with title should be created'),
		);
	}

	/**
	 * @dataProvider	getTestCreateNoExceptionData
	 * @covers			Awf\Hal\Link::__construct
	 */
	public function testCreateNoException($href, $templated, $name, $hreflang, $title, $message)
	{
		try
		{
			// Create the new link
			$result = new Link($href, $templated, $name, $hreflang, $title);

			// Does our href match?
			$this->assertEquals(
				$href,
				$this->getObjectAttribute($result, '_href')
			);

			// Does our templated match?
			$this->assertEquals(
				$templated,
				$this->getObjectAttribute($result, '_templated')
			);

			// Does our name match?
			$this->assertEquals(
				$name,
				$this->getObjectAttribute($result, '_name')
			);

			// Does our hreflang match?
			$this->assertEquals(
				$hreflang,
				$this->getObjectAttribute($result, '_hreflang')
			);

			// Does our title match?
			$this->assertEquals(
				$title,
				$this->getObjectAttribute($result, '_title')
			);
		}
		catch (\Exception $exc)
		{
			$this->fail($message);
		}
	}

	public function getTestCreateExceptionData()
	{
		return array(
			array(null, false, null, null, null, 'Null link is not allowed'),
			array('', false, null, null, null, 'Empty link is not allowed'),
		);
	}

	/**
	 * @dataProvider				getTestCreateExceptionData
	 * @covers						Awf\Hal\Link::__construct
	 * @expectedException			\RuntimeException
	 * @expectedExceptionMessage	A HAL link must always have a non-empty href
	 */
	public function testCreateException($href, $templated, $name, $hreflang, $title, $message)
	{
		$result = new Link($href, $templated, $name, $hreflang, $title);
	}


	public function getTestCheckData()
	{
		return array(
			array('http://www.example.com/nada.json', false, true, 'Absolute URL link should always be considered non-empty'),
			array('nada.json', false, true, 'Relative URL link should always be considered non-empty'),
			array('http://www.example.com/nada{?id}.json', false, true, 'Absolute templated URL should always be considered non-empty'),
			array('nada{?id}.json', false, true, 'Relative templated URL should always be considered non-empty'),
		);
	}

	/**
	 * @dataProvider	getTestCheckData
	 * @covers			Awf\Hal\Link::check
	 */
	public function testCheck($href, $templated, $expect, $message)
	{
		$halLink = new Link($href, $templated);
		$this->assertEquals($expect, $halLink->check(), $message);
	}

	public function getTestMagicGetterData()
	{
		return array(
			array('href', 'http://www.example.com/nada.json', 'The href property cannot be gotten'),
			array('templated', false, 'The templated property cannot be gotten'),
			array('name', 'My name', 'The name property cannot be gotten'),
			array('hreflang', 'en-GB', 'The hreflang property cannot be gotten'),
			array('title', 'My title', 'The title property cannot be gotten'),
			array('invalidwhatever', null, 'An invalid property should not be gotten'),
		);
	}

	/**
	 * @dataProvider	getTestMagicGetterData
	 * @covers			Awf\Hal\Link::__get
	 */
	public function testMagicGetter($property, $expect, $message)
	{
		$link = new Link('http://www.example.com/nada.json', false, 'My name', 'en-GB', 'My title');

		$this->assertEquals($expect, $link->$property, $message);
	}

	public function getTestMagicSetterData()
	{
		return array(
			array('href', 'http://www.example.com/lol.json', 'The href property cannot be set'),
			array('templated', true, 'The templated property cannot be set'),
			array('name', 'My new name', 'The name property cannot be set'),
			array('hreflang', 'el-CY', 'The hreflang property cannot be set'),
			array('title', 'My new title', 'The title property cannot be set'),
			array('invalidwhatever', 123, 'An invalid property should not be set'),
		);
	}

	/**
	 * @dataProvider	getTestMagicGetterData
	 * @covers			Awf\Hal\Link::__set
	 */
	public function testMagicSetter($property, $expect, $message)
	{
		$link = new Link('http://www.example.com/nada.json', false, 'My name', 'en-GB', 'My title');

		$link->$property = $expect;

		$this->assertEquals($expect, $link->$property, $message);
	}

	/**
	 * @dataProvider	getTestMagicGetterData
	 * @covers			Awf\Hal\Link::__set
	 */
	public function testMagicSetterEmptyHref()
	{
		$link = new Link('http://www.example.com/nada.json', false, 'My name', 'en-GB', 'My title');

		$link->href = '';

		$this->assertEquals('http://www.example.com/nada.json', $link->href);
	}
}
