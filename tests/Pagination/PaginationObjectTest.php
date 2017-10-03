<?php
/**
 * @package        awf
 * @subpackage     tests.pagination.object
 * @copyright      2014-2017 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Pagination\Object;

use Awf\Pagination\PaginationObject;
use Awf\Tests\Helpers\AwfTestCase;

/**
 * @covers      Awf\Pagination\Object::<protected>
 * @covers      Awf\Pagination\Object::<private>
 * @package     Awf\Tests\Pagination\Object
 */
class PaginationObjectTest extends AwfTestCase
{
    /**
     * @group       PaginationObject
     * @covers      Awf\Pagination\Object::__construct
     */
    public function test__construct()
    {
        $object = new PaginationObject('Foobar', 2, 'www.example.com/index.php', true);

        $this->assertEquals('Foobar', $object->text);
        $this->assertEquals(2, $object->base);
        $this->assertEquals('www.example.com/index.php', $object->link);
        $this->assertEquals(true, $object->active);
    }
}
