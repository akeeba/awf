<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Mvc;

use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Container\ContainerAwareTrait;
use Awf\Inflector\Inflector;
use Awf\Text\Language;
use Awf\Text\LanguageAwareInterface;
use Awf\Text\LanguageAwareTrait;
use RuntimeException;

/**
 * MVC Factory.
 *
 * @since   1.1.0
 */
class Factory implements ContainerAwareInterface, LanguageAwareInterface
{
	use ContainerAwareTrait;
	use LanguageAwareTrait;

	public function __construct(Container $container)
	{
		$this->setContainer($container);
		$this->languageObject = null;
	}

	public function getLanguage(): Language
	{
		return $this->languageObject ?? $this->getContainer()->language;
	}

	/**
	 * Create a Controller object.
	 *
	 * @param   string|null  $controller  The name of the controller. NULL for current view.
	 *
	 * @return  Controller
	 * @since   1.1.0
	 */
	public function makeController(?string $controller, ?Language $language = null): Controller
	{
		// Make sure I have a controller name
		$controller = $controller ?? $this->container->input->getCmd('view', '');

		// Get the class name suffixes, in the order to be searched for
		$classes = [
			$this->container->applicationNamespace . '\\Controller\\' . ucfirst($controller),
			$this->container->applicationNamespace . '\\Controller\\' . ucfirst(Inflector::singularize($controller)),
			$this->container->applicationNamespace . '\\Controller\\' . ucfirst(Inflector::pluralize($controller)),
			$this->container->applicationNamespace . '\\Controller\\DefaultController',
		];

		$className = array_reduce(
			$classes,
			function (?string $carry, string $item) {
				return $carry ?? (class_exists($item) ? $item : null);
			},
			null
		);

		if (empty($className))
		{
			throw new RuntimeException(
				sprintf(
					'Controller not found (app : controller) = %s : %s',
					$this->container->application->getName(),
					$controller
				)
			);
		}

		return new $className($this->container, $language ?? $this->getLanguage());
	}

	/**
	 * Create a Model object.
	 *
	 * @param   string|null  $modelName  The name of the model. NULL for current view.
	 *
	 * @return  Model
	 * @since   1.1.0
	 */
	public function makeModel(?string $modelName, ?Language $language = null): Model
	{
		// Make sure I have a model name
		$modelName = $modelName ?? $this->container->input->getCmd('view', '');

		// Try to load the Model class
		$classes = array(
			$this->container->applicationNamespace . '\\Model\\' . ucfirst($modelName),
			$this->container->applicationNamespace . '\\Model\\' . ucfirst(Inflector::singularize($modelName)),
			$this->container->applicationNamespace . '\\Model\\' . ucfirst(Inflector::pluralize($modelName)),
			$this->container->applicationNamespace . '\\Model\\DefaultModel',
		);

		$className = array_reduce(
			$classes,
			function (?string $carry, string $item) {
				return $carry ?? (class_exists($item) ? $item : null);
			},
			null
		);

		if (empty($className))
		{
			throw new RuntimeException(
				sprintf(
					'Model not found (app : model) = %s : %s',
					$this->container->application->getName(),
					$modelName
				)
			);
		}

		/** @var Model $model */
		$model  = new $className($this->container, $language ?? $language ?? $this->getLanguage());
		$config = $container['mvc_config'] ?? [];
		$isDeprecated = false;

		if ($config['modelTemporaryInstance'] ?? false)
		{
			$isDeprecated = true;

			$model = $model
				->getClone()
				->savestate(0);
		}

		if ($config['modelClearState'] ?? false)
		{
			$isDeprecated = true;

			$model->clearState();
		}

		if ($config['modelClearInput'] ?? false)
		{
			$isDeprecated = true;

			$model->clearInput();
		}

		if ($isDeprecated)
		{
			trigger_error(
				'Using the container\'s mvc_config to create a temporary model instance is deprecated. Use the MVC Factory instead.',
				E_USER_DEPRECATED
			);
		}

		return $model;
	}

	/**
	 * Create a temporary Model object.
	 *
	 * @param   string|null  $modelName  The name of the model. NULL for current view.
	 *
	 * @return  Model
	 * @since   1.1.0
	 */
	public function makeTempModel(?string $modelName, ?Language $language = null): Model
	{
		return $this->makeModel($modelName, $language ?? $this->getLanguage())
			->getClone()
			->savestate(0)
			->clearState()
			->clearInput();
	}

	/**
	 * Create a View object
	 *
	 * @param   string|null  $viewName  The name of the view. NULL for current view.
	 * @param   string|null  $viewType  The view type (format). NULL for current format, falls back to 'html'.
	 *
	 * @return  View
	 */
	public function makeView(string $viewName = null, string $viewType = null, ?Language $language = null): View
	{
		// Make sure I have a view name
		$viewName = $viewName ?? $this->container->input->getCmd('view', '');
		// Make sure I have a view type
		$viewType = $viewType ?? $this->container->input->getCmd('format', 'html');

		$classes = [
			$this->container->applicationNamespace . '\\View\\' . ucfirst($viewName) . '\\' . ucfirst($viewType),
			$this->container->applicationNamespace . '\\View\\' . ucfirst($viewName) . '\\DefaultView',
			$this->container->applicationNamespace . '\\View\\Default\\' . ucfirst($viewType),
			$this->container->applicationNamespace . '\\View\\DefaultView',
		];

		$className = array_reduce(
			$classes,
			function (?string $carry, string $item) {
				return $carry ?? (class_exists($item) ? $item : null);
			},
			null
		);

		if (empty($className))
		{
			throw new RuntimeException(
				sprintf(
					'View not found (app : view : type) = %s : %s : %s',
					$this->container->application->getName(),
					$viewName,
					$viewType
				)
			);
		}

		return new $className($this->container, $language ?? $this->getLanguage());
	}
}