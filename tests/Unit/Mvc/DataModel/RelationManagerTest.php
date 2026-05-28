<?php

declare(strict_types=1);

/**
 * Fixture model classes for RelationManager tests.
 *
 * They live under a 3-part namespace so DataModel internals and the MVC factory
 * can resolve them correctly:
 *   $foreignParts[0] = 'RelMgr'  (= application_name in the container)
 *   $foreignParts[1] = 'Model'
 *   $foreignParts[2] = '<ClassName>'
 *
 * The container's application_name / applicationNamespace are 'RelMgr' / '\RelMgr'.
 */

namespace RelMgr\Model;

use Awf\Mvc\DataModel;

if (!class_exists(\RelMgr\Model\User::class, false)) {
    class User extends DataModel
    {
        protected $tableName   = 'rm_users';
        protected $idFieldName = 'user_id';

        public static function flushCaches(): void
        {
            static::$tableCache      = [];
            static::$tableFieldCache = [];
        }
    }
}

if (!class_exists(\RelMgr\Model\Article::class, false)) {
    class Article extends DataModel
    {
        protected $tableName   = 'rm_articles';
        protected $idFieldName = 'article_id';

        public static function flushCaches(): void
        {
            static::$tableCache      = [];
            static::$tableFieldCache = [];
        }
    }
}

if (!class_exists(\RelMgr\Model\Tag::class, false)) {
    class Tag extends DataModel
    {
        protected $tableName   = 'rm_tags';
        protected $idFieldName = 'tag_id';

        public static function flushCaches(): void
        {
            static::$tableCache      = [];
            static::$tableFieldCache = [];
        }
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
use Awf\Mvc\DataModel;
use Awf\Mvc\DataModel\Collection;
use Awf\Mvc\DataModel\Relation\Exception\ForeignModelNotFound;
use Awf\Mvc\DataModel\Relation\Exception\RelationNotFound;
use Awf\Mvc\DataModel\Relation\Exception\RelationTypeNotFound;
use Awf\Mvc\DataModel\RelationManager;
use Awf\Session\Segment;
use Awf\Text\Language;
use Awf\User\ManagerInterface as UserManagerInterface;
use Awf\User\UserInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Awf\Mvc\DataModel\RelationManager.
 *
 * Covers:
 * - getRelationTypes() populates the static type map
 * - addRelation() happy path with explicit foreignModelClass
 * - addRelation() throws RelationTypeNotFound for unknown type
 * - addRelation() throws ForeignModelNotFound when class cannot be resolved
 * - addRelation() auto-resolves foreignModelClass from parent class namespace
 * - removeRelation() removes a known relation
 * - resetRelations() clears all relations
 * - getRelationNames() returns registered names
 * - getRelation() returns the Relation object; throws RelationNotFound when missing
 * - getData() proxies to the relation; throws RelationNotFound when missing
 * - getForeignKeyMap() proxies and throws RelationNotFound when missing
 * - getCountSubquery() proxies and throws RelationNotFound when missing
 * - getNew() proxies and throws RelationNotFound when missing
 * - save() all relations; save single relation; throws RelationNotFound when missing
 * - setDataFromCollection() pushes data into relation; throws RelationNotFound when missing
 * - isMagicMethod() / isMagicProperty()
 * - __call() shorthand for addRelation (hasMany, hasOne, etc.)
 * - __call() shorthand getData via getXxx() prefix
 * - __call() throws InvalidArgumentException when creating an unnamed relation
 * - __call() throws InvalidArgumentException when too many get-arguments
 * - __call() throws RelationTypeNotFound for unknown magic method
 * - __get() returns relation data
 * - rebase() updates the parent model on manager and all relations
 * - __clone() clones and resets all child relations
 */
class RelationManagerTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Infrastructure
    // -------------------------------------------------------------------------

    private SqliteDriver $db;
    private Container    $container;

    protected function setUp(): void
    {
        parent::setUp();

        if (!SqliteDriver::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        \RelMgr\Model\User::flushCaches();
        \RelMgr\Model\Article::flushCaches();
        \RelMgr\Model\Tag::flushCaches();

        // Reset the static relation type cache so each test starts fresh
        $prop = new \ReflectionProperty(RelationManager::class, 'relationTypes');
        $prop->setValue(null, []);

        // ---- In-memory SQLite ----
        $this->db = new SqliteDriver([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $this->db->connect();

        $this->db->setQuery(
            'CREATE TABLE rm_users (
                user_id INTEGER PRIMARY KEY AUTOINCREMENT,
                name    TEXT NOT NULL
            )'
        )->execute();

        $this->db->setQuery(
            'CREATE TABLE rm_articles (
                article_id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id    INTEGER,
                title      TEXT NOT NULL
            )'
        )->execute();

        $this->db->setQuery(
            'CREATE TABLE rm_tags (
                tag_id INTEGER PRIMARY KEY AUTOINCREMENT,
                label  TEXT NOT NULL
            )'
        )->execute();

        $this->db->setQuery(
            'CREATE TABLE relMgr_article_tag (
                article_id INTEGER NOT NULL,
                tag_id     INTEGER NOT NULL,
                PRIMARY KEY (article_id, tag_id)
            )'
        )->execute();

        // ---- Container ----
        $tmpDir = sys_get_temp_dir();

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);

        $input   = new Input([]);
        $segment = $this->createMock(Segment::class);
        $segment->method('get')->willReturn(0);
        $segment->method('__get')->willReturn(null);

        $application = $this->createMock(\Awf\Application\Application::class);
        $application->method('getName')->willReturn('RelMgr');

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn(0);

        $userManager = $this->createMock(UserManagerInterface::class);
        $userManager->method('getUser')->willReturn($user);

        $realEd = new EventDispatcher($this->createStub(Container::class));
        $db     = $this->db;

        $this->container = new Container([
            'application_name'     => 'RelMgr',
            'applicationNamespace' => '\\RelMgr',
            'session_segment_name' => 'relMgr_seg',
            'basePath'             => $tmpDir,
            'languagePath'         => $tmpDir,
            'temporaryPath'        => $tmpDir,
            'templatePath'         => $tmpDir,
            'sqlPath'              => $tmpDir,
            'filesystemBase'       => $tmpDir,
            'eventDispatcher'      => $realEd,
            'language'             => $language,
            'input'                => $input,
            'application'          => $application,
            'segment'              => $segment,
            'userManager'          => $userManager,
            'db'                   => $db,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Build a User model (parent) and optionally save it. */
    private function makeUser(string $name = 'Alice', bool $save = false): \RelMgr\Model\User
    {
        $this->container['mvc_config'] = [
            'tableName'      => 'rm_users',
            'idFieldName'    => 'user_id',
            'autoChecks'     => false,
            'ignore_request' => true,
        ];

        $user = new \RelMgr\Model\User($this->container);
        unset($this->container['mvc_config']);

        $user->name = $name;

        if ($save) {
            $user->save();
        }

        return $user;
    }

    /** Build an Article model and optionally save it. */
    private function makeArticle(string $title = 'Test', ?int $userId = null, bool $save = false): \RelMgr\Model\Article
    {
        $this->container['mvc_config'] = [
            'tableName'      => 'rm_articles',
            'idFieldName'    => 'article_id',
            'autoChecks'     => false,
            'ignore_request' => true,
        ];

        $article = new \RelMgr\Model\Article($this->container);
        unset($this->container['mvc_config']);

        $article->title = $title;

        if ($userId !== null) {
            $article->user_id = $userId;
        }

        if ($save) {
            $article->save();
        }

        return $article;
    }

    /** Build a fresh RelationManager attached to a User parent. */
    private function makeManager(bool $saveParent = false): RelationManager
    {
        $user = $this->makeUser('Alice', $saveParent);
        return new RelationManager($user);
    }

    // -------------------------------------------------------------------------
    // getRelationTypes
    // -------------------------------------------------------------------------

    public function testGetRelationTypesReturnsMappedTypes(): void
    {
        $types = RelationManager::getRelationTypes();

        self::assertIsArray($types);
        self::assertNotEmpty($types);
        // Standard relation types that ship with AWF
        self::assertArrayHasKey('hasMany', $types);
        self::assertArrayHasKey('hasOne', $types);
        self::assertArrayHasKey('belongsTo', $types);
        self::assertArrayHasKey('belongsToMany', $types);
    }

    public function testGetRelationTypesIsCached(): void
    {
        $first  = RelationManager::getRelationTypes();
        $second = RelationManager::getRelationTypes();

        self::assertSame($first, $second);
    }

    // -------------------------------------------------------------------------
    // addRelation / getRelationNames / getRelation / removeRelation / resetRelations
    // -------------------------------------------------------------------------

    public function testAddRelationWithExplicitClassRegistersRelation(): void
    {
        $manager = $this->makeManager(true);
        $result  = $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        // addRelation returns the parent model
        self::assertInstanceOf(DataModel::class, $result);
        self::assertContains('articles', $manager->getRelationNames());
    }

    public function testAddRelationThrowsForUnknownType(): void
    {
        $this->expectException(RelationTypeNotFound::class);

        $manager = $this->makeManager(true);
        $manager->addRelation('articles', 'doesNotExist', '\\RelMgr\\Model\\Article');
    }

    public function testAddRelationThrowsForeignModelNotFoundWhenClassMissing(): void
    {
        $this->expectException(ForeignModelNotFound::class);

        // Use a parent model whose namespace has no matching sub-class
        $manager = $this->makeManager(true);
        // Pass a non-existent class and a name that also can't be auto-resolved
        $manager->addRelation('nonexistent', 'hasMany', '\\NoSuchNamespace\\NoSuchClass');
    }

    public function testRemoveRelationDropsIt(): void
    {
        $manager = $this->makeManager(true);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        self::assertContains('articles', $manager->getRelationNames());

        $result = $manager->removeRelation('articles');

        self::assertInstanceOf(DataModel::class, $result);
        self::assertNotContains('articles', $manager->getRelationNames());
    }

    public function testRemoveRelationOnMissingNameIsNoop(): void
    {
        $manager = $this->makeManager(true);
        // Should not throw
        $result = $manager->removeRelation('ghost');

        self::assertInstanceOf(DataModel::class, $result);
    }

    public function testResetRelationsClearsAll(): void
    {
        $manager = $this->makeManager(true);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        self::assertNotEmpty($manager->getRelationNames());

        $manager->resetRelations();

        self::assertEmpty($manager->getRelationNames());
    }

    public function testGetRelationReturnsRelationObject(): void
    {
        $manager = $this->makeManager(true);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        $relation = $manager->getRelation('articles');

        self::assertInstanceOf(\Awf\Mvc\DataModel\Relation::class, $relation);
    }

    public function testGetRelationThrowsWhenNotFound(): void
    {
        $this->expectException(RelationNotFound::class);

        $manager = $this->makeManager(true);
        $manager->getRelation('ghost');
    }

    // -------------------------------------------------------------------------
    // getData
    // -------------------------------------------------------------------------

    public function testGetDataReturnsCollectionForHasMany(): void
    {
        $user = $this->makeUser('Alice', true);
        $uid  = (int) $user->user_id;

        // Insert two articles for this user
        $this->db->setQuery("INSERT INTO rm_articles (user_id, title) VALUES ({$uid}, 'A1')")->execute();
        $this->db->setQuery("INSERT INTO rm_articles (user_id, title) VALUES ({$uid}, 'A2')")->execute();

        $manager = new RelationManager($user);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        $data = $manager->getData('articles');

        self::assertInstanceOf(Collection::class, $data);
        self::assertCount(2, $data);
    }

    public function testGetDataThrowsWhenRelationNotFound(): void
    {
        $this->expectException(RelationNotFound::class);

        $manager = $this->makeManager(true);
        $manager->getData('ghost');
    }

    public function testGetDataWithCallbackFiltersResults(): void
    {
        $user = $this->makeUser('Bob', true);
        $uid  = (int) $user->user_id;

        $this->db->setQuery("INSERT INTO rm_articles (user_id, title) VALUES ({$uid}, 'Keep')")->execute();
        $this->db->setQuery("INSERT INTO rm_articles (user_id, title) VALUES ({$uid}, 'Drop')")->execute();

        $manager = new RelationManager($user);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        $data = $manager->getData('articles', static function (DataModel $m) {
            $m->setState('title', 'Keep');
        });

        self::assertInstanceOf(Collection::class, $data);
        self::assertCount(1, $data);
        self::assertSame('Keep', $data->first()->title);
    }

    // -------------------------------------------------------------------------
    // getForeignKeyMap
    // -------------------------------------------------------------------------

    public function testGetForeignKeyMapReturnsArray(): void
    {
        $manager = $this->makeManager(true);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        $map = $manager->getForeignKeyMap('articles');

        self::assertIsArray($map);
    }

    public function testGetForeignKeyMapThrowsWhenRelationNotFound(): void
    {
        $this->expectException(RelationNotFound::class);

        $manager = $this->makeManager(true);
        $manager->getForeignKeyMap('ghost');
    }

    // -------------------------------------------------------------------------
    // getCountSubquery
    // -------------------------------------------------------------------------

    public function testGetCountSubqueryReturnsQueryObject(): void
    {
        $manager = $this->makeManager(true);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        $subquery = $manager->getCountSubquery('articles');

        self::assertNotNull($subquery);
    }

    public function testGetCountSubqueryThrowsWhenRelationNotFound(): void
    {
        $this->expectException(RelationNotFound::class);

        $manager = $this->makeManager(true);
        $manager->getCountSubquery('ghost');
    }

    // -------------------------------------------------------------------------
    // getNew
    // -------------------------------------------------------------------------

    public function testGetNewHasManyReturnsDataModel(): void
    {
        $manager = $this->makeManager(true);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        $newItem = $manager->getNew('articles');

        self::assertInstanceOf(DataModel::class, $newItem);
    }

    public function testGetNewThrowsWhenRelationNotFound(): void
    {
        $this->expectException(RelationNotFound::class);

        $manager = $this->makeManager(true);
        $manager->getNew('ghost');
    }

    // -------------------------------------------------------------------------
    // save
    // -------------------------------------------------------------------------

    public function testSaveAllRelationsDoesNotThrow(): void
    {
        $user = $this->makeUser('Saver', true);
        $uid  = (int) $user->user_id;

        $manager = new RelationManager($user);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        // Load relation data
        $manager->getData('articles');

        // save() with no argument should not throw
        $result = $manager->save();

        self::assertInstanceOf(DataModel::class, $result);
    }

    public function testSaveSingleRelationDoesNotThrow(): void
    {
        $user = $this->makeUser('Saver2', true);
        $manager = new RelationManager($user);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        $result = $manager->save('articles');

        self::assertInstanceOf(DataModel::class, $result);
    }

    public function testSaveSingleRelationThrowsWhenNotFound(): void
    {
        $this->expectException(RelationNotFound::class);

        $manager = $this->makeManager(true);
        $manager->save('ghost');
    }

    // -------------------------------------------------------------------------
    // setDataFromCollection
    // -------------------------------------------------------------------------

    public function testSetDataFromCollectionPopulatesRelation(): void
    {
        $user = $this->makeUser('Cecil', true);
        $uid  = (int) $user->user_id;

        $manager = new RelationManager($user);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        // Build a small collection with one matching article
        $article = $this->makeArticle('Eager', $uid);

        $col = new Collection([$article]);

        $manager->setDataFromCollection('articles', $col);

        $fetched = $manager->getData('articles');

        self::assertInstanceOf(Collection::class, $fetched);
        self::assertCount(1, $fetched);
    }

    public function testSetDataFromCollectionThrowsWhenRelationNotFound(): void
    {
        $this->expectException(RelationNotFound::class);

        $manager = $this->makeManager(true);
        $col     = new Collection([]);
        $manager->setDataFromCollection('ghost', $col);
    }

    // -------------------------------------------------------------------------
    // isMagicMethod / isMagicProperty
    // -------------------------------------------------------------------------

    public function testIsMagicMethodReturnsTrueForRelationType(): void
    {
        $manager = $this->makeManager(true);

        // 'hasMany' is a known relation type
        self::assertTrue($manager->isMagicMethod('hasMany'));
        self::assertTrue($manager->isMagicMethod('hasOne'));
        self::assertTrue($manager->isMagicMethod('belongsTo'));
    }

    public function testIsMagicMethodReturnsTrueForRegisteredGetAccessor(): void
    {
        $manager = $this->makeManager(true);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        // getArticles maps to the 'articles' relation
        self::assertTrue($manager->isMagicMethod('getArticles'));
    }

    public function testIsMagicMethodReturnsFalseForUnknown(): void
    {
        $manager = $this->makeManager(true);

        self::assertFalse($manager->isMagicMethod('unknownMethod'));
        self::assertFalse($manager->isMagicMethod('getGhost'));
    }

    public function testIsMagicPropertyReturnsTrueForRegisteredRelation(): void
    {
        $manager = $this->makeManager(true);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        self::assertTrue($manager->isMagicProperty('articles'));
    }

    public function testIsMagicPropertyReturnsFalseForUnknown(): void
    {
        $manager = $this->makeManager(true);

        self::assertFalse($manager->isMagicProperty('ghost'));
    }

    // -------------------------------------------------------------------------
    // __call magic
    // -------------------------------------------------------------------------

    public function testMagicCallAddRelationViaHasMany(): void
    {
        $manager = $this->makeManager(true);

        // __call('hasMany', ['articles', '\\RelMgr\\Model\\Article', 'user_id', 'user_id'])
        $result = $manager->hasMany('articles', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        self::assertInstanceOf(DataModel::class, $result);
        self::assertContains('articles', $manager->getRelationNames());
    }

    public function testMagicCallAddRelationThrowsWithNoRelationName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $manager = $this->makeManager(true);
        // hasMany() with no arguments = unnamed relation
        $manager->hasMany();
    }

    public function testMagicCallGetDataViaGetPrefix(): void
    {
        $user = $this->makeUser('Dana', true);
        $uid  = (int) $user->user_id;

        $this->db->setQuery("INSERT INTO rm_articles (user_id, title) VALUES ({$uid}, 'Magic')")->execute();

        $manager = new RelationManager($user);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        // __call('getArticles', []) → getData('articles')
        $data = $manager->getArticles();

        self::assertInstanceOf(Collection::class, $data);
        self::assertCount(1, $data);
    }

    public function testMagicCallGetDataWithCallbackViaGetPrefix(): void
    {
        $user = $this->makeUser('Evan', true);
        $uid  = (int) $user->user_id;

        $this->db->setQuery("INSERT INTO rm_articles (user_id, title) VALUES ({$uid}, 'Yes')")->execute();
        $this->db->setQuery("INSERT INTO rm_articles (user_id, title) VALUES ({$uid}, 'No')")->execute();

        $manager = new RelationManager($user);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        $data = $manager->getArticles(static function (DataModel $m) {
            $m->setState('title', 'Yes');
        });

        self::assertInstanceOf(Collection::class, $data);
        self::assertCount(1, $data);
    }

    public function testMagicCallGetDataTooManyArgumentsThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $manager = $this->makeManager(true);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        // More than 2 arguments to getXxx is invalid
        $manager->getArticles(null, null, null);
    }

    public function testMagicCallUnknownMethodThrows(): void
    {
        $this->expectException(RelationTypeNotFound::class);

        $manager = $this->makeManager(true);
        $manager->totallyUnknownMethod('foo');
    }

    // -------------------------------------------------------------------------
    // __get magic
    // -------------------------------------------------------------------------

    public function testMagicGetReturnsRelationData(): void
    {
        $user = $this->makeUser('Frank', true);
        $uid  = (int) $user->user_id;

        $this->db->setQuery("INSERT INTO rm_articles (user_id, title) VALUES ({$uid}, 'PropArticle')")->execute();

        $manager = new RelationManager($user);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        // __get('articles')
        $data = $manager->articles;

        self::assertInstanceOf(Collection::class, $data);
        self::assertCount(1, $data);
    }

    // -------------------------------------------------------------------------
    // rebase
    // -------------------------------------------------------------------------

    public function testRebaseUpdatesParentModel(): void
    {
        $user1 = $this->makeUser('George', true);
        $user2 = $this->makeUser('Helen', true);

        $manager = new RelationManager($user1);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        $manager->rebase($user2);

        // After rebase the manager's parent is user2; verify through getData (no exception)
        $data = $manager->getData('articles');
        self::assertInstanceOf(Collection::class, $data);
    }

    // -------------------------------------------------------------------------
    // __clone
    // -------------------------------------------------------------------------

    public function testCloneDeepCopiesRelations(): void
    {
        $user = $this->makeUser('Ivan', true);

        $manager = new RelationManager($user);
        $manager->addRelation('articles', 'hasMany', '\\RelMgr\\Model\\Article', 'user_id', 'user_id');

        $cloned = clone $manager;

        // Both managers know the same relation name
        self::assertContains('articles', $cloned->getRelationNames());

        // But the Relation objects are distinct instances
        $orig   = $manager->getRelation('articles');
        $copy   = $cloned->getRelation('articles');
        self::assertNotSame($orig, $copy);
    }

    // -------------------------------------------------------------------------
    // Auto-resolution of foreignModelClass
    // -------------------------------------------------------------------------

    public function testAddRelationAutoResolvesClassFromParentNamespace(): void
    {
        // Parent is RelMgr\Model\User → sibling Article class should be resolved
        $user    = $this->makeUser('Jane', true);
        $manager = new RelationManager($user);

        // Pass null/empty foreignModelClass; the name 'article' should resolve to RelMgr\Model\Article
        $result = $manager->addRelation('article', 'hasMany', null, 'user_id', 'user_id');

        self::assertInstanceOf(DataModel::class, $result);
        self::assertContains('article', $manager->getRelationNames());
    }
}
