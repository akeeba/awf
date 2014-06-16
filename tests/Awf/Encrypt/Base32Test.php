<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Tests\Awf\Encrypt;


use Awf\Encrypt\Base32;
use Tests\Helpers\ReflectionHelper;

/**
 * @coversDefaultClass Awf\Encrypt\Base32
 *
 * @package Tests\Awf\Encrypt
 */
class Base32Test extends \PHPUnit_Framework_TestCase
{
	/** @var  Base32 */
	protected $base32;

	/**
	 * @return  void
	 */
	protected function setUp()
	{

		$this->base32 = new Base32();
	}

	/**
	 *
	 * @return  void
	 */
	public function testEncode()
	{
		$this->assertEquals('MFRGGZDFMZTWQ2LLNRWW433QOFZHG5DVOZ3XQ6L2GEZDGNBVGY3TQOJQIFBEGRCFIZDUQSKLJRGU4T2QKFJFGVCVKZLVQWK2FIRS2LRMEERMFJZEEUTC6KBJHU7UAQCALQVA',
			$this->base32->encode('abcdefghiklmnopqrstuvwxyz1234567890ABCDEFGHIKLMNOPQRSTUVWXYZ*#-.,!"§$%&/()=?@@@\*')
		);
	}

	/**
	 * [testGetVar description]
	 *
	 * @return  void
	 */
	public function testDecode()
	{
		$this->assertEquals('abcdefghiklmnopqrstuvwxyz1234567890ABCDEFGHIKLMNOPQRSTUVWXYZ*#-.,!"§$%&/()=?@@@\*',
			$this->base32->decode('MFRGGZDFMZTWQ2LLNRWW433QOFZHG5DVOZ3XQ6L2GEZDGNBVGY3TQOJQIFBEGRCFIZDUQSKLJRGU4T2QKFJFGVCVKZLVQWK2FIRS2LRMEERMFJZEEUTC6KBJHU7UAQCALQVA')
		);
	}

	public function testDecodeWithCrapData()
	{
		$this->setExpectedException('\Exception');
		$this->base32->decode('Crap data');
	}

	/**
	 * @dataProvider getTestBin2StrExceptions
	 */
	public function testBin2StrExceptions($crapData, $message)
	{
		$this->setExpectedException('\Exception');
		ReflectionHelper::invoke($this->base32, 'bin2str', $crapData);
	}

	/**
	 * @dataProvider getTestBin2StrExceptions
	 */
	public function testFromBinExceptions($crapData, $message)
	{
		$this->setExpectedException('\Exception');
		ReflectionHelper::invoke($this->base32, 'fromBin', $crapData);
	}

	public function getTestBin2StrExceptions()
	{
		return array(
			array('101010101', 'Not divisable by 8'),
			array('0A0A0A0A', 'Not binary data'),
		);
	}
}
 