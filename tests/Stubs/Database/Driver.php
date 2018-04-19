<?php
/**
 * @package		awf
 * @copyright Copyright (c)2014-2018 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license		GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Database;


use Awf\Database\Query;
use Awf\Tests\Helpers\TestHelper;
use Awf\Tests\Stubs\Database\Query as MockQuery;

class Driver
{
	/**
	 * A query string or object.
	 *
	 * @var    mixed
	 * @since  1.0
	 */
	public static $lastQuery = null;

	/**
	 * Creates and instance of the mock Awf\Database\Driver object.
	 *
	 * @param   \PHPUnit_Framework_TestCase  $test        A test object.
	 * @param   string                       $nullDate    A null date string for the driver.
	 * @param   string                       $dateFormat  A date format for the driver.
	 *
	 * @return  object
	 *
	 * @since   1.0
	 */
	public static function create(\PHPUnit_Framework_TestCase $test, $nullDate = '0000-00-00 00:00:00', $dateFormat = 'Y-m-d H:i:s')
	{
		// Collect all the relevant methods in Awf\Database\Driver.
		$methods = array(
			'connect',
			'connected',
			'disconnect',
			'dropTable',
			'escape',
			'execute',
			'fetchArray',
			'fetchAssoc',
			'fetchObject',
			'freeResult',
			'getAffectedRows',
			'getCollation',
			'getConnectors',
			'getDateFormat',
			'getInstance',
			'getLog',
			'getNullDate',
			'getNumRows',
			'getPrefix',
			'getQuery',
			'getTableColumns',
			'getTableCreate',
			'getTableKeys',
			'getTableList',
			'getUtfSupport',
			'getVersion',
			'insertId',
			'insertObject',
			'loadAssoc',
			'loadAssocList',
			'loadColumn',
			'loadObject',
			'loadObjectList',
			'loadResult',
			'loadRow',
			'loadRowList',
			'lockTable',
			'query',
			'quote',
			'quoteName',
			'renameTable',
			'replacePrefix',
			'select',
			'setQuery',
			'setUTF',
			'splitSql',
			'test',
			'isSupported',
			'transactionCommit',
			'transactionRollback',
			'transactionStart',
			'unlockTables',
			'updateObject',
		);

		// Create the mock.
		$mockObject = $test->getMock(
			'\\Awf\\Database\\Driver',
			$methods,
			// Constructor arguments.
			array(),
			// Mock class name.
			'',
			// Call original constructor.
			false
		);

		// Mock selected methods.
		TestHelper::assignMockReturns(
			$mockObject,
			$test,
			array(
				'getNullDate' => $nullDate,
				'getDateFormat' => $dateFormat
			)
		);

		TestHelper::assignMockCallbacks(
			$mockObject,
			$test,
			array(
				'escape' => array((is_callable(array($test, 'mockEscape')) ? $test : __CLASS__), 'mockEscape'),
				'getQuery' => array((is_callable(array($test, 'mockGetQuery')) ? $test : __CLASS__), 'mockGetQuery'),
				'quote' => array((is_callable(array($test, 'mockQuote')) ? $test : __CLASS__), 'mockQuote'),
				'quoteName' => array((is_callable(array($test, 'mockQuoteName')) ? $test : __CLASS__), 'mockQuoteName'),
				'setQuery' => array((is_callable(array($test, 'mockSetQuery')) ? $test : __CLASS__), 'mockSetQuery'),
			)
		);

		return $mockObject;
	}

	/**
	 * Callback for the dbo escape method.
	 *
	 * @param   string  $text  The input text.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public static function mockEscape($text)
	{
		if (substr($text, 0, 1) == '_')
		{
			return $text;
		}

		return "_" . $text ."_";
	}

	/**
	 * Callback for the dbo setQuery method.
	 *
	 * @param   boolean  $new  True to get a new query, false to get the last query.
	 *
	 * @return  Query
	 *
	 * @since   1.0
	 */
	public static function mockGetQuery($new = false)
	{
		if ($new)
		{
			return new MockQuery();
		}
		else
		{
			return self::$lastQuery;
		}
	}

	/**
	 * Mocking the quote method.
	 *
	 * @param   string   $value   The value to be quoted.
	 * @param   boolean  $escape  Optional parameter to provide extra escaping.
	 *
	 * @return  string  The value passed wrapped in MySQL quotes.
	 *
	 * @since   1.0
	 */
	public static function mockQuote($value, $escape = true)
	{
		if (is_array($value))
		{
			foreach ($value as $k => $v)
			{
				$value[$k] = self::mockQuote($v, $escape);
			}

			return $value;
		}

		return '\'' . ($escape ? self::mockEscape($value) : $value) . '\'';
	}

	/**
	 * Mock quoteName method.
	 *
	 * @param   string  $value  The value to be quoted.
	 *
	 * @return  string  The value passed wrapped in MySQL quotes.
	 *
	 * @since   1.0
	 */
	public static function mockQuoteName($value)
	{
		return "`$value`";
	}

	/**
	 * Callback for the dbo setQuery method.
	 *
	 * @param   string  $query  The query.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public static function mockSetQuery($query)
	{
		self::$lastQuery = $query;
	}

}
