<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Stub MVC classes used by ViewTest.
//
// The view class must live inside a namespace matching the AWF convention:
//   \{App}\View\{Name}\{Format}
// so that getName() can parse the class name via the regex pattern.
// ---------------------------------------------------------------------------

namespace ViewTestApp\View\Item {

    use Awf\Mvc\View;

    if (!class_exists('ViewTestApp\\View\\Item\\Html', false)) {
        class Html extends View {}
    }
}

namespace ViewTestApp\Model {

    use Awf\Mvc\Model;

    if (!class_exists('ViewTestApp\\Model\\Item', false)) {
        /** Minimal model that exposes getFoo() and a magic property 'bar'. */
        class Item extends Model
        {
            public function getFoo(): string
            {
                return 'foo-value';
            }

            public function bar(): string
            {
                return 'bar-value';
            }
        }
    }
}
