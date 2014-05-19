<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 *
 * Based on Laravel 4's Illuminate\Database\Eloquent\Collection
 *
 * Laravel 4 is distributed under the MIT license, see https://github.com/laravel/framework/blob/master/LICENSE.txt
 */

namespace Awf\Mvc\DataModel;

use Awf\Utils\Collection as BaseCollection;

class Collection extends BaseCollection
{
	/**
	 * Find a model in the collection by key.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $default
	 *
	 * @return DataModel
	 */
	public function find($key, $default = null)
	{
		if ($key instanceof DataModel)
		{
			$key = $key->getId();
		}

		return array_first($this->items, function($itemKey, $model) use ($key)
		{
			return $model->getId() == $key;

		}, $default);
	}

	/**
	 * Add an item to the collection.
	 *
	 * @param  mixed  $item
	 *
*@return Collection
	 */
	public function add($item)
	{
		$this->items[] = $item;

		return $this;
	}

	/**
	 * Determine if a key exists in the collection.
	 *
	 * @param  mixed  $key
	 *
	 * @return bool
	 */
	public function contains($key)
	{
		return ! is_null($this->find($key));
	}

	/**
	 * Fetch a nested element of the collection.
	 *
	 * @param  string  $key
	 *
*@return Collection
	 */
	public function fetch($key)
	{
		return new static(array_fetch($this->toArray(), $key));
	}

	/**
	 * Get the max value of a given key.
	 *
	 * @param  string  $key
	 *
	 * @return mixed
	 */
	public function max($key)
	{
		return $this->reduce(function($result, $item) use ($key)
		{
			return (is_null($result) || $item->{$key} > $result) ? $item->{$key} : $result;
		});
	}

	/**
	 * Get the min value of a given key.
	 *
	 * @param  string  $key
	 *
	 * @return mixed
	 */
	public function min($key)
	{
		return $this->reduce(function($result, $item) use ($key)
		{
			return (is_null($result) || $item->{$key} < $result) ? $item->{$key} : $result;
		});
	}

	/**
	 * Get the array of primary keys
	 *
	 * @return array
	 */
	public function modelKeys()
	{
		return array_map(function($m) { return $m->getId(); }, $this->items);
	}

	/**
	 * Merge the collection with the given items.
	 *
	 * @param  BaseCollection|array  $items
	 *
	 * @return BaseCollection
	 */
	public function merge($collection)
	{
		$dictionary = $this->getDictionary($this);

		foreach ($collection as $item)
		{
			$dictionary[$item->getId()] = $item;
		}

		return new static(array_values($dictionary));
	}

	/**
	 * Diff the collection with the given items.
	 *
	 * @param   BaseCollection|array  $items
	 *
	 * @return  BaseCollection
	 */
	public function diff($collection)
	{
		$diff = new static;

		$dictionary = $this->getDictionary($collection);

		foreach ($this->items as $item)
		{
			if ( ! isset($dictionary[$item->getId()]))
			{
				$diff->add($item);
			}
		}

		return $diff;
	}

	/**
	 * Intersect the collection with the given items.
	 *
	 * @param   BaseCollection|array  $items
	 *
	 * @return  Collection
	 */
	public function intersect($collection)
	{
		$intersect = new static;

		$dictionary = $this->getDictionary($collection);

		foreach ($this->items as $item)
		{
			if (isset($dictionary[$item->getId()]))
			{
				$intersect->getId($item);
			}
		}

		return $intersect;
	}

	/**
	 * Return only unique items from the collection.
	 *
	 * @return BaseCollection
	 */
	public function unique()
	{
		$dictionary = $this->getDictionary($this);

		return new static(array_values($dictionary));
	}

	/**
	 * Get a dictionary keyed by primary keys.
	 *
	 * @param  BaseCollection  $collection
	 *
	 * @return array
	 */
	protected function getDictionary($collection)
	{
		$dictionary = array();

		foreach ($collection as $value)
		{
			$dictionary[$value->getId()] = $value;
		}

		return $dictionary;
	}

	/**
	 * Get a base Support collection instance from this collection.
	 *
	 * @return BaseCollection
	 */
	public function toBase()
	{
		return new BaseCollection($this->items);
	}

} 