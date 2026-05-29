<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Stub MVC classes used by JsonViewTest.
//
// The fake application namespace is "JsonViewTestApp".
// View classes follow the AWF convention: \{App}\View\{Name}\{Format}.
// ---------------------------------------------------------------------------

namespace JsonViewTestApp\Model {

    use Awf\Mvc\DataModel;

    if (!class_exists('JsonViewTestApp\\Model\\Items', false)) {
        class Items extends DataModel
        {
            public static function flushCaches(): void
            {
                static::$tableCache      = [];
                static::$tableFieldCache = [];
            }
        }
    }

    if (!class_exists('JsonViewTestApp\\Model\\Item', false)) {
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

namespace JsonViewTestApp\View\Items {

    if (!class_exists('JsonViewTestApp\\View\\Items\\Json', false)) {
        class Json extends \Awf\Mvc\DataView\Json {}
    }

    /**
     * Stub that always rejects from onBeforeBrowse.
     * Used to verify display() raises a 403 exception.
     */
    if (!class_exists('JsonViewTestApp\\View\\Items\\JsonRejectBrowse', false)) {
        class JsonRejectBrowse extends \Awf\Mvc\DataView\Json
        {
            public function onBeforeBrowse($tpl = null): bool
            {
                return false;
            }
        }
    }

    /**
     * Stub that accepts onBeforeBrowse but rejects from onAfterBrowse.
     * Used to verify display() raises a 403 exception after the main hook.
     */
    if (!class_exists('JsonViewTestApp\\View\\Items\\JsonRejectAfterBrowse', false)) {
        class JsonRejectAfterBrowse extends \Awf\Mvc\DataView\Json
        {
            public function onBeforeBrowse($tpl = null): bool
            {
                $this->alreadyLoaded = true;
                $this->items         = [];
                return true;
            }

            public function onAfterBrowse($tpl = null): bool
            {
                return false;
            }
        }
    }
}

namespace JsonViewTestApp\View\Item {

    if (!class_exists('JsonViewTestApp\\View\\Item\\Json', false)) {
        class Json extends \Awf\Mvc\DataView\Json {}
    }
}
