<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Stub controller classes used by ControllerTaskTest.
//
// All stubs live under the fake CtrlTaskApp namespace which is set as the
// applicationNamespace of the test Container.  Mixed namespaces require the
// bracketed namespace syntax throughout.
// ---------------------------------------------------------------------------

namespace CtrlTaskApp\Controller {

    use Awf\Mvc\Controller;

    /**
     * Spy controller — records the ordered call log and lets tests configure
     * whether each hook returns true (proceed) or false (abort).
     */
    if (!class_exists('CtrlTaskApp\\Controller\\Spy', false)) {
        class Spy extends Controller
        {
            /** Records the ordered names of every hook/task that was called. */
            public array $callLog = [];

            // Return values for each hook; true = proceed, false = abort.
            public bool $beforeExecuteResult = true;
            public bool $beforeSaveResult    = true;
            public bool $afterSaveResult     = true;
            public bool $afterExecuteResult  = true;

            /** A normal task that the controller can execute. */
            public function save(): void
            {
                $this->callLog[] = 'save';
            }

            /** Another normal task (used to verify task mapping). */
            public function browse(): void
            {
                $this->callLog[] = 'browse';
            }

            public function onBeforeExecute(string $task, string $doTask): bool
            {
                $this->callLog[] = 'onBeforeExecute';
                return $this->beforeExecuteResult;
            }

            public function onBeforeSave(): bool
            {
                $this->callLog[] = 'onBeforeSave';
                return $this->beforeSaveResult;
            }

            public function onAfterSave(): bool
            {
                $this->callLog[] = 'onAfterSave';
                return $this->afterSaveResult;
            }

            public function onAfterExecute(string $task, string $doTask): bool
            {
                $this->callLog[] = 'onAfterExecute';
                return $this->afterExecuteResult;
            }
        }
    }

    /**
     * Controller without any extra hooks — verifies hooks are optional.
     */
    if (!class_exists('CtrlTaskApp\\Controller\\Plain', false)) {
        class Plain extends Controller
        {
            public array $callLog = [];

            public function work(): void
            {
                $this->callLog[] = 'work';
            }
        }
    }

    /**
     * Controller whose main method records the call (used for default task tests).
     */
    if (!class_exists('CtrlTaskApp\\Controller\\Defaultable', false)) {
        class Defaultable extends Controller
        {
            public array $callLog = [];

            public function main(): void
            {
                $this->callLog[] = 'main';
            }
        }
    }
}
