<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Database\Iterator;

use Countable;
use InvalidArgumentException;
use Iterator;
use ReturnTypeWillChange;

abstract class AbstractIterator implements Countable, Iterator
{
	/**
	 * The database cursor.
	 *
	 * @var    mixed
	 */
	protected $cursor;

	/**
	 * The class of object to create.
	 *
	 * @var    string
	 */
	protected $class;

	/**
	 * The name of the column to use for the key of the database record.
	 *
	 * @var    mixed
	 */
	private $_column;

	/**
	 * The current database record.
	 *
	 * @var    mixed
	 */
	private $_current;

	/**
	 * A numeric or string key for the current database record.
	 *
	 * @var    int|string
	 */
	private $_key;

	/**
	 * The number of fetched records.
	 *
	 * @var    integer
	 */
	private $_fetched = 0;

	/**
	 * Database iterator constructor.
	 *
	 * @param   mixed        $cursor  The database cursor.
	 * @param   string|null  $column  An option column to use as the iterator key.
	 * @param   string       $class   The class of object that is returned.
	 *
	 */
	public function __construct($cursor, ?string $column = null, string $class = 'stdClass')
	{
		if (!class_exists($class))
		{
			throw new InvalidArgumentException(sprintf('new %s(*%s*, cursor)', get_class($this), gettype($class)));
		}

		$this->cursor   = $cursor;
		$this->class    = $class;
		$this->_column  = $column;
		$this->_fetched = 0;
		$this->next();
	}

	/**
	 * Moves forward to the next result from the SQL query.
	 *
	 * @return  void
	 *
	 * @see     Iterator::next()
	 */
	#[ReturnTypeWillChange]
	public function next()
	{
		// Set the default key as being the number of fetched object
		$this->_key = $this->_fetched;

		// Try to get an object
		$this->_current = $this->fetchObject();

		// If an object has been found
		if ($this->_current)
		{
			// Set the key as being the indexed column (if it exists)
			if (isset($this->_current->{$this->_column}))
			{
				$this->_key = $this->_current->{$this->_column};
			}

			// Update the number of fetched object
			$this->_fetched++;
		}
	}

	/**
	 * Method to fetch a row from the result set cursor as an object.
	 *
	 * @return  mixed  Either the next row from the result set or false if there are no more rows.
	 */
	abstract protected function fetchObject();

	/**
	 * Database iterator destructor.
	 */
	public function __destruct()
	{
		if ($this->cursor)
		{
			$this->freeResult($this->cursor);
		}
	}

	/**
	 * Method to free up the memory used for the result set.
	 *
	 * @return  void
	 */
	abstract protected function freeResult();

	/**
	 * The current element in the iterator.
	 *
	 * @return  object
	 *
	 * @see     Iterator::current()
	 */
	#[ReturnTypeWillChange]
	public function current()
	{
		return $this->_current;
	}

	/**
	 * The key of the current element in the iterator.
	 *
	 * @return  int|string
	 *
	 * @see     Iterator::key()
	 */
	#[ReturnTypeWillChange]
	public function key()
	{
		return $this->_key;
	}

	/**
	 * Rewinds the iterator.
	 *
	 * This iterator cannot be rewound.
	 *
	 * @return  void
	 *
	 * @see     Iterator::rewind()
	 */
	#[ReturnTypeWillChange]
	public function rewind() {}

	/**
	 * Checks if the current position of the iterator is valid.
	 *
	 * @return  boolean
	 *
	 * @see     Iterator::valid()
	 */
	#[ReturnTypeWillChange]
	public function valid()
	{
		return (boolean)$this->_current;
	}

}