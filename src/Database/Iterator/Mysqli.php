<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Database\Iterator;

class Mysqli extends AbstractIterator
{

	/**
	 * @inheritDoc
	 */
	#[\ReturnTypeWillChange]
	public function count()
	{
		return mysqli_num_rows($this->cursor);
	}

	/**
	 * @inheritDoc
	 */
	protected function fetchObject()
	{
		return mysqli_fetch_object($this->cursor, $this->class);
	}

	/**
	 * @inheritDoc
	 */
	protected function freeResult()
	{
		mysqli_free_result($this->cursor);
	}
}