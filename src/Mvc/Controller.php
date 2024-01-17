<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Mvc;

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Container\ContainerAwareTrait;
use Awf\Exception\App;
use Awf\Input\Input;
use Awf\Text\Language;
use Awf\Text\LanguageAwareInterface;
use Awf\Text\LanguageAwareTrait;
use Exception;
use RuntimeException;

/**
 * Class Controller
 *
 * A generic MVC controller implementation
 *
 * @package Awf\Mvc
 */
#[\AllowDynamicProperties]
class Controller implements ContainerAwareInterface, LanguageAwareInterface
{
	use ContainerAwareTrait;
	use LanguageAwareTrait;

	/**
	 * Instance container.
	 *
	 * @var    Controller
	 */
	protected static $instance;

	/**
	 * The name of the controller
	 *
	 * @var    array
	 */
	protected $name = null;

	/**
	 * The mapped task that was performed.
	 *
	 * @var    string
	 */
	protected $doTask;

	/**
	 * Redirect message.
	 *
	 * @var    string
	 */
	protected $message;

	/**
	 * Redirect message type.
	 *
	 * @var    string
	 */
	protected $messageType;

	/**
	 * Array of class methods
	 *
	 * @var    array
	 */
	protected $methods;

	/**
	 * The set of search directories for resources (views).
	 *
	 * @var    array
	 */
	protected $paths;

	/**
	 * URL for redirection.
	 *
	 * @var    string
	 */
	protected $redirect;

	/**
	 * Current or most recently performed task.
	 *
	 * @var    string
	 */
	protected $task;

	/**
	 * Array of class methods to call for a given task.
	 *
	 * @var    array
	 */
	protected $taskMap;

	/**
	 * Hold an Input object for easier access to the input variables.
	 *
	 * @var    Input
	 */
	protected $input;

	/**
	 * The current view name; you can override it in the configuration
	 *
	 * @var string
	 */
	protected $view = '';

	/**
	 * The current layout; you can override it in the configuration
	 *
	 * @var string
	 */
	protected $layout = null;

	/**
	 * A cached copy of the class configuration parameter passed during initialisation
	 *
	 * @var array
	 */
	protected $config = [];

	/**
	 * Overrides the name of the view's default model
	 *
	 * @var string
	 */
	protected $modelName = null;

	/**
	 * Overrides the name of the view's default view
	 *
	 * @var string
	 */
	protected $viewName = null;

	/**
	 * An array of Model instances known to this Controller
	 *
	 * @var   array[Model]
	 */
	protected $modelInstances = [];

	/**
	 * An array of View instances known to this Controller
	 *
	 * @var   array[View]
	 */
	protected $viewInstances = [];

	/**
	 * Public constructor of the Controller class
	 *
	 * @param   Container|null  $container  The application container
	 *
	 * @throws  App
	 */
	public function __construct(?Container $container = null, ?Language $language = null)
	{
		// Initialise
		$this->methods     = [];
		$this->message     = null;
		$this->messageType = 'info';
		$this->paths       = [];
		$this->redirect    = null;
		$this->taskMap     = [];

		/** @deprecated 2.0 You must provide the container */
		if (empty($container))
		{
			trigger_error(
				sprintf('The container argument is mandatory in %s', __METHOD__),
				E_USER_DEPRECATED
			);

			$container = Application::getInstance()->getContainer();
		}

		$this->setContainer($container);
		$this->setLanguage($language ?? $container->language);

		$container->eventDispatcher->trigger('onControllerBeforeConstruct', [$this, $container]);

		$container = $this->getContainer();
		$config = $container['mvc_config'] ?? [];

		// Get local copies of things included in the container
		$this->input = $container->input;

		// Determine the methods to exclude from the base class.
		$xMethods = get_class_methods('\\Awf\\Mvc\\Controller');

		// Get the public methods in this class using reflection.
		$r        = new \ReflectionClass($this);
		$rMethods = $r->getMethods(\ReflectionMethod::IS_PUBLIC);

		foreach ($rMethods as $rMethod)
		{
			$mName = $rMethod->getName();

			// Add default display method if not explicitly declared.
			if (!in_array($mName, $xMethods) || $mName == 'display' || $mName == 'main')
			{
				$this->methods[] = strtolower($mName);

				// Auto register the methods as tasks.
				$this->taskMap[strtolower($mName)] = $mName;
			}
		}

		// Get the default values for the component and view names
		$this->view   = $this->getName();
		$this->layout = $this->input->getCmd('layout', null);

		// If the default task is set, register it as such
		if (array_key_exists('default_task', $config))
		{
			$this->registerDefaultTask($config['default_task']);
		}
		else
		{
			$this->registerDefaultTask('main');
		}

		// Set the default view.
		if (array_key_exists('default_view', $config))
		{
			$this->default_view = $config['default_view'];
		}
		elseif (empty($this->default_view))
		{
			$this->default_view = $this->view;
		}

		// Cache the config
		$this->config = $config;

		// Set any model/view name overrides
		if (array_key_exists('viewName', $config))
		{
			$this->setViewName($config['viewName']);
		}

		if (array_key_exists('modelName', $config))
		{
			$this->setModelName($config['modelName']);
		}

		$container->eventDispatcher->trigger('onControllerAfterConstruct', [$this, $container]);
	}

	/**
	 * Creates an instance of a controller object.
	 *
	 * @param   string|null     $appName     The application name [optional] Default: the default application
	 * @param   string|null     $controller  The controller name [optional] Default: based on the "view" input parameter
	 * @param   Container|null  $container   The DI container [optional] Default: the application container of the $appName
	 *                                  application
	 *
	 * @return  Controller  A Controller instance
	 *
	 * @throws  RuntimeException  When you are referring to a controller class which doesn't exist
	 * @deprecated 2.0 Go through the MVCFactory in the container instead
	 */
	public static function getInstance(?string $appName = null, ?string $controller = null, ?Container $container = null, ?Language $language = null)
	{
		trigger_error(
			sprintf(
				'Calling %s is deprecated. Use the MVCFactory service of the container instead.',
				__METHOD__
			),
			E_USER_DEPRECATED
		);

		return ($container ?? Application::getInstance($appName)->getContainer())
			->mvcFactory->makeController($controller, $language);
	}

	/**
	 * Executes a given controller task. The onBefore<task> and onAfter<task>
	 * methods are called automatically if they exist.
	 *
	 * @param   string  $task  The task to execute, e.g. "browse"
	 *
	 * @return  null|bool  False on execution failure
	 *
	 * @throws  Exception  When the task is not found
	 */
	public function execute($task)
	{
		$this->task = $task;

		$task = strtolower($task);

		if (isset($this->taskMap[$task]))
		{
			$doTask = $this->taskMap[$task];
		}
		elseif (isset($this->taskMap['__default']))
		{
			$doTask = $this->taskMap['__default'];
		}
		else
		{
			throw new Exception($this->getContainer()->language->sprintf('AWF_APPLICATION_ERROR_TASK_NOT_FOUND', $task), 404);
		}

		$method_name = 'onBeforeExecute';

		if (method_exists($this, $method_name))
		{
			$result = $this->$method_name($task, $doTask);

			if (!$result)
			{
				return false;
			}
		}

		$results = $this->container->eventDispatcher->trigger('onControllerBeforeExecute', [$this, $task]) ?: [];

		if (in_array(false, $results, true))
		{
			return false;
		}

		$method_name = 'onBefore' . ucfirst($task);

		if (method_exists($this, $method_name))
		{
			$result = $this->$method_name();

			if (!$result)
			{
				return false;
			}
		}

		$results = $this->container->eventDispatcher->trigger('onControllerBefore' . ucfirst($task), [$this]) ?: [];

		if (in_array(false, $results, true))
		{
			return false;
		}

		// Do not allow the display task to be directly called
		$task = strtolower($task);

		if (isset($this->taskMap[$task]))
		{
			$doTask = $this->taskMap[$task];
		}
		elseif (isset($this->taskMap['__default']))
		{
			$doTask = $this->taskMap['__default'];
		}
		else
		{
			$doTask = null;
		}

		// Record the actual task being fired
		$this->doTask = $doTask;

		$ret = $this->$doTask();

		$method_name = 'onAfter' . ucfirst($task);

		if (method_exists($this, $method_name))
		{
			$result = $this->$method_name();

			if (!$result)
			{
				return false;
			}
		}

		$results = $this->container->eventDispatcher->trigger('onControllerAfter' . ucfirst($task), [$this, $ret])
			?: [];

		if (in_array(false, $results, true))
		{
			return false;
		}

		$method_name = 'onAfterExecute';

		if (method_exists($this, $method_name))
		{
			$result = $this->$method_name($task, $doTask);

			if (!$result)
			{
				return false;
			}
		}

		$results = $this->container->eventDispatcher->trigger('onControllerAfterExecute', [$this, $task, $ret]) ?: [];

		if (in_array(false, $results, true))
		{
			return false;
		}

		return $ret;
	}

	/**
	 * Default task. Assigns a model to the view and asks the view to render
	 * itself.
	 *
	 * @return  void
	 */
	public function display()
	{
		$viewType = $this->input->getCmd('format', 'html');

		$view = $this->getView();
		$view->setTask($this->task);
		$view->setDoTask($this->doTask);

		// Get/Create the model
		if ($model = $this->getModel())
		{
			// Push the model into the view (as default)
			$view->setDefaultModel($model);
		}

		// Set the layout
		if (!is_null($this->layout))
		{
			$view->setLayout($this->layout);
		}

		// Display the view
		$view->display();
	}

	/**
	 * Alias to the display() task
	 *
	 * @codeCoverageIgnore
	 */
	public function main()
	{
		$this->display();
	}

	/**
	 * Returns a named Model object
	 *
	 * @param   string  $name    The Model name. If null we'll use the modelName
	 *                           variable or, if it's empty, the same name as
	 *                           the Controller
	 * @param   array   $config  Configuration parameters to the Model. If skipped
	 *                           we will use $this->config
	 *
	 * @return  Model  The instance of the Model known to this Controller
	 */
	public function getModel($name = null, $config = [])
	{
		if (!empty($name))
		{
			$modelName = strtolower($name);
		}
		elseif (!empty($this->modelName))
		{
			$modelName = strtolower($this->modelName);
		}
		else
		{
			$modelName = strtolower($this->view);
		}

		if (!array_key_exists($modelName, $this->modelInstances))
		{
			$mvcConfig                     = $this->container['mvc_config'] ?? [];
			$config                        = $config ?: $this->config;
			$this->container['mvc_config'] = $config;

			if (empty($name))
			{
				// Default model instances must have state management enabled
				$this->modelInstances[$modelName] = $this->container->mvcFactory->makeModel($modelName, $this->getLanguage());
			}
			else
			{
				// Other classes are loaded with persistent state disabled and their state/input blanked out
				$this->modelInstances[$modelName] = $this->container->mvcFactory->makeModel($modelName, $this->getLanguage())
					->clearState()
					->clearInput();
			}

			$this->container['mvc_config'] = $mvcConfig;
		}

		return $this->modelInstances[$modelName];
	}

	/**
	 * Returns a named View object
	 *
	 * @param   string  $name    The Model name. If null we'll use the modelName
	 *                           variable or, if it's empty, the same name as
	 *                           the Controller
	 * @param   array   $config  Configuration parameters to the Model. If skipped
	 *                           we will use $this->config
	 *
	 * @return  View  The instance of the Model known to this Controller
	 */
	public function getView($name = null, $config = [])
	{
		if (!empty($name))
		{
			$viewName = strtolower($name);
		}
		elseif (!empty($this->viewName))
		{
			$viewName = strtolower($this->viewName);
		}
		else
		{
			$viewName = strtolower($this->view);
		}

		if (!array_key_exists($viewName, $this->viewInstances))
		{
			$appName = $this->container->application->getName();

			if (empty($config))
			{
				$config = $this->config;
			}

			$viewType = $this->input->getCmd('format', 'html');

			$this->container['mvc_config'] = $config;

			$this->viewInstances[$viewName] = $this->container->mvcFactory->makeView($viewName, $viewType, $this->getLanguage());
		}

		return $this->viewInstances[$viewName];
	}

	/**
	 * Pushes a named view to the Controller
	 *
	 * @param   string  $viewName  The name of the View
	 * @param   View    $view      The actual View object to push
	 *
	 * @return  void
	 */
	public function setView($viewName, View &$view)
	{
		$this->viewInstances[$viewName] = $view;
	}

	/**
	 * Set the name of the view to be used by this Controller
	 *
	 * @param   string  $viewName  The name of the view
	 *
	 * @return  void
	 */
	public function setViewName($viewName)
	{
		$this->viewName = $viewName;
	}

	/**
	 * Set the name of the model to be used by this Controller
	 *
	 * @param   string  $modelName  The name of the model
	 *
	 * @return  void
	 */
	public function setModelName($modelName)
	{
		$this->modelName = $modelName;
	}

	/**
	 * Pushes a named model to the Controller
	 *
	 * @param   string  $modelName  The name of the Model
	 * @param   Model   $model      The actual Model object to push
	 *
	 * @return  void
	 */
	public function setModel($modelName, Model &$model)
	{
		$this->modelInstances[strtolower($modelName)] = $model;
	}

	/**
	 * Method to get the controller name
	 *
	 * The controller name is set by default parsed using the classname, or it can be set by passing a $config['name']
	 * in the class constructor.
	 *
	 * @return  string  The name of the controller
	 *
	 * @throws  RuntimeException  If it's impossible to determine the name and it's not set
	 */
	public function getName()
	{
		if (empty($this->name))
		{
			$r = null;

			if (!preg_match('/(.*)\\\\Controller\\\\(.*)/i', get_class($this), $r))
			{
				throw new RuntimeException($this->getContainer()->language->text('AWF_APPLICATION_ERROR_CONTROLLER_GET_NAME'), 500);
			}

			$this->name = $r[2];
		}

		return $this->name;
	}

	/**
	 * Get the last task that is being performed or was most recently performed.
	 *
	 * @return  string  The task that is being performed or was most recently performed.
	 */
	public function getTask()
	{
		return $this->task;
	}

	/**
	 * Gets the available tasks in the controller.
	 *
	 * @return  array  Array[i] of task names.
	 */
	public function getTasks()
	{
		return $this->methods;
	}

	/**
	 * Redirects the browser or returns false if no redirect is set.
	 *
	 * @return  boolean  False if no redirect exists.
	 */
	public function redirect(): bool
	{
		if ($this->redirect)
		{
			$this->container->application->redirect($this->redirect, $this->message, $this->messageType);

			return true;
		}

		return false;
	}

	/**
	 * Register the default task to perform if a mapping is not found.
	 *
	 * @param   string  $method  The name of the method in the derived class to perform if a named task is not found.
	 *
	 * @return  Controller  This object to support chaining.
	 */
	public function registerDefaultTask($method)
	{
		$this->registerTask('__default', $method);

		return $this;
	}

	/**
	 * Register (map) a task to a method in the class.
	 *
	 * @param   string  $task    The task.
	 * @param   string  $method  The name of the method in the derived class to perform for this task.
	 *
	 * @return  Controller  This object to support chaining.
	 */
	public function registerTask($task, $method)
	{
		if (in_array(strtolower($method), $this->methods))
		{
			$this->taskMap[strtolower($task)] = $method;
		}

		return $this;
	}

	/**
	 * Unregister (unmap) a task in the class.
	 *
	 * @param   string  $task  The task.
	 *
	 * @return  Controller  This object to support chaining.
	 */
	public function unregisterTask($task)
	{
		unset($this->taskMap[strtolower($task)]);

		return $this;
	}

	/**
	 * Sets the internal message that is passed with a redirect
	 *
	 * @param   string  $text  Message to display on redirect.
	 * @param   string  $type  Message type. Optional, defaults to 'message'.
	 *
	 * @return  string  Previous message
	 */
	public function setMessage($text, $type = 'message')
	{
		$previous          = $this->message;
		$this->message     = $text;
		$this->messageType = $type;

		return $previous;
	}

	/**
	 * Set a URL for browser redirection.
	 *
	 * @param   string  $url   URL to redirect to.
	 * @param   string  $msg   Message to display on redirect. Optional, defaults to value set internally by
	 *                         controller, if any.
	 * @param   string  $type  Message type. Optional, defaults to 'message' or the type set by a previous call to
	 *                         setMessage.
	 *
	 * @return  Controller   This object to support chaining.
	 */
	public function setRedirect($url, $msg = null, $type = null)
	{
		$this->redirect = $url;
		if ($msg !== null)
		{
			// Controller may have set this directly
			$this->message = $msg;
		}

		// Ensure the type is not overwritten by a previous call to setMessage.
		if (empty($type))
		{
			if (empty($this->messageType))
			{
				$this->messageType = 'info';
			}
		}
		// If the type is explicitly set, set it.
		else
		{
			$this->messageType = $type;
		}

		return $this;
	}

	/**
	 * Provides CSRF protection through the forced use of a secure token. If the token doesn't match the one in the
	 * session we die() immediately.
	 *
	 * @param   bool  $useCMS  If a token is not found, should we try to use CMS functions?
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 */
	protected function csrfProtection($useCMS = false)
	{
		$inCMS      = $this->container->segment->get('insideCMS', false);
		$tokenValue = $this->container->session->getCsrfToken()->getValue();
		$token      = $this->input->get('token', '', 'raw');

		if ($token == $tokenValue)
		{
			$isValidToken = true;
		}
		else
		{
			$altToken     = $this->input->get($tokenValue, 0, 'int');
			$isValidToken = $altToken == 1;
		}

		// TODO Maybe we should create a real provider for supporting all CMS etc etc but in reality we're only in WordPress, so...
		// We didn't found any valid token, but we're inside a CMS and we were asked to check with CSRF functions
		if (!$isValidToken && $useCMS && $inCMS)
		{
			// If we're inside WordPress, let's get the nonce and the action used to generate it
			if (\function_exists('wp_verify_nonce'))
			{
				$wp_token  = $this->input->get('_wpnonce', '', 'raw');
				$wp_action = $this->input->get('_wpaction', '');

				$isValidToken = \wp_verify_nonce($wp_token, $wp_action);
			}
		}

		if (!$isValidToken)
		{
			throw new Exception('Invalid security token', 500);
		}
	}
}
