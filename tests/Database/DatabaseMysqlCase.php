<?php
/**
 * @package        awf
 * @subpackage     tests.date.date
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 *
 * This class is adapted from Joomla! Framework
 */

namespace Awf\Tests\Database;

use Awf\Tests\Helpers\DatabaseTest;
use Awf\Database\Driver;

/**
 * Abstract test case class for MySQL database testing.
 *
 * @since  1.0
 */
abstract class DatabaseMysqlCase extends DatabaseTest
{
	/**
	 * @var    array  The database driver options for the connection.
	 * @since  1.0
	 */
	protected static $options = array('driver' => 'mysql', 'prefix' => 'awf_');

	/**
	 * This method is called before the first test of this test class is run.
	 *
	 * An example DSN would be: host=localhost;dbname=awf_ut;user=utuser;pass=ut1234
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public static function setUpBeforeClass()
	{
		// First let's look to see if we have a DSN defined or in the environment variables.
		if (defined('AWFTEST_DATABASE_MYSQL_DSN') || getenv('AWFTEST_DATABASE_MYSQL_DSN'))
		{
			$dsn = defined('AWFTEST_DATABASE_MYSQL_DSN') ? AWFTEST_DATABASE_MYSQL_DSN : getenv('AWFTEST_DATABASE_MYSQL_DSN');
		}
		else
		{
			return;
		}

		// First let's trim the mysql: part off the front of the DSN if it exists.
		if (strpos($dsn, 'mysql:') === 0)
		{
			$dsn = substr($dsn, 6);
		}

		// Split the DSN into its parts over semicolons.
		$parts = explode(';', $dsn);

		// Parse each part and populate the options array.
		foreach ($parts as $part)
		{
			list ($k, $v) = explode('=', $part, 2);

			switch ($k)
			{
				case 'host':
					self::$options['host'] = $v;
					break;
				case 'dbname':
					self::$options['database'] = $v;
					break;
				case 'user':
					self::$options['user'] = $v;
					break;
				case 'pass':
					self::$options['password'] = $v;
					break;
			}
		}

		try
		{
			// Attempt to instantiate the driver.
			self::$driver = Driver::getInstance(self::$options);
		}
		catch (\RuntimeException $e)
		{
			self::$driver = null;
		}

		// If for some reason an exception object was returned set our database object to null.
		if (self::$driver instanceof \Exception)
		{
			self::$driver = null;
		}
		else
		{
			static::$container['dbrestore'] = array(
				'dbkey'			=> 'mysqltestcase',
				'dbtype'		=> 'mysql',
				'sqlfile'		=> 'mysql.sql',
				'maxexectime'	=> 1000,
				'runtimebias'	=> 100,
				'dbhost'		=> self::$options['host'],
				'dbuser'		=> self::$options['user'],
				'dbpass'		=> self::$options['password'],
				'dbname'		=> self::$options['database'],
				'prefix'		=> 'awf_',
				'existing'		=> 'drop',
				'foreignkey'	=> 0,
				'utf8db'		=> 0,
				'utf8tables'	=> 0,
				'replace'		=> 0,
			);

			$restore = new \Awf\Database\Restore\Mysql(static::$container);
			$restore->stepRestoration();
		}
	}

	/**
	 * Gets the data set to be loaded into the database during setup
	 *
	 * @return  \PHPUnit_Extensions_Database_DataSet_XmlDataSet
	 *
	 * @since   1.0
	 */
	protected function getDataSet()
	{
		return $this->createXMLDataSet(__DIR__ . '/../Stubs/schema/database.xml');
	}

	/**
	 * Returns the default database connection for running the tests.
	 *
	 * @return  \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
	 *
	 * @since   1.0
	 */
	protected function getConnection()
	{
		// Compile the connection DSN.
		$dsn = 'mysql:host=' . self::$options['host'] . ';dbname=' . self::$options['database'];

		// Create the PDO object from the DSN and options.
		$pdo = new \PDO($dsn, self::$options['user'], self::$options['password']);

		return $this->createDefaultDBConnection($pdo, self::$options['database']);
	}
}
