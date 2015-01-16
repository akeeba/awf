<?php
/**
 * @package        awf
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 *
 * This class is adapted from Joomla! Framework
 */

namespace Awf\Tests\Database\Restore;

use Awf\Tests\Database\DatabaseMysqliCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Tests\Stubs\Utils\TestClosure;
use Fakeapp\Application;

require_once 'MysqliDataprovider.php';

class MysqliTest extends DatabaseMysqliCase
{
    /**
     * @covers          Awf\Database\Restore\Mysqli::__construct
     * @dataProvider    RestoreMysqliDataprovider::getTest__construct
     */
    public function test__construct($test, $check)
    {
        $msg        = 'Mysqli::__construct %s - Case: '.$check['case'];
        $checkQuery = '';
        $container  = Application::getInstance()->getContainer();

        $container['dbrestore'] = array(
            'foreignkey' => $test['mock']['foreign'],
            'dbkey'      => 'mysqltestcase',
        );
        $restore = $this->getMock('Awf\Database\Restore\Mysqli', array('getDatabase', 'populatePartsMap'), array($container), '', false);

        // Let's fake the db, so I can check if the correct query is executed
        $db = new TestClosure(array(
            'setQuery' => function($self, $query) use($test, &$checkQuery){
                $checkQuery = $query;

                if($test['mock']['check'] === 'exception')
                {
                    throw new \Exception();
                }
            },
            'execute'  => function(){}
        ));

        ReflectionHelper::setValue($restore, 'db', $db);

        $restore->__construct($container);

        $this->assertEquals($check['query'], $checkQuery, sprintf($msg, 'Failed to disable foreign key checks'));
    }
}