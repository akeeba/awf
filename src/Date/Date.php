<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Date;

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Container\ContainerAwareInterface;
use Awf\Container\ContainerAwareTrait;
use Awf\Database\Driver;
use Awf\Exception\App;
use DateTime;
use DateTimeZone;
use ReturnTypeWillChange;

/**
 * Date is a class that stores a date and provides logic to manipulate
 * and render that date in a variety of formats.
 *
 * @property-read  string  $daysinmonth   t - Number of days in the given month.
 * @property-read  string  $dayofweek     N - ISO-8601 numeric representation of the day of the week.
 * @property-read  string  $dayofyear     z - The day of the year (starting from 0).
 * @property-read  boolean $isleapyear    L - Whether it's a leap year.
 * @property-read  string  $day           d - Day of the month, 2 digits with leading zeros.
 * @property-read  string  $hour          H - 24-hour format of an hour with leading zeros.
 * @property-read  string  $minute        i - Minutes with leading zeros.
 * @property-read  string  $second        s - Seconds with leading zeros.
 * @property-read  string  $month         m - Numeric representation of a month, with leading zeros.
 * @property-read  string  $ordinal       S - English ordinal suffix for the day of the month, 2 characters.
 * @property-read  string  $week          W - Numeric representation of the day of the week.
 * @property-read  string  $year          Y - A full numeric representation of a year, 4 digits.
 *
 * This class is adapted from the Joomla! Framework
 */
class Date extends DateTime implements ContainerAwareInterface
{
	use ContainerAwareTrait;

	/**
	 * The format string to be applied when using the __toString() magic method.
	 *
	 * @var    string
	 */
	public static $format = 'Y-m-d H:i:s';

	/**
	 * Placeholder for a DateTimeZone object with GMT as the time zone.
	 *
	 * @var    DateTimeZone
	 */
	protected static $gmt;

	/**
	 * Placeholder for a DateTimeZone object with the default server
	 * time zone as the time zone.
	 *
	 * @var    DateTimeZone
	 */
	protected static $stz;

	/**
	 * The DateTimeZone object for usage in rending dates as strings.
	 *
	 * @var    DateTimeZone
	 */
	protected $tz;

	private const REPLACE_DAY_ABBR = "\x021\x03";

	private const REPLACE_DAY_NAME = "\x022\x03";

	private const REPLACE_MONTH_ABBR = "\x023\x03";

	private const REPLACE_MONTH_NAME = "\x024\x03";

	/**
	 * Constructor.
	 *
	 * @param   string          $date       String in a format accepted by strtotime(), defaults to "now".
	 * @param   mixed           $tz         Time zone to be used for the date. Might be a string or a DateTimeZone
	 *                                      object.
	 * @param   Container|null  $container  The DI Container of the application
	 *
	 * @throws App
	 */
	public function __construct($date = 'now', $tz = null, ?Container $container = null)
	{
		/** @deprecated 2.0 The container argument will become mandatory */
		if (empty($container))
		{
			trigger_error(
				sprintf('The container argument is mandatory in %s', __METHOD__), E_USER_DEPRECATED
			);

			$container = Application::getInstance()->getContainer();
		}

		$this->setContainer($container);

		// Create the base GMT and server time zone objects.
		if (empty(self::$gmt) || empty(self::$stz))
		{
			self::$gmt = new DateTimeZone('GMT');
			self::$stz = new DateTimeZone(@date_default_timezone_get());
		}

		// If the time zone object is not set, attempt to build it.
		if (!($tz instanceof DateTimeZone))
		{
			if ($tz === null)
			{
				$tz = self::$gmt;
			}
			elseif (is_string($tz))
			{
				$tz = new DateTimeZone($tz);
			}
		}

		// If the date is numeric assume a unix timestamp and convert it.
		date_default_timezone_set('UTC');
		$date = is_numeric($date) ? date('c', $date) : $date;

		// Call the DateTime constructor.
		parent::__construct($date, $tz);

		// Reset the timezone for 3rd party libraries/extension that does not use JDate
		date_default_timezone_set(self::$stz->getName());

		// Set the timezone object for access later.
		$this->tz = $tz;
	}

	/**
	 * Magic method to access properties of the date given by class to the format method.
	 *
	 * @param   string  $name  The name of the property.
	 *
	 * @return  mixed   A value if the property name is valid, null otherwise.
	 */
	public function __get($name)
	{
		$value = null;

		switch ($name)
		{
			case 'daysinmonth':
				$value = $this->format('t', true);
				break;

			case 'dayofweek':
				$value = $this->format('N', true);
				break;

			case 'dayofyear':
				$value = $this->format('z', true);
				break;

			case 'isleapyear':
				$value = (boolean) $this->format('L', true);
				break;

			case 'day':
				$value = $this->format('d', true);
				break;

			case 'hour':
				$value = $this->format('H', true);
				break;

			case 'minute':
				$value = $this->format('i', true);
				break;

			case 'second':
				$value = $this->format('s', true);
				break;

			case 'month':
				$value = $this->format('m', true);
				break;

			case 'ordinal':
				$value = $this->format('S', true);
				break;

			case 'week':
				$value = $this->format('W', true);
				break;

			case 'year':
				$value = $this->format('Y', true);
				break;

			default:
				$trace = debug_backtrace();
				trigger_error(
					'Undefined property via __get(): ' . $name . ' in ' . $trace[0]['file'] . ' on line ' . $trace[0]['line'],
					E_USER_NOTICE
				);
		}

		return $value;
	}

	/**
	 * Magic method to render the date object in the format specified in the public
	 * static member Date::$format.
	 *
	 * @return  string  The date as a formatted string.
	 */
	public function __toString()
	{
		return (string) parent::format(self::$format);
	}

	/**
	 * Gets the date as a formatted string.
	 *
	 * @param   string   $format  The date format specification string (see {@link PHP_MANUAL#date})
	 * @param   boolean  $local   True to return the date string in the local time zone, false to return it in GMT.
	 *
	 * @return  string   The date string in the specified format format.
	 */
	#[ReturnTypeWillChange]
	public function format($format, bool $local = false, bool $translate = true)
	{
		if ($translate)
		{
			$format = preg_replace('/(^|[^\\\])D/', "\\1" . self::REPLACE_DAY_ABBR, $format);
			$format = preg_replace('/(^|[^\\\])l/', "\\1" . self::REPLACE_DAY_NAME, $format);
			$format = preg_replace('/(^|[^\\\])M/', "\\1" . self::REPLACE_MONTH_ABBR, $format);
			$format = preg_replace('/(^|[^\\\])F/', "\\1" . self::REPLACE_MONTH_NAME, $format);
		}

		// If the returned time should not be local use GMT.
		if (!$local)
		{
			parent::setTimezone(self::$gmt);
		}

		// Format the date.
		$return = parent::format($format);

		if ($translate)
		{
			if (strpos($return, self::REPLACE_DAY_ABBR) !== false)
			{
				$return = str_replace(self::REPLACE_DAY_ABBR, $this->dayToString(parent::format('w'), true), $return);
			}

			if (strpos($return, self::REPLACE_DAY_NAME) !== false)
			{
				$return = str_replace(self::REPLACE_DAY_NAME, $this->dayToString(parent::format('w')), $return);
			}

			if (strpos($return, self::REPLACE_MONTH_ABBR) !== false)
			{
				$return = str_replace(
					self::REPLACE_MONTH_ABBR, $this->monthToString(parent::format('n'), true), $return
				);
			}

			if (strpos($return, self::REPLACE_MONTH_NAME) !== false)
			{
				$return = str_replace(self::REPLACE_MONTH_NAME, $this->monthToString(parent::format('n')), $return);
			}
		}

		if (!$local)
		{
			parent::setTimezone($this->tz);
		}

		return $return;
	}

	/**
	 * Get the time offset from GMT in hours or seconds.
	 *
	 * @param   boolean  $hours  True to return the value in hours.
	 *
	 * @return  float  The time offset from GMT either in hours or in seconds.
	 */
	public function getOffsetFromGMT($hours = false)
	{
		return (float) $hours ? ($this->tz->getOffset($this) / 3600) : $this->tz->getOffset($this);
	}

	/**
	 * Method to wrap the setTimezone() function and set the internal time zone object.
	 *
	 * @param   DateTimeZone  $tz  The new DateTimeZone object.
	 *
	 * @return  Date
	 *
	 * @note    This method can't be type hinted due to a PHP bug: https://bugs.php.net/bug.php?id=61483
	 */
	#[ReturnTypeWillChange]
	public function setTimezone($tz)
	{
		$this->tz = $tz;

		return parent::setTimezone($tz);
	}

	public function toAtom($local = false)
	{
		return $this->format(DateTime::ATOM, $local, false);
	}

	public function toCookie($local = false)
	{
		return $this->format(DateTime::COOKIE, $local, false);
	}

	public function toISO8601_WrongPHP($local = false)
	{
		return $this->format('Y-m-d\TH:i:sO', $local, false);
	}

	public function toISO8601($local = false)
	{
		return $this->format(DateTime::RFC3339, $local, false);
	}

	public function toISO8601Expanded($local = false)
	{
		if (PHP_VERSION_ID < 80200)
		{
			return $this->toISO8601($local);
		}

		return $this->format(DateTime::ISO8601_EXPANDED, $local, false);
	}

	public function toRFC822($local = false)
	{
		return $this->format(DateTime::RFC2822, $local, false);
	}

	public function toRFC850($local = false)
	{
		return $this->format(DateTime::RFC850, $local, false);
	}

	public function toRFC1036($local = false)
	{
		return $this->format(DateTime::RFC1036, $local, false);
	}

	public function toRFC1123($local = false)
	{
		return $this->format(DateTime::RFC1123, $local, false);
	}

	public function toRFC2822($local = false)
	{
		return $this->toRFC822($local);
	}

	public function toRFC3339($local = false)
	{
		return $this->format(DateTime::RFC3339, $local, false);
	}

	public function toRFC3339Extended($local = false)
	{
		return $this->format(DateTime::RFC3339_EXTENDED, $local, false);
	}

	public function toRFC7231($local = false)
	{
		return $this->format(DateTime::RFC7231, $local, false);
	}

	public function toRSS($local = false)
	{
		return $this->format(DateTime::RSS, $local, false);
	}

	public function toW3C($local = false)
	{
		return $this->format(DateTime::W3C, $local, false);
	}

	/**
	 * Gets the date as UNIX time stamp.
	 *
	 * @return  integer  The date as a UNIX timestamp.
	 */
	public function toUnix()
	{
		return (int) parent::format('U');
	}

	/**
	 * Gets the date as an SQL datetime string.
	 *
	 * @param   boolean  $local  True to return the date string in the local time zone, false to return it in GMT.
	 * @param   Driver   $db     The database driver or null to use JFactory::getDbo()
	 *
	 * @return  string  The date string in SQL datetime format.
	 *
	 * @link    http://dev.mysql.com/doc/refman/5.0/en/datetime.html
	 */
	public function toSql($local = false, ?Driver $db = null)
	{
		$db = $db ?? $this->container->db;

		return $this->format($db->getDateFormat(), $local, false);
	}

	protected function dayToString($day, $abbr = false)
	{
		switch ($day)
		{
			case 0 :
				$string = $abbr ? 'Sun' : 'Sunday';

				break;
			case 1 :
				$string = $abbr ? 'Mon' : 'Monday';

				break;
			case 2 :
				$string = $abbr ? 'Tue' : 'Tuesday';

				break;
			case 3 :
				$string = $abbr ? 'Wed' : 'Wednesday';

				break;
			case 4 :
				$string = $abbr ? 'Thu' : 'Thursday';

				break;
			case 5 :
				$string = $abbr ? 'Fri' : 'Friday';

				break;
			case 6 :
				$string = $abbr ? 'Sat' : 'Saturday';

				break;
		}

		$translated = $this->getContainer()->language->text($string);

		return strtolower($translated) !== strtolower($string) ? $translated : $string;
	}

	protected function monthToString($month, $abbr = false)
	{
		switch ($month)
		{
			case 1 :
				$string = $abbr ? 'Jan' : 'January';
				break;
			case 2 :
				$string = $abbr ? 'Feb' : 'February';
				break;
			case 3 :
				$string = $abbr ? 'Mar' : 'March';
				break;
			case 4 :
				$string = $abbr ? 'Apr' : 'April';
				break;
			case 5 :
				$string = 'May';
				break;
			case 6 :
				$string = $abbr ? 'Jun' : 'June';
				break;
			case 7 :
				$string = $abbr ? 'Jul' : 'July';
				break;
			case 8 :
				$string = $abbr ? 'Aug' : 'August';
				break;
			case 9 :
				$string = $abbr ? 'Sep' : 'September';
				break;
			case 10 :
				$string = $abbr ? 'Oct' : 'October';
				break;
			case 11 :
				$string = $abbr ? 'Nov' : 'November';
				break;
			case 12 :
				$string = $abbr ? 'Dec' : 'December';
				break;
		}

		$key        = $string . ($abbr ? '_short' : '_genitive');
		$translated = $this->getContainer()->language->text($key);

		return strtolower($translated) !== strtolower($key) ? $translated : $string;
	}
}
