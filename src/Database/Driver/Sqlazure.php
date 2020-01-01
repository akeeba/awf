<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2020 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Database\Driver;

/**
 * SQL Server database driver
 *
 * This class is adapted from the Joomla! Framework
 */
class Sqlazure extends Sqlsrv
{
	/**
	 * The name of the database driver.
	 *
	 * @var    string
	 */
	public $name = 'sqlzure';

	/**
	 * @var    string  The database technology family supported, e.g. mysql, mssql
	 */
	public static $dbtech = 'mssql';

}
