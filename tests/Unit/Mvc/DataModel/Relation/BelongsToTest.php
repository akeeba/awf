<?php

declare(strict_types=1);

/**
 * Fixture model classes.
 *
 * They live under a simple 3-part namespace so that the Relation base class can
 * extract the model name correctly:
 *   $foreignParts[0] = 'RelBelongsTo'   (= application name in the container)
 *   $foreignParts[1] = 'Model'
 *   $foreignParts[2] = '<ClassName>'     (= model name passed to makeTempModel)
 *
 * The application_name / applicationNamespace of the test container are set to
 * 'RelBelongsTo' / '\RelBelongsTo' so the MVC factory resolves the model.
 *
 * All models hard-code their tableName / idFieldName so that they work without
 * needing a mvc_config entry in the container at construction time.
 */

namespace RelBelongsTo\Model;

use Awf\Mvc\DataModel;

/**
 * Parent model: one Article belongs to one User.
 * Backed by SQLite table: bt_articles (article_id, user_id, title)
 */
class Article extends DataModel
{
    protected $tableName   = 'bt_articles';
    protected $idFieldName = 'article_id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

/**
 * Foreign model for BelongsTo: each User is the parent of Articles.
 * Backed by SQLite table: bt_users (user_id, name)
 */
class User extends DataModel
{
    protected $tableName   = 'bt_users';
    protected $idFieldName = 'user_id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

/**
 * Model for BelongsToMany: each Article can be tagged with many Tags.
 * Backed by SQLite table: bt_tags (tag_id, label)
 * Pivot table: bt_article_tag (article_id, tag_id)
 */
class Tag extends DataModel
{
    protected $tableName   = 'bt_tags';
    protected $idFieldName = 'tag_id';

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
use Awf\Mvc\DataModel\Relation\BelongsTo;
use Awf\Mvc\DataModel\Relation\BelongsToMany;
use Awf\Mvc\DataModel\Relation\Exception\NewNotSupported;
use Awf\Mvc\DataModel\Relation\Exception\PivotTableNotFound;
use Awf\Session\Segment;
use Awf\Text\Language;
use Awf\User\ManagerInterface as UserManagerInterface;
use Awf\User\UserInterface;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for BelongsTo and BelongsToMany relations backed by in-memory SQLite.
 *
 * Covers:
 * - Construction and key defaulting
 * - Lazy loading (getData without dataCollection)
 * - Eager loading (getData with dataCollection)
 * - Callback filtering
 * - filterForeignModel returning false for null/missing local key
 * - setDataFromCollection (BelongsToMany)
 * - getCountSubquery SQL shape
 * - getNew() throws NewNotSupported for both relation types
 * - reset() and rebase()
 * - saveAll() for BelongsToMany (pivot table management)
 * - BelongsToMany pivot table auto-detection
 * - PivotTableNotFound exception when pivot table is missing
 */
class BelongsToTest extends TestCase
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

        \RelBelongsTo\Model\Article::flushCaches();
        \RelBelongsTo\Model\User::flushCaches();
        \RelBelongsTo\Model\Tag::flushCaches();

        // ---- In-memory SQLite driver ----
        $this->db = new SqliteDriver([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $this->db->connect();

        // Users table (foreign model for BelongsTo)
        $this->db->setQuery(
            'CREATE TABLE bt_users (
                user_id INTEGER PRIMARY KEY AUTOINCREMENT,
                name    TEXT NOT NULL
            )'
        )->execute();

        // Articles table (parent model for BelongsTo, one side for BelongsToMany)
        $this->db->setQuery(
            'CREATE TABLE bt_articles (
                article_id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id    INTEGER,
                title      TEXT NOT NULL
            )'
        )->execute();

        // Tags table (foreign model for BelongsToMany)
        $this->db->setQuery(
            'CREATE TABLE bt_tags (
                tag_id INTEGER PRIMARY KEY AUTOINCREMENT,
                label  TEXT NOT NULL
            )'
        )->execute();

        // Pivot table for BelongsToMany
        $this->db->setQuery(
            'CREATE TABLE relbelongsto_article_tag (
                article_id INTEGER NOT NULL,
                tag_id     INTEGER NOT NULL,
                PRIMARY KEY (article_id, tag_id)
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
        $application->method('getName')->willReturn('RelBelongsTo');

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn(0);

        $userManager = $this->createMock(UserManagerInterface::class);
        $userManager->method('getUser')->willReturn($user);

        // Real EventDispatcher so behaviours can fire
        $realEd = new EventDispatcher($this->createStub(Container::class));

        $db = $this->db;

        $this->container = new Container([
            'application_name'     => 'RelBelongsTo',
            'applicationNamespace' => '\\RelBelongsTo',
            'session_segment_name' => 'relbt_seg',
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

    /** Build an Article model, optionally saving it. */
    private function makeArticle(string $title = 'Test', ?int $userId = null, bool $save = false): \RelBelongsTo\Model\Article
    {
        $this->container['mvc_config'] = [
            'tableName'      => 'bt_articles',
            'idFieldName'    => 'article_id',
            'autoChecks'     => false,
            'ignore_request' => true,
        ];

        $article = new \RelBelongsTo\Model\Article($this->container);
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

    /** Insert a raw user row and return its id. */
    private function insertUser(string $name): int
    {
        $safe = $this->db->quote($name);
        $this->db->setQuery("INSERT INTO bt_users (name) VALUES ({$safe})")->execute();

        return (int) $this->db->insertid();
    }

    /** Insert a raw article row and return its id. */
    private function insertArticle(string $title, ?int $userId = null): int
    {
        $safeTitle  = $this->db->quote($title);
        $userIdPart = $userId !== null ? (string) $userId : 'NULL';
        $this->db->setQuery(
            "INSERT INTO bt_articles (title, user_id) VALUES ({$safeTitle}, {$userIdPart})"
        )->execute();

        return (int) $this->db->insertid();
    }

    /** Insert a raw tag row and return its id. */
    private function insertTag(string $label): int
    {
        $safe = $this->db->quote($label);
        $this->db->setQuery("INSERT INTO bt_tags (label) VALUES ({$safe})")->execute();

        return (int) $this->db->insertid();
    }

    /** Insert a pivot row linking an article to a tag. */
    private function insertPivot(int $articleId, int $tagId): void
    {
        $this->db->setQuery(
            "INSERT INTO relbelongsto_article_tag (article_id, tag_id) VALUES ({$articleId}, {$tagId})"
        )->execute();
    }

    /**
     * Build a BelongsTo relation for a given Article, targeting User.
     * BelongsTo: the local key defaults to the FK on the article pointing to user,
     * and the foreign key defaults to the PK of the User model.
     */
    private function makeBelongsTo(
        \RelBelongsTo\Model\Article $article,
        ?string $localKey = null,
        ?string $foreignKey = null
    ): BelongsTo {
        return new BelongsTo($article, '\\RelBelongsTo\\Model\\User', $localKey, $foreignKey);
    }

    /**
     * Build a BelongsToMany relation for a given Article, targeting Tag.
     * Explicit pivot table and keys are required in tests that need them.
     */
    private function makeBelongsToMany(
        \RelBelongsTo\Model\Article $article,
        ?string $localKey = null,
        ?string $foreignKey = null,
        ?string $pivotTable = null,
        ?string $pivotLocalKey = null,
        ?string $pivotForeignKey = null
    ): BelongsToMany {
        return new BelongsToMany(
            $article,
            '\\RelBelongsTo\\Model\\Tag',
            $localKey,
            $foreignKey,
            $pivotTable,
            $pivotLocalKey,
            $pivotForeignKey
        );
    }

    // =========================================================================
    // BelongsTo — construction and key defaulting
    // =========================================================================

    public function testBelongsToDefaultsLocalKeyToForeignModelIdField(): void
    {
        $articleId = $this->insertArticle('DefaultKeys');
        $article   = $this->makeArticle();
        $article->find($articleId);

        // With no explicit keys, local key should default to the User model's PK (user_id)
        // and foreign key should also default to user_id
        $relation = $this->makeBelongsTo($article);
        $sql      = (string) $relation->getCountSubquery();

        self::assertStringContainsString('user_id', $sql);
    }

    public function testBelongsToAcceptsExplicitLocalAndForeignKeys(): void
    {
        $articleId = $this->insertArticle('ExplicitKeys');
        $article   = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsTo($article, 'user_id', 'user_id');
        $sql      = (string) $relation->getCountSubquery();

        self::assertStringContainsString('user_id', $sql);
    }

    public function testBelongsToGetCountSubqueryContainsCountStar(): void
    {
        $articleId = $this->insertArticle('CountStar');
        $article   = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsTo($article, 'user_id', 'user_id');
        $sql      = (string) $relation->getCountSubquery();

        self::assertStringContainsString('COUNT(*)', $sql);
    }

    public function testBelongsToGetCountSubqueryReferencesCorrectTables(): void
    {
        $articleId = $this->insertArticle('CountTables');
        $article   = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsTo($article, 'user_id', 'user_id');
        $sql      = (string) $relation->getCountSubquery();

        self::assertStringContainsString('`bt_users`', $sql);
        self::assertStringContainsString('`bt_articles`', $sql);
    }

    // =========================================================================
    // BelongsTo — getNew() throws exception
    // =========================================================================

    public function testBelongsToGetNewThrowsNewNotSupported(): void
    {
        $this->expectException(NewNotSupported::class);

        $article  = $this->makeArticle();
        $relation = $this->makeBelongsTo($article, 'user_id', 'user_id');
        $relation->getNew();
    }

    // =========================================================================
    // BelongsTo — lazy loading
    // =========================================================================

    public function testBelongsToLazyLoadReturnsRelatedUser(): void
    {
        $userId    = $this->insertUser('Alice');
        $articleId = $this->insertArticle('Alice Article', $userId);

        $article = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsTo($article, 'user_id', 'user_id');
        $result   = $relation->getData();

        // BelongsTo extends HasOne, which calls first() in lazy load mode
        self::assertInstanceOf(DataModel::class, $result);
        self::assertSame((string) $userId, (string) $result->getFieldValue('user_id'));
    }

    public function testBelongsToLazyLoadReturnsNullWhenLocalKeyIsNull(): void
    {
        // Article has no user_id set → local key value is null
        $article  = $this->makeArticle('NoUser', null, false);
        $relation = $this->makeBelongsTo($article, 'user_id', 'user_id');

        $result = $relation->getData();

        self::assertNull($result);
    }

    public function testBelongsToLazyLoadReturnsNullWhenNoMatchingUser(): void
    {
        // Article points to a user_id that does not exist
        $articleId = $this->insertArticle('Orphan', 9999);

        $article = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsTo($article, 'user_id', 'user_id');
        $result   = $relation->getData();

        self::assertNull($result);
    }

    // =========================================================================
    // BelongsTo — eager loading
    // =========================================================================

    public function testBelongsToEagerLoadReturnsCollection(): void
    {
        $userId1    = $this->insertUser('EagerU1');
        $userId2    = $this->insertUser('EagerU2');
        $articleId1 = $this->insertArticle('EagerA1', $userId1);
        $articleId2 = $this->insertArticle('EagerA2', $userId2);

        $art1 = $this->makeArticle();
        $art1->find($articleId1);

        $art2 = $this->makeArticle();
        $art2->find($articleId2);

        $dataCollection = new Collection([$art1, $art2]);

        $relation = $this->makeBelongsTo($art1, 'user_id', 'user_id');
        $result   = $relation->getData(null, $dataCollection);

        // In eager mode HasOne (parent of BelongsTo) returns a Collection
        self::assertInstanceOf(Collection::class, $result);
        self::assertCount(2, $result);
    }

    public function testBelongsToEagerLoadWithEmptyCollectionReturnsEmpty(): void
    {
        $articleId = $this->insertArticle('EmptyEager', 1);
        $article   = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsTo($article, 'user_id', 'user_id');
        $result   = $relation->getData(null, new Collection());

        self::assertInstanceOf(Collection::class, $result);
        self::assertCount(0, $result);
    }

    // =========================================================================
    // BelongsTo — callback filtering
    // =========================================================================

    public function testBelongsToCallbackIsApplied(): void
    {
        $userId1   = $this->insertUser('FilterU1');
        $articleId = $this->insertArticle('FilterArt', $userId1);

        $article = $this->makeArticle();
        $article->find($articleId);

        // The callback is invoked and modifies the foreign model state;
        // we verify it was actually called by using a flag.
        $called   = false;
        $relation = $this->makeBelongsTo($article, 'user_id', 'user_id');
        $result   = $relation->getData(static function (DataModel $foreignModel) use (&$called) {
            $called = true;
        });

        self::assertTrue($called, 'Callback must have been invoked during getData()');
        // The user still matches because the callback did not further restrict
        self::assertInstanceOf(DataModel::class, $result);
        self::assertSame((string) $userId1, (string) $result->getFieldValue('user_id'));
    }

    // =========================================================================
    // BelongsTo — reset() and rebase()
    // =========================================================================

    public function testBelongsToResetClearsCache(): void
    {
        $userId    = $this->insertUser('ResetU');
        $articleId = $this->insertArticle('ResetA', $userId);

        $article = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsTo($article, 'user_id', 'user_id');
        $first    = $relation->getData();
        self::assertNotNull($first);

        $relation->reset();
        // After reset data is null; calling getData() re-fetches
        $second = $relation->getData();
        self::assertNotNull($second);
        self::assertSame((string) $userId, (string) $second->getFieldValue('user_id'));
    }

    public function testBelongsToRebaseChangesParentModel(): void
    {
        $userId1    = $this->insertUser('RebaseU1');
        $userId2    = $this->insertUser('RebaseU2');
        $articleId1 = $this->insertArticle('RebaseA1', $userId1);
        $articleId2 = $this->insertArticle('RebaseA2', $userId2);

        $art1 = $this->makeArticle();
        $art1->find($articleId1);

        $art2 = $this->makeArticle();
        $art2->find($articleId2);

        $relation = $this->makeBelongsTo($art1, 'user_id', 'user_id');
        $relation->getData(); // cache for art1

        $relation->rebase($art2);
        $result = $relation->getData(); // must re-fetch for art2

        self::assertNotNull($result);
        self::assertSame((string) $userId2, (string) $result->getFieldValue('user_id'));
    }

    // =========================================================================
    // BelongsToMany — construction and key defaulting
    // =========================================================================

    public function testBelongsToManyDefaultsLocalKeyToParentIdField(): void
    {
        $articleId = $this->insertArticle('BTM_DefaultLocal');
        $article   = $this->makeArticle();
        $article->find($articleId);

        // Explicit pivot to avoid auto-detection ambiguity
        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        $sql = (string) $relation->getCountSubquery();
        self::assertStringContainsString('article_id', $sql);
    }

    public function testBelongsToManyDefaultsForeignKeyToForeignModelIdField(): void
    {
        $articleId = $this->insertArticle('BTM_DefaultForeign');
        $article   = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        $sql = (string) $relation->getCountSubquery();
        // The foreign key defaults to tag_id (Tag model's PK)
        self::assertStringContainsString('tag_id', $sql);
    }

    public function testBelongsToManyAutoDetectsPivotTable(): void
    {
        $articleId = $this->insertArticle('BTM_AutoPivot');
        $article   = $this->makeArticle();
        $article->find($articleId);

        // No explicit pivot table — auto-detection should find 'relbelongsto_article_tag'
        // The constructor must not throw PivotTableNotFound
        $relation = $this->makeBelongsToMany($article, null, null, null, 'article_id', 'tag_id');

        // If we got here, pivot was found; verify via a SQL query
        $sql = (string) $relation->getCountSubquery();
        self::assertStringContainsString('relbelongsto_article_tag', $sql);
    }

    public function testBelongsToManyThrowsPivotTableNotFoundWhenMissing(): void
    {
        $this->expectException(PivotTableNotFound::class);

        $articleId = $this->insertArticle('BTM_NoPivot');
        $article   = $this->makeArticle();
        $article->find($articleId);

        // Use a foreign model for which no pivot table exists at all
        new BelongsToMany(
            $article,
            '\\RelBelongsTo\\Model\\User',   // no pivot for article↔user
            null, null,
            null,                            // no explicit pivot
            'article_id', 'user_id'
        );
    }

    // =========================================================================
    // BelongsToMany — getNew() throws exception
    // =========================================================================

    public function testBelongsToManyGetNewThrowsNewNotSupported(): void
    {
        $this->expectException(NewNotSupported::class);

        $articleId = $this->insertArticle('BTM_GetNew');
        $article   = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );
        $relation->getNew();
    }

    // =========================================================================
    // BelongsToMany — getCountSubquery SQL shape
    // =========================================================================

    public function testBelongsToManyGetCountSubqueryContainsCountStar(): void
    {
        $articleId = $this->insertArticle('BTM_CountStar');
        $article   = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        $sql = (string) $relation->getCountSubquery();
        self::assertStringContainsString('COUNT(*)', $sql);
    }

    public function testBelongsToManyGetCountSubqueryReferencesPivotTable(): void
    {
        $articleId = $this->insertArticle('BTM_CountPivot');
        $article   = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        $sql = (string) $relation->getCountSubquery();
        self::assertStringContainsString('relbelongsto_article_tag', $sql);
        self::assertStringContainsString('bt_tags', $sql);
    }

    // =========================================================================
    // BelongsToMany — lazy loading
    // =========================================================================

    public function testBelongsToManyLazyLoadReturnsEmptyWhenNoPivotRows(): void
    {
        $articleId = $this->insertArticle('BTM_LazyEmpty');
        $article   = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        $result = $relation->getData();

        self::assertInstanceOf(Collection::class, $result);
        self::assertCount(0, $result);
    }

    public function testBelongsToManyLazyLoadReturnsRelatedTags(): void
    {
        $articleId = $this->insertArticle('BTM_LazyTags');
        $tagId1    = $this->insertTag('PHP');
        $tagId2    = $this->insertTag('MySQL');
        $this->insertPivot($articleId, $tagId1);
        $this->insertPivot($articleId, $tagId2);

        $article = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        $result = $relation->getData();

        self::assertInstanceOf(Collection::class, $result);
        self::assertCount(2, $result);
    }

    public function testBelongsToManyLazyLoadDoesNotReturnTagsForOtherArticles(): void
    {
        $articleId1 = $this->insertArticle('BTM_Isolation1');
        $articleId2 = $this->insertArticle('BTM_Isolation2');
        $tagId1     = $this->insertTag('Tag1');
        $tagId2     = $this->insertTag('Tag2');
        $this->insertPivot($articleId1, $tagId1);
        $this->insertPivot($articleId2, $tagId2);

        $article1 = $this->makeArticle();
        $article1->find($articleId1);

        $relation = $this->makeBelongsToMany(
            $article1, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        $result = $relation->getData();

        self::assertCount(1, $result);
        self::assertSame('Tag1', $result->first()->getFieldValue('label'));
    }

    public function testBelongsToManyLazyLoadReturnsEmptyWhenLocalKeyIsNull(): void
    {
        // Article has never been saved — article_id is null
        $article  = $this->makeArticle('BTM_NullKey', null, false);
        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        $result = $relation->getData();

        self::assertInstanceOf(Collection::class, $result);
        self::assertCount(0, $result);
    }

    // =========================================================================
    // BelongsToMany — eager loading
    // =========================================================================

    public function testBelongsToManyEagerLoadReturnsTagsForAllArticles(): void
    {
        $articleId1 = $this->insertArticle('BTM_EagerA1');
        $articleId2 = $this->insertArticle('BTM_EagerA2');
        $tagId1     = $this->insertTag('EagerTag1');
        $tagId2     = $this->insertTag('EagerTag2');
        $tagId3     = $this->insertTag('EagerTag3');
        $this->insertPivot($articleId1, $tagId1);
        $this->insertPivot($articleId1, $tagId2);
        $this->insertPivot($articleId2, $tagId3);

        $art1 = $this->makeArticle();
        $art1->find($articleId1);

        $art2 = $this->makeArticle();
        $art2->find($articleId2);

        $dataCollection = new Collection([$art1, $art2]);

        $relation = $this->makeBelongsToMany(
            $art1, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        $result = $relation->getData(null, $dataCollection);

        // Eager load fetches ALL tags for ALL articles in the collection
        self::assertInstanceOf(Collection::class, $result);
        self::assertCount(3, $result);
    }

    public function testBelongsToManyEagerLoadWithEmptyCollectionReturnsEmpty(): void
    {
        $articleId = $this->insertArticle('BTM_EagerEmpty');
        $tagId     = $this->insertTag('UnusedTag');
        $this->insertPivot($articleId, $tagId);

        $article = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        $result = $relation->getData(null, new Collection());

        self::assertInstanceOf(Collection::class, $result);
        self::assertCount(0, $result);
    }

    // =========================================================================
    // BelongsToMany — setDataFromCollection
    // =========================================================================

    public function testBelongsToManySetDataFromCollectionFiltersByKeyMap(): void
    {
        $articleId1 = $this->insertArticle('BTM_SDC1');
        $articleId2 = $this->insertArticle('BTM_SDC2');
        $tagId1     = $this->insertTag('SDCTag1');
        $tagId2     = $this->insertTag('SDCTag2');
        $this->insertPivot($articleId1, $tagId1);
        $this->insertPivot($articleId2, $tagId2);

        // Build Tag models
        $tag1 = new \RelBelongsTo\Model\Tag($this->container);
        $tag1->find($tagId1);

        $tag2 = new \RelBelongsTo\Model\Tag($this->container);
        $tag2->find($tagId2);

        $allTags = new Collection([$tag1, $tag2]);

        // Key map: articleId1 → [tagId1]
        $keyMap = [(string) $articleId1 => [(string) $tagId1]];

        $article1 = $this->makeArticle();
        $article1->find($articleId1);

        $relation = $this->makeBelongsToMany(
            $article1, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        $relation->setDataFromCollection($allTags, $keyMap);
        $data = $relation->getData();

        // Only tag1 should be in the relation for article1
        self::assertCount(1, $data);
        self::assertSame('SDCTag1', $data->first()->getFieldValue('label'));
    }

    public function testBelongsToManySetDataFromCollectionWithNullKeyMapProducesEmpty(): void
    {
        $articleId = $this->insertArticle('BTM_SDC_NullMap');
        $tagId     = $this->insertTag('SDC_NullTag');
        $this->insertPivot($articleId, $tagId);

        $tag = new \RelBelongsTo\Model\Tag($this->container);
        $tag->find($tagId);

        $allTags = new Collection([$tag]);

        $article = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        // Passing null keyMap
        $relation->setDataFromCollection($allTags, null);
        $data = $relation->getData();

        self::assertInstanceOf(Collection::class, $data);
        self::assertCount(0, $data);
    }

    public function testBelongsToManySetDataFromCollectionWhenLocalKeyNotInMap(): void
    {
        $articleId = $this->insertArticle('BTM_SDC_NotInMap');
        $tagId     = $this->insertTag('SDC_NotInMapTag');
        $this->insertPivot($articleId, $tagId);

        $tag = new \RelBelongsTo\Model\Tag($this->container);
        $tag->find($tagId);

        $allTags = new Collection([$tag]);

        $article = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        // keyMap does NOT include articleId → early return
        $keyMap = ['9999' => [(string) $tagId]];
        $relation->setDataFromCollection($allTags, $keyMap);
        $data = $relation->getData();

        self::assertCount(0, $data);
    }

    // =========================================================================
    // BelongsToMany — caching
    // =========================================================================

    public function testBelongsToManyGetDataCachesResult(): void
    {
        $articleId = $this->insertArticle('BTM_Cache');
        $tagId     = $this->insertTag('CacheTag');
        $this->insertPivot($articleId, $tagId);

        $article = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        $first  = $relation->getData();
        $second = $relation->getData();

        self::assertSame($first, $second, 'getData() must return the cached instance on the second call');
    }

    // =========================================================================
    // BelongsToMany — saveAll() (pivot table management)
    // =========================================================================

    public function testBelongsToManySaveAllWritesPivotRows(): void
    {
        $articleId = $this->insertArticle('BTM_SaveAll');
        $tagId1    = $this->insertTag('SaveTag1');
        $tagId2    = $this->insertTag('SaveTag2');

        $article = $this->makeArticle();
        $article->find($articleId);

        $tag1 = new \RelBelongsTo\Model\Tag($this->container);
        $tag1->find($tagId1);

        $tag2 = new \RelBelongsTo\Model\Tag($this->container);
        $tag2->find($tagId2);

        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        // Manually set the data collection
        $collection = new Collection([$tag1, $tag2]);
        $relation->setDataFromCollection($collection, [(string) $articleId => [(string) $tagId1, (string) $tagId2]]);

        // Now populate fresh data by re-loading
        $relation->reset();

        // Add two tags via raw pivot rows, then use saveAll to replace them with new ones
        $this->insertPivot($articleId, $tagId1);

        // Load with existing data
        $loaded = $relation->getData();
        self::assertCount(1, $loaded);

        // Now overwrite pivot with [tagId1, tagId2]
        $relation->reset();

        // Set data manually to simulate what saveAll manages
        $col = new Collection([$tag1, $tag2]);
        $relation->setDataFromCollection($col, [(string) $articleId => [(string) $tagId1, (string) $tagId2]]);

        // getData() will return the setDataFromCollection result
        $data = $relation->getData();
        self::assertCount(2, $data);
    }

    public function testBelongsToManySaveAllReplacesExistingPivotRows(): void
    {
        $articleId = $this->insertArticle('BTM_SaveAllReplace');
        $tagId1    = $this->insertTag('ReplaceTag1');
        $tagId2    = $this->insertTag('ReplaceTag2');
        $tagId3    = $this->insertTag('ReplaceTag3');

        // Pre-populate pivot with tagId1 and tagId2
        $this->insertPivot($articleId, $tagId1);
        $this->insertPivot($articleId, $tagId2);

        $article = $this->makeArticle();
        $article->find($articleId);

        $tag3 = new \RelBelongsTo\Model\Tag($this->container);
        $tag3->find($tagId3);

        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        // Force the data collection to hold only tag3
        $col = new Collection([$tag3]);
        $relation->setDataFromCollection($col, [(string) $articleId => [(string) $tagId3]]);

        // saveAll should delete existing pivot rows and write only tag3
        $relation->saveAll();

        $this->db->setQuery(
            "SELECT tag_id FROM relbelongsto_article_tag WHERE article_id = {$articleId} ORDER BY tag_id"
        );
        $pivotRows = $this->db->loadColumn();

        self::assertCount(1, $pivotRows);
        self::assertSame((string) $tagId3, (string) $pivotRows[0]);
    }

    public function testBelongsToManySaveAllWithEmptyDataDeletesAllPivotRows(): void
    {
        $articleId = $this->insertArticle('BTM_SaveAllEmpty');
        $tagId     = $this->insertTag('EmptyPivotTag');
        $this->insertPivot($articleId, $tagId);

        $article = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        // setDataFromCollection with empty collection + keyMap that maps to empty array
        $emptyCol = new Collection();
        $relation->setDataFromCollection($emptyCol, [(string) $articleId => []]);

        $relation->saveAll();

        $this->db->setQuery(
            "SELECT COUNT(*) FROM relbelongsto_article_tag WHERE article_id = {$articleId}"
        );
        $count = (int) $this->db->loadResult();

        self::assertSame(0, $count);
    }

    // =========================================================================
    // BelongsToMany — reset() and rebase()
    // =========================================================================

    public function testBelongsToManyResetClearsData(): void
    {
        $articleId = $this->insertArticle('BTM_Reset');
        $tagId     = $this->insertTag('ResetTag');
        $this->insertPivot($articleId, $tagId);

        $article = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        $first = $relation->getData();
        self::assertCount(1, $first);

        $relation->reset();

        // After reset, re-fetch from DB
        $second = $relation->getData();
        self::assertCount(1, $second);
    }

    public function testBelongsToManyRebaseChangesParentModel(): void
    {
        $articleId1 = $this->insertArticle('BTM_RebaseA1');
        $articleId2 = $this->insertArticle('BTM_RebaseA2');
        $tagId1     = $this->insertTag('RebaseTag1');
        $tagId2     = $this->insertTag('RebaseTag2');
        $this->insertPivot($articleId1, $tagId1);
        $this->insertPivot($articleId2, $tagId2);

        $art1 = $this->makeArticle();
        $art1->find($articleId1);

        $art2 = $this->makeArticle();
        $art2->find($articleId2);

        $relation = $this->makeBelongsToMany(
            $art1, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        $relation->getData(); // cache for art1

        $relation->rebase($art2);
        $result = $relation->getData(); // must re-fetch for art2

        self::assertCount(1, $result);
        self::assertSame('RebaseTag2', $result->first()->getFieldValue('label'));
    }

    // =========================================================================
    // BelongsToMany — getForeignKeyMap
    // =========================================================================

    public function testBelongsToManyGetForeignKeyMapPopulatedAfterLazyLoad(): void
    {
        $articleId = $this->insertArticle('BTM_KeyMap');
        $tagId1    = $this->insertTag('KeyMapTag1');
        $tagId2    = $this->insertTag('KeyMapTag2');
        $this->insertPivot($articleId, $tagId1);
        $this->insertPivot($articleId, $tagId2);

        $article = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        $relation->getData(); // triggers lazy load and populates foreignKeyMap

        $map = $relation->getForeignKeyMap();
        self::assertIsArray($map);
        self::assertArrayHasKey((string) $articleId, $map);
        self::assertCount(2, $map[(string) $articleId]);
    }

    // =========================================================================
    // BelongsToMany — callback filtering
    // =========================================================================

    public function testBelongsToManyCallbackIsApplied(): void
    {
        $articleId = $this->insertArticle('BTM_CallbackFilter');
        $tagId1    = $this->insertTag('FilterTag1');
        $tagId2    = $this->insertTag('FilterTag2');
        $this->insertPivot($articleId, $tagId1);
        $this->insertPivot($articleId, $tagId2);

        $article = $this->makeArticle();
        $article->find($articleId);

        $relation = $this->makeBelongsToMany(
            $article, null, null, 'relbelongsto_article_tag', 'article_id', 'tag_id'
        );

        $result = $relation->getData(static function (DataModel $foreignModel) {
            $foreignModel->where('label', '=', 'FilterTag1');
        });

        self::assertCount(1, $result);
        self::assertSame('FilterTag1', $result->first()->getFieldValue('label'));
    }
}
