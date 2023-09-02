<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Text;

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Document\Document;
use Awf\Exception\App;
use Awf\Utils\ParseIni;

/**
 * Class Text
 *
 * Internationalisation class for Awf applications
 *
 * @package Awf\Text
 */
abstract class Text
{
	/** @var   array  The cache of translation strings */
	private static $strings = [];

	/** @var   array[callable]  Callables to use to process translation strings after laoding them */
	private static $iniProcessCallbacks = [];

	/**
	 * Adds an INI process callback to the stack
	 *
	 * @param   callable  $callable  The processing callback to add
	 *
	 * @deprecated 2.0 Use the callback argument of loadLanguage
	 * @return  void
	 */
	public static function addIniProcessCallback($callable)
	{
		static::$iniProcessCallbacks[] = $callable;
	}

	/**
	 * Loads the language file for a specific language
	 *
	 * Deprecation notice: The second argument must now be a Container object, not an application name
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
	 * @return  void
	 */
	public static function loadLanguage(
		$langCode = null, $container = null, $suffix = '.ini', $overwrite = true, $languagePath = null,
		?callable $callback = null
	)
	{
		/** @deprecated 2.0 The $container argument must be provided */
		if (empty($container))
		{
			trigger_error(
				sprintf(
					'The second argument to %s must be a Container object, not an empty string or null.', __METHOD__
				),
				E_USER_DEPRECATED
			);

			$container = Application::getInstance()->getContainer();
		}

		/** @deprecated 2.0 The $container argument must be a Container */
		if (is_string($container))
		{
			trigger_error(
				sprintf('The second argument to %s must be a Container object, not an application name.', __METHOD__),
				E_USER_DEPRECATED
			);

			$container = Application::getInstance($container)->getContainer();
		}

		if (is_null($langCode))
		{
			$langCode = self::detectLanguage($container, $suffix, $languagePath);
		}

		if (empty($languagePath))
		{
			$languagePath = $container->languagePath;
		}

		$appName = $container->application_name;

		$fileNames = [
			// langPath/MyApp/en-GB.ini
			$languagePath . '/' . strtolower($appName) . '/' . $langCode . $suffix,
			// langPath/MyApp/en-GB/en-GB.ini
			$languagePath . '/' . strtolower($appName) . '/' . $langCode . '/' . $langCode . $suffix,
			// langPath/en-GB.ini
			$languagePath . '/' . $langCode . $suffix,
			// langPath/en-GB/en-GB.ini
			$languagePath . '/' . $langCode . '/' . $langCode . $suffix,
		];

		$filename = null;

		foreach ($fileNames as $file)
		{
			if (@file_exists($file))
			{
				$filename = $file;
				break;
			}
		}

		if (is_null($filename))
		{
			return;
		}

		// Compatibility with Joomla! translation files and Transifex' broken way to conforming to a broken standard.
		$rawText = @file_get_contents($filename);
		$rawText = str_replace('\\"_QQ_\\"', '\"', $rawText);
		$rawText = str_replace('\\"_QQ_"', '\"', $rawText);
		$rawText = str_replace('"_QQ_\\"', '\"', $rawText);
		$rawText = str_replace('"_QQ_"', '\"', $rawText);
		$rawText = str_replace('\\"', '"', $rawText);

		$strings   = ParseIni::parse_ini_file($rawText, false, true);
		$callbacks = [$callback];

		/** @deprecated 2.0 The static::$iniProcessCallbacks will be removed without replacement. Use the $callback argument. */
		if (!empty(static::$iniProcessCallbacks))
		{
			trigger_error(
				sprintf(
					'%s::$iniProcessCallbacks is deprecated. Use the $callback argument of the %s method instead.',
					__CLASS__, __METHOD__
				),
				E_USER_DEPRECATED
			);

			$callbacks = array_merge(static::$iniProcessCallbacks, $callbacks);
		}

		foreach (array_filter($callbacks) as $callback)
		{
			$ret = call_user_func($callback, $filename, $strings);

			if ($ret === false)
			{
				return;
			}
			elseif (is_array($ret))
			{
				$strings = $ret;
			}
		}

		if ($overwrite)
		{
			self::$strings = array_merge(self::$strings, $strings);
		}
		else
		{
			self::$strings = array_merge($strings, self::$strings);
		}
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
	 * @return  string  The language code
	 */
	public static function detectLanguage($container = null, $suffix = '.ini', $languagePath = null)
	{
		/** @deprecated 2.0 The $container argument must be provided */
		if (empty($container))
		{
			trigger_error(
				sprintf(
					'The second argument to %s must be a Container object, not an empty string or null.', __METHOD__
				),
				E_USER_DEPRECATED
			);

			$container = Application::getInstance()->getContainer();
		}

		/** @deprecated 2.0 The $container argument must be a Container */
		if (is_string($container))
		{
			trigger_error(
				sprintf('The second argument to %s must be a Container object, not an application name.', __METHOD__),
				E_USER_DEPRECATED
			);

			$container = Application::getInstance($container)->getContainer();
		}

		if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			return 'en-GB';
		}

		$languages = strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
		// $languages = ' fr-ch;q=0.3, da, en-us;q=0.8, en;q=0.5, fr;q=0.3';
		// need to remove spaces from strings to avoid error
		$languages = str_replace(' ', '', $languages);
		$languages = explode(",", $languages);

		// First we need to sort languages by their weight
		$temp = [];

		foreach ($languages as $lang)
		{
			$parts = explode(';', $lang);

			$q = 1;
			if ((count($parts) > 1) && (substr($parts[1], 0, 2) == 'q='))
			{
				$q = floatval(substr($parts[1], 2));
			}

			$temp[$parts[0]] = $q;
		}

		arsort($temp);
		$languages = $temp;

		foreach ($languages as $language => $weight)
		{
			// pull out the language, place languages into array of full and primary
			// string structure:
			$temp_array = [];
			// slice out the part before the dash, place into array
			$temp_array[0] = $language; //full language
			$parts         = explode('-', $language);
			$temp_array[1] = $parts[0]; // cut out primary language

			if ((strlen($temp_array[0]) == 5)
			    && ((substr($temp_array[0], 2, 1) == '-')
			        || (substr(
				            $temp_array[0], 2, 1
			            ) == '_')))
			{
				$langLocation  = strtoupper(substr($temp_array[0], 3, 2));
				$temp_array[0] = $temp_array[1] . '-' . $langLocation;
			}

			//place this array into main $user_languages language array
			$user_languages[] = $temp_array;
		}

		if (!isset($user_languages))
		{
			return 'en-GB';
		}

		$appName = $container->application_name;

		if (empty($languagePath))
		{
			$languagePath = $container->languagePath;
		}

		$baseName = $languagePath . '/' . strtolower($appName) . '/';

		if (!@is_dir($baseName))
		{
			$baseName = $languagePath . '/';
		}

		if (!@is_dir($baseName))
		{
			return 'en-GB';
		}

		// Look for classic file layout
		foreach ($user_languages as $languageStruct)
		{
			// Search for exact language
			$langFilename = $baseName . $languageStruct[0] . $suffix;

			if (!file_exists($langFilename))
			{
				$langFilename = '';

				if (function_exists('glob'))
				{
					$allFiles = glob($baseName . $languageStruct[1] . '-*' . $suffix);

					// Cover both failure cases: false (filesystem error) and empty array (no file found)
					if (!is_array($allFiles) || empty($allFiles))
					{
						continue;
					}

					$langFilename = array_shift($allFiles);
				}
			}

			if (!empty($langFilename) && file_exists($langFilename))
			{
				return basename($langFilename, $suffix);
			}
		}

		// Look for subdirectory layout
		$allFolders = [];

		try
		{
			$di = new \DirectoryIterator($baseName);
		}
		catch (\Exception $e)
		{
			return 'en-GB';
		}

		/** @var \DirectoryIterator $file */
		foreach ($di as $file)
		{
			if ($di->isDot())
			{
				continue;
			}

			if (!$di->isDir())
			{
				continue;
			}

			$allFolders[] = $file->getFilename();
		}

		foreach ($user_languages as $languageStruct)
		{
			if (array_key_exists($languageStruct[0], $allFolders))
			{
				return $languageStruct[0];
			}

			foreach ($allFolders as $folder)
			{
				if (strpos($folder, $languageStruct[1]) === 0)
				{
					return $folder;
				}
			}
		}

		return 'en-GB';
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
		/** @deprecated 2.0 You must load the languages yourself */
		if (empty(self::$strings))
		{
			self::loadLanguage('en-GB');
			self::loadLanguage();
		}

		$key = strtoupper($key);

		if (array_key_exists($key, self::$strings))
		{
			$string = self::$strings[$key];
		}
		else
		{
			$string = $key;
		}

		if ($jsSafe)
		{
			// Javascript filter
			return addslashes($string);
		}

		if ($interpretBackSlashes && (strpos($string, '\\') !== false))
		{
			// Interpret \n and \t characters
			return str_replace(['\\\\', '\t', '\n'], ["\\", "\t", "\n"], $string);
		}

		return $string;
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
	public static function sprintf($string)
	{
		$args  = func_get_args();
		$count = count($args);
		if ($count > 0)
		{
			$args[0] = self::_($string);

			return call_user_func_array('sprintf', $args);
		}

		return '';
	}

	public static function plural($string, $count)
	{
		$count = empty($count) ? 0 : (int) $count;

		$possibleKeys = [
			strtoupper($string) . '_' . $count,
			strtoupper($string),
		];

		foreach ($possibleKeys as $key)
		{
			$language = self::_($key);

			if ($language === $key)
			{
				continue;
			}

			return sprintf($language, $count);
		}

		return sprintf(self::_($string), $count);
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
		/** @deprecated 2.0 You must load the languages yourself */
		if (empty(self::$strings))
		{
			self::loadLanguage('en-GB');
			self::loadLanguage();
		}

		$key = strtoupper($key);

		return array_key_exists($key, self::$strings);
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

		Application::getInstance()->getDocument()->lang($string, $jsSafe, $interpretBackSlashes);
	}

	/**
	 * Get the strings that have been loaded to the JavaScript language store.
	 *
	 * @return  array
	 */
	public static function getScriptStrings()
	{
		try
		{
			return Application::getInstance()->getDocument()->getScriptOptions('akeeba.text');
		}
		catch (App $e)
		{
			return [];
		}
	}
}
