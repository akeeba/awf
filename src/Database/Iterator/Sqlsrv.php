<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Database\Iterator;

class Sqlsrv extends AbstractIterator
{

	/**
	 * @inheritDoc
	 */
	#[\ReturnTypeWillChange]
	public function count()
	{
		return sqlsrv_num_rows($this->cursor);
	}

	/**
	 * @inheritDoc
	 */
	protected function fetchObject()
	{
		return sqlsrv_fetch_object($this->cursor, $this->class);
	}

	/**
	 * @inheritDoc
	 */
	protected function freeResult()
	{
		sqlsrv_free_stmt($this->cursor);
	}
}