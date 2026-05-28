<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Stub controller classes used by ControllerRedirectTest.
//
// All stubs live under the fake CtrlRedirectApp namespace which is set as the
// applicationNamespace of the test Container.  Mixed namespaces require the
// bracketed namespace syntax throughout.
// ---------------------------------------------------------------------------

namespace CtrlRedirectApp\Controller {

    use Awf\Mvc\Controller;

    /**
     * A minimal controller that records whether redirect() was called
     * (and with what arguments), without actually triggering header()/exit.
     */
    if (!class_exists('CtrlRedirectApp\\Controller\\Stub', false)) {
        class Stub extends Controller
        {
            /** Captures calls made to the application redirect. */
            public array $redirectLog = [];

            /** A no-op task so execute() has something to call. */
            public function noop(): void {}

            /** A task that sets a redirect internally. */
            public function doRedirect(): void
            {
                $this->setRedirect('https://example.com/after', 'Done!', 'info');
            }
        }
    }

    /**
     * Controller subclass that exposes csrfProtection() as public for direct
     * testing, so we do not need to exercise it through a full execute() cycle.
     */
    if (!class_exists('CtrlRedirectApp\\Controller\\CsrfExposed', false)) {
        class CsrfExposed extends Controller
        {
            public function noop(): void {}

            public function checkCsrf(bool $useCms = false): void
            {
                $this->csrfProtection($useCms);
            }
        }
    }
}
