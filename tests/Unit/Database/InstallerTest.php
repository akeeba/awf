<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Database;

use Awf\Container\Container;
use Awf\Database\Driver\Sqlite;
use Awf\Database\Installer;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Awf\Database\Installer — schema XML parsing, condition evaluation,
 * and CREATE/ALTER application against an in-memory SQLite database.
 *
 * All tests skip gracefully when the pdo_sqlite extension is unavailable.
 */
class InstallerTest extends TestCase
{
    private const DATA_DIR = __DIR__ . '/_data/installer';

    private ?Sqlite     $db        = null;
    private ?Container  $container = null;
    private string      $tmpDir    = '';

    // -------------------------------------------------------------------------
    // Infrastructure
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        if (!Sqlite::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        // Create a temp dir that will serve as basePath so the Installer default
        // xmlDirectory resolves to something that exists.
        $this->tmpDir = sys_get_temp_dir() . '/awf_installer_test_' . uniqid('', true);
        mkdir($this->tmpDir, 0755, true);

        // Build an in-memory SQLite driver.
        $this->db = new Sqlite([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Build a minimal container and inject the driver directly so we don't
        // need a real appConfig / full container boot.
        $this->container = new Container([
            'application_name'     => 'TestApp',
            'applicationNamespace' => '\\TestApp',
            'session_segment_name' => 'testapp_seg',
            'basePath'             => $this->tmpDir,
            'languagePath'         => $this->tmpDir,
            'temporaryPath'        => $this->tmpDir,
            'templatePath'         => $this->tmpDir,
            'sqlPath'              => $this->tmpDir,
            'filesystemBase'       => $this->tmpDir,
        ]);

        // Override the lazy `db` service with our pre-built in-memory driver.
        $db = $this->db;
        $this->container['db'] = $db;

        // Clear the static table cache between tests so conditions are re-evaluated.
        (new Installer($this->container))->nukeCache();
    }

    protected function tearDown(): void
    {
        $this->db?->disconnect();
        $this->db        = null;
        $this->container = null;
        $this->rmdirRecursive($this->tmpDir);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function rmdirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \FilesystemIterator($dir, \FilesystemIterator::SKIP_DOTS);
        foreach ($items as $item) {
            if ($item->isDir()) {
                $this->rmdirRecursive($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }

    private function makeInstaller(): Installer
    {
        $installer = new Installer($this->container);
        $installer->nukeCache();

        return $installer;
    }

    private function tableExists(string $tableName): bool
    {
        return in_array($tableName, $this->db->getTableList(), true);
    }

    // -------------------------------------------------------------------------
    // Constructor / accessors
    // -------------------------------------------------------------------------

    public function testConstructorSetsXmlDirectoryFromBasePath(): void
    {
        $installer = new Installer($this->container);

        self::assertSame(
            $this->tmpDir . '/assets/sql/xml',
            $installer->getXmlDirectory()
        );
    }

    public function testSetXmlDirectoryChangesDirectory(): void
    {
        $installer = $this->makeInstaller();
        $installer->setXmlDirectory('/some/other/path');

        self::assertSame('/some/other/path', $installer->getXmlDirectory());
    }

    public function testSetForcedFileChangesFile(): void
    {
        $installer = $this->makeInstaller();
        $installer->setForcedFile('/path/to/schema.xml');

        self::assertSame('/path/to/schema.xml', $installer->getForcedFile());
    }

    public function testSetForcedFileCanBeCleared(): void
    {
        $installer = $this->makeInstaller();
        $installer->setForcedFile('/path/to/schema.xml');
        $installer->setForcedFile('');

        self::assertSame('', $installer->getForcedFile());
    }

    // -------------------------------------------------------------------------
    // nukeCache
    // -------------------------------------------------------------------------

    public function testNukeCacheResetsStaticTableList(): void
    {
        // Create a table, run a schema once so the static cache is populated,
        // drop the table, nuke cache — the next updateSchema must re-query.
        $installer = $this->makeInstaller();
        $installer->setForcedFile(self::DATA_DIR . '/sqlite.xml');
        $installer->updateSchema();

        self::assertTrue($this->tableExists('test_items'), 'Table must exist after first install.');

        // Drop the table externally.
        $this->db->setQuery('DROP TABLE IF EXISTS test_items')->execute();
        self::assertFalse($this->tableExists('test_items'), 'Table must be gone after manual drop.');

        // Nuke the cache; the condition "missing" must now re-evaluate to true
        // and recreate the table.
        $installer->nukeCache();
        $installer->updateSchema();

        self::assertTrue($this->tableExists('test_items'), 'Table must be recreated after nukeCache + updateSchema.');
    }

    // -------------------------------------------------------------------------
    // findSchemaXml / openAndVerify — file selection
    // -------------------------------------------------------------------------

    public function testUpdateSchemaDoesNothingWhenXmlDirectoryDoesNotExist(): void
    {
        $installer = $this->makeInstaller();
        // Default xmlDirectory points to $tmpDir/assets/sql/xml which does not exist
        // → no XML found → updateSchema is a no-op (must not throw).
        $installer->updateSchema();

        // No exception thrown and no tables created.
        self::assertEmpty($this->db->getTableList());
    }

    public function testUpdateSchemaDoesNothingWhenXmlDirectoryIsEmpty(): void
    {
        $emptyDir = $this->tmpDir . '/empty_xml_dir';
        mkdir($emptyDir, 0755, true);

        $installer = $this->makeInstaller();
        $installer->setXmlDirectory($emptyDir);
        $installer->updateSchema();

        self::assertEmpty($this->db->getTableList());
    }

    public function testUpdateSchemaDoesNothingWhenForcedFileDoesNotExist(): void
    {
        $installer = $this->makeInstaller();
        $installer->setForcedFile('/nonexistent/path/to/schema.xml');
        // Also point xmlDirectory at an empty dir so no fallback occurs.
        $emptyDir = $this->tmpDir . '/empty_xml_dir2';
        mkdir($emptyDir, 0755, true);
        $installer->setXmlDirectory($emptyDir);
        $installer->updateSchema();

        self::assertEmpty($this->db->getTableList());
    }

    public function testUpdateSchemaSkipsFileWithWrongRootTag(): void
    {
        $installer = $this->makeInstaller();
        $installer->setForcedFile(self::DATA_DIR . '/not_schema.xml');
        // Point xmlDirectory at an empty dir to prevent fallback.
        $emptyDir = $this->tmpDir . '/empty_xml_dir3';
        mkdir($emptyDir, 0755, true);
        $installer->setXmlDirectory($emptyDir);
        $installer->updateSchema();

        self::assertEmpty($this->db->getTableList());
    }

    public function testUpdateSchemaSkipsFileForWrongDriver(): void
    {
        $installer = $this->makeInstaller();
        $installer->setForcedFile(self::DATA_DIR . '/wrong_driver.xml');
        // Point xmlDirectory at an empty dir to prevent fallback.
        $emptyDir = $this->tmpDir . '/empty_xml_dir4';
        mkdir($emptyDir, 0755, true);
        $installer->setXmlDirectory($emptyDir);
        $installer->updateSchema();

        self::assertEmpty($this->db->getTableList());
    }

    public function testUpdateSchemaSkipsFileWithNoSqlSection(): void
    {
        $installer = $this->makeInstaller();
        $installer->setForcedFile(self::DATA_DIR . '/no_sql_section.xml');
        // Point xmlDirectory at an empty dir to prevent fallback.
        $emptyDir = $this->tmpDir . '/empty_xml_dir5';
        mkdir($emptyDir, 0755, true);
        $installer->setXmlDirectory($emptyDir);
        $installer->updateSchema();

        self::assertEmpty($this->db->getTableList());
    }

    // -------------------------------------------------------------------------
    // updateSchema — happy path (CREATE TABLE via "missing" condition)
    // -------------------------------------------------------------------------

    public function testUpdateSchemaCreatesTableWhenMissing(): void
    {
        $installer = $this->makeInstaller();
        $installer->setForcedFile(self::DATA_DIR . '/sqlite.xml');
        $installer->updateSchema();

        self::assertTrue($this->tableExists('test_items'), 'test_items must be created.');
    }

    public function testUpdateSchemaIsIdempotentWhenTableAlreadyExists(): void
    {
        $installer = $this->makeInstaller();
        $installer->setForcedFile(self::DATA_DIR . '/sqlite.xml');

        // First run creates the table.
        $installer->updateSchema();
        self::assertTrue($this->tableExists('test_items'));

        // Second run should be a no-op (condition "missing" is now false).
        $installer->nukeCache();
        $installer->updateSchema();

        self::assertTrue($this->tableExists('test_items'), 'Table must still exist after second run.');
    }

    // -------------------------------------------------------------------------
    // updateSchema — condition: "true"
    // -------------------------------------------------------------------------

    public function testUpdateSchemaRunsQueryWhenConditionIsTrue(): void
    {
        $installer = $this->makeInstaller();
        $installer->setForcedFile(self::DATA_DIR . '/condition_equals.xml');
        $installer->updateSchema();

        self::assertTrue($this->tableExists('equals_test'), 'equals_test must be created when condition is true.');
    }

    // -------------------------------------------------------------------------
    // updateSchema — condition: "equals"
    // -------------------------------------------------------------------------

    public function testUpdateSchemaSkipsQueryWhenEqualsConditionIsFalse(): void
    {
        $installer = $this->makeInstaller();
        $installer->setForcedFile(self::DATA_DIR . '/condition_equals.xml');
        $installer->updateSchema();

        self::assertFalse($this->tableExists('equals_test_no'), 'equals_test_no must NOT be created when equals condition is false.');
    }

    // -------------------------------------------------------------------------
    // updateSchema — canfail queries
    // -------------------------------------------------------------------------

    public function testUpdateSchemaDoesNotThrowWhenQueryFailsWithCanfail(): void
    {
        $installer = $this->makeInstaller();
        $installer->setForcedFile(self::DATA_DIR . '/with_canfail.xml');

        // Must not throw even though the SQL is invalid.
        $installer->updateSchema();
        self::assertTrue(true, 'No exception thrown for canfail query.');
    }

    public function testUpdateSchemaThrowsWhenQueryFailsWithoutCanfail(): void
    {
        $installer = $this->makeInstaller();
        $installer->setForcedFile(self::DATA_DIR . '/without_canfail.xml');

        $this->expectException(Exception::class);
        $installer->updateSchema();
    }

    // -------------------------------------------------------------------------
    // updateSchema — auto-detect from directory
    // -------------------------------------------------------------------------

    public function testUpdateSchemaAutoDetectsSchemaFileFromDirectory(): void
    {
        // Point at a subdirectory containing ONLY the sqlite.xml fixture so the
        // auto-detection can find exactly one matching file.
        $installer = $this->makeInstaller();
        $installer->setXmlDirectory(self::DATA_DIR . '/autodetect');
        $installer->updateSchema();

        // sqlite.xml should be discovered and applied.
        self::assertTrue($this->tableExists('test_items'), 'test_items must be created when auto-detecting sqlite.xml.');
    }

    // -------------------------------------------------------------------------
    // removeSchema
    // -------------------------------------------------------------------------

    public function testRemoveSchemaDropsTablesListedInXml(): void
    {
        // First, create the tables that removeSchema will try to drop.
        $this->db->setQuery('CREATE TABLE IF NOT EXISTS remove_items (id INTEGER PRIMARY KEY)')->execute();
        $this->db->setQuery('CREATE TABLE IF NOT EXISTS remove_other (id INTEGER PRIMARY KEY)')->execute();

        self::assertTrue($this->tableExists('remove_items'));
        self::assertTrue($this->tableExists('remove_other'));

        $installer = $this->makeInstaller();
        $installer->setForcedFile(self::DATA_DIR . '/remove_schema.xml');
        $installer->removeSchema();

        self::assertFalse($this->tableExists('remove_items'), 'remove_items must be dropped.');
        self::assertFalse($this->tableExists('remove_other'), 'remove_other must be dropped.');
    }

    public function testRemoveSchemaDoesNothingWhenNoSchemaXmlFound(): void
    {
        // Create a table manually; removeSchema with no XML should not touch it.
        $this->db->setQuery('CREATE TABLE keeper (id INTEGER PRIMARY KEY)')->execute();

        $installer = $this->makeInstaller();
        // xmlDirectory is non-existent → no XML found → removeSchema is a no-op.
        $installer->removeSchema();

        self::assertTrue($this->tableExists('keeper'), 'Manually created table must not be touched.');
    }

    public function testRemoveSchemaSilentlySkipsMissingTables(): void
    {
        // The XML references tables that do not exist — must not throw.
        $installer = $this->makeInstaller();
        $installer->setForcedFile(self::DATA_DIR . '/remove_schema.xml');
        $installer->removeSchema();

        // If we reach here without an exception, the test passes.
        self::assertTrue(true, 'removeSchema must not throw when listed tables are already absent.');
    }

    // -------------------------------------------------------------------------
    // condition: "missing" — column check
    // -------------------------------------------------------------------------

    public function testMissingConditionFalseWhenColumnExists(): void
    {
        // Create the table with a "created_at" column already present.
        // The second action in sqlite.xml checks: if "created_at" column is missing → add it.
        // Since it already exists, the ALTER should be skipped (no-op).
        $this->db->setQuery(
            'CREATE TABLE test_items (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                title      TEXT NOT NULL,
                body       TEXT,
                created_at TEXT DEFAULT NULL
            )'
        )->execute();

        $installer = $this->makeInstaller();
        $installer->setForcedFile(self::DATA_DIR . '/sqlite.xml');
        $installer->updateSchema();

        // Table still exists and still has created_at — no duplicate column error.
        self::assertTrue($this->tableExists('test_items'));
        $cols = $this->db->getTableColumns('test_items');
        self::assertArrayHasKey('created_at', $cols);
    }
}
