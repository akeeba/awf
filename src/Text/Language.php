<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2024 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Text;

use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Container\ContainerAwareTrait;
use Awf\User\UserInterface;
use Awf\Utils\ParseIni;

class Language implements ContainerAwareInterface
{
	use ContainerAwareTrait;

	/** @var   array  The cache of translation strings */
	private $strings = [];

	/** @var   array[callable]  Callables to use to process translation strings after loading them */
	private $iniProcessCallbacks = [];

	private $langCode = null;

	/**
	 * Public constructor
	 *
	 * @param   Container  $container  The container of the application we belong in.
	 *
	 * @since   1.1.0
	 */
	public function __construct(Container $container)
	{
		$this->setContainer($container);
	}

	/**
	 * @param   string|null          $langCode      Language code to load, NULL for auto-detection
	 * @param   string|null          $languagePath  The path where language files are stored, NULL for default
	 * @param   bool                 $overwrite     Overwrite already loaded keys?
	 * @param   bool                 $useDefault    Load the default language for missing keys?
	 * @param   callable|callable[]  $callbacks     Post-processing callbacks
	 *
	 * @return  void
	 * @since   1.2.0
	 */
	public function loadLanguage(
		string $langCode = null, ?string $languagePath = null, bool $overwrite = true, bool $useDefault = true,
		$callbacks = []
	): void
	{
		$defaultLanguage = $this->getContainer()->appConfig->get('language', 'en-GB') ?: 'en-GB';
		$languagePath    = $languagePath ?: $this->getContainer()->languagePath;
		$langCode        = $langCode ?? $this->detectLanguage($languagePath);

		if ($useDefault && $langCode !== $defaultLanguage)
		{
			$this->loadLanguage($defaultLanguage, $languagePath, false, false, $callbacks);
		}

		$appName  = $this->getContainer()->application_name;
		$filename = array_reduce(
			[
				// langPath/MyApp/en-GB.ini
				$languagePath . '/' . strtolower($appName) . '/' . $langCode . '.ini',
				// langPath/MyApp/en-GB/en-GB.ini
				$languagePath . '/' . strtolower($appName) . '/' . $langCode . '/' . $langCode . '.ini',
				// langPath/en-GB.ini
				$languagePath . '/' . $langCode . '.ini',
				// langPath/en-GB/en-GB.ini
				$languagePath . '/' . $langCode . '/' . $langCode . '.ini',
			],
			function (?string $carry, string $filename) {
				if ($carry !== null)
				{
					return $carry;
				}

				if (!@file_exists($filename) || !@is_readable($filename))
				{
					return null;
				}

				return $filename;
			}
		);

		if (is_null($filename))
		{
			return;
		}

		$rawText = @file_get_contents($filename);

		if ($rawText === false)
		{
			return;
		}

		// Fix the wrong quotes (`"_QQ_"`) used by third party translation environments
		$rawText   = str_replace('\\"_QQ_\\"', '\"', $rawText);
		$rawText   = str_replace('\\"_QQ_"', '\"', $rawText);
		$rawText   = str_replace('"_QQ_\\"', '\"', $rawText);
		$rawText   = str_replace('"_QQ_"', '\"', $rawText);
		$rawText   = str_replace('\\"', '"', $rawText);
		$strings   = ParseIni::parse_ini_file($rawText, false, true);
		$callbacks = is_array($callbacks) ? $callbacks : [$callbacks];

		foreach (array_filter($callbacks) as $callback)
		{
			$ret = call_user_func($callback, $filename, $strings);

			if ($ret === false)
			{
				return;
			}

			if (is_array($ret))
			{
				$strings = $ret;
			}
		}

		if ($overwrite)
		{
			$this->langCode = $langCode;
			$this->strings  = array_merge($this->strings, $strings);
		}
		else
		{
			$this->strings = array_merge($strings, $this->strings);
		}
	}

	/**
	 * Does a translation key exist?
	 *
	 * @param   string  $key  The key to check
	 *
	 * @return  boolean
	 * @since   1.2.0
	 */
	public function hasKey(string $key): bool
	{
		return array_key_exists(strtoupper($key), $this->strings);
	}

	/**
	 * Translate a string
	 *
	 * @param   string   $key                   Language key
	 * @param   boolean  $jsSafe                Make the result javascript safe. Mutually exclusive with
	 *                                          $interpretBackSlashes.
	 * @param   boolean  $interpretBackSlashes  Interpret \t and \n. Mutually exclusive with $jsSafe.
	 *
	 * @return  string  Human-readable string
	 * @since   1.2.0
	 */
	public function text(string $key, bool $jsSafe = false, bool $interpretBackSlashes = true): string
	{
		$key    = strtoupper($key);
		$string = $this->strings[$key] ?? $key;

		if ($jsSafe)
		{
			return addslashes($string);
		}

		if ($interpretBackSlashes && (strpos($string, '\\') !== false))
		{
			return str_replace(['\\\\', '\t', '\n'], ["\\", "\t", "\n"], $string);
		}

		return $string;
	}

	/**
	 * Passes a string through sprintf.
	 *
	 * Note that this method can take a mixed number of arguments as for the sprintf function.
	 *
	 * @param   string|null  $string        The key of the format string
	 * @param   mixed        ...$arguments  The values to use with sprintf
	 *
	 * @return  string  The translated strings
	 * @since   1.2.0
	 */
	public function sprintf(?string $string, ...$arguments): string
	{
		try
		{
			return sprintf($this->text($string), ...$arguments);
		}
		catch (\Throwable $e)
		{
			return 'BAD TRANSLATION. LANGUAGE KEY “' . $string
			       . '” HAS THE WRONG NUMBER OR KIND OF VALUE ARGUMENTS.';
		}
	}

	/**
	 * Special case of sprintf for a single integer argument handling plural strings.
	 *
	 * Say that the $string is 'FOOBAR' and the $count is 5. This method will first try to find 'FOOBAR_5' and use it
	 * with the sprintf() method. If the 'FOOBAR_5' key does not exist, it will use the sprintf method with the key
	 * 'FOOBAR'.
	 *
	 * This is typically used in language files like so:
	 *
	 * ```ini
	 * EXAMPLE_APPLES_N="%d apples have been eaten."
	 * EXAMPLE_APPLES_N_1="One apple has been eaten."
	 * ```
	 *
	 * In some languages the declension of nouns changes depending on their number, e.g. you may have a different
	 * declension for 0 items, 1-4 items, 5-9 items, and 10 or more items. This can also be dealt with by creating
	 * the _1, and _5, _6, ..., _9 language strings.
	 *
	 * @param   string  $string  The (base) translation key to use
	 * @param   int     $count   The count of items
	 *
	 * @return  string  The human-readable, translated string
	 * @since   1.2.0
	 */
	public function plural(string $string, int $count = 0)
	{
		$altKey = $string . '_' . $count;

		return $this->sprintf($this->hasKey($altKey) ? $altKey : $string, $count);
	}

	/**
	 * Find the best language for a user.
	 *
	 * The returned language is, by order of preference:
	 *
	 * - User profile (the `language` user configuration parameter, if set)
	 * - Browser settings
	 * - Default site language (the `language` application configuration parameter, if set)
	 * - English (United Kingdom)
	 *
	 * @param   string|null         $languagePath  The language path to use
	 * @param   UserInterface|null  $user          User to look up languages for. NULL for the current user.
	 *
	 * @return  string|null  A language code, NULL if there is no good match.
	 * @since   1.2.0
	 */
	public function detectLanguage(?string $languagePath, ?UserInterface $user = null): ?string
	{
		// If there is a logged-in user, their language setting is our primary preference.
		$user     = $user ?? $this->getContainer()->userManager->getUser();
		$language = $user->getId() ? $user->getParameters()->get('language') : null;

		// The secondary fallback is the language set in the user's browser.
		$language = $language ?? $this->detectLanguageFromBrowser($languagePath);

		// The tertiary fallback is the application-wide language.
		$language = $language ?? $this->getContainer()->appConfig->get('language');

		// Finally, we fall back to English (United Kingdom)
		return $language ?? 'en-GB';
	}

	/**
	 * The last loaded language code
	 *
	 * @return  null
	 * @since   1.2.0
	 */
	public function getLangCode()
	{
		return $this->langCode;
	}

	/**
	 * Detect the best matching language from the browser settings
	 *
	 * @param   string|null  $languagePath  The path we're going to be looking for language files in.
	 *
	 * @return  string|null  The detected language. NULL if there are no matches, or we hit an error.
	 * @since   1.2.0
	 */
	private function detectLanguageFromBrowser(?string $languagePath): ?string
	{
		if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			return null;
		}

		/**
		 * Get the language preference from the Accept-Language HTTP header.
		 *
		 * We get something like:
		 * fr-ch;q=0.3, da, en-us;q=0.8, en;q=0.5, fr;q=0.3
		 */
		$languages = strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
		// Remove spaces from strings to avoid errors
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
			// Pull out the language, place languages into array of full and primary string structure.
			$temp_array = [];
			// Slice out the part before the dash, place into array
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

			// Place this array into main $user_languages language array
			$user_languages[] = $temp_array;
		}

		if (!isset($user_languages))
		{
			return null;
		}

		$appName      = $this->getContainer()->application_name;
		$languagePath = $languagePath ?: $this->getContainer()->languagePath;
		$baseName     = $languagePath . '/' . strtolower($appName) . '/';

		if (!@is_dir($baseName))
		{
			$baseName = $languagePath . '/';
		}

		if (!@is_dir($baseName))
		{
			return null;
		}

		// Look for classic file layout
		foreach ($user_languages as $languageStruct)
		{
			// Search for exact language
			$langFilename = $baseName . $languageStruct[0] . '.ini';

			if (!file_exists($langFilename))
			{
				$langFilename = '';

				if (function_exists('glob'))
				{
					$allFiles = glob($baseName . $languageStruct[1] . '-*.ini');

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
				return basename($langFilename, '.ini');
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
			return null;
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

		return null;
	}

}