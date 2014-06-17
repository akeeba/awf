<?php
/**
 * @package        awf
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Document\Menu;

use Awf\Application\Application;
use Awf\Document\Document;
use Awf\Document\Menu\Item;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Fakeapp\Container as FakeContainer;
use Awf\Uri\Uri;

/**
 * Class ItemTest
 *
 * @package Awf\Tests\Document\Menu
 *
 * @coversDefaultClass \Awf\Document\Menu\Item
 */
class ItemTest extends \Awf\Tests\Helpers\ApplicationTestCase
{
	public function testConstruct()
	{
		$data = array(
			'group'        => 'foobar',
			'icon'         => 'icon-foobar',
			'name'         => 'foobar',
			'titleHandler' => array('Foobar', 'handleTitle'),
			'onClick'      => 'alert("Hi")',
			'show'         => array('foobar'),
			'title'        => 'Foo Bar',
			'url'          => 'http://www.example.com',
			'order'        => 1,
			'params'       => array('view' => 'foobar')
		);

		$item = new Item($data);

		$this->assertInstanceOf('\\Awf\\Document\\Menu\\Item', $item);

		$applicationExpected = Application::getInstance('Fakeapp');
		$applicationCurrent = ReflectionHelper::getValue($item, 'application');
		$this->assertEquals($applicationExpected, $applicationCurrent);

		foreach ($data as $key => $expected)
		{
			$this->assertEquals(
				$expected,
				ReflectionHelper::getValue($item, $key)
			);
		}

		return $item;
	}

	/**
	 * @depends testConstruct
	 *
	 * @param Item $item
	 *
	 * @return array[Item]
	 */
	public function testAddChild(Item $item)
	{
		$this->assertEmpty(ReflectionHelper::getValue($item, 'children'));

		$newItem = clone $item;
		$newItem->setName('yo');
		$newItem->setTitle('yo');

		// Make sure I can add an item
		$item->addChild($newItem);
		$this->assertCount(1, ReflectionHelper::getValue($item, 'children'));

		// Adding the same item repeatedly should NOT add a new item
		$item->addChild($newItem);
		$item->addChild($newItem);
		$this->assertCount(1, ReflectionHelper::getValue($item, 'children'));

		// Adding a different item must add to children
		$thirdItem = clone $item;
		$thirdItem->setTitle('omg');
		$thirdItem->setName('omg');
		$item->addChild($thirdItem);
		$this->assertCount(2, ReflectionHelper::getValue($item, 'children'));

		return array($item, $newItem);
	}

	/**
	 * @depends testAddChild
	 *
	 * @param array [Item] $args
	 *
	 * @return mixed
	 */
	public function testRemoveChild(array $args)
	{
		/**
		 * @var Item $item
		 * @var Item $newItem
		 */
		list($item, $newItem) = $args;

		$item->removeChild($newItem);
		$this->assertCount(1, ReflectionHelper::getValue($item, 'children'));

		return $item;
	}

	/**
	 * @depends testRemoveChild
	 *
	 * @param Item $item
	 */
	public function resetChildren(Item $item)
	{
		$this->assertCount(1, ReflectionHelper::getValue($item, 'children'));

		$item->resetChildren();

		$this->assertEmpty(ReflectionHelper::getValue($item, 'children'));
	}

	/**
	 * @depends testAddChild
	 *
	 * @param Item $item
	 */
	public function testGetChildren(array $args)
	{
		/**
		 * @var Item $item
		 * @var Item $newItem
		 */
		list($item, $newItem) = $args;

		$this->assertCount(1, ReflectionHelper::getValue($item, 'children'));

		$children = $item->getChildren();

		$this->assertCount(1, $children);
		$this->assertEquals(ReflectionHelper::getValue($item, 'children'), $children);
	}

	/**
	 * @dataProvider getTestSetter
	 *
	 * @param string $setterName
	 * @param mixed  $setValue
	 * @param mixed  $expectValue
	 */
	public function testSetter($setterName, $setValue, $expectValue = null)
	{
		$data = array(
			'group'        => 'foobar',
			'icon'         => 'icon-foobar',
			'name'         => 'foobar',
			'titleHandler' => array('Foobar', 'handleTitle'),
			'onClick'      => 'alert("Hi")',
			'show'         => array('foobar'),
			'title'        => 'Foo Bar',
			'url'          => 'http://www.example.com',
			'order'        => 1,
			'params'       => array('view' => 'foobar')
		);

		$item = new Item($data);

		if (is_null($expectValue))
		{
			$expectValue = $setValue;
		}

		// Test the setter method
		$method = 'set' . ucfirst($setterName);
		$item->$method($setValue);
		$this->assertEquals($expectValue, ReflectionHelper::getValue($item, $setterName));
	}

	public function getTestSetter()
	{
		// property, set to, expected value
		return array(
			array('group', 'kot'),
			array('icon', 'icon-whatever'),
			array('name', 'test'),
			array('name', 'This Is Sparta!@$%', 'ThisIsSparta'),
			array('titleHandler', 'foobar'),
			array('titleHandler', array('super', 'duper')),
			array('titleHandler', array('super', 'duper', 'whooper'), array('super', 'duper')),
			array('titleHandler', new \stdClass(), ''),
			array('onClick', 'something'),
			array('parent', 'anotherItem'),
			array('show', array('alpha', 'beta')),
			array('show', 'something', array('something')),
			array('title', 'A pretty long string!!!'),
			array('url', 'http://www.example.com'),
			array('order', 123),
		);
	}

	/**
	 * @dataProvider getTestGetter
	 *
	 * @param string $getterName
	 * @param mixed  $setValue
	 * @param mixed  $expectValue
	 */
	public function testGetter($getterName, $setValue, $expectValue = null)
	{
		$data = array(
			'group'        => 'foobar',
			'icon'         => 'icon-foobar',
			'name'         => 'foobar',
			'titleHandler' => array($this, 'handleTitle'),
			'onClick'      => 'alert("Hi")',
			'show'         => array('foobar'),
			'title'        => 'Foo Bar',
			'url'          => 'http://www.example.com',
			'order'        => 1,
			'params'       => array('view' => 'foobar')
		);

		$item = new Item($data);

		if (is_null($expectValue))
		{
			$expectValue = $setValue;
		}

		// Test the setter method
		if (!is_null($setValue))
		{
			ReflectionHelper::setValue($item, $getterName, $setValue);
		}
		$method = 'get' . ucfirst($getterName);
		$actualValue = $item->$method();
		$this->assertEquals($expectValue, $actualValue);
	}

	public function getTestGetter()
	{
		// property, set to, expected value
		return array(
			array('group', 'kot'),
			array('icon', 'icon-whatever'),
			array('name', 'test'),
			array('titleHandler', 'foobar'),
			array('titleHandler', array('super', 'duper')),
			array('onClick', 'something'),
			array('parent', 'anotherItem'),
			array('show', array('alpha', 'beta')),
			array('title', 'A pretty long string!!!'),
			array('url', 'http://www.example.com'),
			array('order', 123),
		);
	}

	public function testTitleHandler()
	{
		$data = array(
			'group'        => 'foobar',
			'icon'         => 'icon-foobar',
			'name'         => 'foobar',
			'titleHandler' => array('\\Awf\\Tests\\Stubs\\Document\\Menu\\TitleProvider', 'titleHandler'),
			'title'        => '',
		);

		$item = new Item($data);

		$title = $item->getTitle();

		$this->assertEquals('test title', $title);
	}

	public function testUrlParams()
	{
		$data = array(
			'group'  => 'foobar',
			'icon'   => 'icon-foobar',
			'name'   => 'foobar',
			'title'  => 'My test',
			'url'    => '',
			'params' => array('foo' => 'bar', 'view' => 'test')
		);

		$item = new Item($data);

		$url = $item->getUrl();

		$this->assertEquals(
			'http://www.example.com/index.php?foo=bar&view=test',
			$url
		);
	}

	public function testSetGetUrlParams()
	{
		$data = array(
			'group'  => 'foobar',
			'icon'   => 'icon-foobar',
			'name'   => 'foobar',
			'title'  => 'My test',
			'url'    => '',
			'params' => array('foo' => 'bar', 'view' => 'test')
		);

		$item = new Item($data);

		$params = array('a' => 1, 'b' => 2);
		$item->setParams($params);

		$this->assertEquals(
			$params,
			ReflectionHelper::getValue($item, 'params')
		);

		$this->assertEquals(
			$params,
			$item->getParams()
		);

		$this->assertEquals(
			'a=1&b=2',
			$item->getParams(true)
		);
	}

	public function testIsActive()
	{
		$_SERVER['HTTP_HOST'] = 'www.example.com';
		$_SERVER['REQUEST_URI'] = '/index.php?view=test';

		$data = array(
			'group'  => 'foobar',
			'icon'   => 'icon-foobar',
			'name'   => 'foobar',
			'title'  => 'My test',
			'url'    => '',
			'params' => array('view' => 'test')
		);

		$item = new Item($data);

		// URL parameters match, it's active
		$this->assertTrue($item->isActive());

		// Different URL and no URL parameters, it's not active
		$item->setUrl('http://www.google.com');
		$item->setParams(array());
		$this->assertFalse($item->isActive());

		// Different URL and URL parameters match, it's active
		$item->setUrl('http://www.google.com');
		$item->setParams(array('view' => 'test'));
		$this->assertTrue($item->isActive());

		// Exact URL match, it's active
		$item->setUrl('http://www.example.com/index.php?view=test');
		$item->setParams(array());
		$this->assertTrue($item->isActive());

		// Not an exact URL match, it's not active
		$item->setUrl('http://www.example.com/index.php?view=other');
		$item->setParams(array());
		$this->assertFalse($item->isActive());
	}
}
