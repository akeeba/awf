<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Stub classes used by ApplicationTest.
//
// A concrete subclass of the abstract Application is required. We define it
// in a dedicated namespace and guard it with class_exists() so the file can
// be included multiple times without redeclaration errors.
// ---------------------------------------------------------------------------

namespace ApplicationTestStubs {

    use Awf\Application\Application;
    use Awf\Container\Container;
    use Awf\Text\Language;

    if (!class_exists('ApplicationTestStubs\\ConcreteApp', false)) {
        /**
         * Minimal concrete Application subclass used exclusively by ApplicationTest.
         */
        class ConcreteApp extends Application
        {
            /** Let tests override the name returned by getName(). */
            public ?string $forcedName = null;

            public function __construct(?Container $container = null, ?Language $languageObject = null)
            {
                parent::__construct($container, $languageObject);
            }

            public function getName(): string
            {
                if ($this->forcedName !== null) {
                    return $this->forcedName;
                }

                return parent::getName();
            }

            public function initialise(): void
            {
                // no-op for tests
            }
        }
    }
}

// ---------------------------------------------------------------------------
// The actual test class
// ---------------------------------------------------------------------------

namespace Awf\Tests\Unit\Application {

    use ApplicationTestStubs\ConcreteApp;
    use Awf\Application\Application;
    use Awf\Container\Container;
    use Awf\Dispatcher\Dispatcher;
    use Awf\Input\Input;
    use Awf\Router\Router;
    use Awf\Session\Manager as SessionManager;
    use Awf\Session\Segment;
    use Awf\Text\Language;
    use PHPUnit\Framework\Attributes\CoversClass;
    use PHPUnit\Framework\TestCase;

    #[CoversClass(Application::class)]
    class ApplicationTest extends TestCase
    {
        // -----------------------------------------------------------------------
        // Infrastructure
        // -----------------------------------------------------------------------

        private string $tmpDir = '';

        protected function setUp(): void
        {
            $this->tmpDir = sys_get_temp_dir() . '/awf_app_test_' . uniqid('', true);
            mkdir($this->tmpDir, 0755, true);

            // Reset the static instances registry before every test.
            $this->clearStaticInstances();
        }

        protected function tearDown(): void
        {
            // Clean up static state so tests don't bleed into one another.
            $this->clearStaticInstances();

            // Destroy the temp directory.
            $this->rmdirRecursive($this->tmpDir);
        }

        private function clearStaticInstances(): void
        {
            // Application::$instances is protected static — reset it via Closure.
            $reset = static function (): void {
                self::$instances = [];
            };
            \Closure::bind($reset, null, Application::class)();
        }

        private function rmdirRecursive(string $dir): void
        {
            if (!is_dir($dir)) {
                return;
            }
            foreach (scandir($dir) as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $path = $dir . DIRECTORY_SEPARATOR . $entry;
                is_dir($path) ? $this->rmdirRecursive($path) : unlink($path);
            }
            rmdir($dir);
        }

        /**
         * Build a Container with stub session/segment/language so constructing a
         * ConcreteApp doesn't hit real I/O.
         *
         * @param  array<string,mixed>  $extra  Extra values to merge into the container.
         */
        private function makeContainer(array $extra = []): Container
        {
            $language = $this->createMock(Language::class);
            $language->method('text')->willReturnCallback(static fn(string $k) => $k);

            $session = $this->createMock(SessionManager::class);
            $session->method('start')->willReturn(true);
            // commit() returns void — no ->willReturn() needed.

            $segment = $this->createMock(Segment::class);
            $segment->method('hasFlash')->willReturn(false);
            $segment->method('getFlash')->willReturn([]);
            // setFlash() returns void — no ->willReturn() needed.

            $base = [
                'application_name'     => 'TestApp',
                'applicationNamespace' => '\\TestApp',
                'session_segment_name' => 'testapp_seg',
                'basePath'             => $this->tmpDir,
                'languagePath'         => $this->tmpDir,
                'temporaryPath'        => $this->tmpDir,
                'templatePath'         => $this->tmpDir,
                'filesystemBase'       => $this->tmpDir,
                'language'             => $language,
                'session'              => $session,
                'segment'              => $segment,
            ];

            return new Container(array_merge($base, $extra));
        }

        /**
         * Instantiate a ConcreteApp with a given Container (and optional
         * Language override).
         */
        private function makeApp(?Container $container = null, ?Language $language = null): ConcreteApp
        {
            $container ??= $this->makeContainer();

            return new ConcreteApp($container, $language);
        }

        // -----------------------------------------------------------------------
        // Construction
        // -----------------------------------------------------------------------

        public function testConstructorSetsContainerOnInstance(): void
        {
            $container = $this->makeContainer();
            $app       = $this->makeApp($container);

            self::assertSame($container, $app->getContainer());
        }

        public function testConstructorSetsApplicationNameFromContainerKey(): void
        {
            $container = $this->makeContainer(['application_name' => 'MyApp']);
            $app       = $this->makeApp($container);

            self::assertSame('MyApp', $app->getName());
        }

        public function testConstructorRegistersInstanceInStaticRegistry(): void
        {
            $container = $this->makeContainer(['application_name' => 'MyApp']);
            $app       = $this->makeApp($container);

            // The static instances array should now contain the app keyed by name.
            $read = static function (): array {
                return self::$instances;
            };
            $instances = \Closure::bind($read, null, Application::class)();

            self::assertArrayHasKey('MyApp', $instances);
            self::assertSame($app, $instances['MyApp']);
        }

        public function testConstructorSetsTemplate(): void
        {
            $app = $this->makeApp();

            // Default template falls back to the application name when no matching dir exists.
            self::assertNotEmpty($app->getTemplate());
        }

        public function testConstructorSetsLanguage(): void
        {
            $language  = $this->createMock(Language::class);
            $container = $this->makeContainer(['language' => $language]);
            $app       = $this->makeApp($container, $language);

            self::assertSame($language, $app->getLanguage());
        }

        public function testStartTimeIsPositive(): void
        {
            $app = $this->makeApp();

            self::assertGreaterThan(0.0, $app->getTimeElapsed());
        }

        // -----------------------------------------------------------------------
        // getName()
        // -----------------------------------------------------------------------

        public function testGetNameReturnsNameSetInContainer(): void
        {
            $container = $this->makeContainer(['application_name' => 'Awesome']);
            $app       = $this->makeApp($container);

            self::assertSame('Awesome', $app->getName());
        }

        public function testGetNameIsCachedAfterFirstCall(): void
        {
            $app  = $this->makeApp();
            $name = $app->getName();

            self::assertSame($name, $app->getName());
        }

        // -----------------------------------------------------------------------
        // getContainer()
        // -----------------------------------------------------------------------

        public function testGetContainerReturnsSameContainerPassedToConstructor(): void
        {
            $container = $this->makeContainer();
            $app       = $this->makeApp($container);

            self::assertSame($container, $app->getContainer());
        }

        // -----------------------------------------------------------------------
        // getTemplate() / setTemplate()
        // -----------------------------------------------------------------------

        public function testGetTemplateReturnsDefaultWhenNoTemplateDirExists(): void
        {
            $app = $this->makeApp();

            // Falls back to getName() when the templatePath/<template> dir doesn't exist.
            self::assertSame($app->getName(), $app->getTemplate());
        }

        public function testSetTemplateUsesGivenNameWhenDirectoryExists(): void
        {
            $templateName = 'mytheme';
            $templateDir  = $this->tmpDir . '/' . $templateName;
            mkdir($templateDir, 0755, true);

            $container = $this->makeContainer(['templatePath' => $this->tmpDir]);
            $app       = $this->makeApp($container);

            $app->setTemplate($templateName);

            self::assertSame($templateName, $app->getTemplate());
        }

        public function testSetTemplateWithNonExistingDirectoryFallsBackToAppName(): void
        {
            $app = $this->makeApp();
            $app->setTemplate('nonexistent_theme');

            self::assertSame($app->getName(), $app->getTemplate());
        }

        public function testSetTemplateWithNullFallsBackToAppName(): void
        {
            $app = $this->makeApp();
            $app->setTemplate(null);

            self::assertSame($app->getName(), $app->getTemplate());
        }

        public function testSetTemplateWithEmptyStringFallsBackToAppName(): void
        {
            $app = $this->makeApp();
            $app->setTemplate('');

            self::assertSame($app->getName(), $app->getTemplate());
        }

        // -----------------------------------------------------------------------
        // getInstance() — deprecated static helper
        // -----------------------------------------------------------------------

        public function testGetInstanceReturnsPreviouslyRegisteredInstance(): void
        {
            $container = $this->makeContainer(['application_name' => 'TestGetInst']);
            $app       = $this->makeApp($container);

            $retrieved = @Application::getInstance('TestGetInst');

            self::assertSame($app, $retrieved);
        }

        public function testGetInstanceTriggersDeprecationWarning(): void
        {
            $container = $this->makeContainer(['application_name' => 'DeprecApp']);
            $this->makeApp($container);

            $triggered = false;

            set_error_handler(
                static function (int $errno, string $errstr) use (&$triggered): bool {
                    if ($errno === E_USER_DEPRECATED) {
                        $triggered = true;
                    }
                    return true;
                }
            );

            try {
                Application::getInstance('DeprecApp');
            } finally {
                restore_error_handler();
            }

            self::assertTrue($triggered, 'getInstance() must trigger E_USER_DEPRECATED');
        }

        public function testGetInstanceWithContainerAndNameRegistersAndReturns(): void
        {
            $container = $this->makeContainer(['application_name' => 'FromContainer']);
            $app       = $this->makeApp($container);

            // The app already registered itself; retrieve with an explicit container.
            $retrieved = @Application::getInstance('FromContainer', $container);

            self::assertSame($app, $retrieved);
        }

        public function testGetInstanceWithNoArgsReturnsFirstRegisteredInstance(): void
        {
            $container = $this->makeContainer(['application_name' => 'FirstApp']);
            $app       = $this->makeApp($container);

            $retrieved = @Application::getInstance();

            self::assertSame($app, $retrieved);
        }

        public function testGetInstanceThrowsForUnknownNameWithNoContainer(): void
        {
            $this->expectException(\Awf\Exception\App::class);

            @Application::getInstance('DoesNotExist');
        }

        public function testGetInstanceThrowsWhenNoInstancesRegisteredAtAll(): void
        {
            // Ensure registry is empty.
            $this->clearStaticInstances();

            $this->expectException(\Awf\Exception\App::class);

            @Application::getInstance();
        }

        // -----------------------------------------------------------------------
        // setInstance()
        // -----------------------------------------------------------------------

        public function testSetInstanceRegistersAGivenApplication(): void
        {
            $container = $this->makeContainer(['application_name' => 'SetInstApp']);
            $app       = $this->makeApp($container);

            // Create a second app, overwrite the entry.
            $container2 = $this->makeContainer(['application_name' => 'Other']);
            $app2       = $this->makeApp($container2);

            @Application::setInstance('SetInstApp', $app2);

            $retrieved = @Application::getInstance('SetInstApp');
            self::assertSame($app2, $retrieved);
        }

        // -----------------------------------------------------------------------
        // route()
        // -----------------------------------------------------------------------

        public function testRouteCallsRouterParse(): void
        {
            $router = $this->createMock(Router::class);
            $router->expects(self::once())
                ->method('parse')
                ->with(null);

            $container = $this->makeContainer(['router' => $router]);
            $app       = $this->makeApp($container);

            $app->route();
        }

        public function testRoutePassesUrlToRouterParse(): void
        {
            $url    = 'https://example.com/index.php?view=test';
            $router = $this->createMock(Router::class);
            $router->expects(self::once())
                ->method('parse')
                ->with($url);

            $container = $this->makeContainer(['router' => $router]);
            $app       = $this->makeApp($container);

            $app->route($url);
        }

        // -----------------------------------------------------------------------
        // dispatch()
        // -----------------------------------------------------------------------

        public function testDispatchCallsDispatcherDispatch(): void
        {
            $dispatcher = $this->createMock(Dispatcher::class);
            $dispatcher->expects(self::once())->method('dispatch');

            $input = new Input(['format' => 'raw']);

            $container = $this->makeContainer([
                'dispatcher' => $dispatcher,
                'input'      => $input,
            ]);
            $app = $this->makeApp($container);

            // The Document constructor triggers MenuManager which fetches $container->application.
            // Register the app we just built so the container returns it directly.
            $container['application'] = static fn() => $app;

            $app->dispatch();
        }

        // -----------------------------------------------------------------------
        // Message queue
        // -----------------------------------------------------------------------

        public function testEnqueueMessageAddsToQueue(): void
        {
            $app = $this->makeApp();
            $app->enqueueMessage('Hello', 'info');

            self::assertCount(1, $app->getMessageQueue());
            self::assertSame('Hello', $app->getMessageQueue()[0]['message']);
            self::assertSame('info', $app->getMessageQueue()[0]['type']);
        }

        public function testEnqueueMessageNormalisesTypeToLowerCase(): void
        {
            $app = $this->makeApp();
            $app->enqueueMessage('Oops', 'Error');

            self::assertSame('error', $app->getMessageQueue()[0]['type']);
        }

        public function testEnqueueMessageDefaultTypeIsInfo(): void
        {
            $app = $this->makeApp();
            $app->enqueueMessage('Default type');

            self::assertSame('info', $app->getMessageQueue()[0]['type']);
        }

        public function testGetMessageQueueForReturnsOnlyMatchingType(): void
        {
            $app = $this->makeApp();
            $app->enqueueMessage('Info msg', 'info');
            $app->enqueueMessage('Error msg', 'error');
            $app->enqueueMessage('Another info', 'info');

            $infoMessages  = $app->getMessageQueueFor('info');
            $errorMessages = $app->getMessageQueueFor('error');

            self::assertCount(2, $infoMessages);
            self::assertContains('Info msg', $infoMessages);
            self::assertContains('Another info', $infoMessages);
            self::assertCount(1, $errorMessages);
            self::assertContains('Error msg', $errorMessages);
        }

        public function testGetMessageQueueForReturnsEmptyArrayWhenNoneMatch(): void
        {
            $app = $this->makeApp();
            $app->enqueueMessage('Hello', 'info');

            self::assertSame([], $app->getMessageQueueFor('warning'));
        }

        public function testClearMessageQueueEmptiesTheQueue(): void
        {
            $app = $this->makeApp();
            $app->enqueueMessage('msg1');
            $app->enqueueMessage('msg2');

            $app->clearMessageQueue();

            self::assertSame([], $app->getMessageQueue());
        }

        public function testGetMessageQueueLoadsFromSessionWhenEmpty(): void
        {
            $flashMessages = [
                ['message' => 'Flash msg', 'type' => 'info'],
            ];

            $segment = $this->createMock(Segment::class);
            $segment->method('hasFlash')->willReturn(true);
            $segment->method('getFlash')->willReturn($flashMessages);
            // setFlash() returns void — no ->willReturn() needed.

            $container = $this->makeContainer(['segment' => $segment]);
            $app       = $this->makeApp($container);

            $queue = $app->getMessageQueue();

            self::assertCount(1, $queue);
            self::assertSame('Flash msg', $queue[0]['message']);
        }

        // -----------------------------------------------------------------------
        // getTimeElapsed()
        // -----------------------------------------------------------------------

        public function testGetTimeElapsedReturnsFloat(): void
        {
            $app = $this->makeApp();

            self::assertIsFloat($app->getTimeElapsed());
        }

        public function testGetTimeElapsedIsPositive(): void
        {
            $app = $this->makeApp();

            self::assertGreaterThan(0.0, $app->getTimeElapsed());
        }

        // -----------------------------------------------------------------------
        // processLanguageIniFile()
        // -----------------------------------------------------------------------

        public function testProcessLanguageIniFileReturnsTrue(): void
        {
            $app    = $this->makeApp();
            $result = $app->processLanguageIniFile('/some/file.ini', ['KEY' => 'Value']);

            self::assertTrue($result);
        }
    }
}
