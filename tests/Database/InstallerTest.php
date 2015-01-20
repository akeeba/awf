<?php
/**
 * @package        awf
 * @copyright      2014-2015 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 *
 */

namespace Awf\Tests\Database;

use Awf\Database\Installer;

/**
 * @covers  Awf\Database\Installer::<protected>
 * @covers  Awf\Database\Installer::<private>
 */
class InstallerTest extends DatabaseMysqliCase
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        // Make sure the #__foobar_example table does not exist before running the tests
        $db = static::$container->db;
        $db->dropTable('#__foobar_example', true);
    }

    public static function tearDownAfterClass()
    {
        // Make sure the #__foobar_example table does not exist after running the tests
        $db = static::$container->db;
        $db->dropTable('#__foobar_example', true);

        parent::tearDownAfterClass();
    }

    /**
     * @covers  Awf\Database\Installer::__construct
     * @covers  Awf\Database\Installer::updateSchema
     *
     * @throws \Exception
     */
    public function testCreateNewTable()
    {
        $db        = static::$container->db;

        $tables = $db->setQuery('SHOW TABLES')->loadColumn();
        $prefix = $db->getPrefix();
        $this->assertFalse(in_array($prefix . 'foobar_example', $tables), 'The table must not exist before testing starts');

        $installer = new Installer(static::$container);
        $installer->setXmlDirectory(static::$container->basePath.'/sql/pick_right');
        $installer->updateSchema();

        $tables = $db->setQuery('SHOW TABLES')->loadColumn();
        $prefix = $db->getPrefix();
        $this->assertTrue(in_array($prefix . 'foobar_example', $tables), 'The table must exist after running updateSchema');
    }

    /**
     * @covers  Awf\Database\Installer::__construct
     * @covers  Awf\Database\Installer::updateSchema
     *
     * @throws \Exception
     */
    public function testInsertDataUsingEqualsChecks()
    {
        $db = static::$container->db;

        $installer = new Installer(static::$container);
        $installer->setForcedFile(static::$container->basePath.'/sql/test_equals.xml');
        $installer->updateSchema();

        $query = $db->getQuery(true)->select('description')->from('#__foobar_example')->where('example_id = 1');
        $actual = $db->setQuery($query)->loadResult();
        $this->assertEquals('Example one', $actual, 'Equals with select');

        $query = $db->getQuery(true)->select('description')->from('#__foobar_example')->where('example_id = 2');
        $actual = $db->setQuery($query)->loadResult();
        $this->assertEquals('Example two', $actual, 'Test not operator');

        $query = $db->getQuery(true)->select('description')->from('#__foobar_example')->where('example_id = 3');
        $actual = $db->setQuery($query)->loadResult();
        $this->assertEquals('Example three', $actual, 'Test type=true');

        $query = $db->getQuery(true)->select('description')->from('#__foobar_example')->where('example_id = 4');
        $actual = $db->setQuery($query)->loadResult();
        $this->assertNotEquals('Example four', $actual, 'Test type=false');

        $query = $db->getQuery(true)->select('description')->from('#__foobar_example')->where('example_id = 5');
        $actual = $db->setQuery($query)->loadResult();
        $this->assertEquals('Example 5', $actual, 'Test operator OR positive');

        $query = $db->getQuery(true)->select('description')->from('#__foobar_example')->where('example_id = 6');
        $actual = $db->setQuery($query)->loadResult();
        $this->assertNotEquals('Example 6', $actual, 'Test operator OR negative');

        $query = $db->getQuery(true)->select('description')->from('#__foobar_example')->where('example_id = 7');
        $actual = $db->setQuery($query)->loadResult();
        $this->assertEquals('Example 7', $actual, 'Test operator NOR positive');

        $query = $db->getQuery(true)->select('description')->from('#__foobar_example')->where('example_id = 8');
        $actual = $db->setQuery($query)->loadResult();
        $this->assertNotEquals('Example 8', $actual, 'Test operator NOR negative');

        $query = $db->getQuery(true)->select('description')->from('#__foobar_example')->where('example_id = 9');
        $actual = $db->setQuery($query)->loadResult();
        $this->assertEquals('Example 9', $actual, 'Test operator XOR positive');

        $query = $db->getQuery(true)->select('description')->from('#__foobar_example')->where('example_id = 10');
        $actual = $db->setQuery($query)->loadResult();
        $this->assertNotEquals('Example 10', $actual, 'Test operator XOR negative');

        $query = $db->getQuery(true)->select('description')->from('#__foobar_example')->where('example_id = 11');
        $actual = $db->setQuery($query)->loadResult();
        $this->assertEquals('Example 11', $actual, 'Test operator MAYBE positive');

        $query = $db->getQuery(true)->select('description')->from('#__foobar_example')->where('example_id = 12');
        $actual = $db->setQuery($query)->loadResult();
        $this->assertNotEquals('Example 12', $actual, 'Test operator MAYBE negative');
    }

    /**
     * @covers  Awf\Database\Installer::__construct
     * @covers  Awf\Database\Installer::updateSchema
     *
     * @throws \Exception
     */
    public function testModifyColumnType()
    {
        $db = static::$container->db;

        $installer = new Installer(static::$container);
        $installer->setForcedFile(static::$container->basePath.'/sql/test_type.xml');
        $installer->updateSchema();

        $coltypes = $db->getTableColumns('#__foobar_example', true);
        $this->assertArrayHasKey('text', $coltypes);
        $this->assertEquals('MEDIUMTEXT', strtoupper($coltypes['text']));
    }

    /**
     * @covers  Awf\Database\Installer::__construct
     * @covers  Awf\Database\Installer::updateSchema
     *
     * @throws \Exception
     */
    public function testFailingSql()
    {
        $this->setExpectedException('RuntimeException');
        $installer = new Installer(static::$container);
        $installer->setForcedFile(static::$container->basePath.'/sql/test_fail.xml');
        $installer->updateSchema();
    }

    /**
     * @covers  Awf\Database\Installer::__construct
     * @covers  Awf\Database\Installer::updateSchema
     *
     * @throws \Exception
     */
    public function testCanFail()
    {
        $installer = new Installer(static::$container);
        $installer->setForcedFile(static::$container->basePath.'/sql/test_canfail.xml');
        $installer->updateSchema();

        // Required for PHPUnit to mark the test as complete
        $this->assertTrue(true);
    }

    /**
     * @covers  FOF30\Database\Installer::removeSchema
     */
    public function testRemoveSchema()
    {
        $db = static::$container->db;
        $tables = $db->setQuery('SHOW TABLES')->loadColumn();
        $prefix = $db->getPrefix();
        $this->assertTrue(in_array($prefix . 'foobar_example', $tables), 'The table must exist before testing starts');

        $installer = new Installer(static::$container);
        $installer->setForcedFile(static::$container->basePath.'/sql/test_canfail.xml');
        $installer->removeSchema();

        $tables = $db->setQuery('SHOW TABLES')->loadColumn();
        $prefix = $db->getPrefix();
        $this->assertFalse(in_array($prefix . 'foobar_example', $tables), 'The table must not exist after running removeSchema');
    }
}