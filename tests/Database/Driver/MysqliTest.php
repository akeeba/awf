<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2022 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Database\Driver;

use Awf\Tests\Database\DatabaseMysqliCase;

/**
 * Test class for Awf\Database\Driver\Mysqli.
 *
 * This class is adapted from Joomla! Framework
 *
 * @since  1.0
 */
class MysqliTest extends DatabaseMysqliCase
{
	protected function setUp($resetContainer = true)
	{
		parent::setUp(false);
	}

	/**
	 * Data for the testEscape test.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function dataTestEscape()
	{
		return array(
			array("'%_abc123", false, '\\\'%_abc123'),
			array("'%_abc123", true, '\\\'\\%\_abc123')
		);
	}

	/**
	 * Data for the testTransactionRollback test.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function dataTestTransactionRollback()
	{
		return array(array(null, 0), array('transactionSavepoint', 1));
	}

	/**
	 * Test connected method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testConnected()
	{
		self::$driver->connect();

		$this->assertTrue(self::$driver->connected());

		self::$driver->disconnect();

		$this->assertFalse(self::$driver->connected());
	}

	/**
	 * Tests the dropTable method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testDropTable()
	{
		$this->assertThat(
			self::$driver->dropTable('#__bar', true),
			$this->isInstanceOf('\\Awf\\Database\\Driver\\Mysqli'),
			'The table is dropped if present.'
		);
	}

	/**
	 * Tests the escape method.
	 *
	 * @param   string  $text     The string to be escaped.
	 * @param   boolean $extra    Optional parameter to provide extra escaping.
	 * @param   string  $expected The expected result.
	 *
	 * @return  void
	 *
	 * @dataProvider  dataTestEscape
	 * @since         1.0
	 */
	public function testEscape($text, $extra, $expected)
	{
		$this->assertThat(
			self::$driver->escape($text, $extra),
			$this->equalTo($expected),
			'The string was not escaped properly'
		);
	}

	/**
	 * Test getAffectedRows method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testGetAffectedRows()
	{
		$query = self::$driver->getQuery(true);
		$query->delete();
		$query->from('awf_dbtest');
		self::$driver->setQuery($query);

		self::$driver->execute();

		$this->assertThat(self::$driver->getAffectedRows(), $this->equalTo(4), __LINE__);
	}

	/**
	 * Test getCollation method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testGetCollation()
	{
		$this->assertThat(
			self::$driver->getCollation(),
			$this->equalTo('utf8_general_ci'),
			'Line:' . __LINE__ . ' The getCollation method should return the collation of the database.'
		);
	}

	/**
	 * Test getNumRows method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testGetNumRows()
	{
		$query = self::$driver->getQuery(true);
		$query->select('*');
		$query->from('awf_dbtest');
		$query->where('description = ' . self::$driver->quote('one'));
		self::$driver->setQuery($query);

		$res = self::$driver->execute();

		$this->assertThat(self::$driver->getNumRows($res), $this->equalTo(2), __LINE__);
	}

	/**
	 * Tests the getTableCreate method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testGetTableCreate()
	{
		$this->assertThat(
			self::$driver->getTableCreate('#__dbtest'),
			$this->isType('array'),
			'The statement to create the table is returned in an array.'
		);
	}

	/**
	 * Test getTableColumns method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testGetTableColumns()
	{
		$tableCol = array('id' => 'int unsigned', 'title' => 'varchar', 'start_date' => 'datetime', 'description' => 'varchar');

		$this->assertThat(
			self::$driver->getTableColumns('awf_dbtest'),
			$this->equalTo($tableCol),
			__LINE__
		);

		/* not only type field */
		$id = new \stdClass;
		$id->Default = null;
		$id->Field = 'id';
		$id->Type = 'int(10) unsigned';
		$id->Null = 'NO';
		$id->Key = 'PRI';
		$id->Collation = null;
		$id->Extra = 'auto_increment';
		$id->Privileges = 'select,insert,update,references';
		$id->Comment = '';

		$title = new \stdClass;
		$title->Default = null;
		$title->Field = 'title';
		$title->Type = 'varchar(50)';
		$title->Null = 'NO';
		$title->Key = '';
		$title->Collation = 'utf8_general_ci';
		$title->Extra = '';
		$title->Privileges = 'select,insert,update,references';
		$title->Comment = '';

		$start_date = new \stdClass;
		$start_date->Default = null;
		$start_date->Field = 'start_date';
		$start_date->Type = 'datetime';
		$start_date->Null = 'NO';
		$start_date->Key = '';
		$start_date->Collation = null;
		$start_date->Extra = '';
		$start_date->Privileges = 'select,insert,update,references';
		$start_date->Comment = '';

		$description = new \stdClass;
		$description->Default = null;
		$description->Field = 'description';
		$description->Type = 'varchar(255)';
		$description->Null = 'NO';
		$description->Key = '';
		$description->Collation = 'utf8_general_ci';
		$description->Extra = '';
		$description->Privileges = 'select,insert,update,references';
		$description->Comment = '';

		$this->assertThat(
			self::$driver->getTableColumns('awf_dbtest', false),
			$this->equalTo(
				array(
					'id'          => $id,
					'title'       => $title,
					'start_date'  => $start_date,
					'description' => $description
				)
			),
			__LINE__
		);
	}

	/**
	 * Tests the getTableKeys method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testGetTableKeys()
	{
		$this->assertThat(
			self::$driver->getTableKeys('#__dbtest'),
			$this->isType('array'),
			'The list of keys for the table is returned in an array.'
		);
	}

	/**
	 * Tests the getTableList method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testGetTableList()
	{
		$this->assertThat(
			self::$driver->getTableList(),
			$this->isType('array'),
			'The list of tables for the database is returned in an array.'
		);
	}

	/**
	 * Test getVersion method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testGetVersion()
	{
		$this->assertThat(
			strlen(self::$driver->getVersion()),
			$this->greaterThan(0),
			'Line:' . __LINE__ . ' The getVersion method should return something without error.'
		);
	}

	/**
	 * Test insertid method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testInsertid()
	{
		self::$driver->truncateTable('#__dbtest');

		$query = self::$driver->getQuery(true)
			->insert('#__dbtest')
			->columns(array('title', 'start_date', 'description'))
			->values(self::$driver->q('New record') . ', ' . self::$driver->q('2014-06-18 00:00:00') . ', ' . self::$driver->q('Something something something text'));
		self::$driver->setQuery($query)->execute();

		$insertId = self::$driver->insertid();

		$this->assertEquals(1, $insertId);
	}

	/**
	 * Test loadAssoc method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testLoadAssoc()
	{
		$query = self::$driver->getQuery(true);
		$query->select('title');
		$query->from('awf_dbtest');
		self::$driver->setQuery($query);
		$result = self::$driver->loadAssoc();

		$this->assertThat($result, $this->equalTo(array('title' => 'Testing')), __LINE__);
	}

	/**
	 * Test loadAssocList method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testLoadAssocList()
	{
		$query = self::$driver->getQuery(true);
		$query->select('title');
		$query->from('awf_dbtest');
		self::$driver->setQuery($query);
		$result = self::$driver->loadAssocList();

		$this->assertThat(
			$result,
			$this->equalTo(
				array(
					array('title' => 'Testing'),
					array('title' => 'Testing2'),
					array('title' => 'Testing3'),
					array('title' => 'Testing4')
				)
			),
			__LINE__
		);
	}

	/**
	 * Test loadColumn method
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testLoadColumn()
	{
		$query = self::$driver->getQuery(true);
		$query->select('title');
		$query->from('awf_dbtest');
		self::$driver->setQuery($query);
		$result = self::$driver->loadColumn();

		$this->assertThat($result, $this->equalTo(array('Testing', 'Testing2', 'Testing3', 'Testing4')), __LINE__);
	}

	/**
	 * Test loadObject method
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testLoadObject()
	{
		$query = self::$driver->getQuery(true);
		$query->select('*');
		$query->from('awf_dbtest');
		$query->where('description=' . self::$driver->quote('three'));
		self::$driver->setQuery($query);
		$result = self::$driver->loadObject();

		$objCompare = new \stdClass;
		$objCompare->id = 3;
		$objCompare->title = 'Testing3';
		$objCompare->start_date = '1980-04-18 00:00:00';
		$objCompare->description = 'three';

		$this->assertThat($result, $this->equalTo($objCompare), __LINE__);
	}

	/**
	 * Test loadObjectList method
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testLoadObjectList()
	{
		$query = self::$driver->getQuery(true);
		$query->select('*');
		$query->from('awf_dbtest');
		$query->order('id');
		self::$driver->setQuery($query);
		$result = self::$driver->loadObjectList();

		$expected = array();

		$objCompare = new \stdClass;
		$objCompare->id = 1;
		$objCompare->title = 'Testing';
		$objCompare->start_date = '1980-04-18 00:00:00';
		$objCompare->description = 'one';

		$expected[] = clone $objCompare;

		$objCompare = new \stdClass;
		$objCompare->id = 2;
		$objCompare->title = 'Testing2';
		$objCompare->start_date = '1980-04-18 00:00:00';
		$objCompare->description = 'one';

		$expected[] = clone $objCompare;

		$objCompare = new \stdClass;
		$objCompare->id = 3;
		$objCompare->title = 'Testing3';
		$objCompare->start_date = '1980-04-18 00:00:00';
		$objCompare->description = 'three';

		$expected[] = clone $objCompare;

		$objCompare = new \stdClass;
		$objCompare->id = 4;
		$objCompare->title = 'Testing4';
		$objCompare->start_date = '1980-04-18 00:00:00';
		$objCompare->description = 'four';

		$expected[] = clone $objCompare;

		$this->assertThat($result, $this->equalTo($expected), __LINE__);
	}

	/**
	 * Test loadResult method
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testLoadResult()
	{
		$query = self::$driver->getQuery(true);
		$query->select('id');
		$query->from('awf_dbtest');
		$query->where('title=' . self::$driver->quote('Testing2'));

		self::$driver->setQuery($query);
		$result = self::$driver->loadResult();

		$this->assertThat($result, $this->equalTo(2), __LINE__);
	}

	/**
	 * Test loadRow method
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testLoadRow()
	{
		$query = self::$driver->getQuery(true);
		$query->select('*');
		$query->from('awf_dbtest');
		$query->where('description=' . self::$driver->quote('three'));
		self::$driver->setQuery($query);
		$result = self::$driver->loadRow();

		$expected = array(3, 'Testing3', '1980-04-18 00:00:00', 'three');

		$this->assertThat($result, $this->equalTo($expected), __LINE__);
	}

	/**
	 * Test loadRowList method
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testLoadRowList()
	{
		$query = self::$driver->getQuery(true);
		$query->select('*');
		$query->from('awf_dbtest');
		$query->where('description=' . self::$driver->quote('one'));
		self::$driver->setQuery($query);
		$result = self::$driver->loadRowList();

		$expected = array(array(1, 'Testing', '1980-04-18 00:00:00', 'one'), array(2, 'Testing2', '1980-04-18 00:00:00', 'one'));

		$this->assertThat($result, $this->equalTo($expected), __LINE__);
	}

	/**
	 * Tests the renameTable method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testRenameTable()
	{
		$newTableName = 'bak_awf_dbtest';

		self::$driver->renameTable('awf_dbtest', $newTableName);

		// Check name change
		$tableList = self::$driver->getTableList();
		$this->assertThat(in_array($newTableName, $tableList), $this->isTrue(), __LINE__);

		// Restore initial state
		self::$driver->renameTable($newTableName, 'awf_dbtest');
	}

	/**
	 * Test the execute method
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testExecute()
	{
		self::$driver->setQuery("REPLACE INTO `awf_dbtest` SET `id` = 5, `title` = 'testTitle'");

		$this->assertThat(self::$driver->execute(), $this->isTrue(), __LINE__);

		$this->assertThat(self::$driver->insertid(), $this->equalTo(5), __LINE__);
	}

	/**
	 * Test select method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testSelect()
	{
		$altDb = defined('AWFTEST_DATABASE_MYSQLI_ALTDB') ? AWFTEST_DATABASE_MYSQLI_ALTDB : getenv('AWFTEST_DATABASE_MYSQLI_ALTDB');
		self::$driver->select($altDb);

		$query = 'SELECT DATABASE()';
		$currentDb = self::$driver->setQuery($query)->loadResult();

		$this->assertEquals(
			$altDb,
			$currentDb
		);

		self::$driver->select(self::$options['database']);
	}

	/**
	 * Test setUTF method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testSetUTF()
	{
		self::$driver->setUTF();

		$query = "show variables like 'character_set_client'";
		$currentCharset = self::$driver->setQuery($query)->loadColumn(1);

		$this->assertEquals('utf8', $currentCharset[0]);
	}

	/**
	 * Tests the transactionCommit method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testTransactionCommit()
	{
		self::$driver->transactionStart();
		$queryIns = self::$driver->getQuery(true);
		$queryIns->insert('#__dbtest')
			->columns('id, title, start_date, description')
			->values("6, 'testTitle', '1970-01-01', 'testDescription'");

		self::$driver->setQuery($queryIns)->execute();

		self::$driver->transactionCommit();

		/* check if value is present */
		$queryCheck = self::$driver->getQuery(true);
		$queryCheck->select('*')
			->from('#__dbtest')
			->where('id = 6');
		self::$driver->setQuery($queryCheck);
		$result = self::$driver->loadRow();

		$expected = array('6', 'testTitle', '1970-01-01 00:00:00', 'testDescription');

		$this->assertThat($result, $this->equalTo($expected), __LINE__);
	}

	/**
	 * Tests the transactionRollback method, with and without savepoint.
	 *
	 * @param   string $toSavepoint Savepoint name to rollback transaction to
	 * @param   int    $tupleCount  Number of tuple found after insertion and rollback
	 *
	 * @return  void
	 *
	 * @since        1.0
	 * @dataProvider dataTestTransactionRollback
	 */
	public function testTransactionRollback($toSavepoint, $tupleCount)
	{
		self::$driver->transactionStart();

		/* try to insert this tuple, inserted only when savepoint != null */
		$queryIns = self::$driver->getQuery(true);
		$queryIns->insert('#__dbtest_innodb')
			->columns('id, title, start_date, description')
			->values("7, 'testRollback', '1970-01-01', 'testRollbackSp'");
		self::$driver->setQuery($queryIns)->execute();

		/* create savepoint only if is passed by data provider */
		if (!is_null($toSavepoint))
		{
			self::$driver->transactionStart((boolean)$toSavepoint);
		}

		/* try to insert this tuple, always rolled back */
		$queryIns = self::$driver->getQuery(true);
		$queryIns->insert('#__dbtest_innodb')
			->columns('id, title, start_date, description')
			->values("8, 'testRollback', '1972-01-01', 'testRollbackSp'");
		self::$driver->setQuery($queryIns)->execute();

		self::$driver->transactionRollback((boolean)$toSavepoint);

		/* release savepoint and commit only if a savepoint exists */
		if (!is_null($toSavepoint))
		{
			self::$driver->transactionCommit();
		}

		/* find how many rows have description='testRollbackSp' :
		 *   - 0 if a savepoint doesn't exist
		 *   - 1 if a savepoint exists
		 */
		$queryCheck = self::$driver->getQuery(true);
		$queryCheck->select('*')
			->from('#__dbtest_innodb')
			->where("description = 'testRollbackSp'");
		self::$driver->setQuery($queryCheck);
		$result = self::$driver->loadRowList();

		$this->assertThat(count($result), $this->equalTo($tupleCount), __LINE__);
	}

	/**
	 * Test isSupported method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testIsSupported()
	{
		$this->assertThat(\Awf\Database\Driver\Mysqli::isSupported(), $this->isTrue(), __LINE__);
	}

	/**
	 * Test insertObject method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testInsertObject()
	{
		$sampleData = (object)array(
			'id'          => null,
			'title'       => 'test_insert',
			'start_date'  => '2014-06-17 00:00:00',
			'description' => 'Testing object insert'
		);
		$table = '#__dbtest';
		$db = self::$driver;

		// Inserting really adds to database
		$db->truncateTable($table);
		$result = $db->insertObject($table, $sampleData);
		$this->assertTrue($result);
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($table)
			->where('title = ' . $db->q($sampleData->title));
		$this->assertNotEmpty($db->setQuery($query)->loadResult());
		$this->assertNull($sampleData->id);

		// Inserting and specifying key updates the object
		$db->truncateTable($table);
		$result = $db->insertObject($table, $sampleData, 'id');
		$this->assertTrue($result);
		$this->assertNotNull($sampleData->id);

		// Bad keys are ignored
		$newSampleData = array_merge((array)$sampleData, array('doesnotexist' => 1234));
		$db->truncateTable($table);
		$result = $db->insertObject($table, $sampleData);
		$this->assertTrue($result);
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($table)
			->where('title = ' . $db->q($sampleData->title));
		$this->assertNotEmpty($db->setQuery($query)->loadResult());

		// "Internal" keys (starting with underscore) and non-scalars are ignored
		$newSampleData = array_merge((array)$sampleData, array('_internal' => 1234, 'baz' => array(1, 2, 3), 'whatever' => (object)array('foo' => 'bar')));
		$db->truncateTable($table);
		$result = $db->insertObject($table, $sampleData);
		$this->assertTrue($result);
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($table)
			->where('title = ' . $db->q($sampleData->title));
		$this->assertNotEmpty($db->setQuery($query)->loadResult());

		// Failures result in exception
		$this->setExpectedException('\RuntimeException');
		$result = $db->insertObject($table . '_foobar', $sampleData, 'id');
	}

	/**
	 * Test updateObject method.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function testUpdateObject()
	{
		$sampleData = (object)array(
			'id'          => null,
			'title'       => 'test_insert',
			'start_date'  => '2014-06-17 00:00:00',
			'description' => 'Testing object insert'
		);
		$table = '#__dbtest';
		$db = self::$driver;

		// Updating full object really updates the database
		$db->truncateTable($table);
		$db->insertObject($table, $sampleData, 'id');
		$newObject = (object)array(
			'id'          => $sampleData->id,
			'title'       => 'test_update',
			'start_date'  => '2005-08-15 18:00:00',
			'description' => 'Updated record'
		);
		$result = $db->updateObject($table, $newObject, 'id');
		$this->assertTrue($result);
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($table)
			->where('title = ' . $db->q($sampleData->title));
		$this->assertEmpty($db->setQuery($query)->loadResult());
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($table)
			->where('title = ' . $db->q($newObject->title));
		$this->assertNotEmpty($db->setQuery($query)->loadResult());

		// Ignoring nulls does not modify data already in the database
		$db->truncateTable($table);
		$db->insertObject($table, $sampleData, 'id');
		$newObject = (object)array(
			'id'          => $sampleData->id,
			'title'       => null,
			'start_date'  => '2005-08-15 18:00:00',
			'description' => 'Updated again record'
		);
		$result = $db->updateObject($table, $newObject, 'id', false);
		$this->assertTrue($result);
		$query = $db->getQuery(true)
			->select('title')
			->from($table)
			->where('id = ' . $db->q($sampleData->id));
		$this->assertEquals($sampleData->title, $db->setQuery($query)->loadResult());

		// "Internal" keys (starting with underscore) and non-scalars are ignored
		$db->truncateTable($table);
		$db->insertObject($table, $sampleData, 'id');
		$newObject = array_merge((array)$sampleData, array(
			'_internal' => 1234,
			'baz' => array(1, 2, 3),
			'whatever' => (object)array('foo' => 'bar'),
		));
		$newObject = (object)$newObject;
		$result = $db->updateObject($table, $newObject, 'id', false);
		$this->assertTrue($result);

		// Wrong ID does not throw error (as no SQL error is raised by the database: it's a valid query with 0 affeced rows)
		$db->truncateTable($table);
		$db->insertObject($table, $sampleData, 'id');
		$newObject = (object)array(
			'id'          => $sampleData->id + 10000,
			'title'       => 'this_will_fail',
			'start_date'  => '2005-08-15 18:00:00',
			'description' => 'Updated again record'
		);
		$result = $db->updateObject($table, $newObject, 'id');
		$this->assertTrue($result);
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($table)
			->where('title = ' . $db->q($newObject->title));
		$this->assertEmpty($db->setQuery($query)->loadResult());

		// Nonexistent fields result in exception
		$db->truncateTable($table);
		$db->insertObject($table, $sampleData, 'id');
		$newObject = array_merge((array)$sampleData, array(
			'iamnothere' => 1234,
		));
		$newObject = (object)$newObject;
		$this->setExpectedException('\RuntimeException');
		$result = $db->updateObject($table, $newObject, 'id', false);
	}
}
