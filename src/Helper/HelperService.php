<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Helper;

use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Container\ContainerAwareTrait;
use Awf\Html\HtmlHelperInterface;
use BadMethodCallException;
use InvalidArgumentException;
use OutOfRangeException;
use ReflectionException;
use Throwable;

class HelperService implements ContainerAwareInterface
{
	use ContainerAwareTrait;

	/**
	 * The known helpers
	 *
	 * @var array<HelperInterface>
	 */
	private $helpers = [];

	/**
	 * Constructor.
	 *
	 * @param   Container  $container  The application container.
	 *
	 * @since   1.1.0
	 */
	public function __construct(Container $container)
	{
		$this->setContainer($container);

		if ($container['autoloadHelpers'] = true)
		{
			try
			{
				$this->loadHelpers();
			}
			catch (Throwable $e)
			{
				// Failure is always an option.
			}
		}
	}

	/**
	 * Register a helper object.
	 *
	 * @param   string               $name    The name prefix of the helper. Empty for automatic detection.
	 * @param   HtmlHelperInterface  $helper  The helper object.
	 *
	 * @return  void
	 * @since   1.1.0
	 */
	public function registerHelper(string $name, HtmlHelperInterface $helper): void
	{
		$this->helpers[($name ?: $helper->getName())] = $helper;
	}

	/**
	 * Instantiate and register a helper class.
	 *
	 * @param   string  $class  The class to instantiate and register
	 *
	 * @return  void
	 * @since   1.1.0
	 */
	public function registerHelperClass(string $class): void
	{
		if (!class_exists($class))
		{
			throw new InvalidArgumentException(
				sprintf('Class %s does not exist.', $class), 500
			);
		}

		if (!is_a($class, HelperInterface::class) && !in_array(HelperInterface::class, class_implements($class)))
		{
			throw new InvalidArgumentException(
				sprintf('Class %s is not an HTML Helper.', $class), 500
			);
		}

		$o = new $class();

		if ($o instanceof ContainerAwareInterface)
		{
			$o->setContainer($this->getContainer());
		}

		$this->helpers[$o->getName()] = $o;
	}

	/**
	 * Unregister a helper by name.
	 *
	 * @param   string  $name  The name of the helper to unregister
	 *
	 * @return  void
	 * @since   1.1.0
	 */
	public function unregisterHelper(string $name): void
	{
		if (isset($this->helpers[$name]))
		{
			unset($this->helpers[$name]);
		}
	}

	/**
	 * Unregister a helper by class name.
	 *
	 * @param   string  $class  The class of the helper to unregister
	 *
	 * @return  void
	 * @since   1.1.0
	 */
	public function unregisterHelperClass(string $class): void
	{
		$toUnregister = [];

		foreach ($this->helpers as $key => $object)
		{
			if (is_a($object, $class))
			{
				$toUnregister[] = $key;
			}
		}

		foreach ($toUnregister as $key)
		{
			$this->unregisterHelper($key);
		}
	}

	/**
	 * Does the service know of this helper name (prefix)?
	 *
	 * @param   string  $name  The helper name to test for.
	 *
	 * @return  bool
	 * @since   1.1.0
	 */
	public function hasHelper(string $name): bool
	{
		return array_key_exists($name, $this->helpers);
	}

	/**
	 * Does the service know of any helper(s) implementing the provided class name?
	 *
	 * @param   string  $class  The class name to test for.
	 *
	 * @return  bool
	 * @since   1.1.0
	 */
	public function hasHelperClass(string $class): bool
	{
		foreach ($this->helpers as $object)
		{
			if (is_a($object, $class))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Get a helper object
	 *
	 * @param   string  $name  The name of the helper object to get
	 *
	 * @return  HelperInterface
	 * @throws  OutOfRangeException  When asked for a helper which does not exist
	 */
	public function get(string $name): HelperInterface
	{
		if (!$this->hasHelper($name))
		{
			throw new OutOfRangeException(
				sprintf(
					'Unknown helper ‘%s’',
					htmlentities($name)
				),
				500
			);
		}

		return $this->helpers[$name];
	}

	/**
	 * Executes a helper method
	 *
	 * @param   string  $methodName    The helper and method to execute e.g. `foo.bar`
	 * @param   array   ...$arguments  Optional arguments to the method
	 *
	 * @return  mixed  NULL if the method is void, the method's return otherwise
	 * @throws  ReflectionException  Should never happen, really
	 * @throws  InvalidArgumentException If $methodName has the wrong format
	 * @throws  BadMethodCallException If the method does not exist
	 * @throws  OutOfRangeException  When asked for a helper which does not exist
	 *
	 * @since   1.1.0
	 */
	public function run(string $methodName, ...$arguments)
	{
		if (strpos($methodName, '.') === false)
		{
			throw new InvalidArgumentException(
				sprintf(
					'%s requires the first argument to be in the format ‘helperName.methodName’',
					__METHOD__
				),
				500
			);
		}

		[$helperName, $methodName] = explode('.', $methodName, 2);

		$helper = $this->get($helperName);

		if (!method_exists($helper, $methodName))
		{
			throw new BadMethodCallException(
				sprintf(
					'Unknown method ‘%s’ in helper ‘%s‘.',
					htmlentities($methodName),
					htmlentities($helperName)
				),
				500
			);
		}

		$refObject  = new \ReflectionObject($helper);
		$refMethod  = $refObject->getMethod($methodName);
		$returnType = $refMethod->getReturnType();

		if ($returnType instanceof \ReflectionNamedType && $returnType->isBuiltin()
		    && $returnType->getName() === 'void')
		{
			$helper->{$methodName}(...$arguments);

			return null;
		}

		return $helper->{$methodName}(...$arguments);
	}

	/**
	 * Magic getter.
	 *
	 * Allows you to do `$container->helper->foobar->something()` instead of
	 * `$container->helper->get('foobar')->something()`.
	 *
	 * @param $name
	 *
	 * @return HelperInterface
	 */
	public function __get($name)
	{
		return $this->get($name);
	}

	/**
	 * Automatically load helper classes.
	 *
	 * @return  void
	 * @since   1.1.0
	 */
	protected function loadHelpers()
	{
		$container   = $this->getContainer();
		$helpersList = $container['helperList'] ?? [];

		if (empty($helpersList))
		{
			$helperPath = $container['helperPath'] ?? ($this->getContainer()->basePath . '/src/Helper');

			if (!@file_exists($helperPath) || !@is_dir($helperPath) || !@is_readable($helperPath))
			{
				return;
			}

			$namespacePrefix = $container->applicationNamespace . '\\Helper\\';

			/** @var \DirectoryIterator $file */
			foreach (new \DirectoryIterator($helperPath) as $file)
			{
				if ($file->isDir() || $file->isDot() || $file->getExtension() !== 'php')
				{
					continue;
				}

				$helpersList[] = $namespacePrefix . $file->getBasename('.php');
			}
		}

		foreach ($helpersList as $className)
		{
			try
			{
				$this->registerHelperClass($className);
			}
			catch (InvalidArgumentException $e)
			{
				// Failure is always an option.
			}
		}
	}
}