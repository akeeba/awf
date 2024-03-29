<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Mvc;

use Awf\Container\Container;
use Awf\Inflector\Inflector;

/**
 * Database-aware Controller
 */
class DataController extends Controller
{
	public function __construct(Container $container = null)
	{
		parent::__construct($container);

		// Set up a default model name if none is provided
		if (empty($this->modelName))
		{
			$this->modelName = Inflector::pluralize($this->view);
		}

		// Set up a default view name if none is provided
		if (empty($this->viewName))
		{
			$this->viewName = Inflector::pluralize($this->view);
		}
	}

	/**
	 * Executes a given controller task. The onBefore<task> and onAfter<task> methods are called automatically if they
	 * exist.
	 *
	 * If $task == 'default' we will determine the CRUD task to use based on the view name and HTTP verb in the request,
	 * overriding the routing.
	 *
	 * @param   string  $task  The task to execute, e.g. "browse"
	 *
	 * @return  null|bool  False on execution failure
	 *
	 * @throws  \Exception  When the task is not found
	 */
	public function execute($task)
	{
		$task = strtolower($task);

		if ($task == 'default')
		{
			$task = $this->getCrudTask();
		}

		return parent::execute($task);
	}

	/**
	 * Implements a default browse task, i.e. read a bunch of records and send
	 * them to the browser.
	 *
	 * @return  void
	 */
	public function browse()
	{
		if ($this->input->get('savestate', -999, 'int') == -999)
		{
			$this->input->set('savestate', true);
		}

		$this->display();
	}

	/**
	 * Single record read. The id set in the request is passed to the model and
	 * then the item layout is used to render the result.
	 *
	 * @return  void
	 *
	 * @throws \RuntimeException When the item is not found
	 */
	public function read()
	{
		// Load the model
		/** @var DataModel $model */
		$model = $this->getModel();

		// If there is no record loaded, try loading a record based on the id passed in the input object
		if (!$model->getId())
		{
			$ids = $this->getIDsFromRequest($model, true);

			if (empty($ids))
			{
				throw new \RuntimeException("Access Denied", 403);
			}

			if ($model->getId() != reset($ids))
			{
				$key = $this->container->application_name . '_ERR_' . $model->getName() . '_NOTFOUND';
				throw new \RuntimeException($this->getContainer()->language->text($key), 404);
			}
		}

		// Set the layout to item, if it's not set in the URL
		if (empty($this->layout))
		{
			$this->layout = 'item';
		}

		$this->display();
	}

	/**
	 * Single record add. The form layout is used to present a blank page.
	 *
	 * @return  void
	 */
	public function add()
	{
		// Load and reset the model
		$model = $this->getModel();
		$model->reset();

		// Set the layout to form, if it's not set in the URL
		if (empty($this->layout))
		{
			$this->layout = 'form';
		}

		// Get temporary data from the session, set if the save failed and we're redirected back here
		$sessionKey = $this->container->application_name . '_' . $this->viewName;
		$itemData   = $this->container->segment->getFlash($sessionKey);

		if (!empty($itemData))
		{
			$model->bind($itemData);
		}

		// Display the edit form
		$this->display();
	}

	/**
	 * Single record edit. The ID set in the request is passed to the model,
	 * then the form layout is used to edit the result.
	 *
	 * @return  void
	 */
	public function edit()
	{
		// Load the model
		/** @var DataModel $model */
		$model = $this->getModel();

		if (!$model->getId())
		{
			$this->getIDsFromRequest($model, true);
		}

		try
		{
			if (!$model->getId())
			{
				throw new \RuntimeException("Access Denied", 403);
			}

			$model->lock();
		}
		catch (\Exception $e)
		{
			// Redirect on error
			if ($customURL = $this->input->getBase64('returnurl', ''))
			{
				$customURL = base64_decode($customURL);
			}

			$router = $this->container->router;
			$url    = !empty($customURL)
				? $customURL
				: $router->route(
					'index.php?&view=' . Inflector::pluralize($this->view)
				);
			$this->setRedirect($url, $e->getMessage(), 'error');

			return;
		}

		// Set the layout to form, if it's not set in the URL
		if (empty($this->layout))
		{
			$this->layout = 'form';
		}

		// Get temporary data from the session, set if the save failed and we're redirected back here
		$sessionKey = $this->container->application_name . '_' . $this->viewName;
		$itemData   = $this->container->segment->getFlash($sessionKey);

		if (!empty($itemData))
		{
			$model->bind($itemData);
		}

		// Display the edit form
		$this->display();
	}

	/**
	 * Save the incoming data and then return to the Edit task
	 *
	 * @return  void
	 */
	public function apply()
	{
		// CSRF prevention
		$this->csrfProtection();

		$this->container->session->getCsrfToken()->regenerateValue();

		// Redirect to the edit task
		if (!$this->applySave())
		{
			return;
		}

		$id      = $this->input->get('id', 0, 'int');
		$textKey = $this->container->application_name . '_LBL_' . Inflector::singularize($this->view) . '_SAVED';

		if ($customURL = $this->input->getBase64('returnurl', ''))
		{
			$customURL = base64_decode($customURL);
		}

		// Special consideration for apply: must always return to the editing page, not the return URL.
		$router = $this->container->router;
		$url    = empty($customURL)
			? $router->route(sprintf('index.php?view=%s&task=edit&id=%d', $this->view, $id))
			: $router->route(
				sprintf('index.php?view=%s&task=edit&id=%d&returnurl=%s', $this->view, $id, base64_encode($customURL))
			);

		$this->setRedirect($url, $this->getContainer()->language->text($textKey));
	}

	/**
	 * Duplicates selected items
	 *
	 * @return  void
	 */
	public function copy()
	{
		// CSRF prevention
		$this->csrfProtection();

		$this->container->session->getCsrfToken()->regenerateValue();

		$model = $this->getModel();

		$ids = $this->getIDsFromRequest($model, true);

		try
		{
			if (empty($ids))
			{
				throw new \RuntimeException("Access Denied", 403);
			}

			$status = true;

			foreach ($ids as $id)
			{
				$model->find($id);
				$model->copy();
			}
		}
		catch (\Exception $e)
		{
			$status = false;
			$error  = $e->getMessage();
		}

		// Redirect
		if ($customURL = $this->input->getBase64('returnurl', ''))
		{
			$customURL = base64_decode($customURL);
		}

		$router = $this->container->router;
		$url    = !empty($customURL)
			? $customURL
			: $router->route(
				'index.php?view=' . Inflector::pluralize($this->view)
			);

		if (!$status)
		{
			$this->setRedirect($url, $error, 'error');
		}
		else
		{
			$textKey = $this->container->application_name . '_LBL_' . Inflector::singularize($this->view) . '_COPIED';
			$this->setRedirect($url, $this->getContainer()->language->text($textKey));
		}
	}

	/**
	 * Save the incoming data and then return to the Browse task
	 *
	 * @return  void
	 */
	public function save()
	{
		// CSRF prevention
		$this->csrfProtection();

		$this->container->session->getCsrfToken()->regenerateValue();

		if (!$this->applySave())
		{
			return;
		}

		$textKey = $this->container->application_name . '_LBL_' . Inflector::singularize($this->view) . '_SAVED';

		if ($customURL = $this->input->getBase64('returnurl', ''))
		{
			$customURL = base64_decode($customURL);
		}

		$router = $this->container->router;
		$url    = !empty($customURL)
			? $customURL
			: $router->route(
				'index.php?view=' . Inflector::pluralize($this->view)
			);
		$this->setRedirect($url, $this->getContainer()->language->text($textKey));
	}

	/**
	 * Save the incoming data and then return to the Add task
	 *
	 * @return  bool
	 */
	public function savenew()
	{
		// CSRF prevention
		$this->csrfProtection();

		$this->container->session->getCsrfToken()->regenerateValue();

		if (!$this->applySave())
		{
			return true;
		}

		$textKey = $this->container->application_name . '_LBL_' . Inflector::singularize($this->view) . '_SAVED';

		if ($customURL = $this->input->getBase64('returnurl', ''))
		{
			$customURL = base64_decode($customURL);
		}

		$router = $this->container->router;
		$url    = !empty($customURL)
			? $customURL
			: $router->route(
				'index.php?view=' . Inflector::singularize($this->view) . '&task=add'
			);
		$this->setRedirect($url, $this->getContainer()->language->text($textKey));
	}

	/**
	 * Cancel the edit, check in the record and return to the Browse task
	 *
	 * @return  void
	 */
	public function cancel()
	{
		$model = $this->getModel();

		if (!$model->getId())
		{
			$this->getIDsFromRequest($model, true);
		}

		if ($model->getId())
		{
			$model->unlock();
		}

		// Remove any saved data
		$this->container->segment->remove($model->getHash() . 'savedata');

		// Redirect to the display task
		if ($customURL = $this->input->getBase64('returnurl', ''))
		{
			$customURL = base64_decode($customURL);
		}

		$router = $this->container->router;
		$url    = !empty($customURL)
			? $customURL
			: $router->route(
				'index.php?view=' . Inflector::pluralize($this->view)
			);
		$this->setRedirect($url);
	}

	/**
	 * Publish (set enabled = 1) an item.
	 *
	 * @return  void
	 */
	public function publish()
	{
		// CSRF prevention
		$this->csrfProtection();

		$this->container->session->getCsrfToken()->regenerateValue();

		$model = $this->getModel();
		$ids   = $this->getIDsFromRequest($model, false);

		try
		{
			if (empty($ids))
			{
				throw new \RuntimeException("Access Denied", 403);
			}

			$status = true;

			foreach ($ids as $id)
			{
				$model->find($id);
				$model->publish();
			}
		}
		catch (\Exception $e)
		{
			$status = false;
			$error  = $e->getMessage();
		}

		// Redirect
		if ($customURL = $this->input->getBase64('returnurl', ''))
		{
			$customURL = base64_decode($customURL);
		}

		$router = $this->container->router;
		$url    = !empty($customURL)
			? $customURL
			: $router->route(
				'index.php?view=' . Inflector::pluralize($this->view)
			);

		if (!$status)
		{
			$this->setRedirect($url, $error, 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
	}

	/**
	 * Unpublish (set enabled = 0) an item.
	 *
	 * @return  void
	 */
	public function unpublish()
	{
		// CSRF prevention
		$this->csrfProtection();

		$this->container->session->getCsrfToken()->regenerateValue();

		$model = $this->getModel();
		$ids   = $this->getIDsFromRequest($model, false);

		try
		{
			if (empty($ids))
			{
				throw new \RuntimeException("Access Denied", 403);
			}

			$status = true;

			foreach ($ids as $id)
			{
				$model->find($id);
				$model->unpublish();
			}
		}
		catch (\Exception $e)
		{
			$status = false;
			$error  = $e->getMessage();
		}

		// Redirect
		if ($customURL = $this->input->getBase64('returnurl', ''))
		{
			$customURL = base64_decode($customURL);
		}

		$router = $this->container->router;
		$url    = !empty($customURL)
			? $customURL
			: $router->route(
				'index.php?view=' . Inflector::pluralize($this->view)
			);

		if (!$status)
		{
			$this->setRedirect($url, $error, 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
	}

	/**
	 * Archive (set enabled = 2) an item.
	 *
	 * @return  void
	 */
	public function archive()
	{
		// CSRF prevention
		$this->csrfProtection();

		$this->container->session->getCsrfToken()->regenerateValue();

		$model = $this->getModel();
		$ids   = $this->getIDsFromRequest($model, false);

		try
		{
			if (empty($ids))
			{
				throw new \RuntimeException("Access Denied", 403);
			}

			$status = true;

			foreach ($ids as $id)
			{
				$model->find($id);
				$model->archive();
			}
		}
		catch (\Exception $e)
		{
			$status = false;
			$error  = $e->getMessage();
		}

		// Redirect
		if ($customURL = $this->input->getBase64('returnurl', ''))
		{
			$customURL = base64_decode($customURL);
		}

		$router = $this->container->router;
		$url    = !empty($customURL)
			? $customURL
			: $router->route(
				'index.php?view=' . Inflector::pluralize($this->view)
			);

		if (!$status)
		{
			$this->setRedirect($url, $error, 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
	}

	/**
	 * Trash (set enabled = -2) an item.
	 *
	 * @return  void
	 */
	public function trash()
	{
		// CSRF prevention
		$this->csrfProtection();

		$this->container->session->getCsrfToken()->regenerateValue();

		$model = $this->getModel();
		$ids   = $this->getIDsFromRequest($model, false);

		try
		{
			if (empty($ids))
			{
				throw new \RuntimeException("Access Denied", 403);
			}

			$status = true;

			foreach ($ids as $id)
			{
				$model->find($id);
				$model->trash();
			}
		}
		catch (\Exception $e)
		{
			$status = false;
			$error  = $e->getMessage();
		}

		// Redirect
		if ($customURL = $this->input->getBase64('returnurl', ''))
		{
			$customURL = base64_decode($customURL);
		}

		$router = $this->container->router;
		$url    = !empty($customURL)
			? $customURL
			: $router->route(
				'index.php?view=' . Inflector::pluralize($this->view)
			);

		if (!$status)
		{
			$this->setRedirect($url, $error, 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
	}

	/**
	 * Saves the order of the items
	 *
	 * @return  void
	 */
	public function saveorder()
	{
		// CSRF prevention
		$this->csrfProtection();

		$this->container->session->getCsrfToken()->regenerateValue();

		$type   = null;
		$msg    = null;
		$model  = $this->getModel();
		$ids    = $this->getIDsFromRequest($model, false);
		$orders = $this->input->get('order', [], 'array');

		// Before saving the order, I have to check I the table really supports the ordering feature
		if (!$model->hasField('ordering'))
		{
			$msg  = sprintf('%s does not support ordering.', $model->getTableName());
			$type = 'error';
		}
		else
		{
			if (empty($ids))
			{
				throw new \RuntimeException("Access Denied", 403);
			}

			$ordering = $model->getFieldAlias('ordering');

			// Several methods could throw exceptions, so let's wrap everything in a try-catch
			try
			{
				if ($n = count($ids))
				{
					for ($i = 0; $i < $n; $i++)
					{
						$item     = $model->find($ids[$i]);
						$neworder = (int) $orders[$i];

						if (!($item instanceof DataModel))
						{
							continue;
						}

						if ($item->getId() == $ids[$i])
						{
							$item->$ordering = $neworder;
							$model->save($item);
						}
					}
				}

				$model->reorder();
			}
			catch (\Exception $e)
			{
				$msg  = $e->getMessage();
				$type = 'error';
			}
		}

		// Redirect
		if ($customURL = $this->input->getBase64('returnurl', ''))
		{
			$customURL = base64_decode($customURL);
		}

		$router = $this->container->router;
		$url    = !empty($customURL)
			? $customURL
			: $router->route(
				'index.php?view=' . Inflector::pluralize($this->view)
			);

		$this->setRedirect($url, $msg, $type);
	}

	/**
	 * Moves selected items one position down the ordering list
	 *
	 * @return  void
	 */
	public function orderdown()
	{
		// CSRF prevention
		$this->csrfProtection();

		$this->container->session->getCsrfToken()->regenerateValue();

		$model = $this->getModel();

		if (!$model->getId())
		{
			$this->getIDsFromRequest($model, true);
		}

		try
		{
			if (empty($model->getId()))
			{
				throw new \RuntimeException("Access Denied", 403);
			}

			$model->move(1);

			$status = true;
		}
		catch (\Exception $e)
		{
			$status = false;
			$error  = $e->getMessage();
		}

		// Redirect
		if ($customURL = $this->input->getBase64('returnurl', ''))
		{
			$customURL = base64_decode($customURL);
		}

		$router = $this->container->router;
		$url    = !empty($customURL)
			? $customURL
			: $router->route(
				'index.php?view=' . Inflector::pluralize($this->view)
			);

		if (!$status)
		{
			$this->setRedirect($url, $error, 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
	}

	/**
	 * Moves selected items one position up the ordering list
	 *
	 * @return  void
	 */
	public function orderup()
	{
		// CSRF prevention
		$this->csrfProtection();

		$this->container->session->getCsrfToken()->regenerateValue();

		$model = $this->getModel();

		if (!$model->getId())
		{
			$this->getIDsFromRequest($model, true);
		}

		try
		{
			if (empty($model->getId()))
			{
				throw new \RuntimeException("Access Denied", 403);
			}

			$model->move(-1);

			$status = true;
		}
		catch (\Exception $e)
		{
			$status = false;
			$error  = $e->getMessage();
		}

		// Redirect
		if ($customURL = $this->input->getBase64('returnurl', ''))
		{
			$customURL = base64_decode($customURL);
		}

		$router = $this->container->router;
		$url    = !empty($customURL)
			? $customURL
			: $router->route(
				'index.php?view=' . Inflector::pluralize($this->view)
			);

		if (!$status)
		{
			$this->setRedirect($url, $error, 'error');
		}
		else
		{
			$this->setRedirect($url);
		}
	}

	/**
	 * Delete selected item(s)
	 *
	 * @return  void
	 */
	public function remove()
	{
		// CSRF prevention
		$this->csrfProtection();

		$this->container->session->getCsrfToken()->regenerateValue();

		$model = $this->getModel();

		$ids = $this->getIDsFromRequest($model, false);

		try
		{
			if (empty($ids))
			{
				throw new \RuntimeException("Access Denied", 403);
			}

			$status = true;

			foreach ($ids as $id)
			{
				$model->find($id);
				$model->delete();
			}
		}
		catch (\Exception $e)
		{
			$status = false;
			$error  = $e->getMessage();
		}

		// Redirect
		if ($customURL = $this->input->getBase64('returnurl', ''))
		{
			$customURL = base64_decode($customURL);
		}

		$router = $this->container->router;
		$url    = !empty($customURL)
			? $customURL
			: $router->route(
				'index.php?view=' . Inflector::pluralize($this->view)
			);

		if (!$status)
		{
			$this->setRedirect($url, $error, 'error');
		}
		else
		{
			$textKey = $this->container->application_name . '_LBL_' . Inflector::singularize($this->view) . '_DELETED';
			$this->setRedirect($url, $this->getContainer()->language->text($textKey));
		}
	}

	/**
	 * Returns a named Model object. Makes sure that the Model is a database-aware model, throwing an exception
	 * otherwise, when $name is null.
	 *
	 * @param   string  $name    The Model name. If null we'll use the modelName
	 *                           variable or, if it's empty, the same name as
	 *                           the Controller
	 * @param   array   $config  Configuration parameters to the Model. If skipped
	 *                           we will use $this->config
	 *
	 * @return  DataModel  The instance of the Model known to this Controller
	 *
	 * @throws  \Exception  When the model type doesn't match our expectations
	 */
	public function getModel($name = null, $config = [])
	{
		$model = parent::getModel($name, $config);

		if (is_null($name) && !($model instanceof DataModel))
		{
			throw new \Exception('Model ' . get_class($model) . ' is not a database-aware Model');
		}

		return $model;
	}

	/**
	 * Gets the list of IDs from the request data
	 *
	 * @param   DataModel  $model       The model where the record will be loaded
	 * @param   bool       $loadRecord  When true, the record matching the *first* ID found will be loaded into $model
	 *
	 * @return array
	 */
	public function getIDsFromRequest(DataModel &$model, $loadRecord = true)
	{
		// Get the ID or list of IDs from the request or the configuration
		$cid = $this->input->get('cid', [], 'array');
		$id  = $this->input->getInt('id', 0);
		$kid = $this->input->getInt($model->getIdFieldName(), 0);

		$ids = [];

		if (is_array($cid) && !empty($cid))
		{
			$ids = $cid;
		}
		else
		{
			if (empty($id))
			{
				if (!empty($kid))
				{
					$ids = [$kid];
				}
			}
			else
			{
				$ids = [$id];
			}
		}

		if ($loadRecord && !empty($ids))
		{
			$id = reset($ids);
			$model->find(['id' => $id]);
		}

		return $ids;
	}

	/**
	 * Determines the CRUD task to use based on the view name and HTTP verb used in the request.
	 *
	 * @return  string  The CRUD task (browse, read, edit, delete)
	 */
	protected function getCrudTask()
	{
		// By default, a plural view means 'browse' and a singular view means 'edit'
		$view = $this->input->getCmd('view', null);
		$task = Inflector::isPlural($view) ? 'browse' : 'edit';

		// If the task is 'edit' but there's no logged in user switch to a 'read' task
		if (($task == 'edit') && !$this->container->userManager->getUser()->getId())
		{
			$task = 'read';
		}

		// Check if there is an id passed in the request
		$id = $this->input->get('id', null, 'int');

		if ($id == 0)
		{
			$ids = $this->input->get('ids', [], 'array');

			if (!empty($ids))
			{
				$id = array_shift($ids);
			}
		}

		// Get the request HTTP verb
		if (!isset($_SERVER['REQUEST_METHOD']))
		{
			$_SERVER['REQUEST_METHOD'] = 'GET';
		}

		$requestMethod = strtoupper($_SERVER['REQUEST_METHOD']);

		// Alter the task based on the verb
		switch ($requestMethod)
		{
			// POST and PUT result in a record being saved, as long as there is an ID
			case 'POST':
			case 'PUT':
				if ($id)
				{
					$task = 'save';
				}
				break;

			// DELETE results in a record being deleted, as long as there is an ID
			case 'DELETE':
				if ($id)
				{
					$task = 'delete';
				}
				break;

			// GET results in browse, edit or add depending on the ID
			case 'GET':
			default:
				// If it's an edit without an ID or ID=0, it's really an add
				if (($task == 'edit') && ($id == 0))
				{
					$task = 'add';
				}
				break;
		}

		return $task;
	}

	/**
	 * Common method to handle apply and save tasks
	 *
	 * @return  bool True on success
	 */
	protected function applySave()
	{
		// Load the model
		$model = $this->getModel();

		if (!$model->getId())
		{
			$this->getIDsFromRequest($model, true);
		}

		$id = $model->getId();

		$data = $this->input->getData();

		// Set the layout to form, if it's not set in the URL
		if (is_null($this->layout))
		{
			$this->layout = 'form';
		}

		// Save the data
		$status = true;

		try
		{
			if (method_exists($this, 'onBeforeApplySave'))
			{
				$this->onBeforeApplySave($data);
			}

			// Save the data
			$model->save($data);

			if ($id != 0)
			{
				// Try to check-in the record if it's not a new one
				$model->unlock();
			}

			if (method_exists($this, 'onAfterApplySave'))
			{
				$this->onAfterApplySave($data);
			}

			$this->input->set('id', $model->getId());
		}
		catch (\Exception $e)
		{
			$status = false;
			$error  = $e->getMessage();
		}

		if (!$status)
		{
			// Cache the item data in the session. We may need to reuse them if the save fails.
			$itemData   = $model->getData();
			$sessionKey = $this->container->application_name . '_' . $this->viewName;
			$this->container->segment->setFlash($sessionKey, $itemData);

			// Redirect on error
			$id = $model->getId();

			if ($customURL = $this->input->getBase64('returnurl', ''))
			{
				$customURL = base64_decode($customURL);
			}

			$router = $this->container->router;

			if (!empty($customURL))
			{
				$url = $customURL;
			}
			elseif ($id != 0)
			{
				$url = $router->route('index.php?view=' . $this->view . '&task=edit&id=' . $id);
			}
			else
			{
				$url = $router->route('index.php?view=' . $this->view . '&task=add');
			}

			$this->setRedirect($url, $error, 'error');
		}
		else
		{
			$this->container->segment->remove($model->getHash() . 'savedata');
		}

		return $status;
	}

	/**
	 * Fires before executing the browse task. In turn, it calls the respective event in the global observers to decide
	 * if the execution of the task should proceed.
	 *
	 * @return bool
	 */
	protected function onBeforeBrowse()
	{
		return true;
	}

	/**
	 * Fires before executing the read task. In turn, it calls the respective event in the global observers to decide
	 * if the execution of the task should proceed.
	 *
	 * @return bool
	 */
	protected function onBeforeRead()
	{
		return true;
	}

	/**
	 * Fires before executing the add task. In turn, it calls the respective event in the global observers to decide
	 * if the execution of the task should proceed.
	 *
	 * @return bool
	 */
	protected function onBeforeAdd()
	{
		return true;
	}

	/**
	 * Fires before executing the edit task. In turn, it calls the respective event in the global observers to decide
	 * if the execution of the task should proceed.
	 *
	 * @return bool
	 */
	protected function onBeforeEdit()
	{
		return true;
	}

	/**
	 * Fires before executing the apply task. In turn, it calls the respective event in the global observers to decide
	 * if the execution of the task should proceed.
	 *
	 * @return bool
	 */
	protected function onBeforeApply()
	{
		return true;
	}

	/**
	 * Fires before executing the copy task. In turn, it calls the respective event in the global observers to decide
	 * if the execution of the task should proceed.
	 *
	 * @return bool
	 */
	protected function onBeforeCopy()
	{
		return true;
	}

	/**
	 * Fires before executing the save task. In turn, it calls the respective event in the global observers to decide
	 * if the execution of the task should proceed.
	 *
	 * @return bool
	 */
	protected function onBeforeSave()
	{
		return true;
	}

	/**
	 * Fires before executing the savenew task. In turn, it calls the respective event in the global observers to decide
	 * if the execution of the task should proceed.
	 *
	 * @return bool
	 */
	protected function onBeforeSavenew()
	{
		return true;
	}

	/**
	 * Fires before executing the canceltask. In turn, it calls the respective event in the global observers to decide
	 * if the execution of the task should proceed.
	 *
	 * @return bool
	 */
	protected function onBeforeCancel()
	{
		return true;
	}

	/**
	 * Fires before executing the publish task. In turn, it calls the respective event in the global observers to decide
	 * if the execution of the task should proceed.
	 *
	 * @return bool
	 */
	protected function onBeforePublish()
	{
		return true;
	}

	/**
	 * Fires before executing the unpublish task. In turn, it calls the respective event in the global observers to
	 * decide if the execution of the task should proceed.
	 *
	 * @return bool
	 */
	protected function onBeforeUnpublish()
	{
		return true;
	}

	/**
	 * Fires before executing the archive task. In turn, it calls the respective event in the global observers to decide
	 * if the execution of the task should proceed.
	 *
	 * @return bool
	 */
	protected function onBeforeArchive()
	{
		return true;
	}

	/**
	 * Fires before executing the trash task. In turn, it calls the respective event in the global observers to decide
	 * if the execution of the task should proceed.
	 *
	 * @return bool
	 */
	protected function onBeforeTrash()
	{
		return true;
	}

	/**
	 * Fires before executing the saveorder task. In turn, it calls the respective event in the global observers to
	 * decide if the execution of the task should proceed.
	 *
	 * @return bool
	 */
	protected function onBeforeSaveorder()
	{
		return true;
	}

	/**
	 * Fires before executing the orderdown task. In turn, it calls the respective event in the global observers to
	 * decide if the execution of the task should proceed.
	 *
	 * @return bool
	 */
	protected function onBeforeOrderdown()
	{
		return true;
	}

	/**
	 * Fires before executing the orderup task. In turn, it calls the respective event in the global observers to decide
	 * if the execution of the task should proceed.
	 *
	 * @return bool
	 */
	protected function onBeforeOrderup()
	{
		return true;
	}

	/**
	 * Fires before executing the remove task. In turn, it calls the respective event in the global observers to decide
	 * if the execution of the task should proceed.
	 *
	 * @return bool
	 */
	protected function onBeforeRemove()
	{
		return true;
	}
}
