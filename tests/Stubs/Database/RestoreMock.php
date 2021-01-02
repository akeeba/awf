<?php
/**
 * @package   awf
 * @copyright Copyright (c)2014-2021 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Database;

use Awf\Database\Restore;

class RestoreMock extends Restore
{
    protected function processQueryLine($query)
    {
        return $query;
    }

}
