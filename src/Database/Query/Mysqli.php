<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Database\Query;

use Awf\Database;

/**
 * Query Building Class.
 *
 * This class is adapted from the Joomla! Framework
 */
class Mysqli extends Database\Query implements Database\QueryLimitable
{
	/**
	 * @var    integer  The offset for the result set.
	 */
	protected $offset;

	/**
	 * @var    integer  The limit for the result set.
	 */
	protected $limit;

	/**
	 * Method to modify a query already in string format with the needed
	 * additions to make the query limited to a particular number of
	 * results, or start at a particular offset.
	 *
	 * @param   string   $query   The query in string format
	 * @param   integer  $limit   The limit for the result set
	 * @param   integer  $offset  The offset for the result set
	 *
	 * @return string
	 *
	 */
	public function processLimit($query, $limit, $offset = 0)
	{
		if ($limit > 0 || $offset > 0)
		{
			$query .= ' LIMIT ' . $offset . ', ' . $limit;
		}

		return $query;
	}

	/**
	 * Concatenates an array of column names or values.
	 *
	 * @param   array   $values     An array of values to concatenate.
	 * @param   string  $separator  As separator to place between each value.
	 *
	 * @return  string  The concatenated values.
	 *
	 */
	public function concatenate($values, $separator = null)
	{
		if ($separator)
		{
			$concat_string = 'CONCAT_WS(' . $this->quote($separator);

			foreach ($values as $value)
			{
				$concat_string .= ', ' . $value;
			}

			return $concat_string . ')';
		}
		else
		{
			return 'CONCAT(' . implode(',', $values) . ')';
		}
	}

	/**
	 * Sets the offset and limit for the result set, if the database driver supports it.
	 *
	 * Usage:
	 * $query->setLimit(100, 0); (retrieve 100 rows, starting at first record)
	 * $query->setLimit(50, 50); (retrieve 50 rows, starting at 50th record)
	 *
	 * @param   integer  $limit   The limit for the result set
	 * @param   integer  $offset  The offset for the result set
	 *
	 * @return  \Awf\Database\Query  Returns this object to allow chaining.
	 *
	 */
	public function setLimit($limit = 0, $offset = 0)
	{
		$this->limit  = (int) $limit;
		$this->offset = (int) $offset;

		return $this;
	}

	/**
	 * Bound parameters are not supported by this driver.
	 *
	 * The signature matches QueryPreparable::bind(), so that a subclass can implement the interface.
	 *
	 * @param   mixed  $key            Ignored.
	 * @param   mixed  &$value         Ignored.
	 * @param   int    $dataType       Ignored.
	 * @param   int    $length         Ignored.
	 * @param   array  $driverOptions  Ignored.
	 *
	 * @return  void
	 *
	 * @throws  \BadMethodCallException  Always.
	 */
	public function bind($key = null, &$value = null, $dataType = \PDO::PARAM_STR, $length = 0, $driverOptions = array())
	{
		throw new \BadMethodCallException('Bound parameters are not supported by ' . static::class . '; use quote() or quoteName().');
	}

	/**
	 * Bound parameters are not supported by this driver.
	 *
	 * @param   mixed  $key  Ignored.
	 *
	 * @return  void
	 *
	 * @throws  \BadMethodCallException  Always.
	 */
	public function &getBounded($key = null)
	{
		throw new \BadMethodCallException('Bound parameters are not supported by ' . static::class . '; use quote() or quoteName().');
	}
}
