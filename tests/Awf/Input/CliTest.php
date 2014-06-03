<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Tests\Awf\Input;

use Awf\Input\Cli;

/**
 * Test class for Awf\Input\Cli.
 *
 * @since  1.0
 */
class CliTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Test the Awf\Input\Cli::get method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Cli::get
	 * @since   1.0
	 */
	public function testGet()
	{
		$_SERVER['argv'] = array('/dev/null', '--foo=bar', '-ab', 'blah', '-g', 'flower sakura');
		$instance = new Cli(null, array('filter' => new \Tests\Stubs\Input\FilterMock));

		$this->assertThat(
			$instance->get('foo'),
			$this->identicalTo('bar'),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertThat(
			$instance->get('a'),
			$this->identicalTo(true),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertThat(
			$instance->get('b'),
			$this->identicalTo(true),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertThat(
			$instance->args,
			$this->equalTo(array('blah')),
			'Line: ' . __LINE__ . '.'
		);

		// Default filter
		$this->assertEquals(
			'flower sakura',
			$instance->get('g'),
			'Default filter should be string. Line: ' . __LINE__
		);
	}

	/**
	 * Test the Awf\Input\Cli::get method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Cli::get
	 * @since   1.0
	 */
	public function testParseLongArguments()
	{
		$_SERVER['argv'] = array('/dev/null', '--ab', 'cd', '--ef', '--gh=bam');
		$instance = new Cli(null, array('filter' => new \Tests\Stubs\Input\FilterMock));

		$this->assertThat(
			$instance->get('ab'),
			$this->identicalTo('cd'),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertThat(
			$instance->get('ef'),
			$this->identicalTo(true),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertThat(
			$instance->get('gh'),
			$this->identicalTo('bam'),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertThat(
			$instance->args,
			$this->equalTo(array()),
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * Test the Awf\Input\Cli::get method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Cli::get
	 * @since   1.0
	 */
	public function testParseShortArguments()
	{
		$_SERVER['argv'] = array('/dev/null', '-ab', '-c', '-e', 'f', 'foobar', 'ghijk');
		$instance = new Cli(null, array('filter' => new \Tests\Stubs\Input\FilterMock));

		$this->assertThat(
			$instance->get('a'),
			$this->identicalTo(true),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertThat(
			$instance->get('b'),
			$this->identicalTo(true),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertThat(
			$instance->get('c'),
			$this->identicalTo(true),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertThat(
			$instance->get('e'),
			$this->identicalTo('f'),
			'Line: ' . __LINE__ . '.'
		);

		$this->assertThat(
			$instance->args,
			$this->equalTo(array('foobar', 'ghijk')),
			'Line: ' . __LINE__ . '.'
		);
	}

	/**
	 * Test the Awf\Input\Cli::get method.
	 *
	 * @return  void
	 *
	 * @covers  Awf\Input\Cli::get
	 * @since   1.0
	 */
	public function testGetFromServer()
	{
		$instance = new Cli(null, array('filter' => new \Tests\Stubs\Input\FilterMock));

		// Check the object type.
		$this->assertInstanceOf(
			'\Awf\\Input\\Input',
			$instance->server,
			'Line: ' . __LINE__ . '.'
		);

		// Test the get method.
		$this->assertThat(
			$instance->server->get('PHP_SELF', null, 'raw'),
			$this->identicalTo($_SERVER['PHP_SELF']),
			'Line: ' . __LINE__ . '.'
		);
	}
}
 