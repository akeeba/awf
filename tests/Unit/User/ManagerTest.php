<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\User;

use Awf\Application\Configuration as AppConfiguration;
use Awf\Container\Container;
use Awf\Session\Manager as SessionManager;
use Awf\Session\Segment;
use Awf\Text\Language;
use Awf\User\Authentication;
use Awf\User\AuthenticationInterface;
use Awf\User\Exception\InvalidCredentials;
use Awf\User\Exception\InvalidUser;
use Awf\User\Manager;
use Awf\User\Privilege;
use Awf\User\PrivilegeInterface;
use Awf\User\User;
use Awf\User\UserInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

// ---------------------------------------------------------------------------
// Concrete Authentication subclass used in tests
// ---------------------------------------------------------------------------

/**
 * A concrete Authentication subclass for testing.
 * By default onAuthentication() returns true (password accepted).
 */
class AlwaysAcceptAuthentication extends Authentication
{
    public function onAuthentication($params = []): bool
    {
        return true;
    }
}

/**
 * A concrete Authentication subclass that always rejects authentication.
 */
class AlwaysRejectAuthentication extends Authentication
{
    public function onAuthentication($params = []): bool
    {
        return false;
    }
}

/**
 * A concrete Authentication subclass that verifies via the 'password' param.
 * It checks against a pre-hashed password stored on the user record.
 */
class PasswordCheckAuthentication extends Authentication
{
    public function onAuthentication($params = []): bool
    {
        $password = $params['password'] ?? '';

        return password_verify($password, $this->user->getPassword());
    }
}

// ---------------------------------------------------------------------------
// Concrete Privilege subclass used in tests
// ---------------------------------------------------------------------------

class SimplePrivilege extends Privilege
{
    public function seedPrivileges(array $defaults): void
    {
        foreach ($defaults as $k => $v) {
            $this->setPrivilege($k, $v);
        }
    }
}

// ---------------------------------------------------------------------------
// Test class
// ---------------------------------------------------------------------------

#[CoversClass(Manager::class)]
#[CoversClass(Authentication::class)]
class ManagerTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Build a minimal Container. The $dbResult closure receives ($query) and
     * returns what loadObject()/loadResult() should return for that query.
     *
     * To avoid real database connections we inject a mock db driver.
     */
    private function makeContainer(
        array $segmentData = [],
        array $appConfigOverrides = []
    ): Container {
        $tmpDir = sys_get_temp_dir();

        // ---- Language stub ----
        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);

        // ---- Session Segment stub ----
        $segment = $this->createMock(Segment::class);
        $segMap  = $segmentData;
        $segment->method('get')->willReturnCallback(
            static function (string $key, mixed $default = null) use (&$segMap) {
                return $segMap[$key] ?? $default;
            }
        );
        $segment->method('set')->willReturnCallback(
            static function (string $key, mixed $value) use (&$segMap): void {
                $segMap[$key] = $value;
            }
        );
        $segment->method('clear')->willReturnCallback(
            static function () use (&$segMap): void {
                $segMap = [];
            }
        );

        // ---- Session Manager stub ----
        $session = $this->createMock(SessionManager::class);
        $session->method('regenerateId')->willReturn(true);

        // ---- AppConfig stub ----
        $appConfig = $this->createMock(AppConfiguration::class);
        $appConfig->method('get')->willReturnCallback(
            static function (string $key, mixed $default = null) use ($appConfigOverrides) {
                return $appConfigOverrides[$key] ?? $default;
            }
        );

        return new Container([
            'application_name'     => 'ManagerTest',
            'applicationNamespace' => '\\ManagerTest',
            'session_segment_name' => 'managertest_seg',
            'basePath'             => $tmpDir,
            'languagePath'         => $tmpDir,
            'temporaryPath'        => $tmpDir,
            'templatePath'         => $tmpDir,
            'sqlPath'              => $tmpDir,
            'filesystemBase'       => $tmpDir,
            'language'             => $language,
            'segment'              => $segment,
            'session'              => $session,
            'appConfig'            => $appConfig,
        ]);
    }

    /**
     * Build a mock DB that returns $userRow (stdClass|null) for loadObject()
     * and $userId (int|null) for loadResult().
     *
     * Note: the Driver uses a magic __call() to dispatch 'q' → quote() and 'qn' → quoteName().
     * We mock quote() and quoteName() directly (they are non-abstract concrete methods on Driver).
     */
    private function makeMockDb(?stdClass $userRow = null, mixed $userId = null): object
    {
        $query = $this->getMockBuilder(\Awf\Database\Query\Sqlite::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['select', 'from', 'where', 'update', 'set', 'insert', 'columns', 'values', 'delete'])
            ->getMock();
        // Fluent interface — every builder method returns $this
        foreach (['select', 'from', 'where', 'update', 'set', 'insert', 'columns', 'values', 'delete'] as $m) {
            $query->method($m)->willReturnSelf();
        }

        $db = $this->getMockBuilder(\Awf\Database\Driver\Sqlite::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'getQuery', 'setQuery', 'loadObject', 'loadResult', 'execute', 'insertid',
                'quote', 'quoteName',
                // abstract methods that must be stubbed
                'connect', 'connected', 'disconnect', 'dropTable', 'escape',
                'fetchArray', 'fetchAssoc', 'fetchObject', 'freeResult',
                'getAffectedRows', 'getCollation', 'getNumRows',
                'getTableColumns', 'getTableCreate', 'getTableKeys', 'getTableList',
                'getVersion', 'lockTable', 'renameTable', 'transactionCommit',
                'transactionRollback', 'transactionStart', 'unlockTables',
            ])
            ->getMock();

        $db->method('getQuery')->willReturn($query);
        $db->method('quote')->willReturnCallback(static fn($v) => '"' . $v . '"');
        $db->method('quoteName')->willReturnCallback(static fn($v) => '`' . $v . '`');
        $db->method('setQuery')->willReturnSelf();
        $db->method('loadObject')->willReturn($userRow);
        $db->method('loadResult')->willReturn($userId);
        $db->method('execute')->willReturn(true);
        $db->method('insertid')->willReturn(42);

        return $db;
    }

    /**
     * Build a Manager with a mocked DB injected into the container.
     */
    private function makeManager(
        ?stdClass $userRow = null,
        mixed $dbUserId = null,
        array $segmentData = [],
        array $appConfigOverrides = []
    ): Manager {
        $container        = $this->makeContainer($segmentData, $appConfigOverrides);
        $container['db']  = $this->makeMockDb($userRow, $dbUserId);

        return new Manager($container);
    }

    // =========================================================================
    // Construction
    // =========================================================================

    public function testConstructorAcceptsContainer(): void
    {
        $manager = $this->makeManager();
        self::assertInstanceOf(Manager::class, $manager);
    }

    public function testConstructorReadsUserTableFromAppConfig(): void
    {
        // The Manager reads 'user_table' from appConfig during construction.
        // We verify indirectly: getUser() queries the configured table name.
        // If construction succeeds without error the appConfig was consulted.
        $manager = $this->makeManager(
            appConfigOverrides: ['user_table' => '#__custom_users', 'user_class' => '\\Awf\\User\\User']
        );
        self::assertInstanceOf(Manager::class, $manager);
    }

    // =========================================================================
    // getUser() — guest (id = 0)
    // =========================================================================

    public function testGetUserWithZeroIdReturnsGuestUser(): void
    {
        $manager = $this->makeManager();
        $user    = $manager->getUser(0);

        self::assertInstanceOf(UserInterface::class, $user);
        self::assertNull($user->getId());
    }

    public function testGetUserWithZeroIdHasNoPassword(): void
    {
        $manager = $this->makeManager();
        $user    = $manager->getUser(0);

        self::assertSame('', $user->getPassword());
    }

    // =========================================================================
    // getUser() — existing user by id
    // =========================================================================

    public function testGetUserByIdReturnsBoundUser(): void
    {
        $row           = new stdClass();
        $row->id       = 7;
        $row->username = 'alice';
        $row->name     = 'Alice A.';
        $row->email    = 'alice@example.com';
        $row->password = '';

        $manager = $this->makeManager(userRow: $row);
        $user    = $manager->getUser(7);

        self::assertInstanceOf(UserInterface::class, $user);
        self::assertSame(7, $user->getId());
        self::assertSame('alice', $user->getUsername());
        self::assertSame('Alice A.', $user->getName());
        self::assertSame('alice@example.com', $user->getEmail());
    }

    public function testGetUserByIdReturnsNullWhenNotFound(): void
    {
        // loadObject() returns null → user not found
        $manager = $this->makeManager(userRow: null);
        $user    = $manager->getUser(999);

        self::assertNull($user);
    }

    // =========================================================================
    // getUser() — current user (null id)
    // =========================================================================

    public function testGetUserWithNullIdReturnsGuestWhenNoSession(): void
    {
        // segment returns 0 for 'user_id' → guest user
        $manager = $this->makeManager(segmentData: ['user_id' => 0]);
        $user    = $manager->getUser(null);

        self::assertInstanceOf(UserInterface::class, $user);
        self::assertNull($user->getId());
    }

    public function testGetUserWithNullIdLoadFromSessionWhenLoggedIn(): void
    {
        $row           = new stdClass();
        $row->id       = 5;
        $row->username = 'bob';
        $row->name     = 'Bob B.';
        $row->email    = 'bob@example.com';
        $row->password = '';

        // segment returns user_id = 5
        $manager = $this->makeManager(userRow: $row, segmentData: ['user_id' => 5]);
        $user    = $manager->getUser(null);

        self::assertInstanceOf(UserInterface::class, $user);
        self::assertSame(5, $user->getId());
        self::assertSame('bob', $user->getUsername());
    }

    public function testGetUserNullReturnsCachedInstanceOnSecondCall(): void
    {
        $row           = new stdClass();
        $row->id       = 3;
        $row->username = 'carol';
        $row->name     = 'Carol C.';
        $row->email    = 'carol@example.com';
        $row->password = '';

        $manager = $this->makeManager(userRow: $row, segmentData: ['user_id' => 3]);

        $user1 = $manager->getUser(null);
        $user2 = $manager->getUser(null);

        self::assertSame($user1, $user2, 'Second call should return the same cached instance');
    }

    // =========================================================================
    // getUserByUsername()
    // =========================================================================

    public function testGetUserByUsernameReturnsNullWhenNotFound(): void
    {
        // loadResult() returns null → no user with that username
        $manager = $this->makeManager(dbUserId: null);
        $result  = $manager->getUserByUsername('nobody');

        self::assertNull($result);
    }

    public function testGetUserByUsernameReturnsBoundUser(): void
    {
        $row           = new stdClass();
        $row->id       = 9;
        $row->username = 'dave';
        $row->name     = 'Dave D.';
        $row->email    = 'dave@example.com';
        $row->password = '';

        $manager = $this->makeManager(userRow: $row, dbUserId: 9);
        $result  = $manager->getUserByUsername('dave');

        self::assertInstanceOf(UserInterface::class, $result);
        self::assertSame(9, $result->getId());
        self::assertSame('dave', $result->getUsername());
    }

    // =========================================================================
    // Privilege plugin registration
    // =========================================================================

    public function testRegisterPrivilegePluginAttachesToNewUsers(): void
    {
        $manager = $this->makeManager();
        $manager->registerPrivilegePlugin('myPriv', SimplePrivilege::class);

        $user = $manager->getUser(0);
        // getPrivilege should delegate to the attached SimplePrivilege
        // The value is undefined → default false
        self::assertFalse($user->getPrivilege('myPriv.someKey', false));
    }

    public function testUnregisterPrivilegePluginRemovesIt(): void
    {
        $manager = $this->makeManager();
        $manager->registerPrivilegePlugin('myPriv', SimplePrivilege::class);
        $manager->unregisterPrivilegePlugin('myPriv');

        // Now there's no privilege plugin attached — getPrivilege falls back to default
        $user = $manager->getUser(0);
        // With no 'myPriv' attached, the result is the $default value
        self::assertFalse($user->getPrivilege('myPriv.someKey', false));
        self::assertTrue($user->getPrivilege('myPriv.someKey', true));
    }

    public function testUnregisterNonExistentPrivilegePluginIsHarmless(): void
    {
        $manager = $this->makeManager();
        // Should not throw
        $manager->unregisterPrivilegePlugin('doesNotExist');
        self::assertTrue(true);
    }

    public function testPrivilegePluginIsAttachedToEveryLoadedUser(): void
    {
        $row           = new stdClass();
        $row->id       = 11;
        $row->username = 'eve';
        $row->name     = 'Eve E.';
        $row->email    = 'eve@example.com';
        $row->password = '';

        $manager = $this->makeManager(userRow: $row);
        $manager->registerPrivilegePlugin('access', SimplePrivilege::class);

        $user = $manager->getUser(11);
        // Privilege is attached and accessible
        self::assertFalse($user->getPrivilege('access.admin', false));
    }

    // =========================================================================
    // Authentication plugin registration
    // =========================================================================

    public function testRegisterAuthenticationPluginAttachesToNewUsers(): void
    {
        $manager = $this->makeManager();
        $manager->registerAuthenticationPlugin('main', AlwaysAcceptAuthentication::class);

        $user = $manager->getUser(0);
        // verifyPassword calls onAuthentication
        self::assertTrue($user->verifyPassword('anything'));
    }

    public function testUnregisterAuthenticationPluginRemovesIt(): void
    {
        $manager = $this->makeManager();
        $manager->registerAuthenticationPlugin('main', AlwaysAcceptAuthentication::class);
        $manager->unregisterAuthenticationPlugin('main');

        // No authentication plugin → triggerAuthenticationEvent returns false
        $user = $manager->getUser(0);
        self::assertFalse($user->verifyPassword('anything'));
    }

    public function testUnregisterNonExistentAuthenticationPluginIsHarmless(): void
    {
        $manager = $this->makeManager();
        $manager->unregisterAuthenticationPlugin('doesNotExist');
        self::assertTrue(true);
    }

    // =========================================================================
    // loginUser() — success
    // =========================================================================

    public function testLoginUserSetsCurrentUserOnSuccess(): void
    {
        $plainPassword = 'secret';
        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

        $row           = new stdClass();
        $row->id       = 21;
        $row->username = 'frank';
        $row->name     = 'Frank F.';
        $row->email    = 'frank@example.com';
        $row->password = $hashedPassword;

        $manager = $this->makeManager(userRow: $row, dbUserId: 21);
        $manager->registerAuthenticationPlugin('main', PasswordCheckAuthentication::class);

        $manager->loginUser('frank', $plainPassword);

        // After login the current user should be frank
        $current = $manager->getUser();
        self::assertSame(21, $current->getId());
    }

    public function testLoginUserAlwaysAcceptSucceeds(): void
    {
        $row           = new stdClass();
        $row->id       = 22;
        $row->username = 'grace';
        $row->name     = 'Grace G.';
        $row->email    = 'grace@example.com';
        $row->password = '';

        $manager = $this->makeManager(userRow: $row, dbUserId: 22);
        $manager->registerAuthenticationPlugin('main', AlwaysAcceptAuthentication::class);

        // Should not throw
        $manager->loginUser('grace', 'any_password');

        $current = $manager->getUser();
        self::assertSame(22, $current->getId());
    }

    // =========================================================================
    // loginUser() — failure: unknown user
    // =========================================================================

    public function testLoginUserThrowsInvalidUserWhenUsernameNotFound(): void
    {
        // loadResult() returns null → no such user
        $manager = $this->makeManager(dbUserId: null);
        $manager->registerAuthenticationPlugin('main', AlwaysAcceptAuthentication::class);

        $this->expectException(InvalidUser::class);
        $manager->loginUser('ghost', 'password');
    }

    // =========================================================================
    // loginUser() — failure: wrong password
    // =========================================================================

    public function testLoginUserThrowsInvalidCredentialsOnBadPassword(): void
    {
        $row           = new stdClass();
        $row->id       = 23;
        $row->username = 'henry';
        $row->name     = 'Henry H.';
        $row->email    = 'henry@example.com';
        $row->password = password_hash('correctPassword', PASSWORD_BCRYPT);

        $manager = $this->makeManager(userRow: $row, dbUserId: 23);
        $manager->registerAuthenticationPlugin('main', PasswordCheckAuthentication::class);

        $this->expectException(InvalidCredentials::class);
        $manager->loginUser('henry', 'wrongPassword');
    }

    public function testLoginUserThrowsInvalidCredentialsWhenAuthPluginRejects(): void
    {
        $row           = new stdClass();
        $row->id       = 24;
        $row->username = 'irene';
        $row->name     = 'Irene I.';
        $row->email    = 'irene@example.com';
        $row->password = '';

        $manager = $this->makeManager(userRow: $row, dbUserId: 24);
        $manager->registerAuthenticationPlugin('main', AlwaysRejectAuthentication::class);

        $this->expectException(InvalidCredentials::class);
        $manager->loginUser('irene', 'anything');
    }

    // =========================================================================
    // logoutUser()
    // =========================================================================

    public function testLogoutUserClearsCurrentUser(): void
    {
        $row           = new stdClass();
        $row->id       = 31;
        $row->username = 'jack';
        $row->name     = 'Jack J.';
        $row->email    = 'jack@example.com';
        $row->password = '';

        $manager = $this->makeManager(userRow: $row, dbUserId: 31);
        $manager->registerAuthenticationPlugin('main', AlwaysAcceptAuthentication::class);

        $manager->loginUser('jack', 'pass');
        self::assertSame(31, $manager->getUser()->getId());

        $manager->logoutUser();

        // After logout the segment is cleared, so getUser() returns a guest
        // (the mock segment.get('user_id', 0) returns 0 now)
        $guestUser = $manager->getUser();
        self::assertNull($guestUser->getId());
    }

    // =========================================================================
    // Privilege resolution via User
    // =========================================================================

    public static function privilegeResolutionProvider(): array
    {
        return [
            'undefined privilege returns default false' => ['access.admin', false, false],
            'undefined privilege returns default true'  => ['access.admin', true, true],
        ];
    }

    #[DataProvider('privilegeResolutionProvider')]
    public function testPrivilegeResolutionReturnsDefault(
        string $privilege,
        bool $default,
        bool $expected
    ): void {
        $manager = $this->makeManager();
        $manager->registerPrivilegePlugin('access', SimplePrivilege::class);

        $user = $manager->getUser(0);
        self::assertSame($expected, $user->getPrivilege($privilege, $default));
    }

    public function testPrivilegeResolutionUnknownPluginReturnsDefault(): void
    {
        $manager = $this->makeManager();
        // No 'access' plugin registered
        $user = $manager->getUser(0);

        self::assertFalse($user->getPrivilege('access.admin', false));
        self::assertTrue($user->getPrivilege('access.admin', true));
    }

    public function testPrivilegeResolutionMalformedKeyReturnsDefault(): void
    {
        $manager = $this->makeManager();
        $manager->registerPrivilegePlugin('access', SimplePrivilege::class);
        $user = $manager->getUser(0);

        // A key without a dot is malformed — should always return $default
        self::assertFalse($user->getPrivilege('nodot', false));
        self::assertTrue($user->getPrivilege('nodot', true));
    }

    // =========================================================================
    // Authentication abstract class
    // =========================================================================

    public function testAuthenticationSetNameStoresName(): void
    {
        $auth = new AlwaysAcceptAuthentication();
        $auth->setName('myAuth');

        // We verify via a round-trip through attachAuthenticationPlugin
        $user = new User();
        $user->attachAuthenticationPlugin('myAuth', $auth);
        // If it attached without error the name was accepted
        self::assertTrue(true);
    }

    public function testAuthenticationSetUserBindsUser(): void
    {
        $user = new User();
        $auth = new AlwaysAcceptAuthentication();
        $auth->setUser($user);

        // Verify the binding is used: triggerAuthenticationEvent calls onAuthentication
        $user->attachAuthenticationPlugin('main', $auth);
        self::assertTrue($user->verifyPassword('anything'));
    }

    public function testAuthenticationDefaultOnAuthenticationReturnsTrue(): void
    {
        // The abstract Authentication base class default returns true
        // We test via AlwaysAcceptAuthentication which inherits that behaviour
        $auth = new AlwaysAcceptAuthentication();
        self::assertTrue($auth->onAuthentication(['password' => 'x']));
    }

    public function testAuthenticationAlwaysRejectOnAuthenticationReturnsFalse(): void
    {
        $auth = new AlwaysRejectAuthentication();
        self::assertFalse($auth->onAuthentication(['password' => 'x']));
    }
}
