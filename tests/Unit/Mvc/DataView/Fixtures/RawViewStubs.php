<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Stub MVC classes used by RawViewTest.
//
// The fake application namespace is "RawViewTestApp".
// View classes follow the AWF convention: \{App}\View\{Name}\{Format}.
// ---------------------------------------------------------------------------

namespace RawViewTestApp\Model {

    use Awf\Mvc\DataModel;

    if (!class_exists('RawViewTestApp\\Model\\Items', false)) {
        class Items extends DataModel
        {
            public static function flushCaches(): void
            {
                static::$tableCache      = [];
                static::$tableFieldCache = [];
            }
        }
    }

    if (!class_exists('RawViewTestApp\\Model\\Item', false)) {
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

namespace RawViewTestApp\View\Items {

    /**
     * Minimal Raw view subclass; overrides loadTemplate() so display() can
     * run end-to-end in tests without needing actual template files on disk.
     * Exposes $lists publicly so tests can read the populated value.
     */
    if (!class_exists('RawViewTestApp\\View\\Items\\Raw', false)) {
        class Raw extends \Awf\Mvc\DataView\Raw
        {
            /** @var \stdClass|null Exposes the protected $lists for test assertions. */
            public $lists = null;

            public function loadTemplate($tpl = null, $strict = false): string
            {
                return '';
            }
        }
    }

    /**
     * Stub that always rejects from onBeforeBrowse.
     * Used to verify display() raises a 403 exception.
     */
    if (!class_exists('RawViewTestApp\\View\\Items\\RawRejectBrowse', false)) {
        class RawRejectBrowse extends \Awf\Mvc\DataView\Raw
        {
            public function onBeforeBrowse($tpl = null): bool
            {
                return false;
            }

            public function loadTemplate($tpl = null, $strict = false): string
            {
                return '';
            }
        }
    }

    /**
     * Stub that accepts onBeforeBrowse but rejects from onAfterBrowse.
     */
    if (!class_exists('RawViewTestApp\\View\\Items\\RawRejectAfterBrowse', false)) {
        class RawRejectAfterBrowse extends \Awf\Mvc\DataView\Raw
        {
            public function onAfterBrowse($tpl = null): bool
            {
                return false;
            }

            public function loadTemplate($tpl = null, $strict = false): string
            {
                return '';
            }
        }
    }
}

namespace RawViewTestApp\View\Item {

    if (!class_exists('RawViewTestApp\\View\\Item\\Raw', false)) {
        class Raw extends \Awf\Mvc\DataView\Raw
        {
            public function loadTemplate($tpl = null, $strict = false): string
            {
                return '';
            }
        }
    }
}
