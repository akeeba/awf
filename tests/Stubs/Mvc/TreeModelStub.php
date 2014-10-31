<?php
/**
 * @package        awf
 * @subpackage     tests.stubs
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Stubs\Mvc;

use Awf\Mvc\TreeModel;

class TreeModelStub extends TreeModel
{
    public function getName()
    {
        return 'nestedset';
    }
}

