<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Text;

use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Container\ContainerAwareTrait;
use Awf\Filesystem\File;
use Awf\Mvc\Factory;
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
		?string $langCode = null, ?string $languagePath = null, bool $overwrite = true, bool $useDefault = true,
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
		// If there is a session started and a logged-in user, their language setting is our primary preference.
		if ($this->getContainer()->session->isStarted())
		{
			$user = $user ?? $this->getContainer()->userManager->getUser();
		}

		if ($user instanceof UserInterface)
		{
			$language = $user->getId() ? $user->getParameters()->get('language') : null;
		}
		else
		{
			$language = null;
		}

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
	 * Returns the weighted list of accepted language given the content of the `Accept-Language` HTTP header.
	 *
	 * The header content is in the format `fr-ch;q=0.3, da, en-us;q=0.8, en;q=0.5, fr;q=0.3`. If omitted, we use the
	 * Accept-Language header passed through the `$_SERVER` superglobal.
	 *
	 * IMPORTANT: We only expect a value to be passed during unit testing. In regular use the parameter is NULL to force
	 * automatic detection from the browser-provided HTTP header.
	 *
	 * It is possible for the language code to be a star (`*`). This is replaced with the $defaultLanguage.
	 *
	 * If the $defaultLanguage is not present, it is added with a minimal 0.001 weight as a "last resort".
	 *
	 * @param   string|null  $acceptLanguage  The content of the `Accept-Language` HTTP header to parse.
	 * @param   string|null  $defaultLanguage The default language to use, see above.
	 *
	 * @return  array|float[]  Key is BCP 47 language code, value is the weight 0 to 1. Sorted by weight descending.
	 * @link    https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Headers/Accept-Language
	 * @since   1.2.2
	 */
	private function getAcceptedLanguages(?string $acceptLanguage = null, ?string $defaultLanguage = null): array
	{
		// Default return if all else fails.
		$defaultLanguage ??= $this->getContainer()->appConfig->get('language', 'en-GB') ?: 'en-GB';
		$defaultLanguage = strtolower($defaultLanguage);
		$defaultLanguageList = [$defaultLanguage => 1.0];

		// If no accept language string passed, get from server environment
		$acceptLanguage ??= $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';

		// Normalise
		$acceptLanguage = strtolower(trim($acceptLanguage ?: ''));

		// If it's empty, we don't have a preference order from the browser.
		if (empty($acceptLanguage))
		{
			return $defaultLanguageList;
		}

		// Convert to an array, e.g. `['fr-ch;q=0.3', 'da', 'en-us;q=0.8', 'en;q=0.5', 'fr;q=0.3']`.
		$rawList = array_map('trim', explode(',', $acceptLanguage));

		// We must have at least one item on the list...
		if (empty($rawList))
		{
			return $defaultLanguageList;
		}

		$ret = [];

		foreach ($rawList as $item)
		{
			// Parse the item (e.g. `en-us;q=0.8`) to a BCP 47 language (`en-us`) and a possible quality code (`q=0.8`).
			$parts  = explode(';', $item, 2);
			$lang   = trim($parts[0] ?? '');
			$qParam = trim($parts[1] ?? '');

			// If no BCP 47 language code was found, skip over this item.
			if (empty($lang))
			{
				continue;
			}

			// If the language code is star (`*`), replace it with $defaultLanguage BUT ONLY if it's not already present.
			if ($lang === '*')
			{
				if (isset($ret[$defaultLanguage]))
				{
					continue;
				}

				$lang = $defaultLanguage;
			}

			// If the quality param is empty or does not start with `q=`, assume the weight is 1.0.
			if (!str_starts_with($qParam ?? '', 'q='))
			{
				$ret[$lang] = 1.0;

				continue;
			}

			// Parse the weight and clamp it to the range 0-1 inclusive.
			$q = @floatval(trim(substr($qParam, 2)));
			$q = min(max(0.0, $q), 1.0);

			// If the weight is less than 0.001 it's effectively 0, i.e. "do not use".
			if ($q < 0.001)
			{
				continue;
			}

			$ret[$lang] = $q;
		}

		// If we have at least one language other than $defaultLanguage, add it with a minimum weight (fallback).
		if (!empty($ret) && !isset($ret[$defaultLanguage]))
		{
			$ret[$defaultLanguage] = 0.001;
		}

		// Sort the array by weight descending.
		arsort($ret, SORT_NUMERIC);

		return $ret ?: $defaultLanguageList;
	}

	/**
	 * Get the languages known to the application by iterating the application's language folder.
	 *
	 * @param   string|null  $languagePath
	 *
	 * @return  array
	 * @since   1.2.2
	 */
	private function getKnownLanguages(?string $languagePath): array
	{
		$languagePath = $languagePath ?: $this->getContainer()->languagePath;
		$baseName     = $languagePath . '/' . strtolower($this->getContainer()->application_name) . '/';

		if (!@is_dir($baseName))
		{
			$baseName = $languagePath . '/';
		}

		if (!@is_dir($baseName))
		{
			return [];
		}

		try
		{
			$di = new \DirectoryIterator($baseName);
		}
		catch (\Exception $e)
		{
			return [];
		}

		$ret = [];

		/** @var \DirectoryIterator $file */
		foreach ($di as $file)
		{
			// TODO This is wrong. We may also have the directory structure .../langCode/langCode.ini
			if ($file->isDot() || !$file->isFile() || strtolower($file->getExtension() ?? '') !== 'ini')
			{
				continue;
			}

			$ret[] = $file->getBasename('.ini');
		}

		asort($ret);

		return $ret;
	}

	/**
	 * Given a weighted list of accepted languages and a list of known languages, find the most relevant language which
	 * is in both arrays. Unlike shifting from an array intersection, this works by doing a partial language match. For
	 * example, the accepted language `el` will match the known language `el-GR`.
	 *
	 * @param   array  $acceptedLanguages
	 * @param   array  $knownLanguages
	 *
	 * @return string|null
	 * @since   1.2.2
	 */
	private function findMostRelevantLanguage(array $acceptedLanguages, array $knownLanguages): ?string
	{
		if (empty($acceptedLanguages))
		{
			return null;
		}

		if (empty($knownLanguages))
		{
			return null;
		}

		// Create a map of possible BCP 47 language codes to the actual language code used in the application.
		$langMap = [];

		foreach ($knownLanguages as $item)
		{
			$parts = explode('-', $item, 2);
			$lang = $parts[0];
			$bcp47 = strtolower($item);

			if (!isset($langMap[$lang]))
			{
				$langMap[$lang] = $item;
			}

			if (!isset($langMap[$bcp47]))
			{
				$langMap[$bcp47] = $item;
			}
		}

		// Walk through the array of accepted languages and find the most relevant language.
		foreach (array_keys($acceptedLanguages) as $item)
		{
			$parts = explode('-', $item, 2);
			$lang = $parts[0];
			$bcp47 = strtolower($item);

			if (isset($langMap[$lang]))
			{
				return $langMap[$lang];
			}

			if (isset($langMap[$bcp47]))
			{
				return $langMap[$bcp47];
			}
		}

		return null;
	}

	/**
	 * Detect the best matching language from the browser settings.
	 *
	 * @param   string|null  $languagePath  The path we're going to be looking for language files in.
	 *
	 * @return  string|null  The detected language. NULL if there are no matches, or we hit an error.
	 * @since   1.2.0
	 */
	private function detectLanguageFromBrowser(?string $languagePath): ?string
	{
		$acceptedLanguages = $this->getAcceptedLanguages();
		$knownLanguages    = $this->getKnownLanguages($languagePath);

		return $this->findMostRelevantLanguage($acceptedLanguages, $knownLanguages);
	}

}