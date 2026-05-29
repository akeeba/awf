<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Stub MVC classes used by CsvViewTest.
//
// The fake application namespace is "CsvViewTestApp".
// View classes follow the AWF convention: \{App}\View\{Name}\{Format}.
// ---------------------------------------------------------------------------

namespace CsvViewTestApp\Model {

    use Awf\Mvc\DataModel;

    if (!class_exists('CsvViewTestApp\\Model\\Items', false)) {
        class Items extends DataModel
        {
            public static function flushCaches(): void
            {
                static::$tableCache      = [];
                static::$tableFieldCache = [];
            }

            /**
             * Allow setting recordData directly for testing array/object serialisation.
             */
            public function setRecordField(string $field, mixed $value): void
            {
                $this->recordData[$field] = $value;
            }
        }
    }

    if (!class_exists('CsvViewTestApp\\Model\\Item', false)) {
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

namespace CsvViewTestApp\View\Items {

    if (!class_exists('CsvViewTestApp\\View\\Items\\Csv', false)) {
        class Csv extends \Awf\Mvc\DataView\Csv {}
    }

    /**
     * Stub that always rejects from onBeforeBrowse.
     * Used to verify display() raises a 403 exception.
     */
    if (!class_exists('CsvViewTestApp\\View\\Items\\CsvRejectBrowse', false)) {
        class CsvRejectBrowse extends \Awf\Mvc\DataView\Csv
        {
            public function onBeforeBrowse($tpl = null): bool
            {
                return false;
            }
        }
    }

    /**
     * Stub that accepts onBeforeBrowse but rejects from onAfterBrowse.
     * Requires the view to have items already loaded so the CSV output path succeeds.
     */
    if (!class_exists('CsvViewTestApp\\View\\Items\\CsvRejectAfterBrowse', false)) {
        class CsvRejectAfterBrowse extends \Awf\Mvc\DataView\Csv
        {
            public function onBeforeBrowse($tpl = null): bool
            {
                return true;
            }

            public function onAfterBrowse($tpl = null): bool
            {
                return false;
            }
        }
    }

    /**
     * Stub whose onBeforeBrowse injects a pre-built DataCollection, enabling
     * tests that need to control the exact items (e.g. array/object field values).
     *
     * Before calling display(), assign the collection to $view->injectedItems.
     */
    if (!class_exists('CsvViewTestApp\\View\\Items\\CsvInjectableItems', false)) {
        class CsvInjectableItems extends \Awf\Mvc\DataView\Csv
        {
            /** @var \Awf\Mvc\DataModel\Collection|null */
            public ?\Awf\Mvc\DataModel\Collection $injectedItems = null;

            public function onBeforeBrowse($tpl = null): bool
            {
                if ($this->injectedItems !== null) {
                    $this->items = $this->injectedItems;
                    $this->alreadyLoaded = true;
                }
                return true;
            }
        }
    }
}

namespace CsvViewTestApp\View\Item {

    if (!class_exists('CsvViewTestApp\\View\\Item\\Csv', false)) {
        class Csv extends \Awf\Mvc\DataView\Csv {}
    }
}
