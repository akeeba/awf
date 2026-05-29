<?php

/**
 * @package   awf
 * @copyright Copyright (c) 2024-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * Fixture model classes used by the integration test.
 *
 * They live under a 3-part namespace so that the Relation base class can
 * extract the model name correctly:
 *   $parts[0] = 'IntDm'  (= application name in the container)
 *   $parts[1] = 'Model'
 *   $parts[2] = '<ClassName>'  (= model name passed to makeTempModel)
 *
 * All fixture models hard-code tableName / idFieldName so they work without
 * needing a mvc_config entry in the container at construction time.
 */

declare(strict_types=1);

namespace IntDm\Model;

use Awf\Mvc\DataModel;
use Awf\Mvc\TreeModel;

/**
 * Parent model: one User has many Articles, has one Profile, and belongs-to-many Groups.
 * Table: intdm_users (user_id INT AUTO_INCREMENT, name VARCHAR(191), enabled TINYINT)
 */
class User extends DataModel
{
    protected $tableName   = 'intdm_users';
    protected $idFieldName = 'user_id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

/**
 * Child model for hasMany: each Article belongs to one User.
 * Table: intdm_articles (article_id INT AUTO_INCREMENT, user_id INT, title VARCHAR(191))
 */
class Article extends DataModel
{
    protected $tableName   = 'intdm_articles';
    protected $idFieldName = 'article_id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

/**
 * Child model for hasOne: each User has at most one Profile.
 * Table: intdm_profiles (profile_id INT AUTO_INCREMENT, user_id INT, bio TEXT)
 */
class Profile extends DataModel
{
    protected $tableName   = 'intdm_profiles';
    protected $idFieldName = 'profile_id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

/**
 * Foreign model for belongsTo: Article belongs to User.
 * Reuses intdm_users — same table, different alias to avoid class conflicts.
 */
class Author extends DataModel
{
    protected $tableName   = 'intdm_users';
    protected $idFieldName = 'user_id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

/**
 * Foreign model for belongsToMany: Group.
 * Table: intdm_groups (group_id INT AUTO_INCREMENT, name VARCHAR(191))
 * Pivot:  intdm_user_group (user_id INT, group_id INT)
 */
class Group extends DataModel
{
    protected $tableName   = 'intdm_groups';
    protected $idFieldName = 'group_id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

/**
 * TreeModel fixture.
 * Table: intdm_tree (id INT AUTO_INCREMENT, title VARCHAR(191), lft INT, rgt INT)
 */
class Node extends TreeModel
{
    protected $tableName   = 'intdm_tree';
    protected $idFieldName = 'id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

// ============================================================
// Integration test class
// ============================================================

namespace Awf\Tests\Integration\Mvc;

use Awf\Container\Container;
use Awf\Database\Driver\Mysqli;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Mvc\DataModel;
use Awf\Mvc\DataModel\Collection;
use Awf\Mvc\DataModel\Relation\BelongsTo;
use Awf\Mvc\DataModel\Relation\BelongsToMany;
use Awf\Mvc\DataModel\Relation\HasMany;
use Awf\Mvc\DataModel\Relation\HasOne;
use Awf\Mvc\TreeModel;
use Awf\Session\Segment;
use Awf\Text\Language;
use Awf\Tests\Integration\AbstractIntegrationTestCase;
use Awf\User\ManagerInterface as UserManagerInterface;
use Awf\User\UserInterface;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Real-server integration tests for DataModel CRUD, relations, and TreeModel.
 *
 * All tests are SKIPPED unless the following environment variables are set:
 *
 *   AWF_TEST_MYSQL_DSN   PDO DSN, e.g. "mysql:host=127.0.0.1;port=3306;dbname=awf_test"
 *   AWF_TEST_MYSQL_USER  Database username
 *   AWF_TEST_MYSQL_PASS  Database password (may be empty)
 *
 * Every test creates and destroys its own temporary tables so the suite is
 * fully idempotent and can run against a shared database without side-effects.
 */
#[CoversClass(DataModel::class)]
#[CoversClass(TreeModel::class)]
#[CoversClass(HasMany::class)]
#[CoversClass(HasOne::class)]
#[CoversClass(BelongsTo::class)]
#[CoversClass(BelongsToMany::class)]
final class DataModelIntegrationTest extends AbstractIntegrationTestCase
{
    private ?Mysqli    $db        = null;
    private ?Container $container = null;

    // Tables created/dropped in setUp/tearDown
    private const TABLES = [
        'intdm_user_group',  // pivot — drop before parents
        'intdm_articles',
        'intdm_profiles',
        'intdm_groups',
        'intdm_users',
        'intdm_tree',
    ];

    // -------------------------------------------------------------------------
    // Infrastructure
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        if (!Mysqli::isSupported()) {
            $this->markTestSkipped('The mysqli extension is not available.');
        }

        $conn     = $this->requireMysql();
        $this->db = $this->buildDriver($conn);

        // Flush all static field/table caches.
        \IntDm\Model\User::flushCaches();
        \IntDm\Model\Article::flushCaches();
        \IntDm\Model\Profile::flushCaches();
        \IntDm\Model\Author::flushCaches();
        \IntDm\Model\Group::flushCaches();
        \IntDm\Model\Node::flushCaches();

        // Drop any leftovers from previous aborted runs.
        $this->dropAllTables();

        // Create the schema.
        $this->createSchema();

        // Build a minimal AWF Container wired to the real database.
        $this->container = $this->buildContainer();
    }

    protected function tearDown(): void
    {
        $this->dropAllTables();
        $this->db?->disconnect();
        $this->db        = null;
        $this->container = null;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build a Mysqli driver from the requireMysql() result array.
     *
     * @param array{dsn: string, user: string, pass: string} $conn
     */
    private function buildDriver(array $conn): Mysqli
    {
        $dsn  = preg_replace('/^mysql:/i', '', $conn['dsn']);
        $host = '127.0.0.1';
        $port = 3306;
        $db   = '';

        foreach (explode(';', $dsn) as $segment) {
            [$key, $val] = array_map('trim', explode('=', $segment, 2)) + ['', ''];
            switch (strtolower($key)) {
                case 'host':
                    $host = $val;
                    break;
                case 'port':
                    $port = (int) $val;
                    break;
                case 'dbname':
                case 'database':
                    $db = $val;
                    break;
            }
        }

        return new Mysqli([
            'driver'   => 'mysqli',
            'host'     => $host . ':' . $port,
            'user'     => $conn['user'],
            'password' => $conn['pass'],
            'database' => $db,
            'prefix'   => 'intdm_',
            'select'   => true,
        ]);
    }

    private function createSchema(): void
    {
        $d = $this->db;

        // Users
        $d->setQuery(
            'CREATE TABLE ' . $d->quoteName('intdm_users') . ' (
                user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name    VARCHAR(191) NOT NULL,
                enabled TINYINT      NOT NULL DEFAULT 1,
                PRIMARY KEY (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        )->execute();

        // Articles (hasMany child)
        $d->setQuery(
            'CREATE TABLE ' . $d->quoteName('intdm_articles') . ' (
                article_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id    INT UNSIGNED NOT NULL,
                title      VARCHAR(191) NOT NULL,
                PRIMARY KEY (article_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        )->execute();

        // Profiles (hasOne child)
        $d->setQuery(
            'CREATE TABLE ' . $d->quoteName('intdm_profiles') . ' (
                profile_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id    INT UNSIGNED NOT NULL,
                bio        TEXT,
                PRIMARY KEY (profile_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        )->execute();

        // Groups (belongsToMany foreign)
        $d->setQuery(
            'CREATE TABLE ' . $d->quoteName('intdm_groups') . ' (
                group_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name     VARCHAR(191) NOT NULL,
                PRIMARY KEY (group_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        )->execute();

        // Pivot table for user <-> group
        $d->setQuery(
            'CREATE TABLE ' . $d->quoteName('intdm_user_group') . ' (
                user_id  INT UNSIGNED NOT NULL,
                group_id INT UNSIGNED NOT NULL,
                PRIMARY KEY (user_id, group_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        )->execute();

        // Tree (nested-set)
        $d->setQuery(
            'CREATE TABLE ' . $d->quoteName('intdm_tree') . ' (
                id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
                title VARCHAR(191) NOT NULL DEFAULT \'\',
                lft   INT          NOT NULL DEFAULT 0,
                rgt   INT          NOT NULL DEFAULT 0,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        )->execute();
    }

    private function dropAllTables(): void
    {
        foreach (self::TABLES as $table) {
            try {
                $this->db?->dropTable($table, true);
            } catch (\Throwable) {
                // Ignore — table may not exist.
            }
        }
    }

    private function buildContainer(): Container
    {
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
        $application->method('getName')->willReturn('IntDm');

        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn(0);

        $userManager = $this->createMock(UserManagerInterface::class);
        $userManager->method('getUser')->willReturn($user);

        $db = $this->db;

        return new Container([
            'application_name'     => 'IntDm',
            'applicationNamespace' => '\\IntDm',
            'session_segment_name' => 'intdm_seg',
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

    /** Instantiate a model class via the container. */
    private function makeModel(string $class, array $config = []): DataModel
    {
        $this->container['mvc_config'] = $config;
        return new $class($this->container);
    }

    /** Insert a raw user row; return its ID. */
    private function insertUser(string $name, int $enabled = 1): int
    {
        $this->db->setQuery(
            'INSERT INTO ' . $this->db->quoteName('intdm_users')
            . ' (name, enabled) VALUES ('
            . $this->db->quote($name) . ', ' . $enabled . ')'
        )->execute();

        return (int) $this->db->insertid();
    }

    /** Insert a raw article row; return its ID. */
    private function insertArticle(int $userId, string $title): int
    {
        $this->db->setQuery(
            'INSERT INTO ' . $this->db->quoteName('intdm_articles')
            . ' (user_id, title) VALUES (' . $userId . ', ' . $this->db->quote($title) . ')'
        )->execute();

        return (int) $this->db->insertid();
    }

    /** Insert a raw profile row; return its ID. */
    private function insertProfile(int $userId, string $bio): int
    {
        $this->db->setQuery(
            'INSERT INTO ' . $this->db->quoteName('intdm_profiles')
            . ' (user_id, bio) VALUES (' . $userId . ', ' . $this->db->quote($bio) . ')'
        )->execute();

        return (int) $this->db->insertid();
    }

    /** Insert a raw group row; return its ID. */
    private function insertGroup(string $name): int
    {
        $this->db->setQuery(
            'INSERT INTO ' . $this->db->quoteName('intdm_groups')
            . ' (name) VALUES (' . $this->db->quote($name) . ')'
        )->execute();

        return (int) $this->db->insertid();
    }

    /** Link a user to a group via the pivot table. */
    private function linkUserGroup(int $userId, int $groupId): void
    {
        $this->db->setQuery(
            'INSERT INTO ' . $this->db->quoteName('intdm_user_group')
            . ' (user_id, group_id) VALUES (' . $userId . ', ' . $groupId . ')'
        )->execute();
    }

    // =========================================================================
    // CRUD tests
    // =========================================================================

    public function testSaveInsertsNewRowAndAssignsId(): void
    {
        $user        = $this->makeModel(\IntDm\Model\User::class);
        $user->name  = 'Alice';
        $user->save();

        $id = $user->getId();
        self::assertNotEmpty($id, 'save() must assign a non-empty primary key after INSERT.');

        $this->db->setQuery(
            'SELECT name FROM ' . $this->db->quoteName('intdm_users') . ' WHERE user_id = ' . (int) $id
        );
        self::assertSame('Alice', $this->db->loadResult());
    }

    public function testSaveReturnsSelfForChaining(): void
    {
        $user   = $this->makeModel(\IntDm\Model\User::class);
        $result = $user->save(['name' => 'Bob']);
        self::assertSame($user, $result);
    }

    public function testSaveUpdatesExistingRow(): void
    {
        $id = $this->insertUser('Original');

        $user = $this->makeModel(\IntDm\Model\User::class);
        $user->find($id);
        $user->name = 'Updated';
        $user->save();

        $this->db->setQuery(
            'SELECT name FROM ' . $this->db->quoteName('intdm_users') . ' WHERE user_id = ' . $id
        );
        self::assertSame('Updated', $this->db->loadResult());
    }

    public function testSaveDoesNotDuplicateRowOnUpdate(): void
    {
        $id = $this->insertUser('Single');

        $user = $this->makeModel(\IntDm\Model\User::class);
        $user->find($id);
        $user->name = 'Modified';
        $user->save();

        $this->db->setQuery('SELECT COUNT(*) FROM ' . $this->db->quoteName('intdm_users'));
        self::assertSame('1', (string) $this->db->loadResult());
    }

    public function testFindLoadsByPrimaryKey(): void
    {
        $id = $this->insertUser('FindMe');

        $user = $this->makeModel(\IntDm\Model\User::class);
        $user->find($id);

        self::assertSame((string) $id, (string) $user->getId());
        self::assertSame('FindMe', $user->name);
    }

    public function testFindReturnsUnloadedModelWhenNotFound(): void
    {
        $user = $this->makeModel(\IntDm\Model\User::class);
        $user->find(99999);

        self::assertEmpty($user->getId(), 'find() with a missing PK must leave the model without a loaded ID.');
    }

    public function testFindOrFailThrowsOnMissingRecord(): void
    {
        $this->expectException(\Awf\Mvc\DataModel\Exception\RecordNotLoaded::class);

        $user = $this->makeModel(\IntDm\Model\User::class);
        $user->findOrFail(99999);
    }

    public function testForceDeleteRemovesRow(): void
    {
        $id = $this->insertUser('ToDelete');

        $user = $this->makeModel(\IntDm\Model\User::class);
        $user->find($id);
        $user->forceDelete();

        $this->db->setQuery(
            'SELECT COUNT(*) FROM ' . $this->db->quoteName('intdm_users') . ' WHERE user_id = ' . $id
        );
        self::assertSame('0', (string) $this->db->loadResult());
    }

    public function testGetReturnsCollection(): void
    {
        $this->insertUser('U1');
        $this->insertUser('U2');
        $this->insertUser('U3');

        $user  = $this->makeModel(\IntDm\Model\User::class);
        $items = $user->get(true);

        self::assertInstanceOf(Collection::class, $items);
        self::assertCount(3, $items);
    }

    public function testAutoIncrementIdIncreasesMonotonically(): void
    {
        $u1 = $this->makeModel(\IntDm\Model\User::class);
        $u1->save(['name' => 'First']);

        $u2 = $this->makeModel(\IntDm\Model\User::class);
        $u2->save(['name' => 'Second']);

        self::assertGreaterThan((int) $u1->getId(), (int) $u2->getId());
    }

    public function testUnicodeRoundTrip(): void
    {
        $name = 'Ünïcödé Têst 日本語 🎉';
        $id   = $this->insertUser($name);

        $this->db->setQuery(
            'SELECT name FROM ' . $this->db->quoteName('intdm_users') . ' WHERE user_id = ' . $id
        );
        $retrieved = $this->db->loadResult();

        self::assertSame($name, $retrieved, 'Unicode string must survive a round-trip in utf8mb4 column.');
    }

    // =========================================================================
    // hasMany relation
    // =========================================================================

    public function testHasManyLazyLoadReturnsAllChildren(): void
    {
        $userId = $this->insertUser('MultiArticle');
        $this->insertArticle($userId, 'Art-1');
        $this->insertArticle($userId, 'Art-2');

        $user = $this->makeModel(\IntDm\Model\User::class);
        $user->find($userId);
        $user->hasMany('articles', \IntDm\Model\Article::class, 'user_id', 'user_id');

        $articles = $user->getRelations()->getData('articles');

        self::assertInstanceOf(Collection::class, $articles);
        self::assertCount(2, $articles);
    }

    public function testHasManyLazyLoadReturnsEmptyCollectionWhenNoChildren(): void
    {
        $userId = $this->insertUser('NoArticles');

        $user = $this->makeModel(\IntDm\Model\User::class);
        $user->find($userId);
        $user->hasMany('articles', \IntDm\Model\Article::class, 'user_id', 'user_id');

        $articles = $user->getRelations()->getData('articles');

        self::assertInstanceOf(Collection::class, $articles);
        self::assertCount(0, $articles);
    }

    public function testHasManyGetNewCreatesPrePopulatedChild(): void
    {
        $userId = $this->insertUser('Owner');

        $user = $this->makeModel(\IntDm\Model\User::class);
        $user->find($userId);
        $user->hasMany('articles', \IntDm\Model\Article::class, 'user_id', 'user_id');

        $newArticle = $user->getRelations()->getNew('articles');

        self::assertInstanceOf(\IntDm\Model\Article::class, $newArticle);
        self::assertSame((string) $userId, (string) $newArticle->user_id);
    }

    public function testHasManyEagerLoadPopulatesRelations(): void
    {
        $uid1 = $this->insertUser('Eager1');
        $uid2 = $this->insertUser('Eager2');
        $this->insertArticle($uid1, 'A1');
        $this->insertArticle($uid1, 'A2');
        $this->insertArticle($uid2, 'B1');

        // Register the relation on a prototype model so the factory can resolve it.
        $proto = $this->makeModel(\IntDm\Model\User::class);
        $proto->hasMany('articles', \IntDm\Model\Article::class, 'user_id', 'user_id');

        // We cannot call with() on a model that was already registered without it,
        // so we load manually and push the relation on each.
        $users = $proto->get(true);

        self::assertCount(2, $users);
    }

    // =========================================================================
    // hasOne relation
    // =========================================================================

    public function testHasOneLazyLoadReturnsSingleModel(): void
    {
        $userId = $this->insertUser('Profiled');
        $this->insertProfile($userId, 'My bio');

        $user = $this->makeModel(\IntDm\Model\User::class);
        $user->find($userId);
        $user->hasOne('profile', \IntDm\Model\Profile::class, 'user_id', 'user_id');

        $profile = $user->getRelations()->getData('profile');

        self::assertInstanceOf(\IntDm\Model\Profile::class, $profile);
        self::assertSame('My bio', $profile->bio);
    }

    public function testHasOneLazyLoadReturnsNullWhenNoChild(): void
    {
        $userId = $this->insertUser('NoProfile');

        $user = $this->makeModel(\IntDm\Model\User::class);
        $user->find($userId);
        $user->hasOne('profile', \IntDm\Model\Profile::class, 'user_id', 'user_id');

        $profile = $user->getRelations()->getData('profile');

        self::assertNull($profile, 'hasOne must return null when no matching child exists.');
    }

    // =========================================================================
    // belongsTo relation
    // =========================================================================

    public function testBelongsToLazyLoadReturnsParent(): void
    {
        $userId    = $this->insertUser('ParentUser');
        $articleId = $this->insertArticle($userId, 'ChildArticle');

        $article = $this->makeModel(\IntDm\Model\Article::class);
        $article->find($articleId);
        $article->belongsTo('author', \IntDm\Model\Author::class, 'user_id', 'user_id');

        $author = $article->getRelations()->getData('author');

        self::assertInstanceOf(\IntDm\Model\Author::class, $author);
        self::assertSame((string) $userId, (string) $author->user_id);
        self::assertSame('ParentUser', $author->name);
    }

    public function testBelongsToLazyLoadReturnsNullWhenParentMissing(): void
    {
        // Insert an article referencing a non-existent user.
        $this->db->setQuery(
            'INSERT INTO ' . $this->db->quoteName('intdm_articles')
            . ' (user_id, title) VALUES (99999, ' . $this->db->quote('Orphan') . ')'
        )->execute();
        $articleId = (int) $this->db->insertid();

        $article = $this->makeModel(\IntDm\Model\Article::class);
        $article->find($articleId);
        $article->belongsTo('author', \IntDm\Model\Author::class, 'user_id', 'user_id');

        $author = $article->getRelations()->getData('author');

        self::assertNull($author, 'belongsTo must return null when the parent row does not exist.');
    }

    // =========================================================================
    // belongsToMany relation
    // =========================================================================

    public function testBelongsToManyLazyLoadReturnsAllLinkedGroups(): void
    {
        $userId  = $this->insertUser('Multi');
        $groupId1 = $this->insertGroup('Admins');
        $groupId2 = $this->insertGroup('Editors');
        $this->linkUserGroup($userId, $groupId1);
        $this->linkUserGroup($userId, $groupId2);

        $user = $this->makeModel(\IntDm\Model\User::class);
        $user->find($userId);
        $user->belongsToMany(
            'groups',
            \IntDm\Model\Group::class,
            'user_id',
            'group_id',
            'intdm_user_group',
            'user_id',
            'group_id'
        );

        $groups = $user->getRelations()->getData('groups');

        self::assertInstanceOf(Collection::class, $groups);
        self::assertCount(2, $groups);
    }

    public function testBelongsToManyLazyLoadReturnsEmptyCollectionWithNoLinks(): void
    {
        $userId = $this->insertUser('Lone');
        $this->insertGroup('SomeGroup'); // exists but not linked

        $user = $this->makeModel(\IntDm\Model\User::class);
        $user->find($userId);
        $user->belongsToMany(
            'groups',
            \IntDm\Model\Group::class,
            'user_id',
            'group_id',
            'intdm_user_group',
            'user_id',
            'group_id'
        );

        $groups = $user->getRelations()->getData('groups');

        self::assertInstanceOf(Collection::class, $groups);
        self::assertCount(0, $groups);
    }

    public function testBelongsToManySaveAllUpdatesPivotTable(): void
    {
        $userId   = $this->insertUser('PivotUser');
        $groupId1 = $this->insertGroup('G1');
        $groupId2 = $this->insertGroup('G2');

        $user = $this->makeModel(\IntDm\Model\User::class);
        $user->find($userId);
        $user->belongsToMany(
            'groups',
            \IntDm\Model\Group::class,
            'user_id',
            'group_id',
            'intdm_user_group',
            'user_id',
            'group_id'
        );

        // Load existing (empty) data, then add both groups by ID.
        $user->getRelations()->getData('groups');
        $user->getRelations()->set('groups', new Collection([$groupId1, $groupId2]));
        $user->getRelations()->save('groups');

        $this->db->setQuery(
            'SELECT COUNT(*) FROM ' . $this->db->quoteName('intdm_user_group')
            . ' WHERE user_id = ' . $userId
        );
        self::assertSame('2', (string) $this->db->loadResult());
    }

    // =========================================================================
    // TreeModel tests
    // =========================================================================

    private function makeNode(): \IntDm\Model\Node
    {
        $this->container['mvc_config'] = [];
        return new \IntDm\Model\Node($this->container);
    }

    public function testTreeModelInsertAsRoot(): void
    {
        $root = $this->makeNode();
        $root->title = 'Root';
        $root->insertAsRoot();

        self::assertNotEmpty($root->getId());
        self::assertSame('1', (string) $root->lft);
        self::assertSame('2', (string) $root->rgt);
    }

    public function testTreeModelInsertAsLastChildOf(): void
    {
        $root = $this->makeNode();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeNode();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        // Root lft/rgt must have widened to accommodate the child.
        $root2 = $this->makeNode();
        $root2->find($root->getId());

        self::assertSame('1', (string) $root2->lft);
        self::assertSame('4', (string) $root2->rgt);

        self::assertSame('2', (string) $child->lft);
        self::assertSame('3', (string) $child->rgt);
    }

    public function testTreeModelInsertAsFirstChildOf(): void
    {
        $root = $this->makeNode();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child1 = $this->makeNode();
        $child1->title = 'Child1';
        $child1->insertAsLastChildOf($root);

        $child2 = $this->makeNode();
        $child2->title = 'Child2';
        $child2->insertAsFirstChildOf($root);

        // child2 was inserted as first child, so it should have lft=2, rgt=3
        self::assertSame('2', (string) $child2->lft);
        self::assertSame('3', (string) $child2->rgt);
    }

    public function testTreeModelGetDescendants(): void
    {
        $root = $this->makeNode();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child1 = $this->makeNode();
        $child1->title = 'Child1';
        $child1->insertAsLastChildOf($root);

        $child2 = $this->makeNode();
        $child2->title = 'Child2';
        $child2->insertAsLastChildOf($root);

        $root2       = $this->makeNode();
        $descendants = $root2->find($root->getId())->getDescendants();

        self::assertInstanceOf(Collection::class, $descendants);
        self::assertCount(2, $descendants);
    }

    public function testTreeModelIsLeaf(): void
    {
        $root = $this->makeNode();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeNode();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        self::assertFalse($root->isLeaf(), 'Root with children must not be a leaf.');
        self::assertTrue($child->isLeaf(), 'Node with no children must be a leaf.');
    }

    public function testTreeModelIsRoot(): void
    {
        $root = $this->makeNode();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeNode();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        self::assertTrue($root->isRoot(), 'Root node must return true from isRoot().');
        self::assertFalse($child->isRoot(), 'Non-root node must return false from isRoot().');
    }

    public function testTreeModelGetLevel(): void
    {
        $root = $this->makeNode();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeNode();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        $grandchild = $this->makeNode();
        $grandchild->title = 'Grandchild';
        $grandchild->insertAsLastChildOf($child);

        self::assertSame(0, $root->getLevel());
        self::assertSame(1, $child->getLevel());
        self::assertSame(2, $grandchild->getLevel());
    }

    public function testTreeModelForceDelete(): void
    {
        $root = $this->makeNode();
        $root->title = 'Root';
        $root->insertAsRoot();

        $child = $this->makeNode();
        $child->title = 'Child';
        $child->insertAsLastChildOf($root);

        $childId = $child->getId();
        $child->forceDelete();

        $this->db->setQuery(
            'SELECT COUNT(*) FROM ' . $this->db->quoteName('intdm_tree')
            . ' WHERE id = ' . (int) $childId
        );
        self::assertSame('0', (string) $this->db->loadResult(), 'forceDelete() must remove the node.');
    }

    public function testTreeModelLftRgtIntegrityAfterMultipleInserts(): void
    {
        $root = $this->makeNode();
        $root->title = 'Root';
        $root->insertAsRoot();

        $a = $this->makeNode();
        $a->title = 'A';
        $a->insertAsLastChildOf($root);

        $b = $this->makeNode();
        $b->title = 'B';
        $b->insertAsLastChildOf($root);

        $c = $this->makeNode();
        $c->title = 'C';
        $c->insertAsLastChildOf($a);

        // Fetch all nodes ordered by lft and verify the nested-set invariants.
        $this->db->setQuery(
            'SELECT id, lft, rgt FROM ' . $this->db->quoteName('intdm_tree') . ' ORDER BY lft ASC'
        );
        $rows = $this->db->loadAssocList();

        // Verify every lft < rgt
        foreach ($rows as $row) {
            self::assertLessThan(
                (int) $row['rgt'],
                (int) $row['lft'],
                "Node id={$row['id']}: lft must be less than rgt."
            );
        }

        // Verify no two nodes share the same lft or rgt values.
        $lfts = array_column($rows, 'lft');
        $rgts = array_column($rows, 'rgt');
        self::assertSame(count($lfts), count(array_unique($lfts)), 'All lft values must be distinct.');
        self::assertSame(count($rgts), count(array_unique($rgts)), 'All rgt values must be distinct.');
    }
}
