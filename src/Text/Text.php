<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Text;

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Document\Document;
use Awf\Exception\App;

/**
 * Class Text
 *
 * Internationalisation class for Awf applications
 *
 * @package Awf\Text
 * @deprecated 2.0 Use the language object
 */
abstract class Text
{
	protected static $container;

	public static function getContainer(): Container
	{
		if (empty(self::$container))
		{
			trigger_error(
				__CLASS__ . '::setContainer() must be called before using the class.',
				E_USER_DEPRECATED
			);
		}

		return self::$container ?? Application::getInstance()->getContainer();
	}

	public static function setContainer(Container $container)
	{
		self::$container = $container;
	}

	/**
	 * Loads the language file for a specific language
	 *
	 * @param   string                 $langCode      The ISO language code, e.g. en-GB, use null for automatic
	 *                                                detection
	 * @param   string|Container|null  $container     The DI container or name of the application to load translation
	 *                                                strings for
	 * @param   string                 $suffix        The suffix of the language file, by default it's .ini
	 * @param   boolean                $overwrite     Should I overwrite old language strings?
	 * @param   string                 $languagePath  The base path to the language files (optional)
	 * @param   callable|null          $callback      A post-processing callback for the language strings
	 *
	 * @deprecated  2.0  Go through the container's language key
	 * @return  void
	 *
	 */
	public static function loadLanguage(
		$langCode = null, $container = null, $suffix = '.ini', $overwrite = true, $languagePath = null,
		?callable $callback = null
	)
	{
		self::getContainer()->language->loadLanguage($langCode, $languagePath, $overwrite, true, $callback);
	}

	/**
	 * Automatically detect the language preferences from the browser, choosing
	 * the best fit language that exists on our system or falling back to en-GB
	 * when no preferred language exists.
	 *
	 * @param   string|Container|null  $container      The DI container or name of the application to load translation
	 *                                                 strings for
	 * @param   string                 $suffix         The suffix of the language file, by default it's .ini
	 * @param   string                 $languagePath   The base path to the language files (optional)
	 *
	 * @deprecated  2.0  Go through the container's language key
	 *
	 * @return  string  The language code
	 */
	public static function detectLanguage($container = null, $suffix = '.ini', $languagePath = null)
	{
		return self::getContainer()->language->detectLanguage($languagePath);
	}

	/**
	 * Translate a string
	 *
	 * @param   string   $key                   Language key
	 * @param   boolean  $jsSafe                Make the result javascript safe. Mutually exclusive with
	 *                                          $interpretBackSlashes.
	 * @param   boolean  $interpretBackSlashes  Interpret \t and \n. Mutually exclusive with $jsSafe.
	 *
	 * @return  string  Translation
	 */
	public static function _($key, $jsSafe = false, $interpretBackSlashes = true)
	{
		return self::getContainer()->language->text($key, $jsSafe, $interpretBackSlashes);
	}

	/**
	 * Passes a string through sprintf.
	 *
	 * Note that this method can take a mixed number of arguments as for the sprintf function.
	 *
	 * @param   string  $string  The format string.
	 *
	 * @return  string  The translated strings
	 */
	public static function sprintf($string, ...$arguments)
	{
		return self::getContainer()->language->sprintf($string, ...$arguments);
	}

	public static function plural($string, $count)
	{
		return self::getContainer()->language->plural($string, $count);
	}

	/**
	 * Does a translation key exist?
	 *
	 * @param   string  $key  The key to check
	 *
	 * @return  boolean
	 */
	public static function hasKey($key)
	{
		return self::getContainer()->language->hasKey($key);
	}

	/**
	 * Translate a string into the current language and stores it in the JavaScript language store.
	 *
	 * @param   string   $string                The Text key.
	 * @param   boolean  $jsSafe                Ensure the output is JavaScript safe.
	 * @param   boolean  $interpretBackSlashes  Interpret \t and \n.
	 *
	 * @deprecated 2.0 Use the document's lang() method instead
	 * @return  void
	 * @see        \Awf\Document\Document::lang
	 */
	public static function script($string = null, $jsSafe = false, $interpretBackSlashes = true)
	{
		trigger_error(
			sprintf('%s is deprecated. Use the document\'s lang() method instead.', __METHOD__),
			E_USER_DEPRECATED
		);

		self::getContainer()->application->getDocument()->lang($string, $jsSafe, $interpretBackSlashes);
	}

	/**
	 * Get the strings that have been loaded to the JavaScript language store.
	 *
	 * @return  array
	 * @deprecated  2.0  Use the document's getScriptOptions('akeeba.text') instead.
	 */
	public static function getScriptStrings()
	{
		try
		{
			return self::getContainer()->application->getDocument()->getScriptOptions('akeeba.text');
		}
		catch (App $e)
		{
			return [];
		}
	}
}
