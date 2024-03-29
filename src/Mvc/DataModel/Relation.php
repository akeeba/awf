<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Mvc\DataModel;


use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Container\ContainerAwareTrait;
use Awf\Database\Query;
use Awf\Mvc\DataModel;
use Awf\Text\Language;
use Awf\Text\LanguageAwareInterface;
use Awf\Text\LanguageAwareTrait;

abstract class Relation implements ContainerAwareInterface, LanguageAwareInterface
{
	use ContainerAwareTrait;
	use LanguageAwareTrait;

	/** @var   DataModel  The data model we are attached to */
	protected $parentModel = null;

	/** @var   string  The name of the model of the foreign key */
	protected $foreignModelClass = null;

	/**
	 * The application name of the foreign model
	 *
	 * @var   string
	 * @deprecated
	 */
	protected $foreignModelApp = null;

	/** @var Container|null The container of the foreign model */
	protected $foreignModelContainer = null;

	/** @var   string  The bade name of the foreign model */
	protected $foreignModelName = null;

	/** @var   string   The local table key for this relation */
	protected $localKey = null;

	/** @var   string   The foreign table key for this relation */
	protected $foreignKey = null;

	/** @var   null  For many-to-many relations, the pivot (glue) table */
	protected $pivotTable = null;

	/** @var   null  For many-to-many relations, the pivot table's column storing the local key */
	protected $pivotLocalKey = null;

	/** @var   null  For many-to-many relations, the pivot table's column storing the foreign key */
	protected $pivotForeignKey = null;

	/** @var   Collection  The data loaded by this relation */
	protected $data = null;

	/** @var array Maps each local table key to an array of foreign table keys, used in many-to-many relations */
	protected $foreignKeyMap = [];

	/**
	 * The language object for creating foreign models.
	 *
	 * @var   Language|null
	 * @since 1.2.0
	 */
	protected $foreignModelLanguage = null;

	/**
	 * Public constructor. Initialises the relation.
	 *
	 * @param   DataModel    $parentModel        The data model we are attached to
	 * @param   string       $foreignModelClass  The class name of the foreign key's model
	 * @param   string|null  $localKey           The local table key for this relation
	 * @param   string|null  $foreignKey         The foreign key for this relation
	 * @param   string|null  $pivotTable         For many-to-many relations, the pivot (glue) table
	 * @param   string|null  $pivotLocalKey      For many-to-many relations, the pivot table's column storing the local
	 *                                           key
	 * @param   string|null  $pivotForeignKey    For many-to-many relations, the pivot table's column storing the
	 *                                           foreign key
	 */
	public function __construct(
		DataModel $parentModel, string $foreignModelClass, ?string $localKey = null, ?string $foreignKey = null,
		?string $pivotTable = null, ?string $pivotLocalKey = null, ?string $pivotForeignKey = null,
		?Container $foreignModelContainer = null, ?Language $foreignModelLanguage = null
	)
	{
		$this->setContainer($parentModel->getContainer());
		$this->setLanguage($this->getContainer()->language);
		$this->parentModel           = $parentModel;
		$this->localKey              = $localKey;
		$this->foreignKey            = $foreignKey;
		$this->pivotTable            = $pivotTable;
		$this->pivotLocalKey         = $pivotLocalKey;
		$this->pivotForeignKey       = $pivotForeignKey;
		$this->foreignModelContainer = $foreignModelContainer;
		$this->foreignModelLanguage  = $foreignModelLanguage;
		$this->foreignModelClass     = $foreignModelClass;

		if (empty($foreignModelContainer))
		{
			$class = ltrim($foreignModelClass, '\\');
			$foreignParts           = explode('\\', $class);
			$this->foreignModelApp  = $foreignParts[0];
			$this->foreignModelName = $foreignParts[2];
		}
		else
		{
			$this->foreignModelApp = null;
		}
	}

	/**
	 * Reset the relation data
	 *
	 * @return $this For chaining
	 */
	public function reset()
	{
		$this->data          = null;
		$this->foreignKeyMap = [];

		return $this;
	}

	/**
	 * Rebase the relation to a different model
	 *
	 * @param   DataModel  $model
	 *
	 * @return $this For chaining
	 */
	public function rebase(DataModel $model)
	{
		$this->parentModel = $model;

		return $this->reset();
	}

	public function getForeignContainer(): Container
	{
		if (
			empty($this->foreignModelContainer)
		    && (
				empty($this->foreignModelApp)
				|| $this->foreignModelApp === $this->getContainer()->application_name
			)
		)
		{
			$this->foreignModelContainer = $this->getContainer();
		}

		return $this->foreignModelContainer ?: Application::getInstance($this->foreignModelApp)->getContainer();
	}

	public function getForeignLanguage(): Language
	{
		return $this->foreignModelLanguage ?: $this->getForeignContainer()->language;
	}

	/**
	 * Get the relation data.
	 *
	 * If you want to apply additional filtering to the foreign model, use the $callback. It can be any function,
	 * static method, public method or closure with an interface of function(DataModel $foreignModel). You are not
	 * supposed to return anything, just modify $foreignModel's state directly. For example, you may want to do:
	 * $foreignModel->setState('foo', 'bar')
	 *
	 * @param   callable|null  $callback  The callback to run on the remote model.
	 * @param   Collection     $dataCollection
	 *
	 * @return Collection|DataModel
	 */
	public function getData(?callable $callback = null, Collection $dataCollection = null)
	{
		if (is_null($this->data))
		{
			// Initialise
			$this->data = new Collection();

			// Get a model instance
			$container = $this->getForeignContainer();
			/** @var DataModel $foreignModel */
			$foreignModel = $container->mvcFactory
				->makeTempModel($this->foreignModelName, $this->getForeignLanguage())
				->setIgnoreRequest(true);

			$filtered = $this->filterForeignModel($foreignModel, $dataCollection);

			if (!$filtered)
			{
				return $this->data;
			}

			// Apply the callback, if applicable
			if (!is_null($callback) && is_callable($callback))
			{
				call_user_func($callback, $foreignModel);
			}

			// Get the list of items from the foreign model and cache in $this->data
			$this->data = $foreignModel->get(true);
		}

		return $this->data;
	}

	/**
	 * Populates the internal $this->data collection from the contents of the provided collection. This is used by
	 * DataModel to push the eager loaded data into each item's relation.
	 *
	 * @param   Collection  $data    The relation data to push into this relation
	 * @param   mixed       $keyMap  Used by many-to-many relations to pass around the local to foreign key map
	 *
	 * @return void
	 */
	public function setDataFromCollection(Collection &$data, $keyMap = null)
	{
		$this->data = new Collection();

		if (!empty($data))
		{
			$localKeyValue = $this->parentModel->getFieldValue($this->localKey);

			/** @var DataModel $item */
			foreach ($data as $key => $item)
			{
				if ($item->getFieldValue($this->foreignKey) == $localKeyValue)
				{
					$this->data->add($item);
				}
			}
		}
	}

	/**
	 * Returns the count subquery for DataModel's has() and whereHas() methods.
	 *
	 * @return Query
	 */
	abstract public function getCountSubquery();

	/**
	 * Returns a new item of the foreignModel type, pre-initialised to fulfil this relation
	 *
	 * @return DataModel
	 *
	 * @throws DataModel\Relation\Exception\NewNotSupported when it's not supported
	 */
	abstract public function getNew();

	/**
	 * Saves all related items. You can use it to touch items as well: every item being saved causes the modified_by and
	 * modified_on fields to be changed automatically, thanks to the DataModel's magic.
	 */
	public function saveAll()
	{
		if ($this->data instanceof Collection)
		{
			foreach ($this->data as $item)
			{
				if ($item instanceof DataModel)
				{
					$item->save();
				}
			}
		}
	}

	/**
	 * Returns the foreign key map of a many-to-many relation, used for eager loading many-to-many relations
	 *
	 * @return array
	 */
	public function &getForeignKeyMap()
	{
		return $this->foreignKeyMap;
	}

	/**
	 * Applies the relation filters to the foreign model when getData is called
	 *
	 * @param   DataModel   $foreignModel    The foreign model you're operating on
	 * @param   Collection  $dataCollection  If it's an eager loaded relation, the collection of loaded parent records
	 *
	 * @return boolean Return false to force an empty data collection
	 */
	abstract protected function filterForeignModel(DataModel $foreignModel, Collection $dataCollection = null);
}
