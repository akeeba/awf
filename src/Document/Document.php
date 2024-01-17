<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Document;

use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Container\ContainerAwareTrait;
use Awf\Document\Menu\MenuManager;
use Awf\Document\Toolbar\Toolbar;
use Awf\Text\Language;
use Awf\Text\LanguageAwareInterface;
use Awf\Text\LanguageAwareTrait;

/**
 * Class Document
 *
 * Generic output document implementation
 *
 * @package Awf\Document
 */
abstract class Document implements ContainerAwareInterface, LanguageAwareInterface
{
	use ContainerAwareTrait;
	use LanguageAwareTrait;

	/** @var   array  Cache of all document instances known to us */
	private static $instances = [];

	/** @var   string  The output data buffer */
	protected $buffer = '';

	/** @var   array  An array of all externally defined JavaScript files */
	protected $scripts = [];

	/** @var   array  An array of all inline JavaScript scripts */
	protected $scriptDeclarations = [];

	/** @var   array  An array of all external CSS files */
	protected $styles = [];

	/** @var   array  An array of all inline CSS styles */
	protected $styleDeclarations = [];

	/**
	 * Array of scripts options
	 *
	 * @var    array
	 */
	protected $scriptOptions = [];

	/** @var   MenuManager  The menu manager for this document */
	protected $menu;

	/** @var   Toolbar  The toolbar for this document */
	protected $toolbar;

	/** @var   string  The MIME type of the request */
	protected $mimeType = 'text/html';

	/** @var   array  Optional HTTP headers to send right before rendering */
	protected $HTTPHeaders = [];

	/** @var   null|string  The base name of the returned document. If set, the browser will initiate a download instead of displaying content inline. */
	protected $name = null;

	public function __construct(Container $container, ?Language $language = null)
	{
		$this->setContainer($container);
		$this->setLanguage($language ?? $container->language);

		$viewPath     = $container->basePath . '/View';
		$viewPath_alt = $container->basePath . '/views';

		$this->menu = new MenuManager($container);
		$this->menu->initialiseFromDirectory($viewPath);
		$this->menu->initialiseFromDirectory($viewPath_alt, false);

		$this->toolbar = new Toolbar($container);
	}

	/**
	 * Return the static instance of the document
	 *
	 * @param   string     $type         The document type (html or json)
	 * @param   Container  $container    The application to which the document is attached
	 * @param   string     $classPrefix  The prefix of the document class to use
	 *
	 * @return  \Awf\Document\Document
	 */
	public static function getInstance(string $type, Container $container, ?string $classPrefix = null, ?Language $language = null)
	{
		$classPrefix = $classPrefix ?? '\\Awf';

		if (!array_key_exists($type, self::$instances))
		{
			$className = $classPrefix . '\\Document\\' . ucfirst($type);

			if (!class_exists($className))
			{
				$className = '\\Awf\\Document\\Html';
			}

			self::$instances[$type] = new $className($container, $language);
		}

		return self::$instances[$type];
	}

	/**
	 * Translate a string into the current language and stores it in the JavaScript language store.
	 *
	 * @param   string   $string                The Text key.
	 * @param   boolean  $jsSafe                Ensure the output is JavaScript safe.
	 * @param   boolean  $interpretBackSlashes  Interpret \t and \n.
	 *
	 * @return  void
	 * @since   1.1.0
	 */
	public function lang(string $string, bool $jsSafe = false, bool $interpretBackSlashes = true)
	{
		// Translate the string.
		$translated = $this->getLanguage()->text($string, $jsSafe, $interpretBackSlashes);

		// Merge an entry into the 'akeeba.text' script option
		$this->addScriptOptions(
			'akeeba.text', [
			strtoupper($string) => $translated,
		], true
		);
	}

	/**
	 * Returns the contents of the buffer
	 *
	 * @return  string
	 */
	public function getBuffer()
	{
		return $this->buffer;
	}

	/**
	 * Sets the buffer (contains the main content of the HTML page or the entire JSON response)
	 *
	 * @param   string  $buffer
	 *
	 * @return  \Awf\Document\Document
	 */
	public function setBuffer($buffer)
	{
		$this->buffer = $buffer;

		return $this;
	}

	/**
	 * Adds an external script to the page
	 *
	 * @param   string   $url     The URL of the script file
	 * @param   boolean  $before  (optional) Should I add this before the template's scripts?
	 * @param   string   $type    (optional) The MIME type of the script file
	 * @param   bool     $defer   (optional) Should I defer loading the JS file?
	 * @param   bool     $async   (optional) Should I make the script async?
	 *
	 * @return  Document
	 */
	public function addScript($url, $before = false, $type = "text/javascript", $defer = false, $async = false)
	{
		$this->scripts[$url]['mime']   = $type;
		$this->scripts[$url]['before'] = $before;
		$this->scripts[$url]['defer']  = $defer;
		$this->scripts[$url]['async']  = $async;

		return $this;
	}

	/**
	 * Adds an external JavaScript module to the page.
	 *
	 * Note that modules are always deferred. Therefore, there are neither defer, nor async parameters.
	 *
	 * @param   string   $url     The URL of the script file
	 * @param   boolean  $before  (optional) Should I add this before the template's scripts?
	 *
	 * @return  self
	 * @since   1.1.2
	 * @see     https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Modules
	 */
	public function addModule(string $url, bool $before = false): Document
	{
		return $this->addScript($url, $before, 'module');
	}

	/**
	 * Adds an inline script to the page's header
	 *
	 * @param   string  $content  The contents of the script (without the script tag)
	 * @param   string  $type     (optional) The MIME type of the script data
	 *
	 * @return  \Awf\Document\Document
	 */
	public function addScriptDeclaration($content, $type = 'text/javascript')
	{
		if (!isset($this->scriptDeclarations[strtolower($type)]))
		{
			$this->scriptDeclarations[strtolower($type)] = $content;
		}
		else
		{
			$this->scriptDeclarations[strtolower($type)] .= chr(13) . $content;
		}

		return $this;
	}

	/**
	 * Add option for script
	 *
	 * @param   string  $key      Name in Storage
	 * @param   mixed   $options  Scrip options as array or string
	 * @param   bool    $merge    Whether merge with existing (true) or replace (false)
	 *
	 * @return  Document instance of $this to allow chaining
	 */
	public function addScriptOptions($key, $options, $merge = true)
	{
		if (empty($this->scriptOptions[$key]))
		{
			$this->scriptOptions[$key] = [];
		}

		if ($merge && is_array($options))
		{
			$this->scriptOptions[$key] = array_replace_recursive($this->scriptOptions[$key], $options);
		}
		else
		{
			$this->scriptOptions[$key] = $options;
		}

		return $this;
	}

	/**
	 * Get script(s) options
	 *
	 * @param   string  $key  Name in Storage
	 *
	 * @return  array  Options for given $key, or all script options
	 */
	public function getScriptOptions($key = null)
	{
		if ($key)
		{
			return (empty($this->scriptOptions[$key])) ? [] : $this->scriptOptions[$key];
		}
		else
		{
			return $this->scriptOptions;
		}
	}

	/**
	 * Adds an external stylesheet to the page
	 *
	 * @param   string   $url     The URL of the stylesheet file
	 * @param   boolean  $before  (optional) Should I add this before the template's scripts?
	 * @param   string   $type    (optional) The MIME type of the stylesheet file
	 * @param   string   $media   (optional) The media target of the stylesheet file
	 *
	 * @return  \Awf\Document\Document
	 */
	public function addStyleSheet($url, $before = false, $type = 'text/css', $media = null)
	{
		$this->styles[$url]['mime']   = $type;
		$this->styles[$url]['media']  = $media;
		$this->styles[$url]['before'] = $before;

		return $this;
	}

	/**
	 * Adds an inline stylesheet to the page's header
	 *
	 * @param   string  $content  The contents of the stylesheet (without the style tag)
	 * @param   string  $type     (optional) The MIME type of the stylesheet data
	 *
	 * @return  \Awf\Document\Document
	 */
	public function addStyleDeclaration($content, $type = 'text/css')
	{
		if (!isset($this->styleDeclarations[strtolower($type)]))
		{
			$this->styleDeclarations[strtolower($type)] = $content;
		}
		else
		{
			$this->styleDeclarations[strtolower($type)] .= chr(13) . $content;
		}

		return $this;
	}

	/**
	 * Return the array with external scripts
	 *
	 * @return  array
	 */
	public function getScripts()
	{
		return $this->scripts;
	}

	/**
	 * Return the array with script declarations
	 *
	 * @return  array
	 */
	public function getScriptDeclarations()
	{
		return $this->scriptDeclarations;
	}

	/**
	 * Return the array with external stylesheets
	 *
	 * @return  array
	 */
	public function getStyles()
	{
		return $this->styles;
	}

	/**
	 * Return the array with style declarations
	 *
	 * @return  array
	 */
	public function getStyleDeclarations()
	{
		return $this->styleDeclarations;
	}

	/**
	 * Each document class implements its own renderer which outputs the buffer
	 * to the browser using the appropriate template.
	 *
	 * @return  void
	 */
	abstract public function render();

	/**
	 * Returns an instance of the menu manager
	 *
	 * @return  MenuManager
	 */
	public function &getMenu()
	{
		return $this->menu;
	}

	/**
	 * Returns a reference to our Toolbar object
	 *
	 * @return Toolbar
	 */
	public function &getToolbar()
	{
		return $this->toolbar;
	}

	/**
	 * Returns a reference to our Application object
	 *
	 * @return \Awf\Application\Application
	 */
	public function getApplication()
	{
		return $this->container->application;
	}

	/**
	 * Get the MIME type of the document
	 *
	 * @return  string
	 */
	public function getMimeType()
	{
		return $this->mimeType;
	}

	/**
	 * Set the MIME type of the document
	 *
	 * @param   string  $mimeType
	 */
	public function setMimeType($mimeType)
	{
		$this->mimeType = $mimeType;
	}

	/**
	 * Add an HTTP header
	 *
	 * @param   string   $header     The HTTP header to add, e.g. Content-Type
	 * @param   string   $content    The content of the HTTP header, e.g. text/plain
	 * @param   boolean  $overwrite  Should I overwrite an existing header?
	 *
	 * @return  void
	 */
	public function addHTTPHeader($header, $content, $overwrite = true)
	{
		if (!$overwrite && isset($this->HTTPHeaders[$header]))
		{
			return;
		}

		$this->HTTPHeaders[$header] = $content;
	}

	/**
	 * Remove an HTTP header if set
	 *
	 * @param   string  $header  The header to remove, e.g. Content-Type
	 *
	 * @return  void
	 */
	public function removeHTTPHeader($header)
	{
		if (isset($this->HTTPHeaders[$header]))
		{
			unset($this->HTTPHeaders[$header]);
		}
	}

	/**
	 * Get the contents of an HTTP header defined in the document
	 *
	 * @param   string  $header   The HTTP header to return
	 * @param   string  $default  The default value if it's not already set
	 *
	 * @return  string  The HTTP header's value
	 */
	public function getHTTPHeader($header, $default = null)
	{
		if (isset($this->HTTPHeaders[$header]))
		{
			return $this->HTTPHeaders[$header];
		}
		else
		{
			return $default;
		}
	}

	/**
	 * Returns the raw HTTP headers as a hash array
	 *
	 * @return array Key = header, value = header value.
	 */
	public function getHTTPHeaders()
	{
		return $this->HTTPHeaders;
	}

	/**
	 * Output the HTTP headers to the browser
	 *
	 * @return  void
	 */
	public function outputHTTPHeaders()
	{
		if (!empty($this->HTTPHeaders) && !headers_sent())
		{
			foreach ($this->HTTPHeaders as $header => $value)
			{
				if (substr($header, 0, 5) == 'HTTP/')
				{
					header($header . ' ' . $value);
				}
				else
				{
					header($header . ': ' . $value);
				}
			}
		}
	}

	/**
	 * Get the document's name
	 *
	 * @return  null|string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Set the document's name
	 *
	 * @param   null|string  $name
	 *
	 * @return  void
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
}
