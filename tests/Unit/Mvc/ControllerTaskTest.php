<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc;

// Stub MVC classes live under the fake \CtrlTaskApp\… namespace.
// The file uses bracketed namespace syntax (required when mixing namespaces),
// which cannot be combined with an unbracketed declaration in the same file.
require_once __DIR__ . '/Fixtures/ControllerTaskStubs.php';

use Awf\Container\Container;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Mvc\Controller;
use Awf\Text\Language;
use CtrlTaskApp\Controller\Defaultable;
use CtrlTaskApp\Controller\Plain;
use CtrlTaskApp\Controller\Spy;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Awf\Mvc\Controller — execute() task dispatch and hook chain.
 *
 * Covers:
 *   - Full onBeforeExecute → onBefore{Task} → task → onAfter{Task} → onAfterExecute chain
 *   - Task-to-method mapping via registerTask / unregisterTask
 *   - Default-task fallback (__default)
 *   - Abort semantics when any hook returns false
 *   - Event-dispatcher lifecycle events
 *   - Missing-task exception
 */
class ControllerTaskTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a minimal Container that avoids filesystem/DB/session access.
     */
    private function makeContainer(string $appName = 'CtrlTaskApp'): Container
    {
        $tmpDir = sys_get_temp_dir();

        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturn([]);

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);
        $language->method('sprintf')->willReturnCallback(
            static fn(string $k, ...$args) => $k . implode(',', $args)
        );

        $input = new Input([]);

        $application = $this->createMock(\Awf\Application\Application::class);
        $application->method('getName')->willReturn($appName);
        $application->method('getTemplate')->willReturn('default');

        return new Container([
            'application_name'     => $appName,
            'applicationNamespace' => '\\' . $appName,
            'session_segment_name' => strtolower($appName) . '_seg',
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
    }

    private function makeSpyController(): Spy
    {
        return new Spy($this->makeContainer());
    }

    private function makePlainController(): Plain
    {
        return new Plain($this->makeContainer());
    }

    private function makeDefaultableController(): Defaultable
    {
        return new Defaultable($this->makeContainer());
    }

    // -------------------------------------------------------------------------
    // execute() — happy-path hook chain order
    // -------------------------------------------------------------------------

    public function testExecuteInvokesFullHookChainInOrder(): void
    {
        $ctrl = $this->makeSpyController();

        $ctrl->execute('save');

        self::assertSame(
            ['onBeforeExecute', 'onBeforeSave', 'save', 'onAfterSave', 'onAfterExecute'],
            $ctrl->callLog
        );
    }

    public function testExecuteRecordsTaskOnController(): void
    {
        $ctrl = $this->makeSpyController();
        $ctrl->execute('save');

        self::assertSame('save', $ctrl->getTask());
    }

    // -------------------------------------------------------------------------
    // execute() — task-to-method mapping
    // -------------------------------------------------------------------------

    public function testExecuteInvokesMappedMethod(): void
    {
        $ctrl = $this->makeSpyController();
        $ctrl->registerTask('store', 'save');

        $ctrl->execute('store');

        // save() must be called
        self::assertContains('save', $ctrl->callLog);
    }

    public function testExecuteIsCaseInsensitiveForTask(): void
    {
        $ctrl = $this->makeSpyController();
        $ctrl->execute('Save');   // mixed-case

        self::assertContains('save', $ctrl->callLog);
    }

    // -------------------------------------------------------------------------
    // execute() — default task fallback
    // -------------------------------------------------------------------------

    public function testDefaultTaskFallbackIsUsedWhenTaskNotFound(): void
    {
        $ctrl = $this->makeDefaultableController();
        // 'main' is the registered default task; 'bogus' is not mapped.
        $ctrl->execute('bogus');

        self::assertContains('main', $ctrl->callLog);
    }

    public function testRegisterDefaultTaskChangesDefault(): void
    {
        $ctrl = $this->makeSpyController();
        $ctrl->registerDefaultTask('browse');

        // Calling an unmapped task now routes to browse()
        $ctrl->execute('nonexistent');

        self::assertContains('browse', $ctrl->callLog);
    }

    // -------------------------------------------------------------------------
    // execute() — abort on hook returning false
    // -------------------------------------------------------------------------

    public function testExecuteAbortsWhenOnBeforeExecuteReturnsFalse(): void
    {
        $ctrl = $this->makeSpyController();
        $ctrl->beforeExecuteResult = false;

        $result = $ctrl->execute('save');

        self::assertFalse($result);
        // onBeforeExecute ran; nothing after it should have run
        self::assertSame(['onBeforeExecute'], $ctrl->callLog);
    }

    public function testExecuteAbortsWhenOnBeforeTaskReturnsFalse(): void
    {
        $ctrl = $this->makeSpyController();
        $ctrl->beforeSaveResult = false;

        $result = $ctrl->execute('save');

        self::assertFalse($result);
        self::assertSame(['onBeforeExecute', 'onBeforeSave'], $ctrl->callLog);
        // The task itself must NOT have been called
        self::assertNotContains('save', $ctrl->callLog);
    }

    public function testExecuteAbortsWhenOnAfterTaskReturnsFalse(): void
    {
        $ctrl = $this->makeSpyController();
        $ctrl->afterSaveResult = false;

        $result = $ctrl->execute('save');

        self::assertFalse($result);
        // save ran, onAfterSave ran, but onAfterExecute must NOT have run
        self::assertSame(['onBeforeExecute', 'onBeforeSave', 'save', 'onAfterSave'], $ctrl->callLog);
    }

    public function testExecuteAbortsWhenOnAfterExecuteReturnsFalse(): void
    {
        $ctrl = $this->makeSpyController();
        $ctrl->afterExecuteResult = false;

        $result = $ctrl->execute('save');

        self::assertFalse($result);
        self::assertSame(
            ['onBeforeExecute', 'onBeforeSave', 'save', 'onAfterSave', 'onAfterExecute'],
            $ctrl->callLog
        );
    }

    // -------------------------------------------------------------------------
    // execute() — hooks are optional (plain controller)
    // -------------------------------------------------------------------------

    public function testExecuteWorksWithNoHookMethodsDefined(): void
    {
        $ctrl = $this->makePlainController();

        $result = $ctrl->execute('work');

        // No exception; work() was called
        self::assertContains('work', $ctrl->callLog);
        // Return value from a void task is null (not false)
        self::assertNotFalse($result);
    }

    // -------------------------------------------------------------------------
    // execute() — unknown task with no default raises Exception
    // -------------------------------------------------------------------------

    public function testExecuteThrowsWhenTaskNotFoundAndNoDefault(): void
    {
        $ctrl = $this->makeSpyController();

        // Remove the __default mapping so there is no fallback.
        $ctrl->unregisterTask('__default');

        $this->expectException(Exception::class);
        $ctrl->execute('totallymissingtask');
    }

    // -------------------------------------------------------------------------
    // execute() — event dispatcher is invoked for each lifecycle event
    // -------------------------------------------------------------------------

    public function testExecuteFiresEventDispatcherForAllLifecycleEvents(): void
    {
        $container = $this->makeContainer();

        // Replace the stub with a mock that records which events were triggered.
        $firedEvents = [];
        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturnCallback(
            static function (string $event, array $args) use (&$firedEvents): array {
                $firedEvents[] = $event;
                return [];
            }
        );
        $container['eventDispatcher'] = $ed;

        $ctrl = new Spy($container);
        $ctrl->execute('save');

        // Construction fires two events; we only care about execute-time events.
        $executionEvents = array_values(array_filter(
            $firedEvents,
            static fn(string $e) => str_starts_with($e, 'onController')
                && $e !== 'onControllerBeforeConstruct'
                && $e !== 'onControllerAfterConstruct'
        ));

        self::assertContains('onControllerBeforeExecute', $executionEvents);
        self::assertContains('onControllerBeforeSave', $executionEvents);
        self::assertContains('onControllerAfterSave', $executionEvents);
        self::assertContains('onControllerAfterExecute', $executionEvents);
    }

    // -------------------------------------------------------------------------
    // execute() — event dispatcher returning false aborts execution
    // -------------------------------------------------------------------------

    public function testExecuteAbortsWhenEventDispatcherReturnsFalseForBeforeExecute(): void
    {
        $container = $this->makeContainer();

        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturnCallback(
            static function (string $event): array {
                if ($event === 'onControllerBeforeExecute') {
                    return [false];
                }
                return [];
            }
        );
        $container['eventDispatcher'] = $ed;

        $ctrl = new Spy($container);
        $result = $ctrl->execute('save');

        self::assertFalse($result);
        // onBeforeExecute (method) ran; save must not have run
        self::assertNotContains('save', $ctrl->callLog);
    }

    public function testExecuteAbortsWhenEventDispatcherReturnsFalseForBeforeTask(): void
    {
        $container = $this->makeContainer();

        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturnCallback(
            static function (string $event): array {
                if ($event === 'onControllerBeforeSave') {
                    return [false];
                }
                return [];
            }
        );
        $container['eventDispatcher'] = $ed;

        $ctrl = new Spy($container);
        $result = $ctrl->execute('save');

        self::assertFalse($result);
        self::assertNotContains('save', $ctrl->callLog);
    }

    // -------------------------------------------------------------------------
    // task / doTask accessors
    // -------------------------------------------------------------------------

    public function testGetTaskReturnsCurrentTask(): void
    {
        $ctrl = $this->makeSpyController();
        $ctrl->execute('save');

        self::assertSame('save', $ctrl->getTask());
    }

    public function testGetTasksListsRegisteredMethods(): void
    {
        $ctrl = $this->makeSpyController();
        $tasks = $ctrl->getTasks();

        self::assertContains('save', $tasks);
        self::assertContains('browse', $tasks);
    }

    // -------------------------------------------------------------------------
    // registerTask / unregisterTask
    // -------------------------------------------------------------------------

    public function testRegisterTaskAddsMapping(): void
    {
        $ctrl = $this->makeSpyController();
        $ctrl->registerTask('persist', 'save');

        $ctrl->execute('persist');

        self::assertContains('save', $ctrl->callLog);
    }

    public function testRegisterTaskIgnoresNonExistentMethod(): void
    {
        $ctrl = $this->makeSpyController();
        // 'ghost' is not a public method on the controller — should be silently ignored.
        $ctrl->registerTask('ghost', 'ghost');

        // 'browse' is a valid task and should still work normally.
        $ctrl->execute('browse');
        self::assertContains('browse', $ctrl->callLog);
    }

    public function testUnregisterTaskPreventsExecutionFallbackToException(): void
    {
        $ctrl = $this->makeSpyController();
        $ctrl->registerTask('persist', 'save');
        $ctrl->unregisterTask('persist');

        // After unregistering 'persist' and also the default, calling 'persist'
        // must throw because there is no mapping and no fallback.
        $ctrl->unregisterTask('__default');

        $this->expectException(Exception::class);
        $ctrl->execute('persist');
    }
}
