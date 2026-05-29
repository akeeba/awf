<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\User;

use Awf\Registry\Registry;
use Awf\User\Privilege;
use Awf\User\PrivilegeInterface;
use Awf\User\User;
use Awf\User\UserInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// ---------------------------------------------------------------------------
// Minimal concrete Privilege implementation for testing
// ---------------------------------------------------------------------------

/**
 * A concrete Privilege subclass used only in tests.
 * Inherits all behaviour from the abstract Privilege class.
 */
class ConcretePrivilege extends Privilege
{
    /**
     * Expose a way to seed the initial privileges array so tests can verify
     * onAfterLoad / onBeforeSave round-trips without touching protected fields
     * via Reflection.
     *
     * @param  array<string, mixed>  $defaults
     */
    public function seedPrivileges(array $defaults): void
    {
        // We deliberately use the public setPrivilege so that no
        // Reflection / deprecated ReflectionProperty::setAccessible() is needed.
        foreach ($defaults as $k => $v) {
            $this->setPrivilege($k, $v);
        }
    }
}

// ---------------------------------------------------------------------------
// Test class
// ---------------------------------------------------------------------------

#[CoversClass(User::class)]
#[CoversClass(Privilege::class)]
class UserTest extends TestCase
{
    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeUser(): User
    {
        return new User();
    }

    private function makePrivilege(): ConcretePrivilege
    {
        return new ConcretePrivilege();
    }

    // =========================================================================
    // User value-object: id / username / name / email / password
    // =========================================================================

    public function testGetIdReturnsNullForNewUser(): void
    {
        $user = $this->makeUser();
        self::assertNull($user->getId());
    }

    public function testBindSetsIdFromArray(): void
    {
        $user = $this->makeUser();
        $data = ['id' => 42, 'username' => 'jdoe', 'name' => 'John Doe', 'email' => 'jdoe@example.com'];
        $user->bind($data);

        self::assertSame(42, $user->getId());
    }

    public function testBindSetsFieldsFromArray(): void
    {
        $user = $this->makeUser();
        $data = ['id' => 7, 'username' => 'alice', 'name' => 'Alice A.', 'email' => 'alice@example.com'];
        $user->bind($data);

        self::assertSame(7, $user->getId());
        self::assertSame('alice', $user->getUsername());
        self::assertSame('Alice A.', $user->getName());
        self::assertSame('alice@example.com', $user->getEmail());
    }

    public function testBindSetsFieldsFromObject(): void
    {
        $user = $this->makeUser();
        $obj  = (object) ['id' => 3, 'username' => 'bob', 'name' => 'Bob B.', 'email' => 'bob@example.com'];
        $user->bind($obj);

        self::assertSame(3, $user->getId());
        self::assertSame('bob', $user->getUsername());
    }

    public function testSetAndGetUsername(): void
    {
        $user = $this->makeUser();
        $user->setUsername('charlie');
        self::assertSame('charlie', $user->getUsername());
    }

    public function testSetAndGetName(): void
    {
        $user = $this->makeUser();
        $user->setName('Charlie Brown');
        self::assertSame('Charlie Brown', $user->getName());
    }

    public function testSetAndGetEmail(): void
    {
        $user = $this->makeUser();
        $user->setEmail('charlie@example.com');
        self::assertSame('charlie@example.com', $user->getEmail());
    }

    // =========================================================================
    // Parameters
    // =========================================================================

    public function testGetParametersReturnsRegistryInstance(): void
    {
        $user = $this->makeUser();
        self::assertInstanceOf(Registry::class, $user->getParameters());
    }

    public function testBindParsesJsonParameters(): void
    {
        $user = $this->makeUser();
        $data = ['id' => 1, 'parameters' => '{"foo":"bar"}'];
        $user->bind($data);

        self::assertSame('bar', $user->getParameters()->get('foo'));
    }

    public function testBindWithNoParametersCreatesEmptyRegistry(): void
    {
        $user = $this->makeUser();
        $data = ['id' => 2];
        $user->bind($data);

        self::assertInstanceOf(Registry::class, $user->getParameters());
    }

    public function testBindIgnoresPrivilegesKey(): void
    {
        // The 'privileges' key must NOT overwrite the internal $privileges array
        $user = $this->makeUser();
        $priv = $this->makePrivilege();
        $user->attachPrivilegePlugin('acl', $priv);
        $priv->setPrivilege('edit', true);

        // Binding data that has a 'privileges' key should not wipe registered plugins
        $data = ['id' => 5, 'privileges' => ['some' => 'noise']];
        $user->bind($data);

        // The privilege plugin is still accessible
        self::assertTrue($user->getPrivilege('acl.edit'));
    }

    // =========================================================================
    // Password hashing
    // =========================================================================

    public function testSetPasswordHashesPassword(): void
    {
        $user = $this->makeUser();
        $user->setPassword('secret');

        $hash = $user->getPassword();
        self::assertNotEmpty($hash);
        // Must NOT store the raw password
        self::assertNotSame('secret', $hash);
    }

    public function testSetPasswordProducesBcryptHash(): void
    {
        $user = $this->makeUser();
        $user->setPassword('hunter2');

        $hash = $user->getPassword();
        // BCrypt hashes start with $2y$ on modern PHP
        self::assertStringStartsWith('$2', $hash);
    }

    public function testGetPasswordAfterBind(): void
    {
        $user  = $this->makeUser();
        $data  = ['id' => 1, 'password' => '$2y$10$fake_hash_for_test'];
        $user->bind($data);

        self::assertSame('$2y$10$fake_hash_for_test', $user->getPassword());
    }

    // =========================================================================
    // Privilege plugin: attach / detach / get / set (via User)
    // =========================================================================

    public function testAttachPrivilegePlugin(): void
    {
        $user = $this->makeUser();
        $priv = $this->makePrivilege();
        $priv->setPrivilege('read', true);
        $user->attachPrivilegePlugin('test', $priv);

        self::assertTrue($user->getPrivilege('test.read'));
    }

    public function testDetachPrivilegePlugin(): void
    {
        $user = $this->makeUser();
        $priv = $this->makePrivilege();
        $priv->setPrivilege('read', true);
        $user->attachPrivilegePlugin('test', $priv);
        $user->detachPrivilegePlugin('test');

        // After detach, unknown plugin → returns default (false)
        self::assertFalse($user->getPrivilege('test.read'));
    }

    public function testDetachNonExistentPluginIsHarmless(): void
    {
        $user = $this->makeUser();
        // Must not throw
        $user->detachPrivilegePlugin('nonexistent');
        self::assertTrue(true);
    }

    public function testSetPrivilegeThroughUser(): void
    {
        $user = $this->makeUser();
        $priv = $this->makePrivilege();
        $user->attachPrivilegePlugin('acl', $priv);

        $user->setPrivilege('acl.write', true);

        self::assertTrue($user->getPrivilege('acl.write'));
    }

    public function testSetPrivilegeReturnsFalseForMissingDot(): void
    {
        $user = $this->makeUser();

        $result = $user->setPrivilege('nodot', true);

        self::assertFalse($result);
    }

    public function testSetPrivilegeReturnsFalseForUnknownPlugin(): void
    {
        $user = $this->makeUser();

        $result = $user->setPrivilege('unknown.priv', true);

        self::assertFalse($result);
    }

    public function testGetPrivilegeReturnsDefaultForMissingDot(): void
    {
        $user = $this->makeUser();
        self::assertFalse($user->getPrivilege('nodot'));
        self::assertTrue($user->getPrivilege('nodot', true));
    }

    public function testGetPrivilegeReturnsDefaultForUnknownPlugin(): void
    {
        $user = $this->makeUser();
        self::assertFalse($user->getPrivilege('unknown.priv'));
        self::assertTrue($user->getPrivilege('unknown.priv', true));
    }

    // =========================================================================
    // Privilege: name / setName / setUser / getPrivilegeNames
    // =========================================================================

    public function testPrivilegeSetName(): void
    {
        $priv = $this->makePrivilege();
        $priv->setName('myPrivilege');
        // setName is used internally; we can verify via attachPrivilegePlugin
        // which calls setName with the registered name — here we just ensure
        // no exception is thrown and the object is still functional.
        self::assertIsArray($priv->getPrivilegeNames());
    }

    public function testPrivilegeGetPrivilegeNames(): void
    {
        $priv = $this->makePrivilege();
        $priv->seedPrivileges(['read' => true, 'write' => false]);

        $names = $priv->getPrivilegeNames();
        sort($names);
        self::assertSame(['read', 'write'], $names);
    }

    public function testPrivilegeGetPrivilegeReturnsSetValue(): void
    {
        $priv = $this->makePrivilege();
        $priv->setPrivilege('read', true);
        $priv->setPrivilege('write', false);

        self::assertTrue($priv->getPrivilege('read'));
        self::assertFalse($priv->getPrivilege('write'));
    }

    public function testPrivilegeGetPrivilegeReturnsDefaultForUnknownKey(): void
    {
        $priv = $this->makePrivilege();

        self::assertFalse($priv->getPrivilege('unknown'));
        self::assertTrue($priv->getPrivilege('unknown2', true));
    }

    public function testPrivilegeGetPrivilegeStoresDefault(): void
    {
        // After calling getPrivilege with a default for an unknown key, that key
        // should now appear in getPrivilegeNames()
        $priv = $this->makePrivilege();
        $priv->getPrivilege('newkey', true);

        self::assertContains('newkey', $priv->getPrivilegeNames());
    }

    // =========================================================================
    // Privilege: onBeforeSave / onAfterLoad round-trip
    // =========================================================================

    public function testOnBeforeSavePersistsToUserParameters(): void
    {
        $user = $this->makeUser();
        $priv = $this->makePrivilege();
        $user->attachPrivilegePlugin('acl', $priv);
        $priv->seedPrivileges(['edit' => true, 'delete' => false]);

        $priv->onBeforeSave();

        $params = $user->getParameters();
        self::assertTrue((bool) $params->get('acl.acl.edit'));
        self::assertFalse((bool) $params->get('acl.acl.delete'));
    }

    public function testOnAfterLoadReadsFromUserParameters(): void
    {
        $user = $this->makeUser();
        $priv = $this->makePrivilege();
        $user->attachPrivilegePlugin('acl', $priv);

        // Seed privilege keys (false by default)
        $priv->seedPrivileges(['edit' => false, 'delete' => false]);

        // Pre-load parameters as if the user record were read from the DB
        $user->getParameters()->set('acl.acl.edit', true);
        $user->getParameters()->set('acl.acl.delete', true);

        $priv->onAfterLoad();

        self::assertTrue($priv->getPrivilege('edit'));
        self::assertTrue($priv->getPrivilege('delete'));
    }

    public function testOnBeforeSaveWithEmptyPrivilegesIsHarmless(): void
    {
        $user = $this->makeUser();
        $priv = $this->makePrivilege();
        $user->attachPrivilegePlugin('acl', $priv);

        // Must not throw
        $priv->onBeforeSave();

        // Parameters should still be empty
        $params = $user->getParameters();
        self::assertEmpty($params->toArray());
    }

    public function testOnAfterSaveIsHarmless(): void
    {
        $priv = $this->makePrivilege();
        // Must not throw
        $priv->onAfterSave();
        self::assertTrue(true);
    }

    public function testOnBeforeLoadIsHarmless(): void
    {
        $priv = $this->makePrivilege();
        $data = (object) ['id' => 1];
        // Must not throw
        $priv->onBeforeLoad($data);
        self::assertTrue(true);
    }

    // =========================================================================
    // Bind triggers privilege lifecycle hooks
    // =========================================================================

    public function testBindCallsOnBeforeLoadAndOnAfterLoad(): void
    {
        $user = $this->makeUser();
        $priv = $this->makePrivilege();
        $user->attachPrivilegePlugin('acl', $priv);
        $priv->seedPrivileges(['publish' => false]);

        // Set up the parameters so onAfterLoad picks them up
        $user->getParameters()->set('acl.acl.publish', true);

        // bind() must call onAfterLoad on the privilege plugin
        $data = ['id' => 99, 'parameters' => '{"acl":{"acl":{"publish":true}}}'];
        $user->bind($data);

        self::assertTrue($priv->getPrivilege('publish'));
    }

    // =========================================================================
    // triggerEvent
    // =========================================================================

    public function testTriggerEventCallsExistingMethod(): void
    {
        // We verify onAfterSave is callable via triggerEvent (it's a no-op, but
        // must not throw)
        $user = $this->makeUser();
        $priv = $this->makePrivilege();
        $user->attachPrivilegePlugin('acl', $priv);

        $user->triggerEvent('onAfterSave');
        self::assertTrue(true);
    }

    public function testTriggerEventWithNoPluginsIsHarmless(): void
    {
        $user = $this->makeUser();
        $user->triggerEvent('onAfterSave');
        self::assertTrue(true);
    }

    // =========================================================================
    // verifyPassword / triggerAuthenticationEvent
    // =========================================================================

    public function testVerifyPasswordReturnsFalseWithNoAuthPlugins(): void
    {
        $user = $this->makeUser();
        // No authentication plugins attached → triggerAuthenticationEvent returns false
        self::assertFalse($user->verifyPassword('anything'));
    }

    // =========================================================================
    // attachPrivilegePlugin sets name and user on the plugin
    // =========================================================================

    public function testAttachPrivilegePluginSetsNameOnPlugin(): void
    {
        $user = $this->makeUser();
        $priv = $this->makePrivilege();
        $user->attachPrivilegePlugin('myname', $priv);

        // After attach, onBeforeSave writes under 'acl.myname.*'
        $priv->setPrivilege('x', true);
        $priv->onBeforeSave();

        self::assertTrue((bool) $user->getParameters()->get('acl.myname.x'));
    }

    public function testAttachPrivilegePluginSetsUserOnPlugin(): void
    {
        $user = $this->makeUser();
        $priv = $this->makePrivilege();
        $user->attachPrivilegePlugin('acl', $priv);

        // onBeforeSave calls $this->user->getParameters() — if setUser wasn't
        // called this would throw. Verifying indirectly.
        $priv->setPrivilege('any', true);
        $priv->onBeforeSave();

        self::assertTrue((bool) $user->getParameters()->get('acl.acl.any'));
    }

    // =========================================================================
    // Multiple privilege plugins
    // =========================================================================

    public function testMultiplePrivilegePlugins(): void
    {
        $user  = $this->makeUser();
        $priv1 = $this->makePrivilege();
        $priv2 = $this->makePrivilege();

        $priv1->setPrivilege('read', true);
        $priv2->setPrivilege('admin', true);

        $user->attachPrivilegePlugin('content', $priv1);
        $user->attachPrivilegePlugin('backend', $priv2);

        self::assertTrue($user->getPrivilege('content.read'));
        self::assertTrue($user->getPrivilege('backend.admin'));
        self::assertFalse($user->getPrivilege('content.admin'));
        self::assertFalse($user->getPrivilege('backend.read'));
    }
}
