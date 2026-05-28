<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Stub MVC classes used by HtmlViewTest.
//
// The fake application namespace is "HtmlViewTestApp".
// View classes follow the AWF convention: \{App}\View\{Name}\{Format}.
// ---------------------------------------------------------------------------

namespace HtmlViewTestApp\Model {

    use Awf\Mvc\DataModel;

    if (!class_exists('HtmlViewTestApp\\Model\\Items', false)) {
        class Items extends DataModel
        {
            public static function flushCaches(): void
            {
                static::$tableCache      = [];
                static::$tableFieldCache = [];
            }
        }
    }

    if (!class_exists('HtmlViewTestApp\\Model\\Item', false)) {
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

namespace HtmlViewTestApp\View\Items {

    /**
     * Minimal Html view subclass; overrides loadTemplate() so display() can
     * run end-to-end in tests without needing actual template files on disk.
     * Exposes $lists publicly so tests can read the populated value.
     * Exposes onBefore* and onAfter* hooks as public so tests can call them directly.
     */
    if (!class_exists('HtmlViewTestApp\\View\\Items\\Html', false)) {
        class Html extends \Awf\Mvc\DataView\Html
        {
            /** @var \stdClass|null Exposes the protected $lists for test assertions. */
            public $lists = null;

            public function loadTemplate($tpl = null, $strict = false): string
            {
                return '';
            }

            public function onBeforeAdd(): bool
            {
                return parent::onBeforeAdd();
            }

            public function onBeforeEdit(): bool
            {
                return parent::onBeforeEdit();
            }

            public function onBeforeRead(): bool
            {
                return parent::onBeforeRead();
            }
        }
    }

    /**
     * Stub that always rejects from onBeforeAdd.
     */
    if (!class_exists('HtmlViewTestApp\\View\\Items\\HtmlRejectAdd', false)) {
        class HtmlRejectAdd extends \Awf\Mvc\DataView\Html
        {
            public function onBeforeAdd(): bool
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
     * Stub that always rejects from onBeforeEdit.
     */
    if (!class_exists('HtmlViewTestApp\\View\\Items\\HtmlRejectEdit', false)) {
        class HtmlRejectEdit extends \Awf\Mvc\DataView\Html
        {
            public function onBeforeEdit(): bool
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
     * Stub that always rejects from onBeforeRead.
     */
    if (!class_exists('HtmlViewTestApp\\View\\Items\\HtmlRejectRead', false)) {
        class HtmlRejectRead extends \Awf\Mvc\DataView\Html
        {
            public function onBeforeRead(): bool
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
     * Stub that accepts onBeforeAdd but rejects from onAfterAdd.
     */
    if (!class_exists('HtmlViewTestApp\\View\\Items\\HtmlRejectAfterAdd', false)) {
        class HtmlRejectAfterAdd extends \Awf\Mvc\DataView\Html
        {
            public function onAfterAdd(): bool
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
     * Stub that accepts onBeforeEdit but rejects from onAfterEdit.
     */
    if (!class_exists('HtmlViewTestApp\\View\\Items\\HtmlRejectAfterEdit', false)) {
        class HtmlRejectAfterEdit extends \Awf\Mvc\DataView\Html
        {
            public function onAfterEdit(): bool
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
     * Stub that accepts onBeforeRead but rejects from onAfterRead.
     */
    if (!class_exists('HtmlViewTestApp\\View\\Items\\HtmlRejectAfterRead', false)) {
        class HtmlRejectAfterRead extends \Awf\Mvc\DataView\Html
        {
            public function onAfterRead(): bool
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

namespace HtmlViewTestApp\View\Item {

    if (!class_exists('HtmlViewTestApp\\View\\Item\\Html', false)) {
        class Html extends \Awf\Mvc\DataView\Html
        {
            public function loadTemplate($tpl = null, $strict = false): string
            {
                return '';
            }
        }
    }
}
