<?php
/**
 * @package    awf
 * @copyright  Copyright (c)2014-2019 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license    GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Event;

use Awf\Event\Observer;

class FirstObserver extends Observer
{
	public $myId = 'one';

	public function returnConditional($stuff)
	{
		return ($stuff == $this->myId);
	}

	public function identifyYourself()
	{
		return $this->myId;
	}

	public function chain($stuff)
	{
		if ($stuff == $this->myId)
		{
			return $this->myId;
		}

		return null;
	}
}
