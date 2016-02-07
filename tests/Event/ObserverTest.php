<?php
/**
 * @package		awf
 * @copyright	2014-2016 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Event;

use Awf\Event\Observable;
use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Event\FirstObserver;

class ObserverTest extends AwfTestCase
{
	/** @var Observable */
	private $dispatcher;

	public static $attachArguments = null;

	public function testConstructor()
	{
		$dummy = new FirstObserver($this->dispatcher);

		$this->assertEquals($dummy, self::$attachArguments);
		$this->assertEquals($this->dispatcher, ReflectionHelper::getValue($dummy, 'subject'));
	}

	public function testGetObservableEvents()
	{
		$dummy = new FirstObserver($this->dispatcher);

		$observableEvents = $dummy->getObservableEvents();

		$this->assertEquals(array(
			'returnConditional',
			'identifyYourself',
			'chain',
		), $observableEvents);
	}

	protected function setUp($resetContainer = true)
	{
		parent::setUp();

		$this->dispatcher = $this->getMockBuilder('\\Awf\\Event\\Observable')
			->disableOriginalConstructor()
			->setMethods(array('attach', 'detach', 'trigger'))
			->getMock();

		$this->dispatcher
			->expects($this->any())
			->method('attach')
			->will($this->returnCallback(function($arg){
				ObserverTest::$attachArguments = $arg;
			}));

		$this->dispatcher
			->expects($this->any())
			->method('detach')
			->willReturnSelf();

		$this->dispatcher
			->expects($this->any())
			->method('trigger')
			->willReturn(array());
	}
}
