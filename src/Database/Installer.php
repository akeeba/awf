<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Database;

use Awf\Container\Container;
use Awf\Filesystem\File;
use Exception;
use SimpleXMLElement;

class Installer
{
	/** @var array Internal cache for table list */
	protected static $allTables = [];

	/** @var  Driver  The database connector object */
	private $db = null;

	/** @var  string  The directory where the XML schema files are stored */
	private $xmlDirectory = null;

	/** @var  string  Force a specific **absolute** file path for the XML schema file */
	private $forcedFile = null;

	/**
	 * Public constructor
	 *
	 * @param   Container  $container  The application container
	 */
	public function __construct(Container $container)
	{
		$this->xmlDirectory = $container->basePath . '/assets/sql/xml';
		$this->db           = $container->db;
	}

	/**
	 * Returns the directory where XML schema files are stored
	 *
	 * @return  string
	 *
	 * @codeCoverageIgnore
	 */
	public function getXmlDirectory()
	{
		return $this->xmlDirectory;
	}

	/**
	 * Sets the directory where XML schema files are stored
	 *
	 * @param   string  $xmlDirectory
	 *
	 * @codeCoverageIgnore
	 */
	public function setXmlDirectory($xmlDirectory)
	{
		$this->xmlDirectory = $xmlDirectory;
	}

	/**
	 * Returns the absolute path to the forced XML schema file
	 *
	 * @return  string
	 *
	 * @codeCoverageIgnore
	 */
	public function getForcedFile()
	{
		return $this->forcedFile;
	}

	/**
	 * Sets the absolute path to an XML schema file which will be read no matter what. Set to a blank string to let the
	 * Installer class auto-detect your schema file based on your database type.
	 *
	 * @param   string  $forcedFile
	 *
	 * @codeCoverageIgnore
	 */
	public function setForcedFile($forcedFile)
	{
		$this->forcedFile = $forcedFile;
	}

	/**
	 * Clears the internal table list cache
	 *
	 * @return  void
	 */
	public function nukeCache(): void
	{
		static::$allTables = [];
	}


	/**
	 * Creates or updates the database schema
	 *
	 * @return  void
	 *
	 * @throws  Exception  When a database query fails and it doesn't have the canfail flag
	 */
	public function updateSchema()
	{
		// Get the schema XML file
		$xml = $this->findSchemaXml();

		if (empty($xml))
		{
			return;
		}

		// Make sure there are SQL commands in this file
		if (!$xml->sql)
		{
			return;
		}

		// Walk the sql > action tags to find all tables
		$tables = [];
		/** @var SimpleXMLElement $actions */
		$actions = $xml->sql->children();

		// If we have an uppercase db prefix we can expect CREATE TABLE fail because we cannot detect reliably
		// the existence of database tables. See https://github.com/joomla/joomla-cms/issues/10928#issuecomment-228549658
		$prefix             = $this->db->getPrefix();
		$canFailCreateTable = preg_match('/[A-Z]/', $prefix);

		/** @var SimpleXMLElement $action */
		foreach ($actions as $action)
		{
			// Get the attributes
			$attributes = $action->attributes();

			// Get the table / view name
			$table = $attributes->table ? (string) $attributes->table : '';

			if (empty($table))
			{
				continue;
			}

			// Am I allowed to let this action fail?
			$canFailAction = $attributes->canfail ?: 0;

			// Evaluate conditions
			$shouldExecute = true;

			/** @var SimpleXMLElement $node */
			foreach ($action->children() as $node)
			{
				if ($node->getName() == 'condition')
				{
					// Get the operator
					$operator = $node->attributes()->operator ? (string) $node->attributes()->operator : 'and';
					$operator = empty($operator) ? 'and' : $operator;

					$condition = $this->conditionMet($table, $node);

					switch ($operator)
					{
						case 'not':
							$shouldExecute = $shouldExecute && !$condition;
							break;

						case 'or':
							$shouldExecute = $shouldExecute || $condition;
							break;

						case 'nor':
							$shouldExecute = !$shouldExecute && !$condition;
							break;

						case 'xor':
							$shouldExecute = ($shouldExecute xor $condition);
							break;

						case 'maybe':
							$shouldExecute = $condition ? true : $shouldExecute;
							break;

						default:
							$shouldExecute = $shouldExecute && $condition;
							break;
					}
				}

				// DO NOT USE BOOLEAN SHORT CIRCUIT EVALUATION!
				// if (!$shouldExecute) break;
			}

			// Make sure all conditions are met OR I have to collect tables from CREATE TABLE queries.
			if (!$shouldExecute)
			{
				continue;
			}

			// Execute queries
			foreach ($action->children() as $node)
			{
				if ($node->getName() == 'query')
				{
					$query = (string) $node;

					$canFail = $node->attributes->canfail ? (string) $node->attributes->canfail : $canFailAction;

					if (is_string($canFail))
					{
						$canFail = strtoupper($canFail);
					}

					$canFail = (in_array($canFail, [true, 1, 'YES', 'TRUE']));

					try
					{
						$this->db->setQuery($query);
						$this->db->execute();
					}
					catch (Exception $e)
					{
						// Special consideration for CREATE TABLE commands on uppercase prefix databases.
						if ($canFailCreateTable && stripos($query, 'CREATE TABLE') !== false)
						{
							$canFail = true;
						}

						// If we are not allowed to fail, throw back the exception we caught
						if (!$canFail)
						{
							throw $e;
						}
					}
				}
			}
		}
	}

	/**
	 * Uninstalls the database schema
	 *
	 * @return  void
	 */
	public function removeSchema()
	{
		// Get the schema XML file
		$xml = $this->findSchemaXml();

		if (empty($xml))
		{
			return;
		}

		// Make sure there are SQL commands in this file
		if (!$xml->sql)
		{
			return;
		}

		// Walk the sql > action tags to find all tables
		$tables = [];
		/** @var SimpleXMLElement $actions */
		$actions = $xml->sql->children();

		/** @var SimpleXMLElement $action */
		foreach ($actions as $action)
		{
			$attributes = $action->attributes();
			$tables[]   = (string) $attributes->table;
		}

		// Simplify the tables list
		$tables = array_unique($tables);

		// Start dropping tables
		foreach ($tables as $table)
		{
			try
			{
				$this->db->dropTable($table);
			}
			catch (Exception $e)
			{
				// Do not fail if I can't drop the table
			}
		}
	}

	/**
	 * Find an suitable schema XML file for this database type and return the SimpleXMLElement holding its information
	 *
	 * @return  null|SimpleXMLElement  Null if no suitable schema XML file is found
	 */
	protected function findSchemaXml()
	{
		$xml = null;

		// Do we have a forced file?
		if ($this->forcedFile)
		{
			$xml = $this->openAndVerify($this->forcedFile);

			if ($xml !== null)
			{
				return $xml;
			}
		}

		// Get all XML files in the schema directory
		$filesystem = new File([]);
		$xmlFiles   = $filesystem->directoryFiles($this->xmlDirectory, '\.xml$');

		if (empty($xmlFiles))
		{
			return $xml;
		}

		foreach ($xmlFiles as $baseName)
		{
			// Remove any accidental whitespace
			$baseName = trim($baseName);

			// Get the full path to the file
			$fileName = $this->xmlDirectory . '/' . $baseName;

			$xml = $this->openAndVerify($fileName);

			if ($xml !== null)
			{
				return $xml;
			}
		}

		return null;
	}

	/**
	 * Opens the schema XML file and return the SimpleXMLElement holding its information. If the file doesn't exist, it
	 * is not a schema file or it doesn't match our database driver we return boolean false.
	 *
	 * @return  null|SimpleXMLElement  False if it's not a suitable XML schema file
	 */
	protected function openAndVerify($fileName): ?SimpleXMLElement
	{
		$driverType = $this->db->name;

		// Make sure the file exists
		if (!@file_exists($fileName))
		{
			return null;
		}

		// Make sure the file is a valid XML document
		try
		{
			$xml = new SimpleXMLElement($fileName, LIBXML_NONET, true);
		}
		catch (Exception $e)
		{
			return null;
		}

		// Make sure the file is an XML schema file
		if ($xml->getName() != 'schema')
		{
			return null;
		}

		if (!$xml->meta)
		{
			return null;
		}

		if (!$xml->meta->drivers)
		{
			return null;
		}

		/** @var SimpleXMLElement $drivers */
		$drivers = $xml->meta->drivers;

		foreach ($drivers->children() as $driverTypeTag)
		{
			$thisDriverType = (string) $driverTypeTag;

			if ($thisDriverType == $driverType)
			{
				return $xml;
			}
		}

		return null;
	}

	/**
	 * Checks if a condition is met
	 *
	 * @param   string            $table  The table we're operating on
	 * @param   SimpleXMLElement  $node   The condition definition node
	 *
	 * @return  bool
	 */
	protected function conditionMet($table, SimpleXMLElement $node)
	{
		if (empty(static::$allTables))
		{
			static::$allTables = $this->db->getTableList();
		}

		// Does the table exist?
		$tableNormal = $this->db->replacePrefix($table);
		$tableExists = in_array($tableNormal, static::$allTables);

		// Initialise
		$condition = false;

		// Get the condition's attributes
		$attributes = $node->attributes();
		$type       = $attributes->type ?: null;
		$value      = $attributes->value ? (string) $attributes->value : null;

		switch ($type)
		{
			// Check if a table or column is missing
			case 'missing':
				$fieldName = (string) $value;

				if (empty($fieldName))
				{
					$condition = !$tableExists;
				}
				else
				{
					try
					{
						$tableColumns = $this->db->getTableColumns($tableNormal, true);
					}
					catch (Exception $e)
					{
						$tableColumns = [];
					}

					$condition = !array_key_exists($fieldName, $tableColumns);
				}

				break;

			// Check if a column type matches the "coltype" attribute
			case 'type':
				try
				{
					$tableColumns = $this->db->getTableColumns($tableNormal, true);
				}
				catch (Exception $e)
				{
					$tableColumns = [];
				}

				$condition = false;

				if (array_key_exists($value, $tableColumns))
				{
					$coltype = $attributes->coltype ?: null;

					if (!empty($coltype))
					{
						$coltype     = strtolower($coltype);
						$currentType = is_string($tableColumns[$value]) ? $tableColumns[$value] : strtolower($tableColumns[$value]->Type);

						$condition = ($coltype === $currentType);
					}
				}

				break;

			// Check if a column is nullable
			case 'nullable':
				try
				{
					$tableColumns = $this->db->getTableColumns($tableNormal, true);
				}
				catch (Exception $e)
				{
					$tableColumns = [];
				}

				$condition = false;

				if (array_key_exists($value, $tableColumns))
				{
					$condition = (is_string($tableColumns[$value]) ? 'YES' : strtolower($tableColumns[$value]->Null)) == 'yes';
				}

				break;

			// Check if a (named) index exists on the table. Currently only supported on MySQL.
			case 'index':
				$indexName = (string) $value;
				$condition = true;

				if (!empty($indexName))
				{
					$indexName = str_replace('#__', $this->db->getPrefix(), $indexName);
					$condition = $this->hasIndex($tableNormal, $indexName);
				}

				break;

			// Check if the result of a query matches our expectation
			case 'equals':
				$query = (string) $node;

				try
				{
					// DO NOT use $this->db->replacePrefix. It does not replace the prefix in strings, only entity names
					$query = str_replace('#__', $this->db->getPrefix(), $query);
					$this->db->setQuery($query);

					$result    = $this->db->loadResult();
					$condition = ($result == $value);
				}
				catch (Exception $e)
				{
					return false;
				}

				break;

			// Always returns true
			case 'true':
				return true;

			default:
				return false;
		}

		return $condition;
	}

	/**
	 * Returns true if table $tableName has an index named $indexName or if it's impossible to retrieve index names for
	 * the table (not enough privileges, not a MySQL database, ...)
	 *
	 * @param   string  $tableName  The name of the table
	 * @param   string  $indexName  The name of the index
	 *
	 * @return  bool
	 */
	private function hasIndex(string $tableName, string $indexName): bool
	{
		static $isMySQL = null;
		static $cache = [];

		if (is_null($isMySQL))
		{
			$driverType = $this->db->name;
			$driverType = strtolower($driverType);
			$isMySQL    = true;

			if (strpos($driverType, 'mysql') === false)
			{
				$isMySQL = false;
			}
		}

		// Not MySQL? Lie and return true.
		if (!$isMySQL)
		{
			return true;
		}

		if (!isset($cache[$tableName]))
		{
			$cache[$tableName] = [];
		}

		if (!isset($cache[$tableName][$indexName]))
		{
			$cache[$tableName][$indexName] = true;

			try
			{
				$indices          = [];
				$query            = 'SHOW INDEXES FROM ' . $this->db->qn($tableName);
				$indexDefinitions = $this->db->setQuery($query)->loadAssocList();

				if (!empty($indexDefinitions) && is_array($indexDefinitions))
				{
					foreach ($indexDefinitions as $def)
					{
						$indices[] = $def['Key_name'];
					}

					$indices = array_unique($indices);
				}

				$cache[$tableName][$indexName] = in_array($indexName, $indices);
			}
			catch (Exception $e)
			{
				// Ignore errors
			}
		}

		return $cache[$tableName][$indexName];
	}
}
