<?php
/**
 * @package		awf-miniblog
 * @copyright	2014 Nicholas K. Dionysopoulos / Akeeba Ltd 
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Platform\Joomla\Helper;

use JFactory;

/**
 * Helper methods for the Joomla! platform
 */
abstract class Helper
{
	protected static $isCli = null;
	protected static $isBackend = null;
	protected static $isFrontend = null;

	/**
	 * Is this the administrative section of the component?
	 *
	 * @return  boolean
	 */
	public static function isBackend()
	{
		if (is_null(self::$isBackend))
		{
			self::detectApplicationSide();
		}

		return self::$isBackend;
	}

	/**
	 * Is this the public section of the component?
	 *
	 * @return  boolean
	 */
	public static function isFrontend()
	{
		if (is_null(self::$isFrontend))
		{
			self::detectApplicationSide();
		}

		return self::$isFrontend;
	}

	/**
	 * Is this a component running in a CLI application?
	 *
	 * @return  boolean
	 */
	public static function isCli()
	{
		if (is_null(self::$isCli))
		{
			self::detectApplicationSide();
		}

		return self::$isCli;
	}

	/**
	 * Detects if we are in front-end, back-end or CLI
	 *
	 * @return  void
	 */
	protected static function detectApplicationSide()
	{
		try
		{
			if (is_null(JFactory::$application))
			{
				$isCLI = true;
			}
			else
			{
				$app = JFactory::getApplication();
				$isCLI = $app instanceof JException || $app instanceof JApplicationCli;
			}
		}
		catch (\Exception $e)
		{
			$isCLI = true;
		}

		if ($isCLI)
		{
			$isAdmin = false;
		}
		else
		{
			$isAdmin = !JFactory::$application ? false : JFactory::getApplication()->isAdmin();
		}

		self::$isBackend = $isAdmin && !$isCLI;
		self::$isFrontend = !$isAdmin && !$isCLI;
		self::$isCli = !$isAdmin && $isCLI;
	}
} 