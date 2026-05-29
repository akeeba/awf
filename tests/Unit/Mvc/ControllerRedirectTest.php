<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc;

// Stub MVC classes live under the fake \CtrlRedirectApp\… namespace.
// The file uses bracketed namespace syntax (required when mixing namespaces),
// which cannot be combined with an unbracketed declaration in the same file.
require_once __DIR__ . '/Fixtures/ControllerRedirectStubs.php';

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Mvc\Controller;
use Awf\Mvc\Model;
use Awf\Mvc\View;
use Awf\Session\CsrfToken;
use Awf\Session\Manager as SessionManager;
use Awf\Session\Segment;
use Awf\Text\Language;
use CtrlRedirectApp\Controller\CsrfExposed;
use CtrlRedirectApp\Controller\Stub;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Awf\Mvc\Controller — setRedirect, getRedirect, message accumulation,
 * getView / getModel lazy creation, and CSRF protection.
 *
 * Covers:
 *   - setRedirect() sets URL, message, and message-type
 *   - setMessage() sets message and type; returns the previous message
 *   - redirect() calls Application::redirect() when a URL is set, returns true
 *   - redirect() returns false when no URL is set
 *   - setRedirect() chaining (returns $this)
 *   - Message-type defaulting logic (empty vs. provided)
 *   - getModel() returns a cached instance on repeated calls
 *   - getView() returns a cached instance on repeated calls
 *   - setModel() / setView() inject instances that getModel() / getView() return
 *   - setModelName() / setViewName() override the default name resolution
 *   - csrfProtection() passes when token matches session token
 *   - csrfProtection() passes when alt-token (field named after the value) == 1
 *   - csrfProtection() throws when neither token form is valid
 */
class ControllerRedirectTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a minimal Container that avoids filesystem/DB/session access.
     *
     * @param array<string,mixed> $inputData   Seed data for the Input stub.
     * @param string              $tokenValue  The CSRF token value to expose.
     * @param bool                $insideCMS   Value for the insideCMS segment key.
     */
    private function makeContainer(
        array  $inputData  = [],
        string $tokenValue = 'test-token-abc',
        bool   $insideCMS  = false
    ): Container {
        $tmpDir = sys_get_temp_dir();

        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturn([]);

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);
        $language->method('sprintf')->willReturnCallback(
            static fn(string $k, ...$args) => $k . implode(',', $args)
        );

        $input = new Input($inputData);

        // Mock application — records redirect calls instead of sending headers.
        $application = $this->createMock(Application::class);
        $application->method('getName')->willReturn('CtrlRedirectApp');
        $application->method('getTemplate')->willReturn('default');

        // Session segment stub (supports get($key, $default)).
        $segment = $this->createMock(Segment::class);
        $segment->method('get')->willReturnCallback(
            static function (string $key, mixed $default = null) use ($insideCMS) {
                if ($key === 'insideCMS') {
                    return $insideCMS;
                }
                return $default;
            }
        );

        // CSRF token stub.
        $csrfToken = $this->createMock(CsrfToken::class);
        $csrfToken->method('getValue')->willReturn($tokenValue);

        // Session manager stub — returns our CSRF token stub.
        $session = $this->createMock(SessionManager::class);
        $session->method('getCsrfToken')->willReturn($csrfToken);

        return new Container([
            'application_name'     => 'CtrlRedirectApp',
            'applicationNamespace' => '\\CtrlRedirectApp',
            'session_segment_name' => 'ctrlredirectapp_seg',
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
            'session'              => $session,
        ]);
    }

    /** Instantiate the default Stub controller. */
    private function makeController(
        array  $inputData  = [],
        string $tokenValue = 'test-token-abc',
        bool   $insideCMS  = false
    ): Stub {
        return new Stub($this->makeContainer($inputData, $tokenValue, $insideCMS));
    }

    /** Instantiate the CSRF-exposed controller variant. */
    private function makeCsrfController(
        array  $inputData  = [],
        string $tokenValue = 'test-token-abc',
        bool   $insideCMS  = false
    ): CsrfExposed {
        return new CsrfExposed($this->makeContainer($inputData, $tokenValue, $insideCMS));
    }

    // -------------------------------------------------------------------------
    // setRedirect() — basic URL storage
    // -------------------------------------------------------------------------

    public function testSetRedirectStoresUrl(): void
    {
        $ctrl = $this->makeController();
        $ctrl->setRedirect('https://example.com/target');

        // redirect() must return true when a URL is set, confirming it was stored.
        // We capture what the application mock receives by expecting the call.
        $container = $this->makeContainer();
        $application = $container['application'];

        // Build a separate controller and spy on the application mock.
        $redirectApp = $this->createMock(Application::class);
        $redirectApp->method('getName')->willReturn('CtrlRedirectApp');
        $redirectApp->method('getTemplate')->willReturn('default');

        $capturedUrl = null;
        $redirectApp->expects($this->once())
            ->method('redirect')
            ->willReturnCallback(static function (string $url) use (&$capturedUrl): void {
                $capturedUrl = $url;
            });

        $container['application'] = $redirectApp;
        $ctrl2 = new Stub($container);
        $ctrl2->setRedirect('https://example.com/target');
        $ctrl2->redirect();

        self::assertSame('https://example.com/target', $capturedUrl);
    }

    public function testSetRedirectReturnsControllerForChaining(): void
    {
        $ctrl   = $this->makeController();
        $result = $ctrl->setRedirect('https://example.com');

        self::assertSame($ctrl, $result);
    }

    // -------------------------------------------------------------------------
    // setRedirect() — message and message-type
    // -------------------------------------------------------------------------

    public function testSetRedirectWithMessageStoresMessage(): void
    {
        $container   = $this->makeContainer();
        $redirectApp = $this->createMock(Application::class);
        $redirectApp->method('getName')->willReturn('CtrlRedirectApp');
        $redirectApp->method('getTemplate')->willReturn('default');

        $capturedMsg  = null;
        $capturedType = null;
        $redirectApp->expects($this->once())
            ->method('redirect')
            ->willReturnCallback(
                static function (string $url, string $msg, string $type) use (&$capturedMsg, &$capturedType): void {
                    $capturedMsg  = $msg;
                    $capturedType = $type;
                }
            );

        $container['application'] = $redirectApp;
        $ctrl = new Stub($container);
        $ctrl->setRedirect('https://example.com', 'Saved successfully.', 'success');
        $ctrl->redirect();

        self::assertSame('Saved successfully.', $capturedMsg);
        self::assertSame('success', $capturedType);
    }

    public function testSetRedirectWithNullMessageDoesNotOverwriteExistingMessage(): void
    {
        $container   = $this->makeContainer();
        $redirectApp = $this->createMock(Application::class);
        $redirectApp->method('getName')->willReturn('CtrlRedirectApp');
        $redirectApp->method('getTemplate')->willReturn('default');

        $capturedMsg = null;
        $redirectApp->expects($this->once())
            ->method('redirect')
            ->willReturnCallback(
                static function (string $url, string $msg) use (&$capturedMsg): void {
                    $capturedMsg = $msg;
                }
            );

        $container['application'] = $redirectApp;
        $ctrl = new Stub($container);
        // Set a message first via setMessage(), then call setRedirect() without a message.
        $ctrl->setMessage('Earlier message', 'warning');
        $ctrl->setRedirect('https://example.com', null);
        $ctrl->redirect();

        self::assertSame('Earlier message', $capturedMsg);
    }

    public function testSetRedirectWithExplicitTypeOverridesDefault(): void
    {
        $container   = $this->makeContainer();
        $redirectApp = $this->createMock(Application::class);
        $redirectApp->method('getName')->willReturn('CtrlRedirectApp');
        $redirectApp->method('getTemplate')->willReturn('default');

        $capturedType = null;
        $redirectApp->expects($this->once())
            ->method('redirect')
            ->willReturnCallback(
                static function (string $url, string $msg, string $type) use (&$capturedType): void {
                    $capturedType = $type;
                }
            );

        $container['application'] = $redirectApp;
        $ctrl = new Stub($container);
        $ctrl->setRedirect('https://example.com', 'msg', 'error');
        $ctrl->redirect();

        self::assertSame('error', $capturedType);
    }

    public function testSetRedirectWithoutTypeDefaultsToInfo(): void
    {
        $container   = $this->makeContainer();
        $redirectApp = $this->createMock(Application::class);
        $redirectApp->method('getName')->willReturn('CtrlRedirectApp');
        $redirectApp->method('getTemplate')->willReturn('default');

        $capturedType = null;
        $redirectApp->expects($this->once())
            ->method('redirect')
            ->willReturnCallback(
                static function (string $url, ?string $msg, string $type) use (&$capturedType): void {
                    $capturedType = $type;
                }
            );

        $container['application'] = $redirectApp;
        $ctrl = new Stub($container);
        // No type given; messageType starts as 'info' after construction.
        $ctrl->setRedirect('https://example.com');
        $ctrl->redirect();

        self::assertSame('info', $capturedType);
    }

    // -------------------------------------------------------------------------
    // redirect() — return values
    // -------------------------------------------------------------------------

    public function testRedirectReturnsTrueWhenUrlIsSet(): void
    {
        $container   = $this->makeContainer();
        $redirectApp = $this->createMock(Application::class);
        $redirectApp->method('getName')->willReturn('CtrlRedirectApp');
        $redirectApp->method('getTemplate')->willReturn('default');
        $redirectApp->method('redirect')->willReturn(null);
        $container['application'] = $redirectApp;

        $ctrl = new Stub($container);
        $ctrl->setRedirect('https://example.com');

        self::assertTrue($ctrl->redirect());
    }

    public function testRedirectReturnsFalseWhenNoUrlIsSet(): void
    {
        $ctrl = $this->makeController();
        // No setRedirect() called — redirect should return false.
        self::assertFalse($ctrl->redirect());
    }

    // -------------------------------------------------------------------------
    // setMessage() — message accumulation
    // -------------------------------------------------------------------------

    public function testSetMessageStoresMessageAndType(): void
    {
        $container   = $this->makeContainer();
        $redirectApp = $this->createMock(Application::class);
        $redirectApp->method('getName')->willReturn('CtrlRedirectApp');
        $redirectApp->method('getTemplate')->willReturn('default');

        $capturedMsg  = null;
        $capturedType = null;
        $redirectApp->expects($this->once())
            ->method('redirect')
            ->willReturnCallback(
                static function (string $url, string $msg, string $type) use (&$capturedMsg, &$capturedType): void {
                    $capturedMsg  = $msg;
                    $capturedType = $type;
                }
            );
        $container['application'] = $redirectApp;

        $ctrl = new Stub($container);
        $ctrl->setMessage('Hello world', 'warning');
        $ctrl->setRedirect('https://example.com');
        $ctrl->redirect();

        self::assertSame('Hello world', $capturedMsg);
        self::assertSame('warning', $capturedType);
    }

    public function testSetMessageReturnsPreviousMessage(): void
    {
        $ctrl = $this->makeController();
        $ctrl->setMessage('First message', 'info');

        $previous = $ctrl->setMessage('Second message', 'error');

        self::assertSame('First message', $previous);
    }

    public function testSetMessageReturnsNullWhenNoPreviousMessage(): void
    {
        $ctrl = $this->makeController();
        // No previous message set — constructor initialises $message to null.
        $previous = $ctrl->setMessage('First message', 'info');

        self::assertNull($previous);
    }

    public function testSetMessageDefaultTypeIsMessage(): void
    {
        $container   = $this->makeContainer();
        $redirectApp = $this->createMock(Application::class);
        $redirectApp->method('getName')->willReturn('CtrlRedirectApp');
        $redirectApp->method('getTemplate')->willReturn('default');

        $capturedType = null;
        $redirectApp->expects($this->once())
            ->method('redirect')
            ->willReturnCallback(
                static function (string $url, string $msg, string $type) use (&$capturedType): void {
                    $capturedType = $type;
                }
            );
        $container['application'] = $redirectApp;

        $ctrl = new Stub($container);
        $ctrl->setMessage('Hello');           // no type → defaults to 'message'
        $ctrl->setRedirect('https://example.com');
        $ctrl->redirect();

        self::assertSame('message', $capturedType);
    }

    // -------------------------------------------------------------------------
    // setMessage() + setRedirect() interaction — last-write-wins for type
    // -------------------------------------------------------------------------

    public function testSetRedirectTypeOverridesEarlierSetMessageType(): void
    {
        $container   = $this->makeContainer();
        $redirectApp = $this->createMock(Application::class);
        $redirectApp->method('getName')->willReturn('CtrlRedirectApp');
        $redirectApp->method('getTemplate')->willReturn('default');

        $capturedType = null;
        $redirectApp->expects($this->once())
            ->method('redirect')
            ->willReturnCallback(
                static function (string $url, string $msg, string $type) use (&$capturedType): void {
                    $capturedType = $type;
                }
            );
        $container['application'] = $redirectApp;

        $ctrl = new Stub($container);
        $ctrl->setMessage('Msg', 'warning');          // sets messageType = 'warning'
        $ctrl->setRedirect('https://example.com', null, 'success');  // overrides to 'success'
        $ctrl->redirect();

        self::assertSame('success', $capturedType);
    }

    // -------------------------------------------------------------------------
    // getModel() — lazy creation and caching
    // -------------------------------------------------------------------------

    public function testGetModelReturnsSameInstanceOnRepeatedCalls(): void
    {
        $container  = $this->makeContainer();
        $mockModel  = $this->createMock(Model::class);
        $ctrl       = new Stub($container);

        // Inject a known model and retrieve it twice.
        $ctrl->setModel('stub', $mockModel);

        $first  = $ctrl->getModel('stub');
        $second = $ctrl->getModel('stub');

        self::assertSame($first, $second);
        self::assertSame($mockModel, $first);
    }

    public function testSetModelMakesModelAvailableViaGetModel(): void
    {
        $ctrl  = $this->makeController();
        $model = $this->createMock(Model::class);

        $ctrl->setModel('mymodel', $model);

        self::assertSame($model, $ctrl->getModel('mymodel'));
    }

    // -------------------------------------------------------------------------
    // getView() — lazy creation and caching
    // -------------------------------------------------------------------------

    public function testGetViewReturnsSameInstanceOnRepeatedCalls(): void
    {
        $ctrl      = $this->makeController();
        $mockView  = $this->createMock(View::class);

        $ctrl->setView('stub', $mockView);

        $first  = $ctrl->getView('stub');
        $second = $ctrl->getView('stub');

        self::assertSame($first, $second);
        self::assertSame($mockView, $first);
    }

    public function testSetViewMakesViewAvailableViaGetView(): void
    {
        $ctrl = $this->makeController();
        $view = $this->createMock(View::class);

        $ctrl->setView('myview', $view);

        self::assertSame($view, $ctrl->getView('myview'));
    }

    // -------------------------------------------------------------------------
    // setModelName() / setViewName() — name overrides
    // -------------------------------------------------------------------------

    public function testSetModelNameAffectsDefaultModelResolution(): void
    {
        $ctrl  = $this->makeController();
        $model = $this->createMock(Model::class);

        $ctrl->setModelName('custom');
        $ctrl->setModel('custom', $model);

        // With no $name argument getModel() should use the overridden modelName.
        self::assertSame($model, $ctrl->getModel());
    }

    public function testSetViewNameAffectsDefaultViewResolution(): void
    {
        $ctrl = $this->makeController();
        $view = $this->createMock(View::class);

        $ctrl->setViewName('custom');
        $ctrl->setView('custom', $view);

        // With no $name argument getView() should use the overridden viewName.
        self::assertSame($view, $ctrl->getView());
    }

    // -------------------------------------------------------------------------
    // csrfProtection() — valid token (exact match)
    // -------------------------------------------------------------------------

    public function testCsrfProtectionPassesWhenTokenMatchesSessionValue(): void
    {
        $ctrl = $this->makeCsrfController(
            ['token' => 'test-token-abc'],
            'test-token-abc'
        );

        // Should not throw.
        $ctrl->checkCsrf();
        $this->addToAssertionCount(1);  // explicit proof that we reached this line
    }

    // -------------------------------------------------------------------------
    // csrfProtection() — valid token (alt form: field named after the value = 1)
    // -------------------------------------------------------------------------

    public function testCsrfProtectionPassesWhenAltTokenFieldIsOne(): void
    {
        // The alt-token form: input field whose *name* is the token value, set to 1.
        $tokenValue = 'my-csrf-value';
        $ctrl       = $this->makeCsrfController(
            [$tokenValue => '1'],   // field named after the token value = 1
            $tokenValue
        );

        // Should not throw.
        $ctrl->checkCsrf();
        $this->addToAssertionCount(1);
    }

    // -------------------------------------------------------------------------
    // csrfProtection() — invalid token throws Exception
    // -------------------------------------------------------------------------

    public function testCsrfProtectionThrowsWhenTokenIsWrong(): void
    {
        $ctrl = $this->makeCsrfController(
            ['token' => 'wrong-token'],
            'test-token-abc'
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid security token');

        $ctrl->checkCsrf();
    }

    public function testCsrfProtectionThrowsWhenNoTokenProvided(): void
    {
        $ctrl = $this->makeCsrfController(
            [],          // no token in input
            'test-token-abc'
        );

        $this->expectException(Exception::class);
        $ctrl->checkCsrf();
    }

    public function testCsrfProtectionThrowsWhenAltTokenFieldIsZero(): void
    {
        $tokenValue = 'my-csrf-value';
        $ctrl       = $this->makeCsrfController(
            [$tokenValue => '0'],   // alt field present but value is 0, not 1
            $tokenValue
        );

        $this->expectException(Exception::class);
        $ctrl->checkCsrf();
    }

    // -------------------------------------------------------------------------
    // redirect() + setRedirect() — empty string URL is falsy (no redirect)
    // -------------------------------------------------------------------------

    public function testRedirectReturnsFalseForEmptyStringUrl(): void
    {
        $ctrl = $this->makeController();
        $ctrl->setRedirect('');  // empty string → falsy → no redirect

        self::assertFalse($ctrl->redirect());
    }

    // -------------------------------------------------------------------------
    // Multiple setRedirect calls — last one wins
    // -------------------------------------------------------------------------

    public function testLastSetRedirectWins(): void
    {
        $container   = $this->makeContainer();
        $redirectApp = $this->createMock(Application::class);
        $redirectApp->method('getName')->willReturn('CtrlRedirectApp');
        $redirectApp->method('getTemplate')->willReturn('default');

        $capturedUrl = null;
        $redirectApp->expects($this->once())
            ->method('redirect')
            ->willReturnCallback(static function (string $url) use (&$capturedUrl): void {
                $capturedUrl = $url;
            });
        $container['application'] = $redirectApp;

        $ctrl = new Stub($container);
        $ctrl->setRedirect('https://example.com/first');
        $ctrl->setRedirect('https://example.com/second');
        $ctrl->redirect();

        self::assertSame('https://example.com/second', $capturedUrl);
    }
}
