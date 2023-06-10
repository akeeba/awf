<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Mvc;

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Input\Input;
use Awf\Mvc\Engine\EngineInterface;
use Awf\Text\Text;
use Awf\Uri\Uri;
use ErrorException;
use Exception;
use RuntimeException;

/**
 * Class View
 *
 * A generic MVC view implementation
 *
 * @package Awf\Mvc
 */
#[\AllowDynamicProperties]
class View
{
	/**
	 * Current or most recently performed task.
	 * Currently public, it should be reduced to protected in the future
	 *
	 * @var  string
	 */
	public $task;

	/**
	 * The mapped task that was performed.
	 * Currently public, it should be reduced to protected in the future
	 *
	 * @var  string
	 */
	public $doTask;

	/**
	 * The name of the view
	 *
	 * @var    array
	 */
	protected $name = null;

	/**
	 * Registered models
	 *
	 * @var    array
	 */
	protected $modelInstances = [];

	/**
	 * The default model
	 *
	 * @var    string
	 */
	protected $defaultModel = null;

	/**
	 * Layout name
	 *
	 * @var    string
	 */
	protected $layout = 'default';

	/**
	 * Layout template
	 *
	 * @var    string
	 */
	protected $layoutTemplate = '_';

	/**
	 * The set of search directories for view templates
	 *
	 * @var   array
	 */
	protected $templatePaths = [];

	/**
	 * The name of the default template source file.
	 *
	 * @var   string
	 */
	protected $template = null;

	/**
	 * The output of the template script.
	 *
	 * @var   string
	 */
	protected $output = null;

	/**
	 * A cached copy of the configuration
	 *
	 * @var   array
	 */
	protected $config = [];

	/**
	 * The input object
	 *
	 * @var   Input
	 */
	protected $input = null;

	/**
	 * The container attached to this view
	 *
	 * @var   Container
	 */
	protected $container;

	/**
	 * Aliases of view templates. For example:
	 *
	 * array('userProfile' => 'users/profile')
	 *
	 * allows you to do something like $this->loadAnyTemplate('userProfile') to display the view template users/profile.
	 * You can also alias one view template with another, e.g. 'users/profile' => 'clients/record'
	 *
	 * @var  array
	 */
	protected $viewTemplateAliases = [];

	/**
	 * The object used to locate view templates in the filesystem
	 *
	 * @var   ViewTemplateFinder
	 */
	protected $viewFinder = null;

	/**
	 * Used when loading template files to avoid variable scope issues
	 *
	 * @var   null
	 */
	protected $_tempFilePath = null;

	/**
	 * Maps view template extensions to view engine classes
	 *
	 * @var    array
	 */
	protected $viewEngineMap = [
		'.blade.php' => 'Awf\\Mvc\\Engine\\BladeEngine',
		'.php'       => 'Awf\\Mvc\\Engine\\PhpEngine',
	];

	/**
	 * All of the finished, captured sections.
	 *
	 * @var array
	 */
	protected $sections = [];

	/**
	 * The stack of in-progress sections.
	 *
	 * @var array
	 */
	protected $sectionStack = [];

	/**
	 * The number of active rendering operations.
	 *
	 * @var int
	 */
	protected $renderCount = 0;

	/**
	 * Should I be strict about the subtemplate being loaded?
	 *
	 * When false, trying to load template `foo_bar` will fall back to `foo` is `foo_bar` does not exist.
	 *
	 * When true, trying to load template `foo_bar` will throw if `foo_bar` does not exist.
	 *
	 * @var bool
	 * @since 1.0.1
	 */
	protected $strictTpl = false;

	/**
	 * Should I be strict about the layout being loaded?
	 *
	 * When false, trying to load layout `foo` will fall back to `default` if `foo` does not exist.
	 *
	 * When true, trying to load layout `foo` will throw if `foo` does not exist.
	 *
	 * @var bool
	 * @since 1.0.1
	 */
	protected $strictLayout = false;

	/**
	 * Constructor
	 *
	 * @param   Container  $container  A named configuration array for object construction.<br/>
	 *                                 Inside it you can have an 'mvc_config' array with the following options:<br/>
	 *                                 name: the name (optional) of the view (defaults to the view class name
	 *                                 suffix).<br/> escape: the name (optional) of the function to use for escaping
	 *                                 strings<br/> template_path: the path (optional) of the layout directory
	 *                                 (defaults to base_path + /views/ + view name<br/> layout: the layout (optional)
	 *                                 to use to display the view<br/>
	 *
	 * @return  View
	 */
	public function __construct($container = null)
	{
		// Make sure we have a container
		if (!is_object($container))
		{
			$container = Application::getInstance()->getContainer();
		}

		$container->eventDispatcher->trigger('onViewBeforeConstruct', [$this, $container]);

		// Cache some useful references in the class
		$this->input = $container->input;

		$this->container = $container;

		$this->config = isset($container['mvc_config']) ? $container['mvc_config'] : [];

		// Get the view name
		$this->name = $this->getName();

		// Set the default template search path
		if (array_key_exists('template_path', $this->config))
		{
			// User-defined dirs
			$this->setTemplatePath($this->config['template_path']);
		}
		else
		{
			$this->setTemplatePath($this->container->basePath . '/View/' . ucfirst($this->name) . '/tmpl');
		}

		// Set the layout
		if (array_key_exists('layout', $this->config))
		{
			$this->setLayout($this->config['layout']);
		}

		$templatePath = $this->container->templatePath;
		$fallback     = $templatePath . '/' . $this->container->application->getTemplate() . '/html/' . ucfirst($this->container->application->getName()) . '/' . $this->name;
		$this->addTemplatePath($fallback);

		// Get extra directories through event dispatchers
		$extraPathsResults = $this->container->eventDispatcher->trigger('onGetViewTemplatePaths', [$this->getName()]);

		if (is_array($extraPathsResults) && !empty($extraPathsResults))
		{
			foreach ($extraPathsResults as $somePaths)
			{
				if (!empty($somePaths))
				{
					foreach ($somePaths as $aPath)
					{
						$this->addTemplatePath($aPath);
					}
				}
			}
		}

		// Apply the viewEngineMap
		if (isset($this->config['viewEngineMap']))
		{
			if (!is_array($this->config['viewEngineMap']))
			{
				$temp                          = explode(',', $this->config['viewEngineMap']);
				$this->config['viewEngineMap'] = [];

				foreach ($temp as $assignment)
				{
					$parts = explode('=>', $assignment, 2);

					if (count($parts) != 2)
					{
						continue;
					}

					$parts = array_map(function ($x) { return trim($x); }, $parts);

					$this->config['viewEngineMap'][$parts[0]] = $parts[1];
				}
			}

			$this->viewEngineMap = array_merge($this->viewEngineMap, $this->config['viewEngineMap']);
		}

		// Set the ViewFinder
		$this->viewFinder = new ViewTemplateFinder($this);

		if (isset($this->config['viewFinder']) && !empty($this->config['viewFinder']) && is_object($this->config['viewFinder']) && ($this->config['viewFinder'] instanceof ViewTemplateFinder))
		{
			$this->viewFinder = $this->config['viewFinder'];
		}

		// Apply the registered view template extensions to the view finder
		$this->viewFinder->setExtensions(array_keys($this->viewEngineMap));

		$this->baseurl = Uri::base(true, $this->container);

		$container->eventDispatcher->trigger('onViewAfterConstruct', [$this, $container]);
	}

	/**
	 * Returns an instance of a view class
	 *
	 * @param   null       $appName    The application name [optional] Default: from container or default app if no
	 *                                 container is provided
	 * @param   null       $viewName   The view name [optional] Default: the "view" input parameter
	 * @param   null       $viewType   The view type [optional] Default: the "format" input parameter or, if not
	 *                                 defined, "html"
	 * @param   Container  $container  The container to be attached to the view
	 *
	 * @return mixed
	 */
	public static function &getInstance($appName = null, $viewName = null, $viewType = null, $container = null)
	{
		if (empty($appName) && !is_object($container))
		{
			$app       = Application::getInstance();
			$appName   = $app->getName();
			$container = $app->getContainer();
		}
		elseif (empty($appName) && is_object($container))
		{
			$appName = $container->application_name;
		}
		elseif (!empty($appName) && !is_object($container))
		{
			$container = Application::getInstance($appName)->getContainer();
		}

		$input = $container->input;

		if (empty($viewName))
		{
			$viewName = $input->getCmd('view', '');
		}

		if (empty($viewType))
		{
			$viewType = $input->getCmd('format', 'html');
		}

		$classNames = [
			$container->applicationNamespace . '\\View\\' . ucfirst($viewName) . '\\' . ucfirst($viewType),
			$container->applicationNamespace . '\\View\\' . ucfirst($viewName) . '\\DefaultView',
			$container->applicationNamespace . '\\View\\Default\\' . ucfirst($viewType),
			$container->applicationNamespace . '\\View\\DefaultView',
		];

		foreach ($classNames as $className)
		{
			if (class_exists($className))
			{
				break;
			}
		}

		if (!class_exists($className))
		{
			throw new RuntimeException("View not found (app : view : type) = $appName : $viewName : $viewType");
		}

		$object = new $className($container);

		return $object;
	}

	/**
	 * Method to get the view name
	 *
	 * The model name by default parsed using the classname, or it can be set
	 * by passing a $config['name'] in the class constructor
	 *
	 * @return  string  The name of the model
	 *
	 * @throws  RuntimeException
	 */
	public function getName()
	{
		if (empty($this->name))
		{
			$r = null;

			if (!preg_match('/(.*)\\\\View\\\\(.*)\\\\(.*)/i', get_class($this), $r))
			{
				throw new RuntimeException(Text::_('AWF_APPLICATION_ERROR_VIEW_GET_NAME'), 500);
			}

			$this->name = $r[2];
		}

		return $this->name;
	}

	/**
	 * Escapes a value for output in a view script.
	 *
	 * @param   mixed  $var  The output to escape.
	 *
	 * @return  mixed  The escaped value.
	 */
	public function escape($var)
	{
		return htmlspecialchars($var ?? '', ENT_COMPAT, 'UTF-8');
	}

	/**
	 * Method to get data from a registered model or a property of the view
	 *
	 * @param   string  $property   The name of the method to call on the Model or the property to get
	 * @param   string  $default    The default value [optional]
	 * @param   string  $modelName  The name of the Model to reference [optional]
	 *
	 * @return  mixed  The return value of the method
	 */
	public function get($property, $default = null, $modelName = null)
	{
		// If $model is null we use the default model
		if (is_null($modelName))
		{
			$model = $this->defaultModel;
		}
		else
		{
			$model = strtolower($modelName);
		}

		// First check to make sure the model requested exists
		if (isset($this->modelInstances[$model]))
		{
			// Model exists, let's build the method name
			$method = 'get' . ucfirst($property);

			// Does the method exist?
			if (method_exists($this->modelInstances[$model], $method))
			{
				// The method exists, let's call it and return what we get
				$result = $this->modelInstances[$model]->$method();

				return $result;
			}
			else
			{
				$result = $this->modelInstances[$model]->$property();

				if (is_null($result))
				{
					return $default;
				}

				return $result;
			}
		}
		// If the model doesn't exist, try to fetch a View property
		else
		{
			if (@isset($this->$property))
			{
				return $this->$property;
			}
			else
			{
				return $default;
			}
		}
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
		elseif (!empty($this->defaultModel))
		{
			$modelName = strtolower($this->defaultModel);
		}
		else
		{
			$modelName = strtolower($this->name);
		}

		if (!array_key_exists($modelName, $this->modelInstances))
		{
			$appName = $this->container->application->getName();

			if (empty($config))
			{
				$config = $this->config;
			}

			$this->container['mvc_config'] = $config;

			$this->modelInstances[$modelName] = Model::getInstance($appName, $modelName, $this->container);
		}

		return $this->modelInstances[$modelName];
	}

	/**
	 * Pushes the default Model to the View
	 *
	 * @param   Model  $model  The model to push
	 */
	public function setDefaultModel(Model &$model)
	{
		$name = $model->getName();

		$this->setDefaultModelName($name);
		$this->setModel($this->defaultModel, $model);
	}

	/**
	 * Set the name of the Model to be used by this View
	 *
	 * @param   string  $modelName  The name of the Model
	 *
	 * @return  void
	 */
	public function setDefaultModelName($modelName)
	{
		$this->defaultModel = $modelName;
	}

	/**
	 * Pushes a named model to the View
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
	 * Overrides the default method to execute and display a template script.
	 * Instead of loadTemplate is uses loadAnyTemplate.
	 *
	 * @param   string  $tpl  The name of the template file to parse
	 *
	 * @return  boolean  True on success
	 *
	 * @throws  Exception  When the layout file is not found
	 */
	public function display($tpl = null)
	{
		$method = 'onBefore' . ucfirst($this->doTask);
		if (method_exists($this, $method))
		{
			$result = $this->$method($tpl);

			if (!$result)
			{
				throw new Exception(Text::_('AWF_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
			}
		}

		$results = $this->container->eventDispatcher->trigger('onViewBefore' . ucfirst($this->doTask), [$this]) ?: [];

		if (in_array(false, $results, true))
		{
			return false;
		}

		$result = $this->loadTemplate($tpl);

		$method = 'onAfter' . ucfirst($this->doTask);

		if (method_exists($this, $method))
		{
			$result = $this->$method($tpl);

			if (!$result)
			{
				throw new Exception(Text::_('AWF_APPLICATION_ERROR_ACCESS_FORBIDDEN'), 403);
			}
		}

		$results = $this->container->eventDispatcher->trigger('onViewAfter' . ucfirst($this->doTask), [
			$this, &$result,
		]) ?: [];

		if (in_array(false, $results, true))
		{
			return false;
		}

		if (is_object($result) && ($result instanceof Exception))
		{
			throw $result;
		}
		else
		{
			echo $result;

			return true;
		}
	}

	/**
	 * Our function uses loadAnyTemplate to provide smarter view template loading.
	 *
	 * @param   string   $tpl     The name of the template file to parse
	 * @param   boolean  $strict  Should we use strict naming, i.e. force a non-empty $tpl?
	 *
	 * @return  mixed  A string if successful, otherwise an Exception
	 */
	public function loadTemplate($tpl = null, $strict = false)
	{
		$basePath = $this->name . '/';

		$strict = $strict || $this->strictTpl;

		if ($strict)
		{
			$paths = [$basePath . $this->getLayout() . ($tpl ? "_$tpl" : '')];

			if (!$this->strictLayout)
			{
				$paths[] = $basePath . 'default' . ($tpl ? "_$tpl" : '');
			}
		}
		else
		{
			$paths = [
				$basePath . $this->getLayout() . ($tpl ? "_$tpl" : ''),
				$basePath . $this->getLayout(),
			];

			if (!$this->strictLayout)
			{
				$paths[] = $basePath . 'default' . ($tpl ? "_$tpl" : '');
				$paths[] = $basePath . 'default';
			}
		}

		$paths = array_unique($paths);

		foreach ($paths as $path)
		{
			try
			{
				$result = $this->loadAnyTemplate($path);
			}
			catch (Exception $e)
			{
				$result = $e;
			}

			if (!($result instanceof Exception))
			{
				break;
			}
		}

		return $result;
	}

	/**
	 * Get the layout.
	 *
	 * @return  string  The layout name
	 */
	public function getLayout()
	{
		return $this->layout;
	}

	/**
	 * Sets the layout name to use
	 *
	 * @param   string  $layout  The layout name or a string in format <template>:<layout file>
	 *
	 * @return  string  Previous value.
	 */
	public function setLayout($layout)
	{
		$previous = $this->layout;
		if (strpos($layout, ':') === false)
		{
			$this->layout = $layout;
		}
		else
		{
			// Convert parameter to array based on :
			$temp         = explode(':', $layout);
			$this->layout = $temp[1];

			// Set layout template
			$this->layoutTemplate = $temp[0];
		}

		return $previous;
	}

	/**
	 * Add an alias for a view template.
	 *
	 * @param   string  $viewTemplate  Existing view template, in the format viewName/layoutName
	 * @param   string  $alias         The alias of the view template (any string will do)
	 *
	 * @return void
	 */
	public function alias($viewTemplate, $alias)
	{
		$this->viewTemplateAliases[$alias] = $viewTemplate;
	}

	/**
	 * Loads a template given any path. The path is in the format viewName/layoutName
	 *
	 * @param   string    $uri          The template path
	 * @param   array     $forceParams  A hash array of variables to be extracted in the local scope of the template
	 *                                  file
	 * @param   callable  $callback     A method to post-process the 3ναluα+3d view template (I use leetspeak here
	 *                                  because of bad quality hosts with broken scanners)
	 *
	 * @return  string  The output of the template
	 *
	 * @throws  Exception  When the layout file is not found
	 */
	public function loadAnyTemplate($uri = '', $forceParams = [], $callback = null)
	{
		if (isset($this->viewTemplateAliases[$uri]))
		{
			$uri = $this->viewTemplateAliases[$uri];
		}

		$layoutTemplate = $this->getLayoutTemplate();

		$extraPaths = [];

		if (!empty($this->templatePaths))
		{
			$extraPaths = $this->templatePaths;
		}

		// First get the raw view template path
		$path = $this->viewFinder->resolveUriToPath($uri, $layoutTemplate, $extraPaths);

		// Now get the parsed view template path
		$this->_tempFilePath = $this->getEngine($path)->get($path, $forceParams);

		// We will keep track of the amount of views being rendered so we can flush
		// the section after the complete rendering operation is done. This will
		// clear out the sections for any separate views that may be rendered.
		$this->incrementRender();

		// Get the processed template
		$contents = $this->processTemplate($forceParams);

		// Once we've finished rendering the view, we'll decrement the render count
		// so that each sections get flushed out next time a view is created and
		// no old sections are staying around in the memory of an environment.
		$this->decrementRender();

		$response = isset($callback) ? $callback($this, $contents) : null;

		if (!is_null($response))
		{
			$contents = $response;
		}

		// Once we have the contents of the view, we will flush the sections if we are
		// done rendering all views so that there is nothing left hanging over when
		// another view gets rendered in the future by the application developer.
		$this->flushSectionsIfDoneRendering();

		return $contents;
	}

	/**
	 * Increment the rendering counter.
	 *
	 * @return void
	 */
	public function incrementRender()
	{
		$this->renderCount++;
	}

	/**
	 * Decrement the rendering counter.
	 *
	 * @return void
	 */
	public function decrementRender()
	{
		$this->renderCount--;
	}

	/**
	 * Check if there are no active render operations.
	 *
	 * @return bool
	 */
	public function doneRendering()
	{
		return $this->renderCount == 0;
	}

	/**
	 * Go through a data array and render a subtemplate against each record (think master-detail views). This is
	 * accessible through Blade templates as @each
	 *
	 * @param   string  $viewTemplate  The view template to use for each subitem, format viewName/layoutName
	 * @param   array   $data          The array of data you want to render. It can be a DataModel\Collection, array,
	 *                                 ...
	 * @param   string  $eachItemName  How to call each item in the loaded subtemplate (passed through $forceParams)
	 * @param   string  $empty         What to display if the array is empty
	 *
	 * @return string
	 * @throws Exception
	 */
	public function renderEach($viewTemplate, $data, $eachItemName, $empty = 'raw|')
	{
		$result = '';

		// If is actually data in the array, we will loop through the data and append
		// an instance of the partial view to the final result HTML passing in the
		// iterated value of this data array, allowing the views to access them.
		if (count($data) > 0)
		{
			foreach ($data as $key => $value)
			{
				$data = ['key' => $key, $eachItemName => $value];

				$result .= $this->loadAnyTemplate($viewTemplate, $data);
			}
		}
		// If there is no data in the array, we will render the contents of the empty
		// view. Alternatively, the "empty view" could be a raw string that begins
		// with "raw|" for convenience and to let this know that it is a string. Or
		// a language string starting with text|.
		else
		{
			if (akeeba_starts_with($empty, 'raw|'))
			{
				$result = substr($empty, 4);
			}
			elseif (akeeba_starts_with($empty, 'text|'))
			{
				$result = Text::_(substr($empty, 5));
			}
			else
			{
				$result = $this->loadAnyTemplate($empty);
			}
		}

		return $result;
	}

	/**
	 * Start injecting content into a section.
	 *
	 * @param   string  $section
	 * @param   string  $content
	 *
	 * @return void
	 */
	public function startSection($section, $content = '')
	{
		if ($content === '')
		{
			if (ob_start())
			{
				$this->sectionStack[] = $section;
			}
		}
		else
		{
			$this->extendSection($section, $content);
		}
	}

	/**
	 * Stop injecting content into a section and return its contents.
	 *
	 * @return string
	 */
	public function yieldSection()
	{
		return $this->yieldContent($this->stopSection());
	}

	/**
	 * Stop injecting content into a section.
	 *
	 * @param   bool  $overwrite
	 *
	 * @return string
	 */
	public function stopSection($overwrite = false)
	{
		if (empty($this->sectionStack))
		{
			// Let's close the output buffering
			ob_get_clean();

			throw new RuntimeException("Blade template renderer: the section stack is empty");
		}

		$last = array_pop($this->sectionStack);

		if ($overwrite)
		{
			$this->sections[$last] = ob_get_clean();
		}
		else
		{
			$this->extendSection($last, ob_get_clean());
		}

		return $last;
	}

	/**
	 * Stop injecting content into a section and append it.
	 *
	 * @return string
	 */
	public function appendSection()
	{
		if (empty($this->sectionStack))
		{
			// Let's close the output buffering
			ob_get_clean();

			throw new RuntimeException("Blade template renderer: the section stack is empty");
		}

		$last = array_pop($this->sectionStack);

		if (isset($this->sections[$last]))
		{
			$this->sections[$last] .= ob_get_clean();
		}
		else
		{
			$this->sections[$last] = ob_get_clean();
		}

		return $last;
	}

	/**
	 * Get the string contents of a section.
	 *
	 * @param   string  $section
	 * @param   string  $default
	 *
	 * @return string
	 */
	public function yieldContent($section, $default = '')
	{
		$sectionContent = $default;

		if (isset($this->sections[$section]))
		{
			$sectionContent = $this->sections[$section];
		}

		return str_replace('@parent', '', $sectionContent);
	}

	/**
	 * Flush all of the section contents.
	 *
	 * @return void
	 */
	public function flushSections()
	{
		$this->sections = [];

		$this->sectionStack = [];
	}

	/**
	 * Flush all of the section contents if done rendering.
	 *
	 * @return void
	 */
	public function flushSectionsIfDoneRendering()
	{
		if ($this->doneRendering())
		{
			$this->flushSections();
		}
	}

	/**
	 * Get the layout template.
	 *
	 * @return  string  The layout template name
	 */
	public function getLayoutTemplate()
	{
		return $this->layoutTemplate;
	}

	/**
	 * Load a helper file
	 *
	 * @param   string  $helperClass   The last part of the name of the helper
	 *                                 class.
	 *
	 * @return  void
	 */
	public function loadHelper($helperClass = null)
	{
		// Get the helper class name
		$className = '\\' . ucfirst($this->container->application->getName()) . '\\Helper\\' . ucfirst($helperClass);

		// This trick autoloads the helper class. We can't instantiate it as
		// helpers are (supposed to be) abstract classes with static method
		// interfaces.
		class_exists($className);
	}

	/**
	 * Returns a reference to the container attached to this View
	 *
	 * @return Container
	 */
	public function &getContainer()
	{
		return $this->container;
	}

	public function getTask()
	{
		return $this->task;
	}

	/**
	 * @param   string  $task
	 *
	 * @return  $this   This for chaining
	 */
	public function setTask($task)
	{
		$this->task = $task;

		return $this;
	}

	public function getDoTask()
	{
		return $this->doTask;
	}

	/**
	 * @param   string  $task
	 *
	 * @return  $this   This for chaining
	 */
	public function setDoTask($task)
	{
		$this->doTask = $task;

		return $this;
	}

	/**
	 * Setter for the strictTpl flag
	 *
	 * @param   bool  $strictTpl  The value to set it to
	 *
	 * @see   self::$strictTpl
	 * @since 1.0.0
	 */
	public function setStrictTpl(bool $strictTpl): void
	{
		$this->strictTpl = $strictTpl;
	}

	/**
	 * Setter for the strictLayout flag
	 *
	 * @param   bool  $strictLayout  The value to set it to
	 *
	 * @see   self::$strictLayout
	 * @since 1.0.0
	 */
	public function setStrictLayout(bool $strictLayout): void
	{
		$this->strictLayout = $strictLayout;
	}

	/**
	 * Sets an entire array of search paths for templates or resources.
	 *
	 * @param   mixed  $path  The new search path, or an array of search paths.  If null or false, resets to the
	 *                        current directory only.
	 *
	 * @return  void
	 */
	protected function setTemplatePath($path)
	{
		// Clear out the prior search dirs
		$this->templatePaths = [];

		// Actually add the user-specified directories
		$this->addTemplatePath($path);

		// Set the alternative template search dir
		$templatePath = $this->container->templatePath;
		$fallback     = $templatePath . '/' . $this->container->application->getTemplate() . '/html/' . strtoupper($this->container->application->getName()) . '/' . $this->getName();
		$this->addTemplatePath($fallback);

		// Get extra directories through event dispatchers
		$extraPathsResults = $this->container->eventDispatcher->trigger('onGetViewTemplatePaths', [$this->getName()]);

		if (is_array($extraPathsResults) && !empty($extraPathsResults))
		{
			foreach ($extraPathsResults as $somePaths)
			{
				if (!empty($somePaths))
				{
					foreach ($somePaths as $aPath)
					{
						$this->addTemplatePath($aPath);
					}
				}
			}
		}
	}

	/**
	 * Adds to the search path for templates and resources.
	 *
	 * @param   mixed  $path  The directory or stream, or an array of either, to search.
	 *
	 * @return  void
	 */
	protected function addTemplatePath($path)
	{
		// Just force to array
		settype($path, 'array');

		// Loop through the path directories
		foreach ($path as $dir)
		{
			// No surrounding spaces allowed!
			$dir = trim($dir);

			// Add trailing separators as needed
			if (substr($dir, -1) != DIRECTORY_SEPARATOR)
			{
				// Directory
				$dir .= DIRECTORY_SEPARATOR;
			}

			// Add to the top of the search dirs
			array_unshift($this->templatePaths, $dir);
		}
	}

	/**
	 * Get the appropriate view engine for the given view template path.
	 *
	 * @param   string  $path  The path of the view template
	 *
	 * @return  EngineInterface
	 *
	 * @throws  RuntimeException
	 */
	protected function getEngine($path)
	{
		foreach ($this->viewEngineMap as $extension => $engine)
		{
			if (substr($path, -strlen($extension)) == $extension)
			{
				return new $engine($this);
			}
		}

		throw new RuntimeException(sprintf("Unrecognised extension in view template “%s”", $path));
	}

	/**
	 * Get the extension used by the view file.
	 *
	 * @param   string  $path
	 *
	 * @return string
	 */
	protected function getExtension($path)
	{
		$extensions = array_keys($this->viewEngineMap);

		return akeeba_array_first($extensions, function ($key, $value) use ($path) {
			return akeeba_ends_with($path, $value);
		});
	}

	/**
	 * Append content to a given section.
	 *
	 * @param   string  $section
	 * @param   string  $content
	 *
	 * @return void
	 */
	protected function extendSection($section, $content)
	{
		if (isset($this->sections[$section]))
		{
			$content = str_replace('@parent', $content, $this->sections[$section]);
		}

		$this->sections[$section] = $content;
	}

	/**
	 * Evaluates the template described in the _tempFilePath property
	 *
	 * @param   array  $forceParams  Forced parameters
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function processTemplate(array &$forceParams)
	{
		// If the engine returned raw content, return the raw content immediately
		if ($this->_tempFilePath['type'] == 'raw')
		{
			return $this->_tempFilePath['content'];
		}

		if (substr($this->_tempFilePath['content'], 0, 4) == 'raw|')
		{
			return substr($this->_tempFilePath['content'], 4);
		}

		$obLevel = ob_get_level();

		ob_start();

		// We'll process the contents of the view inside a try/catch block so we can
		// flush out any stray output that might get out before an error occurs or
		// an exception is thrown. This prevents any partial views from leaking.
		try
		{
			$this->includeTemplateFile($forceParams);
		}
		catch (Exception $e)
		{
			$this->handleViewException($e, $obLevel);
		}

		return ob_get_clean();
	}

	/**
	 * Handle a view exception.
	 *
	 * @param   Exception  $e        The exception to handle
	 * @param   int        $obLevel  The target output buffering level
	 *
	 * @return  void
	 *
	 * @throws  $e
	 */
	protected function handleViewException(Exception $e, $obLevel)
	{
		while (ob_get_level() > $obLevel)
		{
			ob_end_clean();
		}

		$message = $e->getMessage() . ' (View template: ' . realpath($this->_tempFilePath['content']) . ')';

		$newException = new ErrorException($message, 0, 1, $e->getFile(), $e->getLine(), $e);

		throw $newException;
	}

	/**
	 * This method makes sure the current scope isn't polluted with variables when including a view template
	 *
	 * @param   array  $forceParams  Forced parameters
	 *
	 * @return  void
	 */
	private function includeTemplateFile(array &$forceParams)
	{
		// Extract forced parameters
		if (!empty($forceParams))
		{
			extract($forceParams);
		}

		include $this->_tempFilePath['content'];
	}
}
