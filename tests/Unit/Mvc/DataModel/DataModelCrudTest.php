<?php

declare(strict_types=1);

/**
 * Concrete DataModel subclass that must live under a "DataModel\" sub-namespace
 * so that DataModel::getName() can correctly derive the model name.
 */

namespace Awf\Tests\Unit\Mvc\DataModel\Model;

use Awf\Mvc\DataModel;

/**
 * Minimal DataModel fixture backed by the "items" table:
 *   item_id  INTEGER PRIMARY KEY AUTOINCREMENT
 *   title    TEXT NOT NULL
 *   enabled  INTEGER NOT NULL DEFAULT 1
 *   ordering INTEGER DEFAULT 0
 */
class Item extends DataModel
{
    /** Reset the protected static caches so each test starts clean. */
    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

// ============================================================
// Test class
// ============================================================

namespace Awf\Tests\Unit\Mvc\DataModel;

use Awf\Container\Container;
use Awf\Database\Driver\Sqlite as SqliteDriver;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Mvc\DataModel\Exception\RecordNotLoaded;
use Awf\Session\Segment;
use Awf\Text\Language;
use Awf\User\ManagerInterface as UserManagerInterface;
use Awf\User\UserInterface;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for DataModel CRUD operations against in-memory SQLite.
 *
 * Covers: insert, update, find/findOrFail, forceDelete, trash, reset,
 * table/key auto-detection, bind, and related edge/error cases.
 */
class DataModelCrudTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private SqliteDriver $db;
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        if (!SqliteDriver::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        // Flush the static field/table caches so each test starts clean.
        \Awf\Tests\Unit\Mvc\DataModel\Model\Item::flushCaches();

        // ---- Create a fresh in-memory SQLite driver ----
        $this->db = new SqliteDriver([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $this->db->connect();

        // Create the backing table (no prefix — prefix is empty).
        $this->db->setQuery(
            'CREATE TABLE items (
                item_id  INTEGER PRIMARY KEY AUTOINCREMENT,
                title    TEXT    NOT NULL,
                enabled  INTEGER NOT NULL DEFAULT 1,
                ordering INTEGER NOT NULL DEFAULT 0
            )'
        )->execute();

        // ---- Build a minimal Container ----
        $tmpDir = sys_get_temp_dir();

        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturn([]);

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);

        $input = new Input([]);

        $segment = $this->createMock(Segment::class);
        $segment->method('get')->willReturn(0);
        $segment->method('__get')->willReturn(null);

        $application = $this->createMock(\Awf\Application\Application::class);
        $application->method('getName')->willReturn('Testapp');

        // Stub user returns ID 0 (guest).
        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn(0);

        $userManager = $this->createMock(UserManagerInterface::class);
        $userManager->method('getUser')->willReturn($user);

        $db = $this->db; // capture for closure

        $this->container = new Container([
            'application_name'     => 'Testapp',
            'applicationNamespace' => '\\Testapp',
            'session_segment_name' => 'testapp_seg',
            'basePath'             => $tmpDir,
            'languagePath'         => $tmpDir,
            'temporaryPath'        => $tmpDir,
            'templatePath'         => $tmpDir,
            'sqlPath'              => $tmpDir,
            'filesystemBase'       => $tmpDir,
            'eventDispatcher'      => $ed,
            'language'             => $language,
            'input'                => $input,
            'application'          => $application,
            'segment'              => $segment,
            'userManager'          => $userManager,
            'db'                   => $db,
        ]);
    }

    /** Return a freshly instantiated Item model wired to the test container. */
    private function makeModel(array $mvcConfig = []): \Awf\Tests\Unit\Mvc\DataModel\Model\Item
    {
        $config = array_merge([
            'tableName'   => 'items',
            'idFieldName' => 'item_id',
            'autoChecks'  => false,
        ], $mvcConfig);

        $this->container['mvc_config'] = $config;

        return new \Awf\Tests\Unit\Mvc\DataModel\Model\Item($this->container);
    }

    /** Insert a raw row and return its rowid. */
    private function insertRaw(string $title, int $enabled = 1, int $ordering = 0): int
    {
        $this->db->setQuery(
            "INSERT INTO items (title, enabled, ordering) VALUES ('{$title}', {$enabled}, {$ordering})"
        )->execute();

        return (int) $this->db->insertid();
    }

    // -------------------------------------------------------------------------
    // Table / key auto-detection
    // -------------------------------------------------------------------------

    public function testGetTableNameReturnsConfiguredTable(): void
    {
        $model = $this->makeModel();
        self::assertSame('items', $model->getTableName());
    }

    public function testGetIdFieldNameReturnsConfiguredKey(): void
    {
        $model = $this->makeModel();
        self::assertSame('item_id', $model->getIdFieldName());
    }

    public function testKnownFieldsIncludeAllTableColumns(): void
    {
        $model  = $this->makeModel();
        $fields = array_keys($model->getData());

        self::assertContains('item_id',  $fields);
        self::assertContains('title',    $fields);
        self::assertContains('enabled',  $fields);
        self::assertContains('ordering', $fields);
    }

    // -------------------------------------------------------------------------
    // insert (save on new record)
    // -------------------------------------------------------------------------

    public function testSaveInsertsNewRecord(): void
    {
        $model = $this->makeModel();
        $model->title = 'Hello World';
        $model->save();

        $id = $model->getId();
        self::assertNotEmpty($id, 'save() must assign a non-empty primary key after insert.');

        // Verify the row exists in the database.
        $this->db->setQuery("SELECT title FROM items WHERE item_id = {$id}");
        $title = $this->db->loadResult();
        self::assertSame('Hello World', $title);
    }

    public function testSaveReturnsSelfForChaining(): void
    {
        $model  = $this->makeModel();
        $result = $model->save(['title' => 'Chain']);
        self::assertSame($model, $result);
    }

    public function testSaveWithDataArrayBindsBeforeInsert(): void
    {
        $model = $this->makeModel();
        $model->save(['title' => 'Via array', 'enabled' => 0]);

        $id = $model->getId();
        $this->db->setQuery("SELECT title, enabled FROM items WHERE item_id = {$id}");
        $row = $this->db->loadAssoc();

        self::assertSame('Via array', $row['title']);
        self::assertSame('0', (string) $row['enabled']);
    }

    public function testSaveAssignsAutoIncrementId(): void
    {
        $model1 = $this->makeModel();
        $model1->title = 'First';
        $model1->save();

        $model2 = $this->makeModel();
        $model2->title = 'Second';
        $model2->save();

        self::assertGreaterThan((int) $model1->getId(), (int) $model2->getId());
    }

    // -------------------------------------------------------------------------
    // update (save on existing record)
    // -------------------------------------------------------------------------

    public function testSaveUpdatesExistingRecord(): void
    {
        $id = $this->insertRaw('Original');

        $model = $this->makeModel();
        $model->find($id);
        $model->title = 'Updated';
        $model->save();

        $this->db->setQuery("SELECT title FROM items WHERE item_id = {$id}");
        self::assertSame('Updated', $this->db->loadResult());
    }

    public function testSaveDoesNotCreateNewRowOnUpdate(): void
    {
        $id = $this->insertRaw('Row');

        $model = $this->makeModel();
        $model->find($id);
        $model->title = 'Modified';
        $model->save();

        $this->db->setQuery('SELECT COUNT(*) FROM items');
        self::assertSame('1', (string) $this->db->loadResult());
    }

    // -------------------------------------------------------------------------
    // find() / findOrFail()
    // -------------------------------------------------------------------------

    public function testFindLoadsByPrimaryKey(): void
    {
        $id = $this->insertRaw('FindMe');

        $model = $this->makeModel();
        $model->find($id);

        self::assertSame((string) $id, (string) $model->getId());
        self::assertSame('FindMe', $model->title);
    }

    public function testFindByArrayOfKeys(): void
    {
        $id = $this->insertRaw('ArrayFind');

        $model = $this->makeModel();
        $model->find(['item_id' => $id]);

        self::assertSame((string) $id, (string) $model->getId());
    }

    public function testFindReturnsModelWhenNotFound(): void
    {
        $model = $this->makeModel();
        $model->find(99999); // Non-existent ID

        // When not found, the model is reset and getId() is empty.
        self::assertEmpty($model->getId());
    }

    public function testFindWithNullKeyDoesNotLoadRecord(): void
    {
        $this->insertRaw('Existing');

        $model = $this->makeModel();
        $model->find(null);

        self::assertEmpty($model->getId());
    }

    public function testFindOrFailLoadsExistingRecord(): void
    {
        $id    = $this->insertRaw('MustFind');
        $model = $this->makeModel();
        $result = $model->findOrFail($id);

        self::assertSame($model, $result);
        self::assertSame((string) $id, (string) $model->getId());
    }

    public function testFindOrFailThrowsOnMissingRecord(): void
    {
        $this->expectException(\RuntimeException::class);

        $model = $this->makeModel();
        $model->findOrFail(99999);
    }

    // -------------------------------------------------------------------------
    // reset()
    // -------------------------------------------------------------------------

    public function testResetClearsPrimaryKey(): void
    {
        $id = $this->insertRaw('ResetMe');

        $model = $this->makeModel();
        $model->find($id);
        self::assertNotEmpty($model->getId());

        $model->reset();
        self::assertEmpty($model->getId());
    }

    public function testResetClearsAllFields(): void
    {
        $id = $this->insertRaw('ClearAll');

        $model = $this->makeModel();
        $model->find($id);
        $model->reset();

        $data = $model->getData();
        // After reset with defaults the title should be null (no DB default for TEXT).
        self::assertNull($data['title']);
    }

    public function testResetReturnsSelf(): void
    {
        $model  = $this->makeModel();
        $result = $model->reset();
        self::assertSame($model, $result);
    }

    public function testResetWithoutDefaultsNullsAllFields(): void
    {
        $id = $this->insertRaw('NoDefaults', 1, 5);

        $model = $this->makeModel();
        $model->find($id);
        $model->reset(false);

        $data = $model->getData();
        self::assertNull($data['ordering']);
    }

    // -------------------------------------------------------------------------
    // forceDelete()
    // -------------------------------------------------------------------------

    public function testForceDeleteRemovesRow(): void
    {
        $id = $this->insertRaw('DeleteMe');

        $model = $this->makeModel();
        $model->find($id);
        $model->forceDelete();

        $this->db->setQuery("SELECT COUNT(*) FROM items WHERE item_id = {$id}");
        self::assertSame('0', (string) $this->db->loadResult());
    }

    public function testForceDeleteResetsModelAfterDeletion(): void
    {
        $id = $this->insertRaw('DeleteAndReset');

        $model = $this->makeModel();
        $model->find($id);
        $model->forceDelete();

        self::assertEmpty($model->getId());
    }

    public function testForceDeleteByIdArgument(): void
    {
        $id = $this->insertRaw('DeleteById');

        $model = $this->makeModel();
        $model->forceDelete($id);

        $this->db->setQuery("SELECT COUNT(*) FROM items WHERE item_id = {$id}");
        self::assertSame('0', (string) $this->db->loadResult());
    }

    public function testForceDeleteThrowsWhenNoRecordLoaded(): void
    {
        $this->expectException(RecordNotLoaded::class);

        $model = $this->makeModel();
        $model->forceDelete(); // No record loaded, no id argument.
    }

    // -------------------------------------------------------------------------
    // delete() — hard-delete mode (softDelete = false by default)
    // -------------------------------------------------------------------------

    public function testDeleteDelegatesToForceDeleteByDefault(): void
    {
        $id = $this->insertRaw('HardDelete');

        $model = $this->makeModel();
        $model->find($id);
        $model->delete();

        $this->db->setQuery("SELECT COUNT(*) FROM items WHERE item_id = {$id}");
        self::assertSame('0', (string) $this->db->loadResult());
    }

    // -------------------------------------------------------------------------
    // trash() — soft delete (sets enabled = -2)
    // -------------------------------------------------------------------------

    public function testTrashSetsEnabledToMinusTwo(): void
    {
        $id = $this->insertRaw('TrashMe');

        $model = $this->makeModel();
        $model->find($id);
        $model->trash();

        $this->db->setQuery("SELECT enabled FROM items WHERE item_id = {$id}");
        self::assertSame('-2', (string) $this->db->loadResult());
    }

    public function testTrashDoesNotRemoveRow(): void
    {
        $id = $this->insertRaw('TrashKeep');

        $model = $this->makeModel();
        $model->find($id);
        $model->trash();

        $this->db->setQuery("SELECT COUNT(*) FROM items WHERE item_id = {$id}");
        self::assertSame('1', (string) $this->db->loadResult());
    }

    public function testTrashByIdArgument(): void
    {
        $id = $this->insertRaw('TrashById');

        $model = $this->makeModel();
        $model->trash($id);

        $this->db->setQuery("SELECT enabled FROM items WHERE item_id = {$id}");
        self::assertSame('-2', (string) $this->db->loadResult());
    }

    public function testTrashThrowsWhenNoRecordLoaded(): void
    {
        $this->expectException(RecordNotLoaded::class);

        $model = $this->makeModel();
        $model->trash();
    }

    // -------------------------------------------------------------------------
    // bind()
    // -------------------------------------------------------------------------

    public function testBindFromArray(): void
    {
        $model = $this->makeModel();
        $model->bind(['title' => 'Bound', 'enabled' => 0]);

        self::assertSame('Bound', $model->title);
        self::assertSame('0', (string) $model->enabled);
    }

    public function testBindFromObject(): void
    {
        $data           = new \stdClass();
        $data->title    = 'ObjBound';
        $data->enabled  = 1;

        $model = $this->makeModel();
        $model->bind($data);

        self::assertSame('ObjBound', $model->title);
    }

    public function testBindIgnoresListedFields(): void
    {
        $model = $this->makeModel();
        $model->title = 'Original';
        $model->bind(['title' => 'New', 'enabled' => 0], ['title']);

        // title must not change because it is ignored.
        self::assertSame('Original', $model->title);
        // enabled must change.
        self::assertSame('0', (string) $model->enabled);
    }

    public function testBindThrowsOnInvalidArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $model = $this->makeModel();
        $model->bind('not-an-array-or-object');
    }

    public function testBindReturnsSelf(): void
    {
        $model  = $this->makeModel();
        $result = $model->bind(['title' => 'x']);
        self::assertSame($model, $result);
    }

    // -------------------------------------------------------------------------
    // getData() / toArray() / toJson()
    // -------------------------------------------------------------------------

    public function testGetDataReturnsAllKnownFields(): void
    {
        $id = $this->insertRaw('DataTest', 1, 3);

        $model = $this->makeModel();
        $model->find($id);
        $data = $model->getData();

        self::assertArrayHasKey('item_id',  $data);
        self::assertArrayHasKey('title',    $data);
        self::assertArrayHasKey('enabled',  $data);
        self::assertArrayHasKey('ordering', $data);
        self::assertSame('DataTest', $data['title']);
    }

    public function testToArrayEqualsRecordData(): void
    {
        $id = $this->insertRaw('ArrayTest');

        $model = $this->makeModel();
        $model->find($id);

        self::assertSame($model->getData(), $model->toArray());
    }

    public function testToJsonIsValidJson(): void
    {
        $this->insertRaw('JsonTest');

        $model = $this->makeModel();
        $json  = $model->toJson();

        self::assertIsString($json);
        $decoded = json_decode($json, true);
        self::assertIsArray($decoded);
    }

    // -------------------------------------------------------------------------
    // getFieldValue() / setFieldValue() / hasField()
    // -------------------------------------------------------------------------

    public function testSetAndGetFieldValue(): void
    {
        $model = $this->makeModel();
        $model->setFieldValue('title', 'Direct');

        self::assertSame('Direct', $model->getFieldValue('title'));
    }

    public function testGetFieldValueReturnsDefaultForMissingField(): void
    {
        $model = $this->makeModel();
        self::assertSame('fallback', $model->getFieldValue('nonexistent', 'fallback'));
    }

    public function testHasFieldReturnsTrueForKnownField(): void
    {
        $model = $this->makeModel();
        self::assertTrue($model->hasField('title'));
    }

    public function testHasFieldReturnsFalseForUnknownField(): void
    {
        $model = $this->makeModel();
        self::assertFalse($model->hasField('no_such_field'));
    }

    // -------------------------------------------------------------------------
    // Magic property access
    // -------------------------------------------------------------------------

    public function testMagicGetAndSetField(): void
    {
        $model = $this->makeModel();
        $model->title = 'MagicSet';

        self::assertSame('MagicSet', $model->title);
    }

    public function testMagicIsset(): void
    {
        $model = $this->makeModel();
        $model->title = 'Present';

        self::assertTrue(isset($model->title));
    }

    // -------------------------------------------------------------------------
    // getId() / getIdFieldName() / getTableName()
    // -------------------------------------------------------------------------

    public function testGetIdReturnsNullBeforeLoad(): void
    {
        $model = $this->makeModel();
        self::assertNull($model->getId());
    }

    public function testGetIdReturnsCorrectIdAfterLoad(): void
    {
        $id = $this->insertRaw('IdTest');

        $model = $this->makeModel();
        $model->find($id);

        self::assertSame((string) $id, (string) $model->getId());
    }

    // -------------------------------------------------------------------------
    // create() convenience method
    // -------------------------------------------------------------------------

    public function testCreateInsertsAndReturnsModel(): void
    {
        $model = $this->makeModel();
        $result = $model->create(['title' => 'Created', 'enabled' => 1]);

        self::assertSame($model, $result);
        self::assertNotEmpty($model->getId());

        $this->db->setQuery("SELECT title FROM items WHERE item_id = {$model->getId()}");
        self::assertSame('Created', $this->db->loadResult());
    }

    // -------------------------------------------------------------------------
    // copy() — duplicates with a new ID
    // -------------------------------------------------------------------------

    public function testCopyInsertsNewRow(): void
    {
        $id = $this->insertRaw('Original');

        $model = $this->makeModel();
        $model->find($id);
        $model->copy();

        $this->db->setQuery('SELECT COUNT(*) FROM items');
        self::assertSame('2', (string) $this->db->loadResult());
    }

    public function testCopyProducesNewId(): void
    {
        $id = $this->insertRaw('ToCopy');

        $model = $this->makeModel();
        $model->find($id);
        $originalId = (int) $model->getId();
        $model->copy();

        self::assertNotSame($originalId, (int) $model->getId());
    }

    // -------------------------------------------------------------------------
    // Multiple-record round-trip
    // -------------------------------------------------------------------------

    public function testFindAfterMultipleInserts(): void
    {
        $id1 = $this->insertRaw('Alpha');
        $id2 = $this->insertRaw('Beta');
        $id3 = $this->insertRaw('Gamma');

        $model = $this->makeModel();
        $model->find($id2);

        self::assertSame('Beta', $model->title);
        self::assertSame((string) $id2, (string) $model->getId());
    }

    public function testSaveAndReloadPreservesData(): void
    {
        $model = $this->makeModel();
        $model->title   = 'Persist';
        $model->enabled = 0;
        $model->save();

        $savedId = (int) $model->getId();

        $model2 = $this->makeModel();
        $model2->find($savedId);

        self::assertSame('Persist', $model2->title);
        self::assertSame('0', (string) $model2->enabled);
    }

    // -------------------------------------------------------------------------
    // addKnownField()
    // -------------------------------------------------------------------------

    public function testAddKnownFieldRegistersField(): void
    {
        $model = $this->makeModel();
        $model->addKnownField('virtual_field', 'default_val', 'text');

        self::assertTrue($model->hasField('virtual_field'));
    }

    public function testAddKnownFieldDoesNotReplaceExistingByDefault(): void
    {
        $model = $this->makeModel();
        $model->addKnownField('title', 'should-not-replace', 'text', false);

        // The real field still exists — the replace=false flag kept the original.
        self::assertTrue($model->hasField('title'));
    }

    // -------------------------------------------------------------------------
    // NoTableColumns exception
    // -------------------------------------------------------------------------

    public function testConstructorThrowsWhenTableDoesNotExist(): void
    {
        $this->expectException(\Awf\Mvc\DataModel\Exception\NoTableColumns::class);

        // Force a non-existent table name.
        $this->container['mvc_config'] = [
            'tableName'   => 'nonexistent_table_xyz',
            'idFieldName' => 'id',
        ];

        new \Awf\Tests\Unit\Mvc\DataModel\Model\Item($this->container);
    }
}
