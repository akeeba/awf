<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Mvc\DataModel;


use Awf\Application\Application;
use Awf\Mvc\DataModel;
use Awf\Utils\Collection;

abstract class Relation
{
	/** @var   DataModel  The data model we are attached to */
	protected $parentModel = null;

	/** @var   string  The class name of the foreign key's model */
	protected $foreignModelClass = null;

	/** @var   string  The application name of the foreign model */
	protected $foreignModelApp = null;

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

	/** @var   DataModel\Collection  The data loaded by this relation */
	protected $data = null;

	/**
	 * Public constructor. Initialises the relation.
	 *
	 * @param   DataModel  $parentModel        The data model we are attached to
	 * @param   string     $foreignModelClass  The class name of the foreign key's model
	 * @param   string     $localKey           The local table key for this relation
	 * @param   string     $foreignKey         The foreign key for this relation
	 * @param   string     $pivotTable         For many-to-many relations, the pivot (glue) table
	 * @param   string     $pivotLocalKey      For many-to-many relations, the pivot table's column storing the local key
	 * @param   string     $pivotForeignKey    For many-to-many relations, the pivot table's column storing the foreign key
	 */
	public function __construct(DataModel $parentModel, $foreignModelClass, $localKey = null, $foreignKey = null, $pivotTable = null, $pivotLocalKey = null, $pivotForeignKey = null)
	{
		$this->parentModel = $parentModel;
		$this->foreignModelClass = $foreignModelClass;
		$this->localKey = $localKey;
		$this->foreignKey = $foreignKey;
		$this->pivotTable = $pivotTable;
		$this->pivotLocalKey = $pivotLocalKey;
		$this->pivotForeignKey = $pivotForeignKey;

		$class = $foreignModelClass;

		// Work around for PHP 5.3.0 - 5.3.2 https://bugs.php.net/50731
		if ('\\' == $class[0])
		{
			$class = substr($class, 1);
		}

		$foreignParts = explode('\\', $class );
		$this->foreignModelApp = $foreignParts[0];
		$this->foreignModelName = $foreignParts[2];
	}

	/**
	 * Get the relation data.
	 *
	 * If you want to apply additional filtering to the foreign model, use the $callback. It can be any function,
	 * static method, public method or closure with an interface of function(DataModel $foreignModel). You are not
	 * supposed to return anything, just modify $foreignModel's state directly. For example, you may want to do:
	 * $foreignModel->setState('foo', 'bar')
	 *
	 * @param callable              $callback        The callback to run on the remote model.
	 * @param \Awf\Utils\Collection $dataCollection
	 *
	 * @return DataModel\Collection
	 */
	public function &getData(callable $callback = null, \Awf\Utils\Collection $dataCollection = null)
	{
		if (is_null($this->data))
		{
			// Initialise
			$this->data = new Collection();

			// Get a model instance
			$container = Application::getInstance($this->foreignModelApp)->getContainer();
			/** @var DataModel $foreignModel */
			$foreignModel = DataModel::getTmpInstance($this->foreignModelApp, $this->foreignModelName, $container);

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
			$this->data = $foreignModel->get();
		}

		return $this->data;
	}

	/**
	 * Applies the relation filters to the foreign model when getData is called
	 *
	 * @param DataModel             $foreignModel    The foreign model you're operating on
	 * @param \Awf\Utils\Collection $dataCollection  If it's an eager loaded relation, the collection of loaded parent records
	 *
	 * @return boolean Return false to force an empty data collection
	 */
	abstract protected function filterForeignModel(DataModel $foreignModel, \Awf\Utils\Collection $dataCollection = null);

	/**
	 * Returns the count subquery for DataModel's has() and whereHas() methods.
	 *
	 * You may use the callable $callable to customise it. Its interface is function(\Awf|Database\Query $query). You
	 * are not supposed to return anything, just modify $query directly.
	 *
	 * @param callable $callback
	 *
	 * @return mixed
	 */
	abstract public function getCountSubquery(callable $callback = null);

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
}