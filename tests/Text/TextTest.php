<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Text;

use Awf\Text\Text;
use Awf\Tests\Helpers\ReflectionHelper;

/**
 * Tests for the Awf\Text\Text class
 *
 * @coversDefaultClass Awf\Text\Text
 *
 * @package            Awf\Tests\Text
 */
class TextTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @covers ::detectLanguage
	 *
	 * @dataProvider getTestDetectLanguage
	 *
	 * @param string $acceptLang The mocked Accept-language HTTP header content
	 * @param string $suffix     The translation suffix
	 * @param string $expected   The expected language returned
	 */
	public function testDetectLanguage($acceptLang, $suffix, $expected)
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = $acceptLang;

		$result = Text::detectLanguage('lang', $suffix, __DIR__ . '/../data');

		$this->assertEquals(
			$expected,
			$result
		);
	}

	/**
	 * @covers ::addIniProcessCallback
	 */
	public function testAddIniProcessCallback()
	{
		// Make sure there are no callbacks
		ReflectionHelper::setValue('\Awf\Text\Text', 'iniProcessCallbacks', array());

		// Try adding a callback
		Text::addIniProcessCallback(array('\\Awf\\Tests\\Stubs\\Text\\TextCallbacks', 'donada'));

		$this->assertEquals(
			array(array('\\Awf\\Tests\\Stubs\\Text\\TextCallbacks', 'donada')),
			ReflectionHelper::getValue('\Awf\Text\Text', 'iniProcessCallbacks')
		);
	}

	/**
	 * @covers ::loadLanguage
	 */
	public function testLoadLanguage()
	{
		ReflectionHelper::setValue('\Awf\Text\Text', 'strings', array());

		// Make sure there are no auto-loaded strings
		$this->assertEmpty(
			ReflectionHelper::getValue('\Awf\Text\Text', 'strings'),
			'Line: ' . __LINE__ . '.'
		);

		// Load a language for the first time
		// Make sure there are no callbacks
		ReflectionHelper::setValue('\Awf\Text\Text', 'iniProcessCallbacks', array());
		Text::loadLanguage('en-GB', 'lang', '.ini', true, __DIR__ . '/../data');
		$strings = ReflectionHelper::getValue('\Awf\Text\Text', 'strings');

		$this->assertCount(
			2,
			$strings,
			'Line: ' . __LINE__ . '.'
		);

		$this->assertArrayHasKey(
			'LBL_SIMPLE',
			$strings,
			'Line: ' . __LINE__ . '.'
		);

		$this->assertEquals(
			'Simple',
			$strings['LBL_SIMPLE'],
			'Line: ' . __LINE__ . '.'
		);

		// Loading the same app & lang without overwrite should have no effect
		Text::loadLanguage('el-GR', 'lang', '.ini', false, __DIR__ . '/../data');
		$strings = ReflectionHelper::getValue('\Awf\Text\Text', 'strings');

		$this->assertEquals(
			'Simple',
			$strings['LBL_SIMPLE'],
			'Line: ' . __LINE__ . '.'
		);

		// Loading the same app & lang with overwrite should replace the contents
		Text::loadLanguage('el-GR', 'lang', '.ini', true, __DIR__ . '/../data');
		$strings = ReflectionHelper::getValue('\Awf\Text\Text', 'strings');

		$this->assertNotEquals(
			'Simple',
			$strings['LBL_SIMPLE'],
			'Line: ' . __LINE__ . '.'
		);

		// Make sure there are no callbacks and strings
		ReflectionHelper::setValue('\Awf\Text\Text', 'iniProcessCallbacks', array());
		ReflectionHelper::setValue('\Awf\Text\Text', 'strings', array());

		// Load a language from a structured subdirectory for the first time
		Text::loadLanguage('en-GB', 'lang', '.ini', true, __DIR__ . '/../data/lang');
		$strings = ReflectionHelper::getValue('\Awf\Text\Text', 'strings');

		$this->assertCount(
			2,
			$strings,
			'Line: ' . __LINE__ . '.'
		);

		$this->assertArrayHasKey(
			'LBL_SIMPLE',
			$strings,
			'Line: ' . __LINE__ . '.'
		);

		$this->assertEquals(
			'Simple',
			$strings['LBL_SIMPLE'],
			'Line: ' . __LINE__ . '.'
		);

		// Make sure there are no callbacks and strings
		ReflectionHelper::setValue('\Awf\Text\Text', 'iniProcessCallbacks', array());
		ReflectionHelper::setValue('\Awf\Text\Text', 'strings', array());

		// Load a language from a structured subdirectory for the first time using language autodetection
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en;q=1, el;q=0.8; de;q=0.7';
		Text::loadLanguage(null, 'lang', '.ini', true, __DIR__ . '/../data');
		$strings = ReflectionHelper::getValue('\Awf\Text\Text', 'strings');

		$this->assertCount(
			2,
			$strings,
			'Line: ' . __LINE__ . '.'
		);

		$this->assertArrayHasKey(
			'LBL_SIMPLE',
			$strings,
			'Line: ' . __LINE__ . '.'
		);

		$this->assertEquals(
			'Simple',
			$strings['LBL_SIMPLE'],
			'Line: ' . __LINE__ . '.'
		);

		// Make sure there are no callbacks and strings
		ReflectionHelper::setValue('\Awf\Text\Text', 'iniProcessCallbacks', array());
		ReflectionHelper::setValue('\Awf\Text\Text', 'strings', array());

		// Add a dumb callback and make sure it doesn't do anything
		Text::addIniProcessCallback(array('\\Awf\\Tests\\Stubs\\Text\\TextCallbacks', 'donada'));
		Text::loadLanguage('en-GB', 'lang', '.ini', true, __DIR__ . '/../data');
		$strings = ReflectionHelper::getValue('\Awf\Text\Text', 'strings');

		$this->assertEquals(
			'Simple',
			$strings['LBL_SIMPLE'],
			'Line: ' . __LINE__ . '.'
		);

		// Add a file blocker callback and make sure it doesn't load any strings
		ReflectionHelper::setValue('\Awf\Text\Text', 'iniProcessCallbacks', array());
		ReflectionHelper::setValue('\Awf\Text\Text', 'strings', array());
		Text::addIniProcessCallback(array('\\Awf\\Tests\\Stubs\\Text\\TextCallbacks', 'block'));
		Text::loadLanguage('en-GB', 'lang', '.ini', true, __DIR__ . '/../data');
		$strings = ReflectionHelper::getValue('\Awf\Text\Text', 'strings');

		$this->assertEmpty(
			$strings,
			'Line: ' . __LINE__ . '.'
		);

		// Add a transformation callback and make sure it modifies strings
		ReflectionHelper::setValue('\Awf\Text\Text', 'iniProcessCallbacks', array());
		ReflectionHelper::setValue('\Awf\Text\Text', 'strings', array());
		Text::addIniProcessCallback(array('\\Awf\\Tests\\Stubs\\Text\\TextCallbacks', 'preprocess'));
		Text::loadLanguage('en-GB', 'lang', '.ini', true, __DIR__ . '/../data');
		$strings = ReflectionHelper::getValue('\Awf\Text\Text', 'strings');

		$this->assertEquals(
			'Foo Simple',
			$strings['LBL_SIMPLE'],
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * @covers ::_
	 */
	public function testUnderscore()
	{
		ReflectionHelper::setValue('\Awf\Text\Text', 'iniProcessCallbacks', array());
		ReflectionHelper::setValue('\Awf\Text\Text', 'strings', array());
		Text::loadLanguage('en-GB', 'lang', '.ini', true, __DIR__ . '/../data');

		$this->assertEquals(
			'Simple',
			Text::_('LBL_SIMPLE'),
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * @covers ::sprintf
	 */
	public function testSprintf()
	{
		ReflectionHelper::setValue('\Awf\Text\Text', 'iniProcessCallbacks', array());
		ReflectionHelper::setValue('\Awf\Text\Text', 'strings', array());
		Text::loadLanguage('en-GB', 'lang', '.ini', true, __DIR__ . '/../data');

		$this->assertEquals(
			'Param Foo',
			Text::sprintf('LBL_PARAMS', 'Foo'),
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * @covers ::hasKey
	 */
	public function testHasKey()
	{
		ReflectionHelper::setValue('\Awf\Text\Text', 'iniProcessCallbacks', array());
		ReflectionHelper::setValue('\Awf\Text\Text', 'strings', array());
		Text::loadLanguage('en-GB', 'lang', '.ini', true, __DIR__ . '/../data');

		$this->assertTrue(
			Text::hasKey('LBL_SIMPLE'),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertTrue(
			Text::hasKey('LBL_PARAMS'),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertTrue(
			!Text::hasKey('YOSARIAN_LIVES'),
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * Data provider for testDetectLanguage
	 *
	 * @return array
	 */
	public function getTestDetectLanguage()
	{
		return array(
			array(
				'fr-ch;q=0.3, da, en-us;q=0.8, en;q=0.5, fr;q=0.3',
				'.ini',
				'en-GB'
			),
			array(
				'el-gr',
				'.foobar',
				'en-GB'
			),
			array(
				'el-gr',
				'.ini',
				'el-GR'
			),
			array(
				'el-gr;q=0.8, en-GB;q=0.7, de-DE;q=0.9',
				'.ini',
				'de-DE'
			),
			array(
				'el-cy;q=0.8, en-GB;q=0.7',
				'.ini',
				'el-GR'
			),
			array(
				'el;q=0.8, en-US;q=0.7',
				'.ini',
				'el-GR'
			),
			array(
				'el;q=0.8, de-AT;q=0.9',
				'.ini',
				'de-DE'
			),
		);
	}
}
