<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Html;

use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Container\ContainerAwareTrait;
use BadMethodCallException;
use InvalidArgumentException;
use OutOfRangeException;

/**
 * HTML Helper Service.
 *
 * @since 1.1.0
 */
class HtmlService implements ContainerAwareInterface
{
	use ContainerAwareTrait;

	/**
	 * Known helper objects
	 *
	 * @var   array<HtmlHelperInterface>
	 * @since 1.1.0
	 */
	protected $helpers = [];

	/**
	 * Option values related to the generation of HTML output. Recognized
	 * options are:
	 *     fmtDepth, integer. The current indent depth.
	 *     fmtEol, string. The end of line string, default is linefeed.
	 *     fmtIndent, string. The string to use for indentation, default is
	 *     tab.
	 *
	 * @var    array
	 */
	private $formatOptions = ['format.depth' => 0, 'format.eol' => "\n", 'format.indent' => "\t"];

	/**
	 * Public constructor.
	 *
	 * @param   Container  $container  The DI Container of the application
	 *
	 * @since 1.1.0
	 */
	public function __construct(Container $container)
	{
		$this->setContainer($container);
	}

	/**
	 * Register an HTML helper object.
	 *
	 * @param   string               $name    The name to register the helper under. Empty for automatic detection.
	 * @param   HtmlHelperInterface  $helper  The helper object.
	 *
	 * @return  void
	 * @since   1.1.0
	 */
	public function registerHelper(string $name, HtmlHelperInterface $helper): void
	{
		$name = $name ?: $helper->getName();

		$this->helpers[$name] = $helper;
	}

	/**
	 * Instantiate and register an HTML helper class.
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

		if (!is_a($class, HtmlHelperInterface::class) && !in_array(HtmlHelperInterface::class, class_implements($class)))
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

		$this->registerHelper($o->getName(), $o);
	}

	/**
	 * Unregister an HTML helper by name.
	 *
	 * @param   string  $name  The name of the HTML helper to unregister
	 *
	 * @return  void
	 * @since   1.1.0
	 */
	public function unregisterHelper(string $name): void
	{
		if (!isset($this->helpers[$name]))
		{
			return;
		}

		unset($this->helpers[$name]);
	}

	/**
	 * Unregister an HTML helper by class name.
	 *
	 * @param   string  $class  The class of the HTML helper to unregister
	 *
	 * @return  void
	 * @since   1.1.0
	 */
	public function unregisterHelperClass(string $class): void
	{
		$toUnregister = [];

		foreach ($this->helpers as $key => $object)
		{
			if (!is_a($object, $class))
			{
				continue;
			}

			$toUnregister[] = $key;
		}

		if (empty($toUnregister))
		{
			return;
		}

		foreach ($toUnregister as $key)
		{
			$this->unregisterHelper($key);
		}
	}

	/**
	 * Does the service know of this HTML helper name (prefix)?
	 *
	 * @param   string  $name  The HTML helper name to test for.
	 *
	 * @return  bool
	 * @since   1.1.0
	 */
	public function hasHelper(string $name): bool
	{
		return array_key_exists($name, $this->helpers);
	}

	/**
	 * Does the service know of any HTML helper(s) implementing the provided class name?
	 *
	 * @param   string  $class  The class name to test for.
	 *
	 * @return  bool
	 * @since   1.1.0
	 */
	public function hasHelperClass(string $class): bool
	{
		foreach ($this->helpers as $key => $object)
		{
			if (is_a($object, $class))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Run an HTML helper method and return its results.
	 *
	 * Example:
	 * ```php
	 * echo $container->html->get('basic.link', 'https://www.akeeba.com', 'Akeeba Ltd.')
	 * ```
	 *
	 * WARNING! Do not use this with void helper methods; it will cause errors in newer versions of PHP.
	 *
	 * @param   mixed  ...$arguments  The arguments to the helper.
	 *
	 * @return  mixed  The result of the helper. void (NULL) if it returns no result.
	 * @since   1.1.0
	 */
	public function get(...$arguments)
	{
		if (empty($arguments))
		{
			throw new InvalidArgumentException(
				sprintf('You need at least one argument when calling %s', __METHOD__), 500
			);
		}

		$key   = array_shift($arguments);
		$parts = explode('.', $key, 2);

		if (count($parts) === 1)
		{
			$name   = 'basic';
			$method = $parts[0];
		}
		else
		{
			[$name, $method] = $parts;
		}

		if (!$this->hasHelper($name))
		{
			throw new BadMethodCallException(
				sprintf('HTML helper ‘%s’ not found', $name)
			);
		}

		$helper = $this->helpers[$name];

		if (!method_exists($helper, $method))
		{
			throw new BadMethodCallException(
				sprintf('HTML helper method ‘%s.%s’ not found', $name, $method)
			);
		}

		if (!empty($arguments))
		{
			return $helper->$method(...$arguments);
		}

		return $helper->$method();
	}

	/**
	 * Run an HTML helper method _without_ returning any results. Use to call void helper methods.
	 *
	 * Example:
	 *  ```php
	 *  $container->html->get('behaviour.calendar')
	 *  ```
	 *
	 * @param   mixed  ...$arguments  The arguments to the HTML helper
	 *
	 * @return  void
	 * @since   1.1.0
	 */
	public function run(...$arguments): void
	{
		if (empty($arguments))
		{
			throw new InvalidArgumentException(
				sprintf('You need at least one argument when calling %s', __METHOD__), 500
			);
		}

		$key   = array_shift($arguments);
		$parts = explode('.', $key, 2);

		if (count($parts) === 1)
		{
			$name   = 'basic';
			$method = $parts[0];
		}
		else
		{
			[$name, $method] = $parts;
		}

		if (!$this->hasHelper($name))
		{
			throw new BadMethodCallException(
				sprintf('HTML helper ‘%s’ not found', $name)
			);
		}

		$helper = $this->helpers[$name];

		if (!method_exists($helper, $method))
		{
			throw new BadMethodCallException(
				sprintf('HTML helper method ‘%s.%s’ not found', $name, $method)
			);
		}

		if (!empty($arguments))
		{
			$helper->$method(...$arguments);

			return;
		}

		$helper->$method();
	}

	/**
	 * Returns the current format options.
	 *
	 * @return  array
	 * @since   1.1.0
	 */
	public function getFormatOptions(): array
	{
		return $this->formatOptions;
	}

	/**
	 * Set new format options
	 *
	 * @param   array  $formatOptions  The new format options to set
	 *
	 * @return  $this  Self, for chaining
	 * @since   1.1.0
	 */
	public function setFormatOptions(array $formatOptions): HtmlService
	{
		foreach ($formatOptions as $k => $v)
		{
			if (!isset($this->formatOptions[$k]))
			{
				continue;
			}

			$this->formatOptions[$k] = $v;
		}

		return $this;
	}

	/**
	 * Get a helper object
	 *
	 * @param   string  $name  The name of the helper object to get
	 *
	 * @return  mixed
	 * @throws  OutOfRangeException  When asked for a helper which does not exist
	 */
	public function __get($name)
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

}