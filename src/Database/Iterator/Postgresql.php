<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Database\Iterator;

class Postgresql extends AbstractIterator
{

	/**
	 * @inheritDoc
	 */
	#[\ReturnTypeWillChange]
	public function count()
	{
		return pg_num_rows($this->cursor);
	}

	/**
	 * @inheritDoc
	 */
	protected function fetchObject()
	{
		return pg_fetch_object($this->cursor, null, $this->class);
	}

	/**
	 * @inheritDoc
	 */
	protected function freeResult()
	{
		pg_free_result($this->cursor);
	}
}