<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Mvc\DataModel;

use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Container\ContainerAwareTrait;
use Awf\Inflector\Inflector;
use Awf\Mvc\DataModel;
use Awf\Mvc\DataModel\Relation\Exception\ForeignModelNotFound;
use Awf\Mvc\DataModel\Relation\Exception\RelationTypeNotFound;
use Awf\Text\Language;
use Awf\Text\LanguageAwareInterface;
use Awf\Text\LanguageAwareTrait;
use RuntimeException;

class RelationManager implements ContainerAwareInterface, LanguageAwareInterface
{
	use ContainerAwareTrait;
	use LanguageAwareTrait;

	/** @var DataModel The data model we are attached to */
	protected $parentModel = null;

	/** @var Relation[] The relations known to us */
	protected $relations = array();

	/** @var array A list of the names of eager loaded relations */
	protected $eager = array();

	/** @var array The known relation types */
	protected static $relationTypes = array();

	/**
	 * Creates a new relation manager for the defined parent model
	 *
	 * @param DataModel $parentModel The model we are attached to
	 */
	public function __construct(DataModel $parentModel)
	{
		// Set the parent model
		$this->parentModel = $parentModel;
		$this->setContainer($this->parentModel->getContainer());
		$this->setLanguage($this->parentModel->getLanguage());

		// Make sure the relation types are initialised
		static::getRelationTypes();
	}

	/**
	 * Implements deep cloning of the relation object
	 */
	function __clone()
	{
		$relations = array();

		if (!empty($this->relations))
		{
			/** @var Relation $relation */
			foreach ($this->relations as $key => $relation)
			{
				$relations[$key] = clone($relation);
				$relations[$key]->reset();
			}
		}

		$this->relations = $relations;
	}

	/**
	 * Rebase a relation manager
	 *
	 * @param DataModel $parentModel
	 */
	public function rebase(DataModel $parentModel)
	{
		$this->parentModel = $parentModel;

		if (count($this->relations))
		{
			foreach ($this->relations as $name => $relation)
			{
				/** @var Relation $relation */
				$relation->rebase($parentModel);
			}
		}
	}

	/**
	 * Populates the internal $this->data collection of a relation from the contents of the provided collection. This is
	 * used by DataModel to push the eager loaded data into each item's relation.
	 *
	 * @param string     $name      Relation name
	 * @param Collection $data      The relation data to push into this relation
	 * @param mixed      $keyMap    Used by many-to-many relations to pass around the local to foreign key map
	 *
	 * @return void
	 *
	 * @throws Relation\Exception\RelationNotFound
	 */
	public function setDataFromCollection($name, Collection &$data, $keyMap = null)
	{
		if (!isset($this->relations[$name]))
		{
			throw new DataModel\Relation\Exception\RelationNotFound("Relation '$name' not found");
		}

		$this->relations[$name]->setDataFromCollection($data, $keyMap);
	}

	/**
	 * Populates the static map of relation type methods and relation handling classes
	 *
	 * @return array Key = method name, Value = relation handling class
	 */
	public static function getRelationTypes()
	{
		if (empty(static::$relationTypes))
		{
			$relationTypeDirectory = __DIR__ . '/Relation';
			$fs = new \DirectoryIterator($relationTypeDirectory);

			/** @var $file \DirectoryIterator */
			foreach ($fs as $file)
			{
				if ($file->isDir())
				{
					continue;
				}

				if ($file->getExtension() != 'php')
				{
					continue;
				}

				$baseName = ucfirst($file->getBasename('.php'));
				$methodName = strtolower($baseName[0]) . substr($baseName, 1);
				$className = '\\Awf\\Mvc\\DataModel\\Relation\\' . $baseName;

				if (!class_exists($className, true))
				{
					continue;
				}

				static::$relationTypes[$methodName] = $className;
			}
		}

		return static::$relationTypes;
	}

	/**
	 * Adds a relation to the relation manager
	 *
	 * @param   string          $name               The name of the relation as known to this relation manager, e.g. 'phone'
	 * @param   string          $type               The relation type, e.g. 'hasOne'
	 * @param   string|null     $foreignModelClass  The class name of the foreign key's model, e.g. '\Foobar\Phones'
	 * @param   string|null     $localKey           The local table key for this relation
	 * @param   string|null     $foreignKey         The foreign key for this relation
	 * @param   string|null     $pivotTable         For many-to-many relations, the pivot (glue) table
	 * @param   string|null     $pivotLocalKey      For many-to-many relations, the pivot table's column storing the local key
	 * @param   string|null     $pivotForeignKey    For many-to-many relations, the pivot table's column storing the foreign key
	 * @param   Container|null  $foreignKeyContainer
	 *
	 * @return DataModel The parent model, for chaining
	 *
	 * @throws ForeignModelNotFound when $foreignModelClass doesn't exist
	 * @throws RelationTypeNotFound when $type is not known
	 */
	public function addRelation(
		string $name, string $type, ?string $foreignModelClass = null, ?string $localKey = null,
		?string $foreignKey = null, ?string $pivotTable = null, ?string $pivotLocalKey = null,
		?string $pivotForeignKey = null, ?Container $foreignKeyContainer = null, ?Language $foreignKeyLanguage = null
	)
	{
		if (!isset(static::$relationTypes[$type]))
		{
			throw new DataModel\Relation\Exception\RelationTypeNotFound("Relation type '$type' not found");
		}

		$foreignKeyContainer = $foreignKeyContainer ?? $this->getContainer();
		$foreignKeyLanguage = $foreignKeyLanguage ?? $foreignKeyContainer->language;

		if (empty($foreignModelClass) || !class_exists($foreignModelClass, true))
		{
			try
			{
				$model = $foreignKeyContainer->mvcFactory->makeTempModel($foreignModelClass, $foreignKeyLanguage);
			}
			catch (RuntimeException $e)
			{
				// Guess the foreign model class if necessary
				$parentClass = get_class($this->parentModel);
				$classNameParts = explode('\\', $parentClass);
				array_pop($classNameParts);
				$classPrefix = implode('\\', $classNameParts);

				$foreignModelClass = $classPrefix . '\\' . ucfirst($name);

				if (!class_exists($foreignModelClass, true))
				{
					$foreignModelClass = $classPrefix . '\\' . ucfirst(Inflector::pluralize($name));
				}

				if (!class_exists($foreignModelClass, true))
				{
					throw new DataModel\Relation\Exception\ForeignModelNotFound("Foreign model '$foreignModelClass' for relation '$name' not found");
				}
			}
		}

		$className = static::$relationTypes[$type];
		/** @var Relation $relation */
		$relation = new $className($this->parentModel, $foreignModelClass, $localKey, $foreignKey,
			$pivotTable, $pivotLocalKey, $pivotForeignKey, $foreignKeyContainer);

		$this->relations[$name] = $relation;

		return $this->parentModel;
	}

	/**
	 * Removes a known relation
	 *
	 * @param string $name The name of the relation to remove
	 *
	 * @return DataModel The parent model, for chaining
	 */
	public function removeRelation($name)
	{
		if (isset($this->relations[$name]))
		{
			unset ($this->relations[$name]);
		}

		return $this->parentModel;
	}

	/**
	 * Removes all known relations
	 */
	public function resetRelations()
	{
		$this->relations = array();
	}

	/**
	 * Returns a list of all known relations' names
	 *
	 * @return array
	 */
	public function getRelationNames()
	{
		return array_keys($this->relations);
	}

	/**
	 * Gets the related items of a relation
	 *
	 * @param string                $name           The name of the relation to return data for
	 *
	 * @return Relation
	 *
	 * @throws Relation\Exception\RelationNotFound
	 */
	public function &getRelation($name)
	{
		if (!isset($this->relations[$name]))
		{
			throw new DataModel\Relation\Exception\RelationNotFound("Relation '$name' not found");
		}

		return $this->relations[$name];
	}


	/**
	 * Get a new related item which satisfies relation $name and adds it to this relation's data list.
	 *
	 * @param string $name The relation based on which a new item is returned
	 *
	 * @return DataModel
	 *
	 * @throws Relation\Exception\RelationNotFound
	 */
	public function getNew($name)
	{
		if (!isset($this->relations[$name]))
		{
			throw new DataModel\Relation\Exception\RelationNotFound("Relation '$name' not found");
		}

		return $this->relations[$name]->getNew();
	}

	/**
	 * Saves all related items belonging to the specified relation or, if $name is null, all known relations which
	 * support saving.
	 *
	 * @param null|string $name The relation to save, or null to save all known relations
	 *
	 * @return DataModel The parent model, for chaining
	 *
	 * @throws Relation\Exception\RelationNotFound
	 */
	public function save($name = null)
	{
		if (is_null($name))
		{
			foreach ($this->relations as $name => $relation)
			{
				try
				{
					$relation->saveAll();
				}
				catch (DataModel\Relation\Exception\SaveNotSupported $e)
				{
					// We don't care if a relation doesn't support saving
				}
			}
		}
		else
		{
			if (!isset($this->relations[$name]))
			{
				throw new DataModel\Relation\Exception\RelationNotFound("Relation '$name' not found");
			}

			$this->relations[$name]->saveAll();
		}

		return $this->parentModel;
	}

	/**
	 * Gets the related items of a relation
	 *
	 * @param string                $name           The name of the relation to return data for
	 * @param callable              $callback       A callback to customise the returned data
	 * @param \Awf\Utils\Collection $dataCollection Used when fetching the data of an eager loaded relation
	 *
	 * @see Relation::getData()
	 *
	 * @return Collection|DataModel
	 *
	 * @throws Relation\Exception\RelationNotFound
	 */
	public function getData($name, $callback = null, \Awf\Utils\Collection $dataCollection = null)
	{
		if (!isset($this->relations[$name]))
		{
			throw new DataModel\Relation\Exception\RelationNotFound("Relation '$name' not found");
		}

		return $this->relations[$name]->getData($callback, $dataCollection);
	}

	/**
	 * Gets the foreign key map of a many-to-many relation
	 *
	 * @param string                $name           The name of the relation to return data for
	 *
	 * @return array
	 *
	 * @throws Relation\Exception\RelationNotFound
	 */
	public function &getForeignKeyMap($name)
	{
		if (!isset($this->relations[$name]))
		{
			throw new DataModel\Relation\Exception\RelationNotFound("Relation '$name' not found");
		}

		return $this->relations[$name]->getForeignKeyMap();
	}

	public function getCountSubquery($name)
	{
		if (!isset($this->relations[$name]))
		{
			throw new DataModel\Relation\Exception\RelationNotFound("Relation '$name' not found");
		}

		return $this->relations[$name]->getCountSubquery();
	}

	/**
	 * A magic method which allows us to define relations using shorthand notation, e.g. $manager->hasOne('phone')
	 * instead of $manager->addRelation('phone', 'hasOne')
	 *
	 * You can also use it to get data of a relation using shorthand notation, e.g. $manager->getPhone($callback)
	 * instead of $manager->getData('phone', $callback);
	 *
	 * @param string $name      The magic method to call
	 * @param array  $arguments The arguments to the magic method
	 *
	 * @return DataModel The parent model, for chaining
	 *
	 * @throws \InvalidArgumentException
	 * @throws DataModel\Relation\Exception\RelationTypeNotFound
	 */
	function __call($name, $arguments)
	{
		$numberOfArguments = count($arguments);

		if (isset(static::$relationTypes[$name]))
		{
			if ($numberOfArguments < 1)
			{
				throw new \InvalidArgumentException("You can not create an unnamed '$name' relation");
			}

			$relName = array_shift($arguments);
			$arguments = array_merge([$relName, $name], array_values($arguments));

			return $this->addRelation(...$arguments);
		}
		elseif (substr($name, 0, 3) == 'get')
		{
			$relationName = substr($name, 3);
			$relationName = strtolower($relationName[0]) . substr($relationName, 1);

			if($numberOfArguments > 2)
			{
				throw new \InvalidArgumentException("Invalid number of arguments getting data for the '$relationName' relation");
			}

			return $this->getData($relationName, ...$arguments);
		}

		// Throw an exception otherwise
		throw new DataModel\Relation\Exception\RelationTypeNotFound("Relation type '$name' not known to relation manager");
	}

	/**
	 * Is $name a magic-callable method?
	 *
	 * @param string $name The name of a potential magic-callable method
	 *
	 * @return bool
	 */
	public function isMagicMethod($name)
	{
		if (isset(static::$relationTypes[$name]))
		{
			return true;
		}
		elseif (substr($name, 0, 3) == 'get')
		{
			$relationName = substr($name, 3);
			$relationName = strtolower($relationName[0]) . substr($relationName, 1);

			if (isset($this->relations[$relationName]))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Is $name a magic property? Corollary: returns true if a relation of this name is known to the relation manager.
	 *
	 * @param string $name The name of a potential magic property
	 *
	 * @return bool
	 */
	public function isMagicProperty($name)
	{
		return isset($this->relations[$name]);
	}

	/**
	 * Magic method to get the data of a relation using shorthand notation, e.g. $manager->phone instead of
	 * $manager->getData('phone')
	 *
	 * @param $name
	 *
	 * @return Collection
	 */
	function __get($name)
	{
		return $this->getData($name);
	}
}
