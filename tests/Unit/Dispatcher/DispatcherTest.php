<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Stub classes used by DispatcherTest.
//
// All stubs live under the fake \DispatcherTestApp\… namespace which is set
// as the applicationNamespace of the test Container.  Because this file mixes
// multiple namespaces it uses the bracketed namespace syntax throughout.
// ---------------------------------------------------------------------------

namespace DispatcherTestApp\Controller {

    use Awf\Mvc\Controller;

    /**
     * Spy controller that records calls and returns configurable results.
     */
    if (!class_exists('DispatcherTestApp\\Controller\\Spy', false)) {
        class Spy extends Controller
        {
            public array $callLog = [];
            public bool  $executeResult = true;

            public function default(): void
            {
                $this->callLog[] = 'default';
            }

            public function customTask(): void
            {
                $this->callLog[] = 'customTask';
            }

            public function execute($task): bool
            {
                $this->callLog[] = 'execute:' . $task;
                return $this->executeResult;
            }

            public function redirect(): bool
            {
                $this->callLog[] = 'redirect';
                return false;
            }
        }
    }

    /**
     * Minimal default controller.
     */
    if (!class_exists('DispatcherTestApp\\Controller\\DefaultController', false)) {
        class DefaultController extends Controller
        {
            public array $callLog = [];

            public function execute($task): bool
            {
                $this->callLog[] = 'execute:' . $task;
                return true;
            }

            public function redirect(): bool
            {
                return false;
            }
        }
    }

    /**
     * Minimal "main" controller (matches defaultView = 'main').
     */
    if (!class_exists('DispatcherTestApp\\Controller\\Main', false)) {
        class Main extends Controller
        {
            public array $callLog = [];

            public function execute($task): bool
            {
                $this->callLog[] = 'execute:' . $task;
                return true;
            }

            public function redirect(): bool
            {
                return false;
            }
        }
    }
}

// ---------------------------------------------------------------------------
// The actual test class
// ---------------------------------------------------------------------------

namespace Awf\Tests\Unit\Dispatcher {

    use Awf\Container\Container;
    use Awf\Dispatcher\Dispatcher;
    use Awf\Event\Dispatcher as EventDispatcher;
    use Awf\Input\Input;
    use Awf\Mvc\Controller;
    use Awf\Mvc\Factory as MvcFactory;
    use Awf\Text\Language;
    use DispatcherTestApp\Controller\DefaultController;
    use DispatcherTestApp\Controller\Main;
    use DispatcherTestApp\Controller\Spy;
    use PHPUnit\Framework\TestCase;

    /**
     * Tests for Awf\Dispatcher\Dispatcher.
     *
     * Covers:
     *   - Constructor: default view set from input or falls back to defaultView
     *   - Constructor: deprecated warning when no container provided (skipped — requires
     *     a running Application singleton which is out of scope)
     *   - dispatch(): happy path — makeController called, execute() called, redirect() called
     *   - dispatch(): onBeforeDispatch() returning false throws 403
     *   - dispatch(): onBeforeDispatch() throwing throws 403
     *   - dispatch(): execute() returning false throws 403
     *   - dispatch(): onAfterDispatch() returning false throws 403
     *   - dispatch(): default task used when task input is empty
     *   - dispatch(): view from input used for makeController
     *   - Subclass: overriding onBeforeDispatch / onAfterDispatch
     */
    class DispatcherTest extends TestCase
    {
        // -------------------------------------------------------------------------
        // Helpers
        // -------------------------------------------------------------------------

        /**
         * Build a minimal Container with a stub mvcFactory and language.
         *
         * @param  array<string,mixed>  $inputData   Initial input key→value pairs.
         * @param  Controller|null      $controller  The controller the stub factory returns.
         */
        private function makeContainer(
            array $inputData = [],
            ?Controller $controller = null
        ): Container {
            $tmpDir = sys_get_temp_dir();

            $language = $this->createMock(Language::class);
            $language->method('text')->willReturnCallback(static fn(string $k) => $k);
            $language->method('sprintf')->willReturnCallback(
                static fn(string $k, ...$args) => $k . implode(',', $args)
            );

            $ed = $this->createMock(EventDispatcher::class);
            $ed->method('trigger')->willReturn([]);

            $input = new Input($inputData);

            $application = $this->createMock(\Awf\Application\Application::class);
            $application->method('getName')->willReturn('DispatcherTestApp');
            $application->method('getTemplate')->willReturn('default');

            $container = new Container([
                'application_name'     => 'DispatcherTestApp',
                'applicationNamespace' => '\\DispatcherTestApp',
                'session_segment_name' => 'dispatchertestapp_seg',
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
            ]);

            // Replace the mvcFactory with a stub that returns $controller.
            if ($controller !== null) {
                $fixedController = $controller;
                $container['mvcFactory'] = static function () use ($fixedController): MvcFactory {
                    // Return a stub factory whose makeController always returns the spy.
                    $stub = new class($fixedController) extends MvcFactory {
                        private Controller $ctrl;

                        public function __construct(Controller $ctrl)
                        {
                            // Bypass Container requirement of real Factory
                            $this->ctrl = $ctrl;
                        }

                        public function makeController(?string $controller, ?Language $language = null): Controller
                        {
                            return $this->ctrl;
                        }
                    };
                    return $stub;
                };
            }

            return $container;
        }

        /**
         * Build a fresh Spy controller bound to a minimal Container.
         * The Container's mvcFactory will always return this very Spy.
         */
        private function makeSpyAndContainer(array $inputData = []): array
        {
            // We need the container first for the Spy constructor, but the spy
            // also needs the container to exist.  Bootstrap with a real factory,
            // then swap in the stub after both objects exist.
            $tmpDir = sys_get_temp_dir();

            $language = $this->createMock(Language::class);
            $language->method('text')->willReturnCallback(static fn(string $k) => $k);

            $ed = $this->createMock(EventDispatcher::class);
            $ed->method('trigger')->willReturn([]);

            $input = new Input($inputData);

            $application = $this->createMock(\Awf\Application\Application::class);
            $application->method('getName')->willReturn('DispatcherTestApp');

            $container = new Container([
                'application_name'     => 'DispatcherTestApp',
                'applicationNamespace' => '\\DispatcherTestApp',
                'session_segment_name' => 'dispatchertestapp_seg',
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
            ]);

            $spy = new Spy($container);

            // Swap in a stub factory that always returns $spy.
            $container['mvcFactory'] = static function () use ($spy): MvcFactory {
                return new class($spy) extends MvcFactory {
                    private Controller $ctrl;

                    public function __construct(Controller $ctrl)
                    {
                        $this->ctrl = $ctrl;
                    }

                    public function makeController(?string $controller, ?Language $language = null): Controller
                    {
                        return $this->ctrl;
                    }
                };
            };

            return [$spy, $container];
        }

        // -------------------------------------------------------------------------
        // Constructor — view/defaultView resolution
        // -------------------------------------------------------------------------

        public function testConstructorSetsViewFromInput(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer(['view' => 'items']);

            $dispatcher = new Dispatcher($container);

            // After construction the input should still carry the given view
            self::assertSame('items', $container->input->getCmd('view', ''));
        }

        public function testConstructorFallsBackToDefaultViewWhenInputEmpty(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer([]);

            $dispatcher = new Dispatcher($container);

            // The default view is 'main' — it should be written back to input
            self::assertSame('main', $container->input->getCmd('view', ''));
        }

        public function testConstructorFallsBackToDefaultViewWhenInputIsBlank(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer(['view' => '']);

            $dispatcher = new Dispatcher($container);

            self::assertSame('main', $container->input->getCmd('view', ''));
        }

        public function testDefaultViewPropertyDefaultsToMain(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer([]);

            $dispatcher = new Dispatcher($container);

            self::assertSame('main', $dispatcher->defaultView);
        }

        public function testConstructorRespectsCustomDefaultView(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer([]);

            // Subclass that overrides defaultView — must omit the type to match parent
            $dispatcher = new class($container) extends Dispatcher {
                // phpcs:ignore
                public $defaultView = 'dashboard';
            };

            self::assertSame('dashboard', $container->input->getCmd('view', ''));
        }

        // -------------------------------------------------------------------------
        // dispatch() — happy path
        // -------------------------------------------------------------------------

        public function testDispatchHappyPathCallsExecuteAndRedirect(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer(['view' => 'spy', 'task' => 'default']);

            $dispatcher = new Dispatcher($container);
            $dispatcher->dispatch();

            self::assertContains('execute:default', $spy->callLog);
            self::assertContains('redirect', $spy->callLog);
        }

        public function testDispatchUsesTaskFromInput(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer(['view' => 'spy', 'task' => 'customTask']);

            $dispatcher = new Dispatcher($container);
            $dispatcher->dispatch();

            self::assertContains('execute:customTask', $spy->callLog);
        }

        public function testDispatchDefaultsTaskToDefaultWhenInputEmpty(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer(['view' => 'spy']);

            $dispatcher = new Dispatcher($container);
            $dispatcher->dispatch();

            self::assertContains('execute:default', $spy->callLog);
        }

        public function testDispatchSetsTaskInputToDefaultWhenEmpty(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer(['view' => 'spy', 'task' => '']);

            $dispatcher = new Dispatcher($container);
            $dispatcher->dispatch();

            // task should be normalised to 'default'
            self::assertSame('default', $container->input->getCmd('task', ''));
        }

        public function testDispatchCallsRedirectAfterExecute(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer(['view' => 'spy', 'task' => 'default']);

            $dispatcher = new Dispatcher($container);
            $dispatcher->dispatch();

            $executePos = array_search('execute:default', $spy->callLog, true);
            $redirectPos = array_search('redirect', $spy->callLog, true);

            self::assertNotFalse($executePos);
            self::assertNotFalse($redirectPos);
            self::assertGreaterThan($executePos, $redirectPos, 'redirect() must come after execute()');
        }

        // -------------------------------------------------------------------------
        // dispatch() — onBeforeDispatch blocks
        // -------------------------------------------------------------------------

        public function testDispatchThrows403WhenOnBeforeDispatchReturnsFalse(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer(['view' => 'spy', 'task' => 'default']);

            $dispatcher = new class($container) extends Dispatcher {
                public function onBeforeDispatch(): bool
                {
                    return false;
                }
            };

            $this->expectException(\Exception::class);
            $this->expectExceptionCode(403);

            $dispatcher->dispatch();
        }

        public function testDispatchThrows403WhenOnBeforeDispatchThrows(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer(['view' => 'spy', 'task' => 'default']);

            $dispatcher = new class($container) extends Dispatcher {
                public function onBeforeDispatch(): bool
                {
                    throw new \RuntimeException('Access denied by hook');
                }
            };

            $this->expectException(\Exception::class);
            $this->expectExceptionCode(403);

            $dispatcher->dispatch();
        }

        public function testDispatchDoesNotCallExecuteWhenOnBeforeDispatchFails(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer(['view' => 'spy', 'task' => 'default']);

            $dispatcher = new class($container) extends Dispatcher {
                public function onBeforeDispatch(): bool
                {
                    return false;
                }
            };

            try {
                $dispatcher->dispatch();
            } catch (\Exception $e) {
                // expected
            }

            self::assertEmpty($spy->callLog, 'execute() must not be called when onBeforeDispatch() fails');
        }

        // -------------------------------------------------------------------------
        // dispatch() — controller execute() returning false
        // -------------------------------------------------------------------------

        public function testDispatchThrows403WhenExecuteReturnsFalse(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer(['view' => 'spy', 'task' => 'default']);
            $spy->executeResult = false;

            $dispatcher = new Dispatcher($container);

            $this->expectException(\Exception::class);
            $this->expectExceptionCode(403);

            $dispatcher->dispatch();
        }

        public function testDispatchDoesNotCallRedirectWhenExecuteReturnsFalse(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer(['view' => 'spy', 'task' => 'default']);
            $spy->executeResult = false;

            $dispatcher = new Dispatcher($container);

            try {
                $dispatcher->dispatch();
            } catch (\Exception $e) {
                // expected
            }

            self::assertNotContains('redirect', $spy->callLog);
        }

        // -------------------------------------------------------------------------
        // dispatch() — onAfterDispatch blocks
        // -------------------------------------------------------------------------

        public function testDispatchThrows403WhenOnAfterDispatchReturnsFalse(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer(['view' => 'spy', 'task' => 'default']);

            $dispatcher = new class($container) extends Dispatcher {
                public function onAfterDispatch(): bool
                {
                    return false;
                }
            };

            $this->expectException(\Exception::class);
            $this->expectExceptionCode(403);

            $dispatcher->dispatch();
        }

        public function testDispatchDoesNotCallRedirectWhenOnAfterDispatchFails(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer(['view' => 'spy', 'task' => 'default']);

            $dispatcher = new class($container) extends Dispatcher {
                public function onAfterDispatch(): bool
                {
                    return false;
                }
            };

            try {
                $dispatcher->dispatch();
            } catch (\Exception $e) {
                // expected
            }

            self::assertNotContains('redirect', $spy->callLog);
        }

        // -------------------------------------------------------------------------
        // Subclass hook overrides
        // -------------------------------------------------------------------------

        public function testOnBeforeDispatchDefaultReturnsTrue(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer([]);

            $dispatcher = new Dispatcher($container);

            self::assertTrue($dispatcher->onBeforeDispatch());
        }

        public function testOnAfterDispatchDefaultReturnsTrue(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer([]);

            $dispatcher = new Dispatcher($container);

            self::assertTrue($dispatcher->onAfterDispatch());
        }

        public function testSubclassCanOverrideOnBeforeDispatchToAllow(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer(['view' => 'spy', 'task' => 'default']);

            $dispatcher = new class($container) extends Dispatcher {
                public bool $beforeResult = true;

                public function onBeforeDispatch(): bool
                {
                    return $this->beforeResult;
                }
            };

            // Does not throw
            $dispatcher->dispatch();

            self::assertContains('execute:default', $spy->callLog);
        }

        public function testSubclassCanOverrideOnAfterDispatchToAllow(): void
        {
            [$spy, $container] = $this->makeSpyAndContainer(['view' => 'spy', 'task' => 'default']);

            $dispatcher = new class($container) extends Dispatcher {
                public function onAfterDispatch(): bool
                {
                    return true;
                }
            };

            // Does not throw
            $dispatcher->dispatch();

            self::assertContains('redirect', $spy->callLog);
        }

        // -------------------------------------------------------------------------
        // dispatch() — view routing through mvcFactory
        // -------------------------------------------------------------------------

        public function testDispatchPassesViewNameToMakeController(): void
        {
            $tmpDir   = sys_get_temp_dir();
            $capturedView = null;

            $language = $this->createMock(Language::class);
            $language->method('text')->willReturnCallback(static fn(string $k) => $k);

            $ed = $this->createMock(EventDispatcher::class);
            $ed->method('trigger')->willReturn([]);

            $input = new Input(['view' => 'products', 'task' => 'default']);

            $application = $this->createMock(\Awf\Application\Application::class);
            $application->method('getName')->willReturn('DispatcherTestApp');

            $container = new Container([
                'application_name'     => 'DispatcherTestApp',
                'applicationNamespace' => '\\DispatcherTestApp',
                'session_segment_name' => 'dispatchertestapp_seg',
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
            ]);

            // Build a spy controller we can attach to the container
            $spyCtrl = new Spy($container);

            $container['mvcFactory'] = static function () use ($spyCtrl, &$capturedView): MvcFactory {
                return new class($spyCtrl, $capturedView) extends MvcFactory {
                    private Controller $ctrl;
                    private mixed $capture;

                    public function __construct(Controller $ctrl, mixed &$capture)
                    {
                        $this->ctrl    = $ctrl;
                        $this->capture = &$capture;
                    }

                    public function makeController(?string $controller, ?Language $language = null): Controller
                    {
                        $this->capture = $controller;
                        return $this->ctrl;
                    }
                };
            };

            $dispatcher = new Dispatcher($container);
            $dispatcher->dispatch();

            self::assertSame('products', $capturedView);
        }
    }
}
