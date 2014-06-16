<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Registry;
use Awf\Registry\AbstractRegistryFormat;

/**
 * @coversDefaultClass Awf\Registry\AbstractRegistryFormat
 *
 * @package Awf\Tests\Registry
 */
class AbstractRegistryFormatTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Data provider for testGetInstance
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function seedTestGetInstance()
	{
		return array(
			array('Xml'),
			array('Ini'),
			array('Json'),
			array('Php'),
		);
	}

	/**
	 * Test the AbstractRegistryFormat::getInstance method.
	 *
	 * @param   string  $format  The format to load
	 *
	 * @return  void
	 *
	 * @dataProvider  seedTestGetInstance
	 * @since         1.0
	 */
	public function testGetInstance($format)
	{
		$class = '\\Awf\\Registry\\Format\\' . $format;

		$object = AbstractRegistryFormat::getInstance($format);
		$this->assertThat(
			$object instanceof $class,
			$this->isTrue()
		);
	}

	/**
	 * Test getInstance with a non-existent format.
	 *
	 * @return  void
	 *
	 * @expectedException  \InvalidArgumentException
	 * @since              1.0
	 */
	public function testGetInstanceNonExistent()
	{
		AbstractRegistryFormat::getInstance('Troll');
	}
}