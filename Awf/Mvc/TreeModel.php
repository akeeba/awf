<?php
/**
 * @package		awf
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Mvc;

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Inflector\Inflector;
use Awf\Mvc\DataModel\RelationManager;
use Awf\Utils\String;

/**
 * A DataModel which implements nested trees
 *
 * @package Awf\Mvc
 *
 * @property int $lft Left value (for nested set implementation)
 * @property int $rgt Right value (for nested set implementation)
 * @property string $hash Slug hash (for faster searching)
 */
class TreeModel extends DataModel
{
	/** @var int The level (depth) of this node in the tree */
	protected $treeDepth = null;

	/** @var TreeModel The root node in the tree */
	protected $treeRoot = null;

	/** @var TreeModel The parent node of ourselves */
	protected $treeParent = null;

	/** @var bool Should I perform a nested get (used to query ascendants/descendants) */
	protected $treeNestedGet = false;

	/**
	 * Public constructor. Overrides the parent constructor, making sure there are lft/rgt columns which make it
	 * compatible with nested sets.
	 *
	 * @see \Awf\Mvc\DataModel::__construct()
	 *
	 * @param Container $container
	 *
	 * @throws \RuntimeException When lft/rgt columns are not found
	 */
	public function __construct(\Awf\Container\Container $container = null)
	{
		parent::__construct($container);

		if (!$this->hasField('lft') || !$this->hasField('rgt'))
		{
			throw new \RuntimeException("Table $this->tableName is not compatible with TreeModel: it does not have lft/rgt columns");
		}
	}

	/**
	 * Overrides the automated table checks to handle the 'hash' column for faster searching
	 *
	 * @return $this|DataModel
	 */
	public function check()
	{
		parent::check();

		// Create a slug if there is a title and an empty slug
		if ($this->hasField('title') && $this->hasField('slug') && empty($this->slug))
		{
			$this->slug = String::toSlug($this->title);
		}

		// Create the SHA-1 hash of the slug for faster searching (make sure the hash column is CHAR(64) to take
		// advantage of MySQL's optimised searching for fixed size CHAR columns)
		if ($this->hasField('hash') && $this->hasField('slug'))
		{
			$this->hash = sha1($this->slug);
		}

		// Reset cached values
		$this->resetTreeCache();

		return $this;
	}

	/**
	 * Delete a node, either the currently loaded one or the one specified in $id. If an $id is specified that node
	 * is loaded before trying to delete it. In the end the data model is reset. If the node has any children nodes
	 * they will be removed before the node itself is deleted if $recursive == true (default: true).
	 *
	 * @param   mixed $id        Primary key (id field) value
	 * @param   bool  $recursive Should I recursively delete any nodes in the subtree? (default: true)
	 *
	 * @return  $this  for chaining
	 */
	public function forceDelete($id = null, $recursive = true)
	{
		// Load the specified record (if necessary)
		if (!empty($id))
		{
			$this->findOrFail($id);
		}

		// Recursively delete all children nodes as long as we are not a leaf node and $recursive is enabled
		if ($recursive && !$this->isLeaf())
		{
			// Get a reference to the database
			$db = $this->getDbo();

			// Get my lft/rgt values
			$myLeft = $this->lft;
			$myRight = $this->rgt;

			$fldLft = $db->qn($this->getFieldAlias('lft'));
			$fldRgt = $db->qn($this->getFieldAlias('rgt'));

			// Get all sub-nodes
			$subNodes = $this->getClone()->reset()
				->whereRaw($fldLft . ' > ' . $fldLft)
				->whereRaw($fldRgt . ' < ' . $fldRgt)
				->get(true);

			// Delete all subnodes (goes through the model to trigger the observers)
			if (!empty($subNodes))
			{
				array_walk($subNodes, function($item, $key){
					/** @var TreeModel $item */
					$item->forceDelete(null, false);
				});
			}
		}

		// Finally delete the node itself
		parent::delete($id);

		return $this;
	}

	/**
	 * Not supported in nested sets
	 *
	 * @param   string $where Ignored
	 *
	 * @return  static  Self, for chaining
	 *
	 * @throws  \RuntimeException
	 */
	public function reorder($where = '')
	{
		throw new \RuntimeException('reorder() is not supported by TreeModel');
	}

	/**
	 * Not supported in nested sets
	 *
	 * @param   integer $delta   Ignored
	 * @param   string  $where   Ignored
	 *
	 * @return  static  Self, for chaining
	 *
	 * @throws  \RuntimeException
	 */
	public function move($delta, $where = '')
	{
		throw new \RuntimeException('move() is not supported by TreeModel');
	}

	/**
	 * Create a new record with the provided data. It is inserted as the last child of the current node's parent
	 *
	 * @param   array $data The data to use in the new record
	 *
	 * @return  static  The new node
	 */
	public function create($data)
	{
		$newNode = $this->reset()->bind($data);

		if ($this->isRoot())
		{
			return $newNode->insertAsChildOf($this);
		}
		else
		{
			return $newNode->insertAsChildOf($this->getParent());
		}
	}

	/**
	 * Makes a copy of the record, inserting it as the last child of the current node's parent.
	 *
	 * @return static
	 */
	public function copy()
	{
		return $this->create($this->toArray());
	}

	/**
	 * Reset the record data and the tree cache
	 *
	 * @param   boolean $useDefaults Should I use the default values? Default: yes
	 *
	 * @return  static  Self, for chaining
	 */
	public function reset($useDefaults = true)
	{
		$this->resetTreeCache();

		return parent::reset($useDefaults);
	}

	/**
	 * Insert the current node as a tree root. It is a good idea to never use this method, instead providing a root node
	 * in your schema installation and then sticking to only one root.
	 *
	 * @return DataModel
	 */
	public function insertAsRoot()
	{
		// First we need to find the right value of the last parent, a.k.a. the max(rgt) of the table
		$db = $this->getDbo();

		// Get the lft/rgt names
		$fldRgt = $db->qn($this->getFieldAlias('rgt'));

		$query = $db->getQuery(true)
			->select('MAX(' . $fldRgt . ')')
			->from($db->qn($this->tableName));
		$maxRgt = $db->setQuery($query, 0, 1)->loadResult();

		if (empty($maxRgt))
		{
			$maxRgt = 0;
		}

		$this->lft = ++$maxRgt;
		$this->rgt = ++$maxRgt;

		return $this->save();
	}

	/**
	 * Insert the current node as the first (leftmost) child of a parent node.
	 *
	 * WARNING: If it's an existing node it will be COPIED, not moved.
	 *
	 * @param TreeModel $parentNode The node which will become our parent
	 *
	 * @return $this for chaining
	 * @throws \Exception
	 */
	public function insertAsFirstChildOf(TreeModel $parentNode)
	{
		// Get a reference to the database
		$db = $this->getDbo();

		// Get the field names
		$fldRgt = $db->qn($this->getFieldAlias('rgt'));
		$fldLft = $db->qn($this->getFieldAlias('lft'));

		// Get the value of the parent node's rgt
		$myRight = $parentNode->rgt;

		// Update my lft/rgt values
		$this->lft = $myRight;
		$this->rgt = $myRight;

		// Wrap everything in a transaction
		$db->transactionStart();

		try
		{
			// Make a hole (2 queries)
			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldRgt . ' = ' . $fldRgt . '+2')
				->where($fldRgt . '>=' . $db->q($myRight));
			$db->setQuery($query)->execute();

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . '+2')
				->where($fldLft . '>' . $db->q($myRight));
			$db->setQuery($query)->execute();

			// Insert the new node
			$this->save();

			// Commit the transaction
			$db->transactionCommit();
		}
		catch (\Exception $e)
		{
			// Roll back the transaction on error
			$db->transactionRollback();

			throw $e;
		}

		return $this;
	}

	/**
	 * Insert the current node as the last (rightmost) child of a parent node.
	 *
	 * WARNING: If it's an existing node it will be COPIED, not moved.
	 *
	 * @param TreeModel $parentNode The node which will become our parent
	 *
	 * @return $this for chaining
	 * @throws \Exception
	 */
	public function insertAsLastChildOf(TreeModel $parentNode)
	{
		// Get a reference to the database
		$db = $this->getDbo();

		// Get the field names
		$fldRgt = $db->qn($this->getFieldAlias('rgt'));
		$fldLft = $db->qn($this->getFieldAlias('lft'));

		// Get the value of the parent node's lft
		$myLeft = $parentNode->lft;

		// Update my lft/rgt values
		$this->lft = $myLeft + 1;
		$this->rgt = $myLeft + 2;

		// Wrap everything in a transaction
		$db->transactionStart();

		try
		{
			// Make a hole (2 queries)
			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . '+2')
				->where($fldLft . '>' . $db->q($myLeft));
			$db->setQuery($query)->execute();

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldRgt . ' = ' . $fldRgt . '+2')
				->where($fldLft . '>' . $db->q($fldLft));
			$db->setQuery($query)->execute();

			// Insert the new node
			$this->save();

			// Commit the transaction
			$db->transactionCommit();
		}
		catch (\Exception $e)
		{
			// Roll back the transaction on error
			$db->transactionRollback();

			throw $e;
		}

		return $this;
	}

	/**
	 * Alias for insertAsLastchildOf
	 *
	 * @param $parentNode
	 */
	public function insertAsChildOf(TreeModel $parentNode)
	{
		return $this->insertAsLastChildOf($parentNode);
	}

	/**
	 * Insert the current node to the left of (before) a sibling node
	 *
	 * WARNING: If it's an existing node it will be COPIED, not moved.
	 *
	 * @param TreeModel $siblingNode We will be inserted before this node
	 *
	 * @return $this for chaining
	 * @throws \Exception
	 */
	public function insertLeftOf(TreeModel $siblingNode)
	{
		// Get a reference to the database
		$db = $this->getDbo();

		// Get the field names
		$fldRgt = $db->qn($this->getFieldAlias('rgt'));
		$fldLft = $db->qn($this->getFieldAlias('lft'));

		// Get the value of the parent node's rgt
		$myRight = $siblingNode->rgt;

		// Update my lft/rgt values
		$this->lft = $myRight + 1;
		$this->rgt = $myRight + 2;

		$db->transactionStart();

		try
		{
			$db->setQuery(
				$db->getQuery(true)
					->update($db->qn($this->tableName))
					->set($fldRgt . ' = ' . $fldRgt . '+2')
					->where($fldRgt . ' > ' . $db->q($myRight))
			)->execute();

			$db->setQuery(
				$db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . '+2')
				->where($fldLft . ' > ' . $db->q($myRight))
			)->execute();

			$this->save();
		}
		catch (\Exception $e)
		{
			$db->transactionRollback();

			throw $e;
		}

		return $this;
	}

	/**
	 * Insert the current node to the right of (after) a sibling node
	 *
	 * WARNING: If it's an existing node it will be COPIED, not moved.
	 *
	 * @param TreeModel $siblingNode We will be inserted after this node
	 *
	 * @return $this for chaining
	 * @throws \Exception
	 */
	public function insertRightOf(TreeModel $siblingNode)
	{
		// Get a reference to the database
		$db = $this->getDbo();

		// Get the field names
		$fldRgt = $db->qn($this->getFieldAlias('rgt'));
		$fldLft = $db->qn($this->getFieldAlias('lft'));

		// Get the value of the parent node's lft
		$myLeft = $siblingNode->lft;

		// Update my lft/rgt values
		$this->lft = $myLeft;
		$this->rgt = $myLeft + 1;

		$db->transactionStart();

		try
		{
			$db->setQuery(
				$db->getQuery(true)
					->update($db->qn($this->tableName))
					->set($fldLft . ' = ' . $fldLft . '+2')
					->where($fldLft . ' >= ' . $db->q($myLeft))
			)->execute();

			$db->setQuery(
				$db->getQuery(true)
					->update($db->qn($this->tableName))
					->set($fldRgt . ' = ' . $fldRgt . '+2')
					->where($fldRgt . ' > ' . $db->q($myLeft))
			)->execute();

			$this->save();
		}
		catch (\Exception $e)
		{
			$db->transactionRollback();

			throw $e;
		}

		return $this;

		/**

		 */
		/**/
	}

	/**
	 * Alias for insertRightOf
	 *
	 * @param TreeModel $siblingNode
	 *
	 * @return $this for chaining
	 */
	public function insertAsSiblingOf(TreeModel $siblingNode)
	{
		return $this->insertRightOf($siblingNode);
	}

	/**
	 * Move the current node (and its subtree) one position to the left in the tree, i.e. before its left-hand sibling
	 *
	 * @return $this
	 */
	public function moveLeft()
	{
		// If it is a root node we will not move the node (roots don't participate in tree ordering)
		if ($this->isRoot())
		{
			return $this;
		}

		// Are we already the leftmost node?
		$parentNode = $this->getParent();
		if ($parentNode->lft == ($this->lft - 1))
		{
			return $this;
		}

		// Get the sibling to the left
		$db = $this->getDbo();
		$leftSibling = $this->getClone()->reset()
			->whereRaw($db->qn($this->getFieldAlias('rgt') . ' = ' . $db->q($this->rgt - 1)))
			->firstOrFail();

		// Move the node
		return $this->moveToLeftOf($leftSibling);
	}

	/**
	 * Move the current node (and its subtree) one position to the right in the tree, i.e. after its right-hand sibling
	 *
	 * @return $this
	 */
	public function moveRight()
	{
		// If it is a root node we will not move the node (roots don't participate in tree ordering)
		if ($this->isRoot())
		{
			return $this;
		}

		// Are we already the rightmost node?
		$parentNode = $this->getParent();
		if ($parentNode->rgt == ($this->rgt + 1))
		{
			return $this;
		}

		// Get the sibling to the right
		$db = $this->getDbo();
		$rightSibling = $this->getClone()->reset()
			->whereRaw($db->qn($this->getFieldAlias('lft') . ' = ' . $db->q($this->rgt + 1)))
			->firstOrFail();

		// Move the node
		return $this->moveToRightOf($rightSibling);
	}

	/**
	 * Moves the current node (and its subtree) to the left of another node. The other node can be in a different
	 * position in the tree or even under a different root.
	 *
	 * @param TreeModel $siblingNode
	 *
	 * @return $this for chaining
	 *
	 * @throws \Exception
	 */
	public function moveToLeftOf(TreeModel $siblingNode)
	{
		$db = $this->getDbo();

		// Get left/right names
		$fldLft = $db->qn($this->getFieldAlias('lft'));
		$fldRgt = $db->qn($this->getFieldAlias('rgt'));

		// Get node metrics
		$myLeft = $this->lft;
		$myRight = $this->rgt;
		$myWidth = $myRight - $myLeft + 1;

		// Get parent metrics
		$parent = $this->getParent();
		$pRight = $parent->rgt;
		$pLeft = $parent->lft;

		// Get far right value
		$query = $db->setQuery(true)
			->select('MAX(' . $fldRgt . ')')
			->from($db->qn($this->tableName));
		$rRight = $db->setQuery($query)->loadResult();
		$moveRight = $rRight + $myWidth - $myLeft + 1;
		$moveLeft = $myLeft + $moveRight - $pLeft;

		// If the parent's left was less than the moved node's left then the hole has moved to the right.
		$holeRight = ($pLeft < $myLeft) ? $myWidth : 0;

		try
		{
			// Start the transaction
			$db->transactionStart();

			// Move subtree as new root
			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . ' + ' . $db->q($moveRight))
				->set($fldRgt . ' = ' . $fldRgt . ' + ' . $db->q($moveRight))
				->where($fldLft . ' >= ' . $db->q($myLeft))
				->where($fldLft . ' <= ' . $db->q($myRight));
			$db->setQuery($query)->execute();

			// Make hole to the left of the sibling
			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldRgt . ' = ' . $fldRgt . ' + ' . $db->q($myWidth))
				->where($fldLft . ' >= ' . $db->q($pLeft))
				->where($fldLft . ' < ' . $db->q($rRight + $myWidth + 1));
			$db->setQuery($query)->execute();

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . ' + ' . $db->q($myWidth))
				->where($fldLft . ' >= ' . $db->q($pLeft))
				->where($fldLft . ' < ' . $db->q($rRight + $myWidth + 1));
			$db->setQuery($query)->execute();

			// Move subtree in the hole

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . ' - ' . $db->q($moveLeft))
				->where($fldLft . ' >= ' . $db->q($myLeft + $moveRight));
			$db->setQuery($query)->execute();

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldRgt . ' = ' . $fldRgt . ' - ' . $db->q($moveLeft))
				->where($fldRgt . ' >= ' . $db->q($myLeft + $moveRight));
			$db->setQuery($query)->execute();

			// Remove hole left behind by moved subtree.

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldRgt . ' = ' . $fldRgt . ' - ' . $db->q($myWidth))
				->where($fldRgt . ' > ' . $db->q($myRight + $holeRight));
			$db->setQuery($query)->execute();
			// UPDATE nestedset SET rgt = rgt - @myWidth WHERE rgt > (@myRight + @holeRight);

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . ' - ' . $db->q($myWidth))
				->where($fldLft . ' > ' . $db->q($myRight + $holeRight));
			$db->setQuery($query)->execute();

			// Commit the transaction
			$db->transactionCommit();
		}
		catch (\Exception $e)
		{
			$db->transactionRollback();

			throw $e;
		}

		return $this;
	}

	/**
	 * Moves the current node (and its subtree) to the right of another node. The other node can be in a different
	 * position in the tree or even under a different root.
	 *
	 * @param TreeModel $siblingNode
	 *
	 * @return $this for chaining
	 *
	 * @throws \Exception
	 */
	public function moveToRightOf(TreeModel $siblingNode)
	{
		$db = $this->getDbo();

		// Get left/right names
		$fldLft = $db->qn($this->getFieldAlias('lft'));
		$fldRgt = $db->qn($this->getFieldAlias('rgt'));

		// Get node metrics
		$myLeft = $this->lft;
		$myRight = $this->rgt;
		$myWidth = $myRight - $myLeft + 1;

		// Get parent metrics
		$parent = $this->getParent();
		$pRight = $parent->rgt;
		$pLeft = $parent->lft;

		// Get far right value
		$query = $db->setQuery(true)
			->select('MAX(' . $fldRgt . ')')
			->from($db->qn($this->tableName));
		$rRight = $db->setQuery($query)->loadResult();
		$moveRight = $rRight + $myWidth - $myLeft + 1;
		$moveLeft = $myLeft + $moveRight - $pRight - 1;

		// If the parent's left was less than the moved node's left then the hole has moved to the right.
		$holeRight = ($pLeft < $myLeft) ? $myWidth : 0;

		try
		{
			// Start the transaction
			$db->transactionStart();

			// Move subtree as new root
			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . ' + ' . $db->q($moveRight))
				->set($fldRgt . ' = ' . $fldRgt . ' + ' . $db->q($moveRight))
				->where($fldLft . ' >= ' . $db->q($myLeft))
				->where($fldLft . ' <= ' . $db->q($myRight));
			$db->setQuery($query)->execute();

			// Make hole after sibling

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldRgt . ' = ' . $fldRgt . ' + ' . $db->q($myWidth))
				->where($fldRgt . ' > ' . $db->q($pRight))
				->where($fldLft . ' < ' . $db->q($rRight + $myWidth + 1));
			$db->setQuery($query)->execute();

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . ' + ' . $db->q($myWidth))
				->where($fldLft . ' > ' . $db->q($pRight))
				->where($fldRgt . ' < ' . $db->q($rRight + $myWidth + 1));
			$db->setQuery($query)->execute();

			// Move subtree in the hole

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . ' - ' . $db->q($moveLeft))
				->where($fldLft . ' >= ' . $db->q($myLeft + $moveRight));
			$db->setQuery($query)->execute();

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldRgt . ' = ' . $fldRgt . ' - ' . $db->q($moveLeft))
				->where($fldRgt . ' >= ' . $db->q($myLeft + $moveRight));
			$db->setQuery($query)->execute();

			// Remove hole left behind by moved subtree.

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldRgt . ' = ' . $fldRgt . ' - ' . $db->q($myWidth))
				->where($fldRgt . ' > ' . $db->q($myRight + $holeRight));
			$db->setQuery($query)->execute();

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . ' - ' . $db->q($myWidth))
				->where($fldLft . ' > ' . $db->q($myRight + $holeRight));
			$db->setQuery($query)->execute();

			// Commit the transaction
			$db->transactionCommit();
		}
		catch (\Exception $e)
		{
			$db->transactionRollback();

			throw $e;
		}

		return $this;
	}

	/**
	 * Alias for moveToRightOf
	 *
	 * @param TreeModel $siblingNode
	 *
	 * @return $this for chaining
	 */
	public function makeNextSiblingOf(TreeModel $siblingNode)
	{
		return $this->moveToRightOf($siblingNode);
	}

	/**
	 * Alias for makeNextSiblingOf
	 *
	 * @param TreeModel $siblingNode
	 *
	 * @return $this for chaining
	 */
	public function makeSiblingOf(TreeModel $siblingNode)
	{
		return $this->makeNextSiblingOf($siblingNode);
	}

	/**
	 * Alias for moveToLeftOf
	 *
	 * @param TreeModel $siblingNode
	 *
	 * @return $this for chaining
	 */
	public function makePreviousSiblingOf(TreeModel $siblingNode)
	{
		return $this->moveToLeftOf($siblingNode);
	}

	/**
	 * Moves a node and its subtree as a the first (leftmost) child of $parentNode
	 *
	 * @param TreeModel $parentNode
	 *
	 * @return $this for chaining
	 *
	 * @throws \Exception
	 */
	public function makeFirstChildOf(TreeModel $parentNode)
	{
		$db = $this->getDbo();

		// Get left/right names
		$fldLft = $db->qn($this->getFieldAlias('lft'));
		$fldRgt = $db->qn($this->getFieldAlias('rgt'));

		// Get node metrics
		$myLeft = $this->lft;
		$myRight = $this->rgt;
		$myWidth = $myRight - $myLeft + 1;

		// Get parent metrics
		$parent = $this->getParent();
		$pRight = $parent->rgt;
		$pLeft = $parent->lft;

		// Get far right value
		$query = $db->setQuery(true)
			->select('MAX(' . $fldRgt . ')')
			->from($db->qn($this->tableName));
		$rRight = $db->setQuery($query)->loadResult();
		$moveRight = $rRight + $myWidth - $myLeft + 1;
		$moveLeft = $myLeft + $moveRight - $pLeft - 1;

		// If the parent's left was less than the moved node's left then the hole has moved to the right.
		$holeRight = ($pLeft < $myLeft) ? $myWidth : 0;

		try
		{
			// Start the transaction
			$db->transactionStart();

			// Move subtree as new root
			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . ' + ' . $db->q($moveRight))
				->set($fldRgt . ' = ' . $fldRgt . ' + ' . $db->q($moveRight))
				->where($fldLft . ' >= ' . $db->q($myLeft))
				->where($fldLft . ' <= ' . $db->q($myRight));
			$db->setQuery($query)->execute();

			// Make hole to the left of the sibling
			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldRgt . ' = ' . $fldRgt . ' + ' . $db->q($myWidth))
				->where($fldLft . ' > ' . $db->q($pLeft))
				->where($fldLft . ' < ' . $db->q($rRight + $myWidth + 1));
			$db->setQuery($query)->execute();

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . ' + ' . $db->q($myWidth))
				->where($fldLft . ' > ' . $db->q($pLeft))
				->where($fldRgt . ' < ' . $db->q($rRight + $myWidth + 1));
			$db->setQuery($query)->execute();

			// Move subtree in the hole

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . ' - ' . $db->q($moveLeft))
				->where($fldLft . ' >= ' . $db->q($myLeft + $moveRight));
			$db->setQuery($query)->execute();

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldRgt . ' = ' . $fldRgt . ' - ' . $db->q($moveLeft))
				->where($fldRgt . ' >= ' . $db->q($myLeft + $moveRight));
			$db->setQuery($query)->execute();

			// Remove hole left behind by moved subtree.

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldRgt . ' = ' . $fldRgt . ' - ' . $db->q($myWidth))
				->where($fldRgt . ' > ' . $db->q($myRight + $holeRight));
			$db->setQuery($query)->execute();

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . ' - ' . $db->q($myWidth))
				->where($fldLft . ' > ' . $db->q($myRight + $holeRight));
			$db->setQuery($query)->execute();

			// Commit the transaction
			$db->transactionCommit();
		}
		catch (\Exception $e)
		{
			$db->transactionRollback();

			throw $e;
		}

		return $this;
	}

	/**
	 * Moves a node and its subtree as a the last (rightmost) child of $parentNode
	 *
	 * @param TreeModel $parentNode
	 *
	 * @return $this for chaining
	 *
	 * @throws \Exception
	 */
	public function makeLastChildOf(TreeModel $parentNode)
	{
		$db = $this->getDbo();

		// Get left/right names
		$fldLft = $db->qn($this->getFieldAlias('lft'));
		$fldRgt = $db->qn($this->getFieldAlias('rgt'));

		// Get node metrics
		$myLeft = $this->lft;
		$myRight = $this->rgt;
		$myWidth = $myRight - $myLeft + 1;

		// Get parent metrics
		$parent = $this->getParent();
		$pRight = $parent->rgt;
		$pLeft = $parent->lft;

		// Get far right value
		$query = $db->setQuery(true)
			->select('MAX(' . $fldRgt . ')')
			->from($db->qn($this->tableName));
		$rRight = $db->setQuery($query)->loadResult();
		$moveRight = $rRight + $myWidth - $myLeft + 1;
		$moveLeft = $myLeft + $moveRight - $pRight;

		// If the parent's left was less than the moved node's left then the hole has moved to the right.
		$holeRight = ($pLeft < $myLeft) ? $myWidth : 0;

		try
		{
			// Start the transaction
			$db->transactionStart();

			// Move subtree as new root
			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . ' + ' . $db->q($moveRight))
				->set($fldRgt . ' = ' . $fldRgt . ' + ' . $db->q($moveRight))
				->where($fldLft . ' >= ' . $db->q($myLeft))
				->where($fldLft . ' <= ' . $db->q($myRight));
			$db->setQuery($query)->execute();

			// Make hole after sibling

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldRgt . ' = ' . $fldRgt . ' + ' . $db->q($myWidth))
				->where($fldRgt . ' > ' . $db->q($pRight))
				->where($fldLft . ' < ' . $db->q($rRight + $myWidth + 1));
			$db->setQuery($query)->execute();

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . ' + ' . $db->q($myWidth))
				->where($fldLft . ' > ' . $db->q($pRight))
				->where($fldRgt . ' < ' . $db->q($rRight + $myWidth + 1));
			$db->setQuery($query)->execute();

			// Move subtree in the hole

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . ' - ' . $db->q($moveLeft))
				->where($fldLft . ' >= ' . $db->q($myLeft + $moveRight));
			$db->setQuery($query)->execute();

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldRgt . ' = ' . $fldRgt . ' - ' . $db->q($moveLeft))
				->where($fldRgt . ' >= ' . $db->q($myLeft + $moveRight));
			$db->setQuery($query)->execute();

			// Remove hole left behind by moved subtree.

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldRgt . ' = ' . $fldRgt . ' - ' . $db->q($myWidth))
				->where($fldRgt . ' > ' . $db->q($myRight + $holeRight));
			$db->setQuery($query)->execute();

			$query = $db->getQuery(true)
				->update($db->qn($this->tableName))
				->set($fldLft . ' = ' . $fldLft . ' - ' . $db->q($myWidth))
				->where($fldLft . ' > ' . $db->q($myRight + $holeRight));
			$db->setQuery($query)->execute();

			// Commit the transaction
			$db->transactionCommit();
		}
		catch (\Exception $e)
		{
			$db->transactionRollback();

			throw $e;
		}

		return $this;
	}

	/**
	 * Alias for makeLastChildOf
	 *
	 * @param TreeModel $parentNode
	 *
	 * @return $this for chaining
	 */
	public function makeChildOf(TreeModel $parentNode)
	{
		return $this->makeLastChildOf($parentNode);
	}

	/**
	 * Makes the current node a root (and moving its entire subtree along the way). This is achieved by moving the node
	 * to the right of its root node
	 *
	 * @return  $this  for chaining
	 */
	public function makeRoot()
	{
		// Make sure we are not a root
		if ($this->isRoot())
		{
			return $this;
		}

		// Get a reference to my root
		$myRoot = $this->getRoot();

		// Double check I am not a root
		if ($this->equals($myRoot))
		{
			return $this;
		}

		// Move myself to the right of my root
		return $this->moveToRightOf($myRoot);
	}

	/**
	 * Gets the level (depth) of this node in the tree. The result is cached in $this->treeDepth for faster retrieval.
	 *
	 * @return int|mixed
	 */
	public function getLevel()
	{
		if (is_null($this->treeDepth))
		{
			$db = $this->getDbo();

			$fldLft = $db->qn($this->getFieldAlias('lft'));
			$fldRgt = $db->qn($this->getFieldAlias('rgt'));

			$query = $db->getQuery(true)
				->select('(COUNT(' . $db->qn('parent') . '.' . $fldLft . ') - 1) AS ' . $db->qn('depth'))
				->from($db->qn($this->tableName), $db->qn('node'))
				->from($db->qn($this->tableName), $db->qn('parent'))
				->where($db->qn('node') . '.' . $fldLft . ' >= ' . $db->qn('parent') . '.' . $fldLft)
				->where($db->qn('node') . '.' . $fldLft . ' <= ' . $db->qn('parent') . '.' . $fldRgt)
				->where($db->qn('node') . '.' . $fldLft . ' = ' . $db->q($this->lft))
				->group($db->qn('node') . '.' . $fldLft)
				->order($db->qn('node') . '.' . $fldLft . ' ASC');

			$this->treeDepth = $db->setQuery($query, 0, 1)->loadResult();
		}

		return $this->treeDepth;
	}

	/**
	 * Returns the immediate parent of the current node
	 *
	 * @return static
	 */
	public function getParent()
	{
		if ($this->isRoot())
		{
			return $this;
		}

		if (empty($this->treeParent) || !is_object($this->treeParent) || !($this->treeParent instanceof TreeModel))
		{
			$db = $this->getDbo();

			$fldLft = $db->qn($this->getFieldAlias('lft'));
			$fldRgt = $db->qn($this->getFieldAlias('rgt'));

			$query = $db->getQuery(true)
				->select($db->qn('parent') . '.' . $fldLft)
				->from($db->qn($this->tableName), $db->qn('node'))
				->from($db->qn($this->tableName), $db->qn('parent'))
				->where($db->qn('node') . '.' . $fldLft . ' >= ' . $db->qn('parent') . '.' . $fldLft)
				->where($db->qn('node') . '.' . $fldLft . ' <= ' . $db->qn('parent') . '.' . $fldRgt)
				->where($db->qn('node') . '.' . $fldLft . ' = ' . $db->q($this->lft))
				->order($db->qn('parent') . '.' . $fldLft . ' DESC');
			$targetLft = $db->setQuery($query, 1, 1)->loadResult();

			$this->treeParent = $this->getClone()->reset()
				->whereRaw($fldLft . ' = ' . $db->qn($targetLft))
				->firstOrFail();
		}

		return $this->treeParent;
	}

	/**
	 * Is this a top-level root node?
	 *
	 * @return bool
	 */
	public function isRoot()
	{
		// If lft=1 it is necessarily a root node
		if ($this->lft == 1)
		{
			return true;
		}

		// Otherwise make sure its level is 0
		return $this->getLevel() == 0;
	}

	/**
	 * Is this a leaf node (a node without children)?
	 *
	 * @return bool
	 */
	public function isLeaf()
	{
		return ($this->rgt - 1) == $this->lft;
	}

	/**
	 * Is this a child node (not root)?
	 *
	 * @return bool
	 */
	public function isChild()
	{
		return !$this->isRoot();
	}

	public function isDescendantOf(TreeModel $otherNode)
	{
		// @todo returns true if node is a descendant of the other
	}

	public function isSelfOrDescendantOf(TreeModel $otherNode)
	{
		// @todo returns true if node is self or a descendant
	}

	public function isAncestorOf(TreeModel $otherNode)
	{
		// @todo returns true if node is an ancestor of the other
	}

	public function isSelfOrAncestorOf(TreeModel $otherNode)
	{
		// @todo returns true if node is self or an ancestor of the other
	}

	/**
	 * Is $node this very node?
	 *
	 * @param $node
	 *
	 * @return bool
	 */
	public function equals(TreeModel $node)
	{
		return ($this == $node);
	}

	public function insideSubtree(TreeModel $otherNode)
	{
		// @todo checks whether the given node is inside the subtree defined by the left and right indices of the current node
	}

	/**
	 * Returns true if both this node and $otherNode are root, leaf or child (same tree scope)
	 *
	 * @param TreeModel $otherNode
	 *
	 * @return bool
	 */
	public function inSameScope(TreeModel $otherNode)
	{
		if ($this->isLeaf())
		{
			return $otherNode->isLeaf();
		}
		elseif ($this->isRoot())
		{
			return $otherNode->isRoot();
		}
		elseif ($this->isChild())
		{
			return $otherNode->isChild();
		}
		else
		{
			return false;
		}
	}

	/**
	 * get() will return all ancestor nodes and ourselves
	 *
	 * @return void
	 */
	protected function scopeAncestorsAndSelf()
	{
		$this->treeNestedGet = true;

		$db = $this->getDbo();

		$fldLft = $db->qn($this->getFieldAlias('lft'));
		$fldRgt = $db->qn($this->getFieldAlias('rgt'));

		$this->whereRaw($db->qn('parent') . '.' . $fldLft . ' >= ' . $db->qn('node') . '.' . $fldLft);
		$this->whereRaw($db->qn('parent') . '.' . $fldLft . ' <= ' . $db->qn('node') . '.' . $fldRgt);
		$this->whereRaw($db->qn('parent') . '.' . $fldLft . ' = ' . $db->q($this->lft));
	}

	/**
	 * get() will return all ancestor nodes but not ourselves
	 *
	 * @return void
	 */
	protected function scopeAncestors()
	{
		$this->treeNestedGet = true;

		$db = $this->getDbo();

		$fldLft = $db->qn($this->getFieldAlias('lft'));
		$fldRgt = $db->qn($this->getFieldAlias('rgt'));

		$this->whereRaw($db->qn('parent') . '.' . $fldLft . ' > ' . $db->qn('node') . '.' . $fldLft);
		$this->whereRaw($db->qn('parent') . '.' . $fldLft . ' < ' . $db->qn('node') . '.' . $fldRgt);
		$this->whereRaw($db->qn('parent') . '.' . $fldLft . ' = ' . $db->q($this->lft));
	}

	/**
	 * get() will return all sibling nodes and ourselves
	 *
	 * @return void
	 */
	protected function scopeSiblingsAndSelf()
	{
		$db = $this->getDbo();

		$fldLft = $db->qn($this->getFieldAlias('lft'));
		$fldRgt = $db->qn($this->getFieldAlias('rgt'));

		$parent = $this->getParent();
		$this->whereRaw($db->qn('node') . '.' . $fldLft . ' > ' . $db->q($parent->lft));
		$this->whereRaw($db->qn('node') . '.' . $fldRgt . ' < ' . $db->q($parent->rgt));
	}

	/**
	 * get() will return all sibling nodes but not ourselves
	 *
	 * @return void
	 */
	protected function scopeSiblings()
	{
		$this->scopeSiblingsAndSelf();
		$this->scopeWithoutSelf();
	}

	/**
	 * get() will return only leaf nodes
	 *
	 * @return void
	 */
	protected function scopeLeaves()
	{
		$db = $this->getDbo();

		$fldLft = $db->qn($this->getFieldAlias('lft'));
		$fldRgt = $db->qn($this->getFieldAlias('rgt'));

		$this->whereRaw($db->qn('node') . '.' . $fldLft . ' = ' . $db->qn('node') . '.' .$fldRgt . ' - ' . $db->q(1));
	}

	/**
	 * get() will return all descendants (even subtrees of subtrees!) and ourselves
	 *
	 * @return void
	 */
	protected function scopeDescendantsAndSelf()
	{
		$this->treeNestedGet = true;

		$db = $this->getDbo();

		$fldLft = $db->qn($this->getFieldAlias('lft'));
		$fldRgt = $db->qn($this->getFieldAlias('rgt'));

		$this->whereRaw($db->qn('node') . '.' . $fldLft . ' >= ' . $db->qn('parent') . '.' . $fldLft);
		$this->whereRaw($db->qn('node') . '.' . $fldLft . ' <= ' . $db->qn('parent') . '.' . $fldRgt);
		$this->whereRaw($db->qn('parent') . '.' . $fldLft . ' = ' . $db->q($this->lft));
	}

	/**
	 * get() will return all descendants (even subtrees of subtrees!) but not ourselves
	 *
	 * @return void
	 */
	protected function scopeDescendants()
	{
		$this->treeNestedGet = true;

		$db = $this->getDbo();

		$fldLft = $db->qn($this->getFieldAlias('lft'));
		$fldRgt = $db->qn($this->getFieldAlias('rgt'));

		$this->whereRaw($db->qn('node') . '.' . $fldLft . ' > ' . $db->qn('parent') . '.' . $fldLft);
		$this->whereRaw($db->qn('node') . '.' . $fldLft . ' < ' . $db->qn('parent') . '.' . $fldRgt);
		$this->whereRaw($db->qn('parent') . '.' . $fldLft . ' = ' . $db->q($this->lft));

	}

	/**
	 * get() will only return immediate descendants (first level children) of the current node
	 *
	 * @return void
	 */
	protected function scopeImmediateDescendants()
	{
		$db = $this->getDbo();

		$fldLft = $db->qn($this->getFieldAlias('lft'));
		$fldRgt = $db->qn($this->getFieldAlias('rgt'));

		$subQuery = $db->getQuery(true)
			->select(array(
				$db->qn('node') . '.' . $fldLft,
				'(COUNT(*) - 1) AS ' . $db->qn('depth')
			))
			->from($db->qn($this->tableName), 'node')
			->from($db->qn($this->tableName), 'parent')
			->where($db->qn('node') . '.' . $fldLft . ' >= ' . $db->qn('parent') . '.' . $fldLft)
			->where($db->qn('node') . '.' . $fldLft . ' <= ' . $db->qn('parent') . '.' . $fldRgt)
			->where($db->qn('node') . '.' . $fldLft . ' = ' . $db->q($this->lft))
			->group($db->qn('node') . '.' . $fldLft)
			->order($db->qn('node') . '.' . $fldLft . ' ASC');

		$query = $db->getQuery(true)
			->select(array(
				$db->qn('node') . '.' . $fldLft,
				'(COUNT(' . $db->qn('parent') . '.' . $fldLft . ') - (' .
					$db->qn('sub_tree') . '.' . $db->qn('depth') . ' + 1)) AS ' . $db->qn('depth')
			))
			->from($this->tableName, 'node')
			->join('CROSS', $db->qn($this->tableName) . ' AS ' . $db->qn('parent'))
			->join('CROSS', $db->qn($this->tableName) . ' AS ' . $db->qn('sub_parent'))
			->join('CROSS', '(' . $subQuery . ') AS ' . $db->qn('sub_tree'))
			->where($db->qn('node') . '.' . $fldLft . ' >= ' . $db->qn('parent') . '.' . $fldLft)
			->where($db->qn('node') . '.' . $fldLft . ' <= ' . $db->qn('parent') . '.' . $fldRgt)
			->where($db->qn('node') . '.' . $fldLft . ' >= ' . $db->qn('sub_parent') . '.' . $fldLft)
			->where($db->qn('node') . '.' . $fldLft . ' <= ' . $db->qn('sub_parent') . '.' . $fldRgt)
			->where($db->qn('sub_parent') . '.' . $fldLft . ' = ' . $db->qn('sub_tree') . '.' . $fldLft)
			->group($db->qn('node') . '.' . $fldLft)
			->having(array(
				$db->qn('depth') . ' > ' . $db->q(0),
				$db->qn('depth') . ' <= ' . $db->q(1),
			))
			->order($db->qn('node') . '.' . $fldLft . ' ASC');

		$leftValues = $db->setQuery($query)->loadColumn();

		if (empty($leftValues))
		{
			$leftValues = array(0);
		}

		array_walk($leftValues, function(&$item, $key) use (&$db) {
			$item = $db->q($item);
		});

		$this->whereRaw($db->qn('node') . '.' . $fldLft . ' IN (' . implode(',', $leftValues) . ')');
	}

	/**
	 * get() will not return the selected node if it's part of the query results
	 *
	 * @param TreeModel $node The node to exclude from the results
	 *
	 * @return void
	 */
	public function withoutNode(TreeModel $node)
	{
		$db = $this->getDbo();

		$fldLft = $db->qn($this->getFieldAlias('lft'));

		$this->whereRaw('NOT(' . $db->qn('node') . '.' . $fldLft . ' = ' . $db->q($node->lft) . ')');
	}

	/**
	 * get() will not return ourselves if it's part of the query results
	 *
	 * @return void
	 */
	protected function scopeWithoutSelf()
	{
		$this->withoutNode($this);
	}

	/**
	 * get() will not return our root if it's part of the query results
	 *
	 * @return void
	 */
	protected function scopeWithoutRoot()
	{
		$rootNode = $this->getRoot();
		$this->withoutNode($rootNode);
	}

	/**
	 * Returns the root node of the tree this node belongs to
	 *
	 * @return static
	 *
	 * @throws \RuntimeException
	 */
	public function getRoot()
	{
		// If this is a root node return itself (there is no such thing as the root of a root node)
		if ($this->isRoot())
		{
			return $this;
		}

		if (empty($this->treeRoot) || !is_object($this->treeRoot) || !($this->treeRoot instanceof TreeModel))
		{
			$this->treeRoot = null;

			// First try to get the record with the minimum ID
			$db = $this->getDbo();

			$fldLft = $db->qn($this->getFieldAlias('lft'));
			$fldRgt = $db->qn($this->getFieldAlias('rgt'));

			$subQuery = $db->getQuery(true)
				->select('MIN(' . $fldLft . ')')
				->from($db->qn($this->tableName));

			try
			{
				$root = $this->getClone()->reset()
					->whereRaw($fldLft . ' = (' . (string)$subQuery . ')')
					->firstOrFail();

				if (($root->lft < $this->lft) && ($root->rgt > $this->rgt))
				{
					$this->treeRoot = $root;
				}
			}
			catch (\RuntimeException $e)
			{
				// If there is no root found throw an exception. Basically: your table is FUBAR.
				throw new \RuntimeException("No root found for table {$this->tableName}, node lft=" . $this->lft);
			}

			// If the above method didn't work, get all roots and select the one with the appropriate lft/rgt values
			if (is_null($this->treeRoot))
			{
				// Find the node with depth = 0, lft < our lft and rgt > our right. That's our root node.
				$query = $db->getQuery(true)
					->select(array(
						$fldLft,
						'(COUNT(' . $db->qn('parent') . '.' . $fldLft . ') - 1) AS ' . $db->qn('depth')
					))
					->from($db->qn($this->tableName), $db->qn('node'))
					->from($db->qn($this->tableName), $db->qn('parent'))
					->where($db->qn('node') . '.' . $fldLft . ' >= ' . $db->qn('parent') . '.' . $fldLft)
					->where($db->qn('node') . '.' . $fldLft . ' <= ' . $db->qn('parent') . '.' . $fldRgt)
					->where($db->qn('node') . '.' . $fldLft . ' < ' . $db->q($this->lft))
					->where($db->qn('node') . '.' . $fldRgt . ' > ' . $db->q($this->rgt))
					->having($db->qn('depth') . ' = ' . $db->q(0))
					->group($db->qn('node') . '.' . $fldLft);

				// Get the lft value
				$targetLeft = $db->setQuery($query)->loadResult();

				if (empty($targetLeft))
				{
					// If there is no root found throw an exception. Basically: your table is FUBAR.
					throw new \RuntimeException("No root found for table {$this->tableName}, node lft=" . $this->lft);
				}

				try
				{
					$this->treeRoot = $this->getClone()->reset()
						->whereRaw($fldLft . ' = ' . $db->q($targetLeft))
						->firstOrFail();
				}
				catch (\RuntimeException $e)
				{
					// If there is no root found throw an exception. Basically: your table is FUBAR.
					throw new \RuntimeException("No root found for table {$this->tableName}, node lft=" . $this->lft);
				}
			}
		}

		return $this->treeRoot;
	}

	/**
	 * Get all ancestors to this node and the node itself. In other words it gets the full path to the node and the node
	 * itself.
	 *
	 * @return DataModel\Collection
	 */
	public function getAncestorsAndSelf()
	{
		$this->scopeAncestorsAndSelf();

		return $this->get(true);
	}

	/**
	 * Get all ancestors to this node and the node itself, but not the root node. If you want to
	 *
	 * @return DataModel\Collection
	 */
	public function getAncestorsAndSelfWithoutRoot()
	{
		$this->scopeAncestorsAndSelf();
		$this->scopeWithoutRoot();

		return $this->get(true);
	}

	/**
	 * Get all ancestors to this node but not the node itself. In other words it gets the path to the node, without the
	 * node itself.
	 *
	 * @return DataModel\Collection
	 */
	public function getAncestors()
	{
		$this->scopeAncestorsAndSelf();
		$this->scopeWithoutSelf();

		return $this->get(true);
	}

	/**
	 * Get all ancestors to this node but not the node itself and its root.
	 *
	 * @return DataModel\Collection
	 */
	public function getAncestorsWithoutRoot()
	{
		$this->scopeAncestors();
		$this->scopeWithoutRoot();

		return $this->get(true);
	}

	/**
	 * Get all sibling nodes, including ourselves
	 *
	 * @return DataModel\Collection
	 */
	public function getSiblingsAndSelf()
	{
		$this->scopeSiblingsAndSelf();

		return $this->get(true);
	}

	/**
	 * Get all sibling nodes, except ourselves
	 *
	 * @return DataModel\Collection
	 */
	public function getSiblings()
	{
		$this->scopeSiblings();

		return $this->get(true);
	}

	/**
	 * Get all leaf nodes in the tree. You may want to use the scopes to narrow down the search in a specific subtree or
	 * path.
	 *
	 * @return DataModel\Collection
	 */
	public function getLeaves()
	{
		$this->scopeLeaves();

		return $this->get(true);
	}

	/**
	 * Get all descendant (children) nodes and ourselves.
	 *
	 * Note: all descendant nodes, even descendants of our immediate descendants, will be returned.
	 *
	 * @return DataModel\Collection
	 */
	public function getDescendantsAndSelf()
	{
		$this->scopeDescendantsAndSelf();

		return $this->get(true);
	}

	/**
	 * Get only our descendant (children) nodes, not ourselves.
	 *
	 * Note: all descendant nodes, even descendants of our immediate descendants, will be returned.
	 *
	 * @return DataModel\Collection
	 */
	public function getDescendants()
	{
		$this->scopeDescendants();

		return $this->get(true);
	}

	/**
	 * Get the immediate descendants (children). Unlike getDescendants it only goes one level deep into the tree
	 * structure. Descendants of descendant nodes will not be returned.
	 *
	 * @return DataModel\Collection
	 */
	public function getImmediateDescendants()
	{
		$this->scopeImmediateDescendants();

		return $this->get(true);
	}

	/**
	 * Returns a hashed array where each element's key is the value of the $key column (default: the ID column of the
	 * table) and its value is the value of the $column column (default: title). Each nesting level will have the value
	 * of the $column column prefixed by a number of $separator strings, as many as its nesting level (depth).
	 *
	 * This is useful for creating HTML select elements showing the hierarchy in a human readable format.
	 *
	 * @param string $column
	 * @param null   $key
	 * @param string $seperator
	 *
	 * @return array
	 */
	public function getNestedList($column = 'title', $key = null, $seperator = '  ')
	{
		$db = $this->getDbo();

		$fldLft = $db->qn($this->getFieldAlias('lft'));
		$fldRgt = $db->qn($this->getFieldAlias('rgt'));

		if (empty($key) || !$this->hasField($key))
		{
			$key = $this->getIdFieldName();
		}

		if (empty($column))
		{
			$column = 'title';
		}

		$fldKey = $db->qn($this->getFieldAlias($key));
		$fldColumn = $db->qn($this->getFieldAlias($column));

		$query = $db->getQuery(true)
			->select(array(
				$db->qn('node') . '.' . $fldKey,
				$db->qn('node') . '.' . $fldColumn,
				'(COUNT(' . $db->qn('parent') . '.' . $fldKey . ') - 1) AS ' . $db->qn('depth')
			))
			->from($db->qn($this->tableName), $db->qn('node'))
			->from($db->qn($this->tableName), $db->qn('parent'))
			->where($db->qn('node') . '.' . $fldLft . ' >= ' . $db->qn('parent') . '.' . $fldLft)
			->where($db->qn('node') . '.' . $fldLft . ' <= ' . $db->qn('parent') . '.' . $fldRgt)
			->group($db->qn('node') . '.' . $fldLft)
			->order($db->qn('node') . '.' . $fldLft . ' ASC');

		$tempResults = $db->setQuery($query)->loadAssocList();
		$ret = array();

		if (!empty($tempResults))
		{
			foreach ($tempResults as $row)
			{
				$ret[$row[$key]] = str_repeat($seperator, $row['depth']) . $row[$column];
			}
		}

		return $ret;
	}

	public function isValid()
	{
		// @todo
	}

	public function rebuild()
	{
		// @todo
	}

	/**
	 * Resets cached values used to speed up querying the tree
	 *
	 * @return  static  for chaining
	 */
	protected function resetTreeCache()
	{
		$this->treeDepth = null;
		$this->treeRoot = null;
		$this->treeParent = null;
		$this->treeNestedGet = false;

		return $this;
	}

	/**
	 * Overrides the DataModel's buildQuery to allow nested set searches using the provided scopes
	 *
	 * @param bool $overrideLimits
	 *
	 * @return \Awf\Database\Query
	 */
	public function buildQuery($overrideLimits = false)
	{
		$db = $this->getDbo();

		$query = parent::buildQuery($overrideLimits);

		$query
			->select(null)
			->select($db->qn('node') . '.*')
			->from(null)
			->from($db->qn($this->tableName), $db->qn('node'));

		if ($this->treeNestedGet)
		{
			$query
				->from($db->qn($this->tableName), $db->qn('parent'));
		}

		return $query;
	}
} 