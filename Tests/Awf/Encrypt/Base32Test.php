<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Tests\Awf\Encrypt;


use Awf\Encrypt\Base32;

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

}
 