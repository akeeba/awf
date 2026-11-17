<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Mvc\HyphenatedPrefix\Model;

use Awf\Mvc\DataModel;

/**
 * DataModel fixture with a small-integer column, which AWF filters with the Boolean filter.
 */
class Flag extends DataModel
{
    protected $tableName = '#__flags';

    protected $idFieldName = 'flag_id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}
