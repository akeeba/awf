<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Utils;

use Awf\Utils\Buffer;

/**
 * Class BufferTest
 *
 * @package Awf\Tests\Utils
 *
 * @coversDefaultClass Awf\Utils\Buffer
 */
class BufferTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @covers Awf\Utils\Buffer::stream_open
	 *
	 * @return Buffer
	 */
	public function testStreamOpen()
	{
		$buffer = new Buffer();

		$dummy = null;
		$return = $buffer->stream_open('buffer://path/to/some/file', 'w', null, $dummy);

		$this->assertTrue($return);

		$this->assertEquals(
			'path',
			$buffer->name
		);

		$this->assertArrayHasKey(
			'path',
			$buffer->_buffers
		);

		$this->assertNull(
			$buffer->_buffers['path']
		);

		$this->assertEquals(
			0,
			$buffer->position
		);

		return $buffer;
	}

	/**
	 * @depends testStreamOpen
	 *
	 * @param Buffer $buffer
	 *
	 * @covers ::stream_write
	 *
	 * @return Buffer
	 */
	public function testStreamWrite(Buffer $buffer)
	{
		$written = $buffer->stream_write('0123456789');

		$this->assertEquals(
			10,
			$written
		);

		$this->assertEquals(
			10,
			$buffer->position
		);

		$this->assertEquals(
			'0123456789',
			$buffer->_buffers['path']
		);

		$buffer->position = 0;

		$written = $buffer->stream_write('ABCDE');

		$this->assertEquals(
			5,
			$written
		);

		$this->assertEquals(
			5,
			$buffer->position
		);

		$this->assertEquals(
			'ABCDE56789',
			$buffer->_buffers['path']
		);

		return $buffer;
	}

	/**
	 * @depends testStreamWrite
	 *
	 * @param Buffer $buffer
	 *
	 * @covers ::stream_read
	 */
	public function testStreamRead(Buffer $buffer)
	{
		$buffer->position = 0;

		$return = $buffer->stream_read(5);

		$this->assertEquals(
			'ABCDE',
			$return
		);

		$this->assertEquals(
			5,
			$buffer->position
		);

		$buffer->position = 1;

		$return = $buffer->stream_read(5);

		$this->assertEquals(
			6,
			$buffer->position
		);

		$this->assertEquals(
			'BCDE5',
			$return
		);

		$buffer->position = 0;

		$return = $buffer->stream_read(1024);

		$this->assertEquals(
			10,
			$buffer->position
		);

		$this->assertEquals(
			'ABCDE56789',
			$return
		);
	}

	/**
	 * @depends testStreamWrite
	 *
	 * @param Buffer $buffer
	 *
	 * @covers ::stream_tell
	 */
	public function testStreamTell(Buffer $buffer)
	{
		$positions = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

		foreach ($positions as $pos)
		{
			$buffer->position = $pos;

			$ret = $buffer->stream_tell();

			$this->assertEquals($pos, $ret);
		}
	}

	/**
	 * @depends testStreamWrite
	 *
	 * @param Buffer $buffer
	 *
	 * @covers ::stream_eof
	 */
	public function testStreamEof(Buffer $buffer)
	{
		$data = [
			[0, false],
			[5, false],
			[10, true]
		];

		foreach ($data as $datum)
		{
			list($pos, $expected) = $datum;

			$buffer->position = $pos;

			$ret = $buffer->stream_eof();

			$this->assertEquals($expected, $ret);
		}
	}

	/**
	 * @depends testStreamWrite
	 *
	 * @param Buffer $buffer
	 *
	 * @covers ::stream_seek
	 */
	public function testStreamSeek(Buffer $buffer)
	{
		$data = [
			[0, SEEK_SET, 0, true],
			[5, SEEK_SET, 5, true],
			[100, SEEK_SET, 5, false],
			[2, SEEK_CUR, 7, true],
			[5, SEEK_CUR, 12, true],
			[-6, SEEK_CUR, 12, false],
			[5, SEEK_SET, 5, true],
			[-5, SEEK_END, 5, true],
			[-10, SEEK_END, 0, true],
			[0, SEEK_END, 10, true],
			[-20, SEEK_END, 10, false],
		];

		foreach ($data as $point)
		{
			list($offset, $whence, $expectedPos, $expectedResult) = $point;

			$result = $buffer->stream_seek($offset, $whence);

			$this->assertEquals($expectedResult, $result);
			$this->assertEquals($expectedPos, $buffer->position);
		}
	}
}