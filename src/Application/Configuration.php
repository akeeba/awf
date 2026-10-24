<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Application;


use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Container\ContainerAwareTrait;
use Awf\Registry\Registry;

class Configuration extends Registry implements ContainerAwareInterface
{
	use ContainerAwareTrait;

	/** @var int How long to wait before re-reading a configuration file which looked empty or damaged */
	private const RETRY_DELAY_MICROSECONDS = 50000;

	/** @var string The path to the default JSON configuration file */
	protected $defaultPath = null;

	/**
	 * Public constructor
	 *
	 * @param Container $container The DI container we belong to
	 * @param null      $data      [optional] Data to initialise the application configuration
	 */
	public function __construct(Container $container, $data = null)
	{
		parent::__construct($data);

		$this->setContainer($container);
	}

	/**
	 * Loads the configuration off a JSON file
	 *
	 * A file which does not exist (or cannot be read at all) results in an empty configuration: that is the
	 * "application not configured yet" case. A file which exists but is empty, or does not hold a complete JSON
	 * document, is a different matter: it is either being rewritten by a concurrent request, or it is damaged. Treating
	 * it as "not configured" would have the application overwrite the real configuration with a stub. Therefore we read
	 * it a second time after a short pause, and throw an exception if it is still unusable.
	 *
	 * @param   string  $filePath  The path to the JSON file (optional)
	 *
	 * @return  void
	 *
	 * @throws  \RuntimeException  When the file exists but is empty or does not contain valid configuration data
	 */
	public function loadConfiguration($filePath = null)
	{
		if (empty($filePath))
		{
			$filePath = $this->getDefaultPath();
		}

		// Reset the class
		$this->data = new \stdClass();

		// Try to open the file
		$fileData = @\file_get_contents($filePath);

		// A missing or unreadable file is the "not configured yet" case: leave the configuration empty.
		if ($fileData === false)
		{
			return;
		}

		$json = $this->extractJson($fileData);

		if ($json === null)
		{
			// Possibly caught in the middle of someone else's write. Give it a moment and try again.
			\usleep(self::RETRY_DELAY_MICROSECONDS);

			$fileData = @\file_get_contents($filePath);

			if ($fileData === false)
			{
				return;
			}

			$json = $this->extractJson($fileData);
		}

		if ($json === null)
		{
			throw new \RuntimeException(sprintf('The configuration file %s is empty or unparsable', $filePath), 500);
		}

		$this->loadString($json);
	}

	/**
	 * Returns the JSON part of the contents of a configuration file, or null if they are not a complete configuration
	 *
	 * @param   string  $fileData  The raw contents of the configuration file
	 *
	 * @return  string|null
	 */
	private function extractJson($fileData)
	{
		// The first line is the PHP "die" guard. Without a second line there is no configuration (e.g. truncated file).
		$parts = explode("\n", $fileData, 2);

		if (count($parts) < 2)
		{
			return null;
		}

		$json = trim($parts[1]);

		if ($json === '')
		{
			return null;
		}

		// The configuration body must decode to a JSON object, otherwise it is truncated or mangled.
		if (!(\json_decode($json) instanceof \stdClass))
		{
			return null;
		}

		return $parts[1];
	}

	/**
	 * Save the application configuration
	 *
	 * @param   string $filePath The path to the JSON file (optional)
	 *
	 * @return  void
	 *
	 * @throws  \RuntimeException  When saving fails
	 */
	public function saveConfiguration($filePath = null)
	{
		if (empty($filePath))
		{
			$filePath = $this->getDefaultPath();
		}

		$fileData = $this->toString('JSON', array('pretty_print' => true));
		$fileData = "<?php die; ?>\n" . $fileData;

		$res = $this->container->fileSystem->write($filePath, $fileData);

        if (!$res)
        {
            throw new \RuntimeException('Can not save ' . $filePath, 500);
        }
	}

	/**
	 * Sets the default configuration file path
	 *
	 * @param string $defaultPath
	 */
	public function setDefaultPath($defaultPath)
	{
		$this->defaultPath = $defaultPath;
	}

	/**
	 * Returns the default configuration file path. If it's not specified, it defines it based on the built-in
	 * conventions.
	 *
	 * @return string
	 */
	public function getDefaultPath()
	{
		if (empty($this->defaultPath))
		{
			$this->defaultPath = $this->container->basePath . '/assets/private/config.php';
		}

		return $this->defaultPath;
	}
}
