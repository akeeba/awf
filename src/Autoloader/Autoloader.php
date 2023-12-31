<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Autoloader;

/**
 * A PSR-4 class autoloader. This is a modified version of Composer's ClassLoader class
 *
 * @codeCoverageIgnore
 * @deprecated 2.0 No replacement. Use Composer's autoloader instead.
 */
class Autoloader
{
	/** @var   Autoloader  The static instance of this autoloader */
	private static $instance;

	/** @var   array  Lengths of PSR-4 prefixes */
	private $prefixLengths = [];

	/** @var   array  Prefix to directory map */
	private $prefixDirs = [];

	/** @var   array  Fall-back directories */
	private $fallbackDirs = [];

	/**
	 * @deprecated 2.0
	 * @return Autoloader
	 *
	 */
	public static function getInstance()
	{
		trigger_error(
			sprintf('%s is deprecated. Use the PSR-4 autoloader provided by Composer instead.', __CLASS__),
			E_USER_DEPRECATED
		);

		if (!is_object(self::$instance))
		{
			self::$instance = new Autoloader();
		}

		return self::$instance;
	}

	/**
	 * Returns the prefix to directory map
	 *
	 * @deprecated 2.0
	 * @return  array
	 */
	public function getPrefixes()
	{
		return $this->prefixDirs;
	}

	/**
	 * Returns the list of fall-back directories
	 *
	 * @deprecated 2.0
	 * @return  array
	 */
	public function getFallbackDirs()
	{
		return $this->fallbackDirs;
	}

	/**
	 * Registers a set of PSR-4 directories for a given namespace, either
	 * appending or prefixing to the ones previously set for this namespace.
	 *
	 * @param   string        $prefix   The prefix/namespace, with trailing '\\'
	 * @param   array|string  $paths    The PSR-0 base directories
	 * @param   boolean       $prepend  Whether to prefix the directories
	 *
	 * @deprecated 2.0
	 * @return  $this for chaining
	 *
	 * @throws  \InvalidArgumentException  When the prefix is invalid
	 */
	public function addMap($prefix, $paths, $prepend = false)
	{
		if (!$prefix)
		{
			// Register directories for the root namespace.
			if ($prepend)
			{
				$this->fallbackDirs = array_merge(
					(array) $paths,
					$this->fallbackDirs
				);
			}
			else
			{
				$this->fallbackDirs = array_merge(
					$this->fallbackDirs,
					(array) $paths
				);
			}
		}
		elseif (!isset($this->prefixDirs[$prefix]))
		{
			// Register directories for a new namespace.
			$length = strlen($prefix);
			if ('\\' !== $prefix[$length - 1])
			{
				throw new \InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
			}
			$this->prefixLengths[$prefix[0]][$prefix] = $length;
			$this->prefixDirs[$prefix]                = (array) $paths;
		}
		elseif ($prepend)
		{
			// Prepend directories for an already registered namespace.
			$this->prefixDirs[$prefix] = array_merge(
				(array) $paths,
				$this->prefixDirs[$prefix]
			);
		}
		else
		{
			// Append directories for an already registered namespace.
			$this->prefixDirs[$prefix] = array_merge(
				$this->prefixDirs[$prefix],
				(array) $paths
			);
		}

		return $this;
	}

	/**
	 * Registers a set of PSR-4 directories for a given namespace,
	 * replacing any others previously set for this namespace.
	 *
	 * @param   string        $prefix  The prefix/namespace, with trailing '\\'
	 * @param   array|string  $paths   The PSR-4 base directories
	 *
	 * @deprecated 2.0
	 * @return  void
	 *
	 * @throws  \InvalidArgumentException  When the prefix is invalid
	 */
	public function setMap($prefix, $paths)
	{
		if (!$prefix)
		{
			$this->fallbackDirs = (array) $paths;
		}
		else
		{
			$length = strlen($prefix);
			if ('\\' !== $prefix[$length - 1])
			{
				throw new \InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
			}
			$this->prefixLengths[$prefix[0]][$prefix] = $length;
			$this->prefixDirs[$prefix]                = (array) $paths;
		}
	}

	/**
	 * Registers this instance as an autoloader.
	 *
	 * @param   boolean  $prepend  Whether to prepend the autoloader or not
	 *
	 * @deprecated 2.0
	 * @return  void
	 */
	public function register($prepend = false)
	{
		spl_autoload_register([$this, 'loadClass'], true, $prepend);
	}

	/**
	 * Unregisters this instance as an autoloader.
	 *
	 * @deprecated 2.0
	 * @return  void
	 */
	public function unregister()
	{
		spl_autoload_unregister([$this, 'loadClass']);
	}

	/**
	 * Loads the given class or interface.
	 *
	 * @param   string  $class  The name of the class
	 *
	 * @deprecated 2.0
	 *
	 * @return  boolean|null True if loaded, null otherwise
	 */
	public function loadClass($class)
	{
		if ($file = $this->findFile($class))
		{
			include $file;

			return true;
		}

		return false;
	}

	/**
	 * Finds the path to the file where the class is defined.
	 *
	 * @param   string  $class  The name of the class
	 *
	 * @deprecated 2.0
	 *
	 * @return  string|false  The path if found, false otherwise
	 */
	public function findFile($class)
	{
		// work around for PHP 5.3.0 - 5.3.2 https://bugs.php.net/50731
		if ('\\' == $class[0])
		{
			$class = substr($class, 1);
		}

		// PSR-4 lookup
		$logicalPath = strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';

		$first = $class[0];

		if (isset($this->prefixLengths[$first]))
		{
			foreach ($this->prefixLengths[$first] as $prefix => $length)
			{
				if (0 === strpos($class, $prefix))
				{
					foreach ($this->prefixDirs[$prefix] as $dir)
					{
						if (file_exists($file = $dir . DIRECTORY_SEPARATOR . substr($logicalPath, $length)))
						{
							return $file;
						}
					}
				}
			}
		}

		// PSR-4 fallback dirs
		foreach ($this->fallbackDirs as $dir)
		{
			if (file_exists($file = $dir . DIRECTORY_SEPARATOR . $logicalPath))
			{
				return $file;
			}
		}

		return false;
	}
}

call_user_func(function() {
	$autoloader = Autoloader::getInstance();
	$autoloader->addMap('Awf\\', [realpath(__DIR__ . '/..')]);
	$autoloader->register();
});

