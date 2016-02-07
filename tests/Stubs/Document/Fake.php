<?php
/**
 * @package		awf
 * @copyright	2014-2016 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Document;


use Awf\Document\Document;

class Fake extends Document
{
	/**
	 * Each document class implements its own renderer which outputs the buffer
	 * to the browser using the appropriate template.
	 *
	 * @return  void
	 */
	public function render()
	{
		// Do nothing, actually
		return null;
	}
}