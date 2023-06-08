<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Database\Iterator;

use PDOStatement;

class Pdo extends AbstractIterator
{
	#[\ReturnTypeWillChange]
	public function count()
	{
		if (!empty($this->cursor) && $this->cursor instanceof PDOStatement)
		{
			return $this->cursor->rowCount();
		}

		return 0;
	}

	protected function fetchObject()
	{
		if (!empty($this->cursor) && $this->cursor instanceof PDOStatement)
		{
			return $this->cursor->fetchObject($this->class);
		}

		return false;
	}

	protected function freeResult()
	{
		if (empty($this->cursor) || !$this->cursor instanceof PDOStatement)
		{
			return;
		}

		$this->cursor->closeCursor();
	}
}