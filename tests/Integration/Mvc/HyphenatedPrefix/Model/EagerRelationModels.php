<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

/**
 * Fixture models for the eager-loading relation tests. Relations resolve their foreign model as
 * "<application>\Model\<Name>", and the integration test container's application is "Testapp".
 */

namespace Testapp\Model;

use Awf\Mvc\DataModel;

class Eparent extends DataModel
{
    protected $tableName = '#__eparents';

    protected $idFieldName = 'id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

class Echild extends DataModel
{
    protected $tableName = '#__echildren';

    protected $idFieldName = 'id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}

class Etag extends DataModel
{
    protected $tableName = '#__etags';

    protected $idFieldName = 'id';

    public static function flushCaches(): void
    {
        static::$tableCache      = [];
        static::$tableFieldCache = [];
    }
}
