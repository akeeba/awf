<?php
/**
 * @package		awf
 * @copyright	2014-2016 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Document\Toolbar;

use Awf\Document\Toolbar\Button;
use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\ReflectionHelper;

/**
 * Class ButtonTest
 *
 * @package Awf\Tests\Document\Toolbar
 *
 * @coversDefaultClass \Awf\Document\Toolbar
 */
class ButtonTest extends AwfTestCase
{
	protected function setUp($resetContainer = true)
	{
		parent::setUp(false);
	}

	/**
	 * @group   Button
	 */
	public function testConstruct()
	{
		$data = array(
			'class'		=> 'testClass',
			'icon'		=> 'testIcon',
			'title'		=> 'testTitle',
			'id'		=> 'testId',
			'onClick'	=> 'testOnClick',
			'url'		=> 'testURL',
			'invalid'	=> 'testInvalid'
		);

		$button = new Button($data);

		foreach ($data as $k => $v)
		{
			if ($k == 'invalid')
			{
				continue;
			}

			$this->assertEquals($v, ReflectionHelper::getValue($button, $k));
		}
	}

	/**
	 * @group   Button
	 */
	public function testGetId()
	{
		$data = array(
			'class'		=> 'testClass',
			'icon'		=> 'testIcon',
			'title'		=> 'testTitle',
			'id'		=> 'testId',
			'onClick'	=> 'testOnClick',
			'url'		=> 'testURL',
			'invalid'	=> 'testInvalid'
		);

		$button = new Button($data);

		$button->setId(null);
		$button->setTitle('Foo Bar');

		$this->assertEquals('FooBar', $button->getId());

		$button->setId(null);
		$button->setTitle('!@#$%^&*()some!@#$%^&*()-string_mate');

		$this->assertEquals('some-string_mate', $button->getId());
	}
}
