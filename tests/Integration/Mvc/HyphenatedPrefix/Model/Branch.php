<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Mvc\HyphenatedPrefix\Model;

use Awf\Mvc\TreeModel;

/**
 * TreeModel fixture. The table name uses the "#__" placeholder, so the prefix is resolved by the driver.
 */
class Branch extends TreeModel
{
    protected $tableName = '#__branches';

    protected $idFieldName = 'id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}
