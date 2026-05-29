<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Stub MVC classes used by DataControllerTest.
//
// The fake application namespace is "DcTestApp". Controller/Model stubs
// live under DcTestApp\Controller\ and DcTestApp\Model\ respectively.
// ---------------------------------------------------------------------------

namespace DcTestApp\Model {

    use Awf\Mvc\DataModel;

    /**
     * Minimal DataModel fixture backed by the "items" table.
     */
    if (!class_exists('DcTestApp\\Model\\Items', false)) {
        class Items extends DataModel
        {
            public static function flushCaches(): void
            {
                static::$tableCache      = [];
                static::$tableFieldCache = [];
            }
        }
    }

    if (!class_exists('DcTestApp\\Model\\Item', false)) {
        class Item extends DataModel
        {
            public static function flushCaches(): void
            {
                static::$tableCache      = [];
                static::$tableFieldCache = [];
            }
        }
    }
}

namespace DcTestApp\Controller {

    use Awf\Mvc\DataController;

    /**
     * DataController subclass that:
     *  - Captures redirect calls instead of actually redirecting
     *  - Overrides display() so it does not try to create views/templates
     */
    if (!class_exists('DcTestApp\\Controller\\Items', false)) {
        class Items extends DataController
        {
            /** Captured redirect target URL. */
            public ?string $redirectUrl  = null;
            public ?string $redirectMsg  = null;
            public ?string $redirectType = null;

            /** Prevent actual view rendering. */
            public function display(): void
            {
                // no-op
            }

            /** Expose setRedirect so tests can inspect it. */
            public function setRedirect($url, $msg = null, $type = null): static
            {
                $this->redirectUrl  = $url;
                $this->redirectMsg  = $msg;
                $this->redirectType = $type;
                return parent::setRedirect($url, $msg, $type);
            }

            /** Expose the protected csrfProtection for testing. */
            public function bypassCsrf(): void
            {
                // overriding csrfProtection to be a no-op in tests
            }
        }
    }

    if (!class_exists('DcTestApp\\Controller\\Item', false)) {
        class Item extends DataController
        {
            public ?string $redirectUrl  = null;
            public ?string $redirectMsg  = null;
            public ?string $redirectType = null;

            public function display(): void {}

            public function setRedirect($url, $msg = null, $type = null): static
            {
                $this->redirectUrl  = $url;
                $this->redirectMsg  = $msg;
                $this->redirectType = $type;
                return parent::setRedirect($url, $msg, $type);
            }
        }
    }
}
