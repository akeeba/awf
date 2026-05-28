<?php

declare(strict_types=1);

/**
 * Fixture model classes.
 *
 * They live under a simple 3-part namespace so that the Relation base class can
 * extract the model name correctly:
 *   $foreignParts[0] = 'RelHasMany'   (= application name in the container)
 *   $foreignParts[1] = 'Model'
 *   $foreignParts[2] = '<ClassName>'  (= model name passed to makeTempModel)
 *
 * The application_name / applicationNamespace of the test container are set to
 * 'RelHasMany' / '\RelHasMany' so the MVC factory resolves the model.
 *
 * Both models hard-code their tableName / idFieldName so that they work without
 * needing a mvc_config entry in the container at construction time.
 */

namespace RelHasMany\Model;

use Awf\Mvc\DataModel;

/**
 * Parent model: one User has many Articles.
 * Backed by SQLite table: rel_users (user_id, name)
 */
class User extends DataModel
{
    protected $tableName   = 'rel_users';
    protected $idFieldName = 'user_id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

/**
 * Child model for hasMany: each Article belongs to one User via user_id FK.
 * Backed by SQLite table: rel_articles (article_id, user_id, title)
 */
class Article extends DataModel
{
    protected $tableName   = 'rel_articles';
    protected $idFieldName = 'article_id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

/**
 * Child model for hasOne: each User has at most one Profile via user_id FK.
 * Backed by SQLite table: rel_profiles (profile_id, user_id, bio)
 */
class Profile extends DataModel
{
    protected $tableName   = 'rel_profiles';
    protected $idFieldName = 'profile_id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

// ============================================================
// Test class
// ============================================================

namespace Awf\Tests\Unit\Mvc\DataModel\Relation;

use Awf\Container\Container;
use Awf\Database\Driver\Sqlite as SqliteDriver;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Mvc\DataModel;
use Awf\Mvc\DataModel\Collection;
use Awf\Mvc\DataModel\Relation\HasMany;
use Awf\Mvc\DataModel\Relation\HasOne;
use Awf\Session\Segment;
use Awf\Text\Language;
use Awf\User\ManagerInterface as UserManagerInterface;
use Awf\User\UserInterface;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for HasMany and HasOne relations backed by in-memory SQLite.
 *
 * Covers:
 * - Construction and key defaulting
 * - Lazy loading (getData without dataCollection)
 * - Eager loading (getData with dataCollection)
 * - HasOne returns a single model (or null via first())
 * - Callback filtering
 * - filterForeignModel returning false for null/missing local key
 * - setDataFromCollection
 * - getCountSubquery SQL shape
 * - getNew() creates and caches a new related item
 * - reset() and rebase()
 * - saveAll() persists all related items
 */
class HasManyTest extends TestCase
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

        \RelHasMany\Model\User::flushCaches();
        \RelHasMany\Model\Article::flushCaches();
        \RelHasMany\Model\Profile::flushCaches();

        // ---- In-memory SQLite driver ----
        $this->db = new SqliteDriver([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $this->db->connect();

        // Parent table
        $this->db->setQuery(
            'CREATE TABLE rel_users (
                user_id INTEGER PRIMARY KEY AUTOINCREMENT,
                name    TEXT NOT NULL
            )'
        )->execute();

        // Child table for hasMany
        $this->db->setQuery(
            'CREATE TABLE rel_articles (
                article_id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id    INTEGER NOT NULL,
                title      TEXT    NOT NULL
            )'
        )->execute();

        // Child table for hasOne
        $this->db->setQuery(
            'CREATE TABLE rel_profiles (
                profile_id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id    INTEGER NOT NULL,
                bio        TEXT    NOT NULL DEFAULT ""
            )'
        )->execute();

        // ---- Minimal Container ----
        $tmpDir = sys_get_temp_dir();

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);

        $input   = new Input([]);
        $segment = $this->createMock(Segment::class);
        $segment->method('get')->willReturn(0);
        $segment->method('__get')->willReturn(null);

        $application = $this->createMock(\Awf\Application\Application::class);
        $application->method('getName')->willReturn('RelHasMany');

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn(0);

        $userManager = $this->createMock(UserManagerInterface::class);
        $userManager->method('getUser')->willReturn($user);

        // Real EventDispatcher so behaviours can fire
        $realEd = new EventDispatcher($this->createStub(Container::class));

        $db = $this->db;

        $this->container = new Container([
            'application_name'     => 'RelHasMany',
            'applicationNamespace' => '\\RelHasMany',
            'session_segment_name' => 'relmany_seg',
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

    /** Build a User model with a given name and optionally save it to the DB. */
    private function makeUser(string $name = 'Alice', bool $save = false): \RelHasMany\Model\User
    {
        $this->container['mvc_config'] = [
            'tableName'      => 'rel_users',
            'idFieldName'    => 'user_id',
            'autoChecks'     => false,
            'ignore_request' => true,
        ];

        $user = new \RelHasMany\Model\User($this->container);

        // Clear mvc_config so subsequent makeTempModel calls for foreign models
        // do not pick up the parent model's table/key configuration.
        unset($this->container['mvc_config']);

        $user->name = $name;

        if ($save) {
            $user->save();
        }

        return $user;
    }

    /** Insert a raw user row and return its id. */
    private function insertUser(string $name): int
    {
        $safe = $this->db->quote($name);
        $this->db->setQuery("INSERT INTO rel_users (name) VALUES ({$safe})")->execute();

        return (int) $this->db->insertid();
    }

    /** Insert a raw article row and return its id. */
    private function insertArticle(int $userId, string $title): int
    {
        $safeTitle = $this->db->quote($title);
        $this->db->setQuery(
            "INSERT INTO rel_articles (user_id, title) VALUES ({$userId}, {$safeTitle})"
        )->execute();

        return (int) $this->db->insertid();
    }

    /** Insert a raw profile row and return its id. */
    private function insertProfile(int $userId, string $bio): int
    {
        $safeBio = $this->db->quote($bio);
        $this->db->setQuery(
            "INSERT INTO rel_profiles (user_id, bio) VALUES ({$userId}, {$safeBio})"
        )->execute();

        return (int) $this->db->insertid();
    }

    /**
     * Build a HasMany relation for a given (already-saved) User, targeting Article.
     * localKey defaults to user_id, foreignKey defaults to user_id.
     */
    private function makeHasMany(
        \RelHasMany\Model\User $user,
        ?string $localKey = null,
        ?string $foreignKey = null
    ): HasMany {
        return new HasMany($user, '\\RelHasMany\\Model\\Article', $localKey, $foreignKey);
    }

    /**
     * Build a HasOne relation for a given (already-saved) User, targeting Profile.
     */
    private function makeHasOne(
        \RelHasMany\Model\User $user,
        ?string $localKey = null,
        ?string $foreignKey = null
    ): HasOne {
        return new HasOne($user, '\\RelHasMany\\Model\\Profile', $localKey, $foreignKey);
    }

    // =========================================================================
    // HasMany — construction and key defaulting
    // =========================================================================

    public function testHasManyDefaultsLocalKeyToParentIdField(): void
    {
        $user     = $this->makeUser();
        $relation = $this->makeHasMany($user);

        // getCountSubquery is a convenient way to confirm the key names without
        // triggering a real DB lookup (the parent model has no id yet).
        // We inspect the SQL string instead.
        $user->name = 'Bob';
        $user->save();

        $sql = (string) $relation->getCountSubquery();

        // The local key defaults to the parent model's PK: user_id
        self::assertStringContainsString('`user_id`', $sql);
    }

    public function testHasManyDefaultsForeignKeyToLocalKey(): void
    {
        $user     = $this->makeUser();
        $user->save();
        $relation = $this->makeHasMany($user);

        $sql = (string) $relation->getCountSubquery();

        // Both sides of the join reference user_id
        self::assertSame(2, substr_count($sql, '`user_id`'));
    }

    public function testHasManyAcceptsExplicitKeys(): void
    {
        $user     = $this->makeUser();
        $user->save();
        $relation = $this->makeHasMany($user, 'user_id', 'user_id');

        $sql = (string) $relation->getCountSubquery();
        self::assertStringContainsString('`user_id`', $sql);
    }

    // =========================================================================
    // HasMany — lazy loading (getData without dataCollection)
    // =========================================================================

    public function testHasManyLazyLoadReturnsEmptyCollectionWhenNoRelatedRows(): void
    {
        $userId = $this->insertUser('Alice');
        $user   = $this->makeUser();
        $user->find($userId);

        $relation = $this->makeHasMany($user);
        $result   = $relation->getData();

        self::assertInstanceOf(Collection::class, $result);
        self::assertCount(0, $result);
    }

    public function testHasManyLazyLoadReturnsRelatedRows(): void
    {
        $userId = $this->insertUser('Bob');
        $this->insertArticle($userId, 'First');
        $this->insertArticle($userId, 'Second');

        $user = $this->makeUser();
        $user->find($userId);

        $relation = $this->makeHasMany($user);
        $result   = $relation->getData();

        self::assertInstanceOf(Collection::class, $result);
        self::assertCount(2, $result);
    }

    public function testHasManyLazyLoadDoesNotReturnRowsFromOtherParents(): void
    {
        $userId1 = $this->insertUser('Alice');
        $userId2 = $this->insertUser('Bob');
        $this->insertArticle($userId1, 'AliceArticle');
        $this->insertArticle($userId2, 'BobArticle');

        $user = $this->makeUser();
        $user->find($userId1);

        $relation = $this->makeHasMany($user);
        $result   = $relation->getData();

        self::assertCount(1, $result);
        self::assertSame('AliceArticle', $result->first()->title);
    }

    public function testHasManyLazyLoadReturnsFalseWhenLocalKeyIsNull(): void
    {
        // User has not been saved/loaded — PK is null
        $user     = $this->makeUser('Ghost', false);
        $relation = $this->makeHasMany($user);

        $result = $relation->getData();

        // filterForeignModel returns false → empty collection
        self::assertInstanceOf(Collection::class, $result);
        self::assertCount(0, $result);
    }

    // =========================================================================
    // HasMany — caching (getData called twice)
    // =========================================================================

    public function testHasManyGetDataCachesResult(): void
    {
        $userId = $this->insertUser('Cache');
        $this->insertArticle($userId, 'Cached');

        $user = $this->makeUser();
        $user->find($userId);

        $relation = $this->makeHasMany($user);

        $first  = $relation->getData();
        $second = $relation->getData();

        self::assertSame($first, $second, 'getData() must return the cached instance on the second call');
    }

    // =========================================================================
    // HasMany — callback filtering
    // =========================================================================

    public function testHasManyCallbackIsApplied(): void
    {
        $userId = $this->insertUser('Filter');
        $this->insertArticle($userId, 'Keep');
        $this->insertArticle($userId, 'Drop');

        $user = $this->makeUser();
        $user->find($userId);

        $relation = $this->makeHasMany($user);

        $result = $relation->getData(static function (DataModel $foreignModel) {
            $foreignModel->where('title', '=', 'Keep');
        });

        self::assertCount(1, $result);
        self::assertSame('Keep', $result->first()->title);
    }

    // =========================================================================
    // HasMany — eager loading (getData with dataCollection)
    // =========================================================================

    public function testHasManyEagerLoadReturnsAllMatchingRows(): void
    {
        $userId1 = $this->insertUser('EagerA');
        $userId2 = $this->insertUser('EagerB');
        $this->insertArticle($userId1, 'A1');
        $this->insertArticle($userId1, 'A2');
        $this->insertArticle($userId2, 'B1');

        // Build a Collection of parent models to simulate eager loading
        $userA = $this->makeUser();
        $userA->find($userId1);

        $userB = $this->makeUser();
        $userB->find($userId2);

        $dataCollection = new Collection([$userA, $userB]);

        $relation = $this->makeHasMany($userA);
        $result   = $relation->getData(null, $dataCollection);

        // Eager load fetches ALL articles belonging to ANY user in the collection
        self::assertCount(3, $result);
    }

    public function testHasManyEagerLoadWithEmptyCollectionReturnsEmpty(): void
    {
        $userId = $this->insertUser('Empty');
        $this->insertArticle($userId, 'A');

        $user = $this->makeUser();
        $user->find($userId);

        $relation       = $this->makeHasMany($user);
        $emptyCol       = new Collection();
        $result         = $relation->getData(null, $emptyCol);

        self::assertInstanceOf(Collection::class, $result);
        self::assertCount(0, $result);
    }

    // =========================================================================
    // HasMany — setDataFromCollection
    // =========================================================================

    public function testSetDataFromCollectionFiltersByForeignKey(): void
    {
        $userId1 = $this->insertUser('SDC_A');
        $userId2 = $this->insertUser('SDC_B');
        $art1Id  = $this->insertArticle($userId1, 'ForA');
        $art2Id  = $this->insertArticle($userId2, 'ForB');

        // Build Article models for the collection (no mvc_config needed because
        // the Article class hard-codes tableName / idFieldName as properties)
        $artA = new \RelHasMany\Model\Article($this->container);
        $artA->find($art1Id);

        $artB = new \RelHasMany\Model\Article($this->container);
        $artB->find($art2Id);

        $allArticles = new Collection([$artA, $artB]);

        // Set up the relation for User 1
        $user = $this->makeUser();
        $user->find($userId1);
        $relation = $this->makeHasMany($user);

        $relation->setDataFromCollection($allArticles);
        $data = $relation->getData();

        // Only the article belonging to user 1 must be included
        self::assertCount(1, $data);
        self::assertSame('ForA', $data->first()->title);
    }

    // =========================================================================
    // HasMany — reset() and rebase()
    // =========================================================================

    public function testResetClearsData(): void
    {
        $userId = $this->insertUser('Reset');
        $this->insertArticle($userId, 'R1');

        $user = $this->makeUser();
        $user->find($userId);

        $relation = $this->makeHasMany($user);
        $relation->getData(); // populate cache

        $relation->reset();

        // After reset, data is null; getData will re-fetch
        $userId2 = $this->insertUser('Reset2');
        $this->insertArticle($userId2, 'R2');

        // Re-fetching after reset must give the cached data for this parent again
        $result = $relation->getData();
        self::assertCount(1, $result);
        self::assertSame('R1', $result->first()->title);
    }

    public function testRebaseChangesParentModel(): void
    {
        $userId1 = $this->insertUser('Rebase1');
        $userId2 = $this->insertUser('Rebase2');
        $this->insertArticle($userId1, 'Article1');
        $this->insertArticle($userId2, 'Article2');

        $user1 = $this->makeUser();
        $user1->find($userId1);

        $user2 = $this->makeUser();
        $user2->find($userId2);

        $relation = $this->makeHasMany($user1);
        $relation->getData(); // cache for user1

        $relation->rebase($user2);
        $result = $relation->getData(); // must re-fetch for user2

        self::assertCount(1, $result);
        self::assertSame('Article2', $result->first()->title);
    }

    // =========================================================================
    // HasMany — getCountSubquery SQL structure
    // =========================================================================

    public function testGetCountSubqueryContainsCountStar(): void
    {
        $userId = $this->insertUser('CountSQ');
        $user   = $this->makeUser();
        $user->find($userId);

        $relation = $this->makeHasMany($user);
        $sql      = (string) $relation->getCountSubquery();

        self::assertStringContainsString('COUNT(*)', $sql);
    }

    public function testGetCountSubqueryReferencesCorrectTables(): void
    {
        $userId = $this->insertUser('CountTbl');
        $user   = $this->makeUser();
        $user->find($userId);

        $relation = $this->makeHasMany($user);
        $sql      = (string) $relation->getCountSubquery();

        self::assertStringContainsString('`rel_articles`', $sql);
        self::assertStringContainsString('`rel_users`', $sql);
    }

    public function testGetCountSubqueryJoinsOnForeignKey(): void
    {
        $userId = $this->insertUser('CountFK');
        $user   = $this->makeUser();
        $user->find($userId);

        $relation = $this->makeHasMany($user);
        $sql      = (string) $relation->getCountSubquery();

        // Both sides of the correlated subquery should reference user_id
        self::assertGreaterThanOrEqual(2, substr_count($sql, 'user_id'));
    }

    // =========================================================================
    // HasMany — getNew()
    // =========================================================================

    public function testGetNewReturnsArticleWithForeignKeyPreSet(): void
    {
        $userId = $this->insertUser('NewFK');
        $user   = $this->makeUser();
        $user->find($userId);

        $relation   = $this->makeHasMany($user);
        $newArticle = $relation->getNew();

        self::assertInstanceOf(DataModel::class, $newArticle);
        self::assertSame((string) $userId, (string) $newArticle->getFieldValue('user_id'));
    }

    public function testGetNewAddsItemToDataCollection(): void
    {
        $userId = $this->insertUser('NewAdd');
        $user   = $this->makeUser();
        $user->find($userId);

        $relation = $this->makeHasMany($user);
        $relation->getNew();
        $relation->getNew();

        $data = $relation->getData();
        self::assertCount(2, $data);
    }

    // =========================================================================
    // HasMany — saveAll()
    // =========================================================================

    public function testSaveAllPersistsAllRelatedItems(): void
    {
        $userId = $this->insertUser('SaveAll');
        $user   = $this->makeUser();
        $user->find($userId);

        $relation   = $this->makeHasMany($user);
        $newArticle = $relation->getNew();
        $newArticle->title = 'Saved via relation';

        $relation->saveAll();

        $this->db->setQuery("SELECT COUNT(*) FROM rel_articles WHERE user_id = {$userId}");
        $count = (int) $this->db->loadResult();

        self::assertSame(1, $count);
    }

    public function testSaveAllDoesNothingWhenNoData(): void
    {
        $userId = $this->insertUser('SaveAllEmpty');
        $user   = $this->makeUser();
        $user->find($userId);

        $relation = $this->makeHasMany($user);
        $relation->saveAll(); // must not throw

        $this->db->setQuery("SELECT COUNT(*) FROM rel_articles WHERE user_id = {$userId}");
        self::assertSame(0, (int) $this->db->loadResult());
    }

    // =========================================================================
    // HasOne — construction and key defaulting
    // =========================================================================

    public function testHasOneDefaultsLocalKeyToParentIdField(): void
    {
        $userId   = $this->insertUser('HasOneKey');
        $user     = $this->makeUser();
        $user->find($userId);

        $relation = $this->makeHasOne($user);
        $sql      = (string) $relation->getCountSubquery();

        self::assertStringContainsString('`user_id`', $sql);
    }

    // =========================================================================
    // HasOne — lazy loading returns first item or null
    // =========================================================================

    public function testHasOneReturnsNullWhenNoRelatedRow(): void
    {
        $userId = $this->insertUser('HasOneNull');
        $user   = $this->makeUser();
        $user->find($userId);

        $relation = $this->makeHasOne($user);
        $result   = $relation->getData();

        // HasOne.getData() calls Collection::first() on empty → null
        self::assertNull($result);
    }

    public function testHasOneReturnsSingleModel(): void
    {
        $userId = $this->insertUser('HasOneModel');
        $this->insertProfile($userId, 'My bio');

        $user = $this->makeUser();
        $user->find($userId);

        $relation = $this->makeHasOne($user);
        $result   = $relation->getData();

        self::assertInstanceOf(DataModel::class, $result);
        self::assertSame('My bio', $result->getFieldValue('bio'));
    }

    public function testHasOneReturnsFirstWhenMultipleRowsExist(): void
    {
        // Edge case: more than one matching row (data integrity violation but the
        // system should still return the first record without crashing).
        $userId = $this->insertUser('HasOneMulti');
        $this->insertProfile($userId, 'Bio 1');
        $this->insertProfile($userId, 'Bio 2');

        $user = $this->makeUser();
        $user->find($userId);

        $relation = $this->makeHasOne($user);
        $result   = $relation->getData();

        // Returns first, not null, not an exception
        self::assertInstanceOf(DataModel::class, $result);
    }

    // =========================================================================
    // HasOne — eager loading returns Collection (not a single model)
    // =========================================================================

    public function testHasOneEagerLoadReturnsCollection(): void
    {
        $userId = $this->insertUser('HasOneEager');
        $this->insertProfile($userId, 'Eager bio');

        $user = $this->makeUser();
        $user->find($userId);

        $dataCollection = new Collection([$user]);
        $relation       = $this->makeHasOne($user);
        $result         = $relation->getData(null, $dataCollection);

        // In eager-loading mode, HasOne delegates to HasMany which returns a Collection
        self::assertInstanceOf(Collection::class, $result);
        self::assertCount(1, $result);
    }

    // =========================================================================
    // HasOne — null local key produces empty result
    // =========================================================================

    public function testHasOneNullLocalKeyReturnsNull(): void
    {
        $user     = $this->makeUser('Ghost', false);
        $relation = $this->makeHasOne($user);

        $result = $relation->getData();

        self::assertNull($result);
    }

    // =========================================================================
    // HasMany — getForeignKeyMap
    // =========================================================================

    public function testGetForeignKeyMapReturnsArray(): void
    {
        $user     = $this->makeUser();
        $relation = $this->makeHasMany($user);

        $map = &$relation->getForeignKeyMap();
        self::assertIsArray($map);
    }
}
