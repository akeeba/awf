<?php
/**
 * @package        awf
 * @subpackage     tests.pagination.pagination
 * @copyright      2014 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license        GNU GPL version 3 or later
 */

namespace Awf\Tests\Pagination\Pagination;

use Awf\Input\Input;
use Awf\Pagination\Pagination;
use Awf\Tests\Helpers\AwfTestCase;
use Awf\Tests\Helpers\ReflectionHelper;
use Awf\Application\Application;
use Awf\Text\Text;

require_once 'PaginationDataprovider.php';

/**
 * @covers      Awf\Pagination\Pagination::<protected>
 * @covers      Awf\Pagination\Pagination::<private>
 * @package     Awf\Tests\Pagination\Pagination
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

    /**
     * @group           Pagination
     * @group           PaginationSetAdditionalUrlParamsFromInput
     * @covers          Awf\Pagination\Pagination::setAdditionalUrlParamsFromInput
     * @dataProvider    PaginationDataprovider::getTestSetAdditionalUrlParamsFromInput
     */
    public function testsetAdditionalUrlParamsFromInput($test)
    {
        $pagination = $this->getMock('Awf\Pagination\Pagination', array('setAdditionalUrlParam'), array(20, 0, 5), '', false);

        // I presume that the param is added only once for each iteration: even if I have more params to add, they should
        // be ignored (maybe they are not handled or allowed). In this way the test is much more easier to maintain and understand
        $pagination->expects($this->once())->method('setAdditionalUrlParam')->willReturn(null)->with(
            $this->equalTo('foobar'), $this->equalTo('dummy')
        );

        $input = null;
        $app   = Application::getInstance('Fakeapp');

        if($test['mock']['input'])
        {
            $container = $app->getContainer();
            $container->appConfig->set('base_url', '/administrator/index.php?option=com_foobar');
            $container['input'] = new Input(array(
                'option' => 'shouldBeRemoved',
                'foobar' => 'dummy'
            ));

            $app = Application::getInstance('Fakeapp', $container);
        }
        elseif($test['input'])
        {
            $input = new Input($test['input']);
        }

        // I have to inject the application since I'm not calling the constructor. I can't call it since it will automatically
        // invoke the function under tests, messing around with my test
        ReflectionHelper::setValue($pagination, 'application', $app);

        $pagination->setAdditionalUrlParamsFromInput($input);
    }

    /**
     * @group           Pagination
     * @group           PaginationClearAdditionalUrlParams
     * @covers          Awf\Pagination\Pagination::clearAdditionalUrlParams
     */
    public function testClearAdditionalUrlParams()
    {
        $pagination = new Pagination(20, 0, 5);

        ReflectionHelper::setValue($pagination, 'additionalUrlParams', array('foo' => 'bar'));

        $pagination->clearAdditionalUrlParams();

        $this->assertEmpty(ReflectionHelper::getValue($pagination, 'additionalUrlParams'), 'Pagination::additionalUrlParams Failed to reset additional params');
    }

    /**
     * @group           Pagination
     * @group           PaginationGetAdditionalUrlParam
     * @covers          Awf\Pagination\Pagination::getAdditionalUrlParam
     * @dataProvider    PaginationDataprovider::getTestGetAdditionalUrlParam
     */
    public function testGetAdditionalUrlParam($test, $check)
    {
        $msg        = 'Pagination::getAdditionalUrlParam %s - Case: '.$check['case'];
        $pagination = new Pagination(20, 0, 5);

        ReflectionHelper::setValue($pagination, 'additionalUrlParams', array('foo' => 'bar', 'empty' => null));

        $result = $pagination->getAdditionalUrlParam($test['key']);

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Failed to return the correct result'));
    }

    /**
     * @group           Pagination
     * @group           PaginationGetAdditionalUrlParams
     * @covers          Awf\Pagination\Pagination::getAdditionalUrlParams
     */
    public function testGetAdditionalUrlParams()
    {
        $params     = array('foo' => 'bar', 'empty' => null);
        $pagination = new Pagination(20, 0, 5);

        ReflectionHelper::setValue($pagination, 'additionalUrlParams', $params);

        $this->assertSame($params, $pagination->getAdditionalUrlParams(), 'Pagination::getAdditionalUrlParams Failed to return the params array');
    }

    /**
     * @group           Pagination
     * @group           PaginationGetRowOffset
     * @covers          Awf\Pagination\Pagination::getRowOffset
     */
    public function testGetRowOffset()
    {
        $pagination = new Pagination(20, 3, 5);
        $result = $pagination->getRowOffset(5);

        $this->assertEquals(9, $result, 'Pagination::getRowOffset Failed to return the correct offset');
    }

    /**
     * @group           Pagination
     * @group           PaginationGetData
     * @covers          Awf\Pagination\Pagination::getData
     * @dataProvider    PaginationDataprovider::getTestGetData
     */
    public function testGetData($test, $check)
    {
        $msg        = 'Pagination::getData %s - Case: '.$check['case'];
        $pagination = new Pagination($test['total'], $test['start'], $test['limit'], $test['displayed']);

        ReflectionHelper::setValue($pagination, 'data', $test['mock']['data']);

        if($test['mock']['addParams'])
        {
            ReflectionHelper::setValue($pagination, 'additionalUrlParams', $test['mock']['addParams']);
        }

        $result = $pagination->getData();

        // If it's not an array I just want to do a quick check on the size: maybe I have 50 pages so it's impossible to
        // check every item
        if(isset($check['result']->pages) && !is_array($check['result']->pages))
        {
            $count = $check['result']->pages;
            $this->assertCount($count, $result->pages);

            unset($result->pages);
            unset($check['result']->pages);
        }

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @group           Pagination
     * @group           PaginationGetPagesCounter
     * @covers          Awf\Pagination\Pagination::getPagesCounter
     * @dataProvider    PaginationDataprovider::getTestGetPagesCounter
     */
    public function testGetPagesCounter($test, $check)
    {
        $msg        = 'Pagination::getPagesCounter %s - Case: '.$check['case'];
        $pagination = new Pagination(20, 3, 5);

        ReflectionHelper::setValue($pagination, 'pagesCurrent', $test['current']);
        ReflectionHelper::setValue($pagination, 'pagesTotal', $test['total']);

        $result = $pagination->getPagesCounter();

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }

    /**
     * @group           Pagination
     * @group           PaginationGetResultsCounter
     * @covers          Awf\Pagination\Pagination::getResultsCounter
     * @dataProvider    PaginationDataprovider::getTestGetResultsCounter
     */
    public function testGetResultsCounter($test, $check)
    {
        $msg        = 'Pagination::getResultsCounter %s - Case: '.$check['case'];
        $pagination = new Pagination(20, 0, 5);

        ReflectionHelper::setValue($pagination, 'limitStart', $test['mock']['start']);
        ReflectionHelper::setValue($pagination, 'limit', $test['mock']['limit']);
        ReflectionHelper::setValue($pagination, 'total', $test['mock']['total']);

        $result = $pagination->getResultsCounter();

        $this->assertEquals($check['result'], $result, sprintf($msg, 'Returned the wrong result'));
    }
}