<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Database;

/**
 * Query Element Class.
 *
 * This class is adapted from the Joomla! Framework
 *
 * @property-read    string  $name      The name of the element.
 * @property-read    array   $elements  An array of elements.
 * @property-read    string  $glue      Glue piece.
 */
class QueryElement
{
	/**
	 * @var    string  The name of the element.
	 */
	protected $name = null;

	/**
	 * @var    array  An array of elements.
	 */
	protected $elements = null;

	/**
	 * @var    string  Glue piece.
	 */
	protected $glue = null;

	/**
	 * Constructor.
	 *
	 * @param   string  $name      The name of the element.
	 * @param   mixed   $elements  String or array.
	 * @param   string  $glue      The glue for elements.
	 */
	public function __construct($name, $elements, $glue = ',')
	{
		$this->elements = array();
		$this->name = $name;
		$this->glue = $glue;

		$this->append($elements);
	}

	/**
	 * Magic function to convert the query element to a string.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		if (substr($this->name, -2) == '()')
		{
			return PHP_EOL . substr($this->name, 0, -2) . '(' . implode($this->glue, $this->elements) . ')';
		}
		else
		{
			return PHP_EOL . $this->name . ' ' . implode($this->glue, $this->elements);
		}
	}

	/**
	 * Appends element parts to the internal list.
	 *
	 * @param   mixed  $elements  String or array.
	 *
	 * @return  void
	 */
	public function append($elements)
	{
		if (is_array($elements))
		{
			$this->elements = array_merge($this->elements, $elements);
		}
		else
		{
			$this->elements = array_merge($this->elements, array($elements));
		}
	}

	/**
	 * Gets the elements of this element.
	 *
	 * @return  string
	 */
	public function getElements()
	{
		return $this->elements;
	}

	public function getGlue()
	{
		return $this->glue;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * Method to provide deep copy support to nested objects and arrays
	 * when cloning.
	 *
	 * @return  void
	 */
	public function __clone()
	{
		foreach ($this as $k => $v)
		{
			if (\is_object($v))
			{
				$this->{$k} = clone $v;
			}
			elseif (\is_array($v))
			{
				foreach ($v as $i => $element)
				{
					if (\is_object($element))
					{
						$this->{$k}[$i] = clone $element;
					}
				}
			}
		}
	}
}
