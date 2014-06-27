<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Event;

use Awf\Event\Observable;

class SecondObserver extends FirstObserver
{
	function __construct(Observable &$subject)
	{
		parent::__construct($subject); // TODO: Change the autogenerated stub

		$this->myId = 'two';
	}

	public function onlySecond()
	{
		return 'only second';
	}
}