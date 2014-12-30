<?php
/**
 * @package        awf
 * @subpackage     tests.pagination.pagination
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Pagination\Pagination;

use Awf\Pagination\Pagination;
use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\ReflectionHelper;

require_once 'PaginationDataprovider.php';

/**
 * @covers      Awf\Pagination\Object::<protected>
 * @covers      Awf\Pagination\Object::<private>
 * @package     Awf\Tests\Pagination\Object
 */
class PaginationTest extends AwfTestCase
{
    /**
     * @group           Pagination
     * @group           PaginationConstruct
     * @covers          Awf\Pagination\Pagination::__construct
     * @dataProvider    PaginationDataprovider::getTest__construct
     */
    public function test__construct($test, $check)
    {
        $msg = 'Pagination::__construct %s - Case: '.$check['case'];

        // Prevent calling the original constructor until the mock is set up
        $pagination = $this->getMock('Awf\Pagination\Pagination', array('setAdditionalUrlParamsFromInput'), array(), '', false);
        $pagination->expects($this->any())->method('setAdditionalUrlParamsFromInput')->willReturn(null);

        // Now call it
        $pagination->__construct($test['total'], $test['start'], $test['limit'], $test['displayed'], $test['app']);

        $this->assertEquals($check['total']         , $pagination->total, sprintf($msg, 'Failed to set the total'));
        $this->assertEquals($check['limitStart']    , $pagination->limitStart, sprintf($msg, 'Failed to set the limitStart'));
        $this->assertEquals($check['limit']         , $pagination->limit, sprintf($msg, 'Failed to set the limit'));
        $this->assertEquals($check['pagesTotal']    , $pagination->pagesTotal, sprintf($msg, 'Failed to set the total pages'));
        $this->assertEquals($check['pagesCurrent']  , $pagination->pagesCurrent, sprintf($msg, 'Failed to set the current page'));
        $this->assertEquals($check['pagesDisplayed'], $pagination->pagesDisplayed, sprintf($msg, 'Failed to set the pages displayed'));
        $this->assertEquals($check['pagesStart']    , $pagination->pagesStart, sprintf($msg, 'Failed to set the starting page'));
        $this->assertEquals($check['pagesStop']     , $pagination->pagesStop, sprintf($msg, 'Failed to set the last page'));
        $this->assertEquals($check['viewAll']       , ReflectionHelper::getValue($pagination, 'viewAll'), sprintf($msg, 'Failed to set the viewAll flag'));
    }

    /**
     * @group           Pagination
     * @group           PaginationSetAdditionalUrlParam
     * @covers          Awf\Pagination\Pagination::setAdditionalUrlParam
     * @dataProvider    PaginationDataprovider::getTestSetAdditionalUrlParam
     */
    public function testSetAdditionalUrlParam($test, $check)
    {
        $msg        = 'Pagination::setAdditionalUrlParam %s - Case: '.$check['case'];
        $pagination = new Pagination(20, 0, 5);

        ReflectionHelper::setValue($pagination, 'additionalUrlParams', $test['mock']['params']);

        $result = $pagination->setAdditionalUrlParam($test['key'], $test['value']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
        $this->assertEquals($check['params'], ReflectionHelper::getValue($pagination, 'additionalUrlParams'), sprintf($msg, 'Failed to set the url param'));
    }

    /**
     * @group           Pagination
     * @group           PaginationSetAdditionalUrlParams
     * @covers          Awf\Pagination\Pagination::setAdditionalUrlParams
     */
    public function testSetAdditionalUrlParams()
    {
        $params = array(
            'foo' => 'bar',
            'dummy' => 'foobar'
        );

        $pagination = $this->getMock('Awf\Pagination\Pagination', array('setAdditionalUrlParam'), array(20, 0, 5), '', false);
        $pagination->expects($this->exactly(count($params)))->method('setAdditionalUrlParam')->withConsecutive(
            array($this->equalTo('foo'), $this->equalTo('bar')),
            array($this->equalTo('dummy'), $this->equalTo('foobar'))
        );

        $pagination->setAdditionalUrlParams($params);

    }
}