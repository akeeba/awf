<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Stub MVC classes used by FactoryTest.
//
// All stubs live under the fake application namespace \FactoryTestApp\… (or
// \FactoryTestApp2\…) which is set as the applicationNamespace of the test
// Container.  Because this file mixes multiple namespaces it uses the
// bracketed namespace syntax throughout.
// ---------------------------------------------------------------------------

namespace FactoryTestApp\Controller {

    use Awf\Mvc\Controller;

    if (!class_exists('FactoryTestApp\\Controller\\Item', false)) {
        /** Matches name="item" exactly */
        class Item extends Controller {}
    }

    if (!class_exists('FactoryTestApp\\Controller\\Items', false)) {
        /** Plural form — also resolves for singular "item" via Inflector */
        class Items extends Controller {}
    }

    if (!class_exists('FactoryTestApp\\Controller\\DefaultController', false)) {
        /** Fallback controller */
        class DefaultController extends Controller {}
    }
}

namespace FactoryTestApp\Model {

    use Awf\Mvc\Model;

    if (!class_exists('FactoryTestApp\\Model\\Item', false)) {
        /** Matches name="item" exactly */
        class Item extends Model {}
    }

    if (!class_exists('FactoryTestApp\\Model\\DefaultModel', false)) {
        /** Fallback model */
        class DefaultModel extends Model {}
    }
}

namespace FactoryTestApp\View\Item {

    use Awf\Mvc\View;

    if (!class_exists('FactoryTestApp\\View\\Item\\Html', false)) {
        /** Matches view=item + format=html */
        class Html extends View {}
    }

    if (!class_exists('FactoryTestApp\\View\\Item\\DefaultView', false)) {
        /** Default for any other format on the Item view */
        class DefaultView extends View {}
    }
}

namespace FactoryTestApp\View\Default {

    use Awf\Mvc\View;

    if (!class_exists('FactoryTestApp\\View\\Default\\Json', false)) {
        /** Default cross-view for format=json */
        class Json extends View {}
    }
}

namespace FactoryTestApp\View {

    use Awf\Mvc\View;

    if (!class_exists('FactoryTestApp\\View\\DefaultView', false)) {
        /** Ultimate fallback view */
        class DefaultView extends View {}
    }
}

// ---------------------------------------------------------------------------
// Second fake namespace — only a plural controller (no singular, no Default)
// ---------------------------------------------------------------------------

namespace FactoryTestApp2\Controller {

    use Awf\Mvc\Controller;

    if (!class_exists('FactoryTestApp2\\Controller\\Widgets', false)) {
        /** Only a plural form — resolves when name="widget" (singular) is passed */
        class Widgets extends Controller {}
    }
}

namespace FactoryTestApp2\Model {
    // intentionally empty — forces RuntimeException in makeModel tests
}

namespace FactoryTestApp2\View {
    // intentionally empty — forces RuntimeException in makeView tests
}
