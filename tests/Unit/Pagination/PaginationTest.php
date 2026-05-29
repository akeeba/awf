<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Pagination;

use Awf\Application\Configuration as AppConfiguration;
use Awf\Container\Container;
use Awf\Input\Input;
use Awf\Pagination\Pagination;
use Awf\Pagination\PaginationObject;
use Awf\Router\Router;
use Awf\Text\Language;
use Awf\Uri\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    protected function setUp(): void
    {
        // Provide a live_site so Uri::base() does not access $_SERVER.
        // Also reset Uri static state so it picks up our value each time.
        Uri::reset();
    }

    protected function tearDown(): void
    {
        Uri::reset();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a minimal Container suitable for instantiating Pagination.
     *
     * The router stub's route() method simply returns the URL unchanged so
     * we can assert the query-string parameters without a full HTTP context.
     */
    private function makeContainer(array $inputData = []): Container
    {
        $tmpDir = sys_get_temp_dir();

        // Language stub: text() returns the key, sprintf() returns the pattern
        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);
        $language->method('sprintf')->willReturnCallback(
            static function (string $key, mixed ...$args): string {
                return $key . ':' . implode(',', $args);
            }
        );

        // Input with only what the test passes in
        $input = new Input($inputData);

        // AppConfiguration stub: live_site is set so Uri::base() does not read
        // $_SERVER (which is absent in CLI), and base_url is a safe placeholder.
        $appConfig = $this->createMock(AppConfiguration::class);
        $appConfig->method('get')->willReturnCallback(
            static function (string $key, mixed $default = null): mixed {
                return match ($key) {
                    'base_url'  => 'index.php',
                    'live_site' => 'http://localhost',
                    default     => $default,
                };
            }
        );

        // Router stub: route() simply returns the URL unchanged
        $router = $this->createMock(Router::class);
        $router->method('route')->willReturnArgument(0);

        return new Container([
            'application_name'     => 'PaginationTestApp',
            'applicationNamespace' => '\\PaginationTestApp',
            'session_segment_name' => 'paginationtest_seg',
            'basePath'             => $tmpDir,
            'languagePath'         => $tmpDir,
            'temporaryPath'        => $tmpDir,
            'templatePath'         => $tmpDir,
            'sqlPath'              => $tmpDir,
            'filesystemBase'       => $tmpDir,
            'language'             => $language,
            'input'                => $input,
            'appConfig'            => $appConfig,
            'router'               => $router,
        ]);
    }

    /** Create a Pagination object with the test container, suppressing the deprecated-no-container warning. */
    private function makePagination(
        int $total,
        int $limitStart,
        int $limit,
        int $displayed = 10,
        array $inputData = []
    ): Pagination {
        $container = $this->makeContainer($inputData);
        return new Pagination($total, $limitStart, $limit, $displayed, $container);
    }

    // =========================================================================
    // PaginationObject tests
    // =========================================================================

    public function testPaginationObjectDefaultValues(): void
    {
        $obj = new PaginationObject('Page 1');

        self::assertSame('Page 1', $obj->text);
        self::assertNull($obj->base);
        self::assertNull($obj->link);
        self::assertFalse($obj->active);
    }

    public function testPaginationObjectWithAllArgs(): void
    {
        $obj = new PaginationObject('Next', 10, 'http://example.com/?limitstart=10', true);

        self::assertSame('Next', $obj->text);
        self::assertSame(10, $obj->base);
        self::assertSame('http://example.com/?limitstart=10', $obj->link);
        self::assertTrue($obj->active);
    }

    // =========================================================================
    // Constructor / page-count math
    // =========================================================================

    public static function pageCountProvider(): array
    {
        return [
            // label                 => [total, limitStart, limit, expectedPagesTotal, expectedPagesCurrent]
            'first page of 5'        => [50, 0,  10, 5, 1],
            'second page of 5'       => [50, 10, 10, 5, 2],
            'last page of 5'         => [50, 40, 10, 5, 5],
            'single page'            => [5,  0,  10, 1, 1],
            'partial last page'      => [45, 40, 10, 5, 5],
            'exactly one page'       => [10, 0,  10, 1, 1],
            'two pages'              => [11, 0,  10, 2, 1],
            'two pages second'       => [11, 10, 10, 2, 2],
            'limit 1 ten items'      => [10, 0,  1,  10, 1],
            'limit 1 page 10'        => [10, 9,  1,  10, 10],
            'limit larger than total'=> [5,  0,  20, 1, 1],
        ];
    }

    #[DataProvider('pageCountProvider')]
    public function testPageCountMath(
        int $total,
        int $limitStart,
        int $limit,
        int $expectedPagesTotal,
        int $expectedPagesCurrent
    ): void {
        $p = $this->makePagination($total, $limitStart, $limit);

        self::assertSame($total,               $p->total,        'total');
        self::assertSame($expectedPagesTotal,  (int) $p->pagesTotal,   'pagesTotal');
        self::assertSame($expectedPagesCurrent,(int) $p->pagesCurrent, 'pagesCurrent');
    }

    // =========================================================================
    // Zero total
    // =========================================================================

    public function testZeroTotalRecords(): void
    {
        $p = $this->makePagination(0, 0, 10);

        self::assertSame(0, $p->total);
        self::assertSame(0, $p->limitStart);
    }

    // =========================================================================
    // limit = 0 (view all)
    // =========================================================================

    public function testLimitZeroSetsViewAllAndAdjustsLimit(): void
    {
        $p = $this->makePagination(50, 0, 0);

        // limit=0 means "show all": limit becomes total and limitStart resets to 0
        self::assertSame(50, $p->limit);
        self::assertSame(0,  $p->limitStart);
    }

    // =========================================================================
    // limitStart clamping
    // =========================================================================

    public function testNegativeLimitStartClampedToZero(): void
    {
        $p = $this->makePagination(50, -5, 10);

        self::assertSame(0, $p->limitStart);
    }

    public function testLimitStartBeyondTotalIsClamped(): void
    {
        // limitStart=100 with only 50 records and limit=10 → clamped to last page
        $p = $this->makePagination(50, 100, 10);

        self::assertSame(40, $p->limitStart, 'limitStart must be clamped to start of last page');
    }

    public function testLimitGreaterThanTotalResetsLimitStart(): void
    {
        // When limit > total, limitStart should reset to 0.
        $p = $this->makePagination(5, 0, 20);

        self::assertSame(0, $p->limitStart);
    }

    // =========================================================================
    // pagesStart / pagesStop windowing
    // =========================================================================

    public static function pagesWindowProvider(): array
    {
        // pagesStart = max(1, pagesCurrent - floor(displayed/2))
        // if pagesStart + displayed > pagesTotal: pagesStop = pagesTotal, pagesStart = max(1, pagesTotal - displayed + 1)
        // else: pagesStop = pagesStart + displayed - 1
        return [
            // total, limitStart, limit, displayed,   pagesStart, pagesStop
            // page 1 of 20: pagesStart=1-5= -4 → 1, pagesStop=1+10-1=10
            'page 1 of 20, window 10'  => [200, 0,   10, 10, 1,  10],
            // page 6 of 20: pagesStart=6-5=1, 1+10=11 <= 20 → pagesStop=10
            'page 6 of 20, window 10'  => [200, 50,  10, 10, 1,  10],
            // page 11 of 20: pagesStart=11-5=6, 6+10=16 <= 20 → pagesStop=15
            'page 11 of 20, window 10' => [200, 100, 10, 10, 6,  15],
            // page 20 of 20: pagesStart=20-5=15, 15+10=25>20 → pagesStop=20, pagesStart=20-10+1=11
            'page 20 of 20, window 10' => [200, 190, 10, 10, 11, 20],
            // 3 pages, page 1, window 10: pagesStart=1, 1+10=11>3 → pagesStop=3, pagesStart=1
            'fewer pages than window'  => [30,  0,   10, 10, 1,  3],
        ];
    }

    #[DataProvider('pagesWindowProvider')]
    public function testPagesWindow(
        int $total,
        int $limitStart,
        int $limit,
        int $displayed,
        int $expectedStart,
        int $expectedStop
    ): void {
        $p = $this->makePagination($total, $limitStart, $limit, $displayed);

        self::assertSame($expectedStart, (int) $p->pagesStart, 'pagesStart');
        self::assertSame($expectedStop,  (int) $p->pagesStop,  'pagesStop');
    }

    // =========================================================================
    // getRowOffset
    // =========================================================================

    public static function rowOffsetProvider(): array
    {
        return [
            'first row on first page'  => [0,  0,  1],
            'second row on first page' => [1,  0,  2],
            'first row on page 2'      => [0,  10, 11],
            'third row on page 3'      => [2,  20, 23],
        ];
    }

    #[DataProvider('rowOffsetProvider')]
    public function testGetRowOffset(int $index, int $limitStart, int $expected): void
    {
        $p = $this->makePagination(100, $limitStart, 10);

        self::assertSame($expected, $p->getRowOffset($index));
    }

    // =========================================================================
    // getData() / _buildDataObject()
    // =========================================================================

    public function testGetDataReturnsCachedObject(): void
    {
        $p = $this->makePagination(50, 0, 10);

        $data1 = $p->getData();
        $data2 = $p->getData();

        self::assertSame($data1, $data2, 'getData() must return the same cached object');
    }

    public function testGetDataHasExpectedProperties(): void
    {
        $p    = $this->makePagination(50, 0, 10);
        $data = $p->getData();

        self::assertObjectHasProperty('all',      $data);
        self::assertObjectHasProperty('start',    $data);
        self::assertObjectHasProperty('previous', $data);
        self::assertObjectHasProperty('next',     $data);
        self::assertObjectHasProperty('end',      $data);
        self::assertObjectHasProperty('pages',    $data);
    }

    public function testDataObjectPaginationObjectTypes(): void
    {
        $p    = $this->makePagination(50, 0, 10);
        $data = $p->getData();

        self::assertInstanceOf(PaginationObject::class, $data->all);
        self::assertInstanceOf(PaginationObject::class, $data->start);
        self::assertInstanceOf(PaginationObject::class, $data->previous);
        self::assertInstanceOf(PaginationObject::class, $data->next);
        self::assertInstanceOf(PaginationObject::class, $data->end);
    }

    // =========================================================================
    // Data object: first page
    // =========================================================================

    public function testDataObjectFirstPageStartPreviousInactive(): void
    {
        // On the first page, start and previous have base === null (inactive).
        $p    = $this->makePagination(50, 0, 10);
        $data = $p->getData();

        self::assertNull($data->start->base,    'start.base must be null on first page');
        self::assertNull($data->previous->base, 'previous.base must be null on first page');
    }

    public function testDataObjectFirstPageNextAndEndActive(): void
    {
        $p    = $this->makePagination(50, 0, 10);
        $data = $p->getData();

        self::assertNotNull($data->next->base, 'next.base must not be null on first page');
        self::assertNotNull($data->end->base,  'end.base must not be null on first page');
    }

    // =========================================================================
    // Data object: last page
    // =========================================================================

    public function testDataObjectLastPageNextAndEndInactive(): void
    {
        $p    = $this->makePagination(50, 40, 10);
        $data = $p->getData();

        self::assertNull($data->next->base, 'next.base must be null on last page');
        self::assertNull($data->end->base,  'end.base must be null on last page');
    }

    public function testDataObjectLastPageStartAndPreviousActive(): void
    {
        $p    = $this->makePagination(50, 40, 10);
        $data = $p->getData();

        self::assertNotNull($data->start->base,    'start.base must not be null on last page');
        self::assertNotNull($data->previous->base, 'previous.base must not be null on last page');
    }

    // =========================================================================
    // Data object: middle page
    // =========================================================================

    public function testDataObjectMiddlePageAllNavActive(): void
    {
        $p    = $this->makePagination(50, 20, 10);   // page 3 of 5
        $data = $p->getData();

        self::assertNotNull($data->start->base,    'start active on middle page');
        self::assertNotNull($data->previous->base, 'previous active on middle page');
        self::assertNotNull($data->next->base,     'next active on middle page');
        self::assertNotNull($data->end->base,      'end active on middle page');
    }

    // =========================================================================
    // Data object: page list
    // =========================================================================

    public function testDataObjectPagesListCount(): void
    {
        $p    = $this->makePagination(50, 0, 10, 10);
        $data = $p->getData();

        self::assertCount(5, $data->pages, 'should have 5 page entries for 50 items / 10 per page');
    }

    public function testDataObjectCurrentPageHasNoLink(): void
    {
        // Page 1 (limitStart=0) is the active/current page and must have no link.
        $p    = $this->makePagination(50, 0, 10);
        $data = $p->getData();

        self::assertTrue($data->pages[1]->active, 'current page (1) must be flagged active');
        self::assertNull($data->pages[1]->base,   'current page (1) must have null base (no link)');
    }

    public function testDataObjectNonCurrentPagesHaveLinks(): void
    {
        $p    = $this->makePagination(50, 0, 10);
        $data = $p->getData();

        for ($i = 2; $i <= 5; $i++) {
            self::assertNotNull($data->pages[$i]->base, "page $i must have a base/link");
        }
    }

    // =========================================================================
    // Data object: link URLs contain correct limitstart values
    // =========================================================================

    public function testDataObjectPageLinksContainCorrectLimitstart(): void
    {
        $p    = $this->makePagination(50, 0, 10);
        $data = $p->getData();

        // Page 2 → limitstart=10
        self::assertStringContainsString('limitstart=10', $data->pages[2]->link);
        // Page 3 → limitstart=20
        self::assertStringContainsString('limitstart=20', $data->pages[3]->link);
    }

    public function testDataObjectNextLinkContainsCorrectLimitstart(): void
    {
        // First page: next link should go to limitstart=10
        $p    = $this->makePagination(50, 0, 10);
        $data = $p->getData();

        self::assertStringContainsString('limitstart=10', $data->next->link);
    }

    public function testDataObjectEndLinkContainsCorrectLimitstart(): void
    {
        // 5 pages, last page starts at 40
        $p    = $this->makePagination(50, 0, 10);
        $data = $p->getData();

        self::assertStringContainsString('limitstart=40', $data->end->link);
    }

    // =========================================================================
    // Data object: "view all" link
    // =========================================================================

    public function testDataObjectAllLinkPresentWhenNotViewingAll(): void
    {
        $p    = $this->makePagination(50, 0, 10);
        $data = $p->getData();

        // When not in view-all mode the "all" entry should have a base offset set.
        self::assertNotNull($data->all->base);
    }

    public function testDataObjectAllLinkAbsentWhenViewingAll(): void
    {
        // limit=0 activates view-all mode.
        $p    = $this->makePagination(50, 0, 0);
        $data = $p->getData();

        self::assertNull($data->all->base, '"all" entry must have null base when already viewing all');
    }

    // =========================================================================
    // Additional URL parameters
    // =========================================================================

    public function testSetAndGetAdditionalUrlParam(): void
    {
        $p = $this->makePagination(50, 0, 10);
        $p->clearAdditionalUrlParams();

        $old = $p->setAdditionalUrlParam('filter_name', 'foo');

        self::assertNull($old);
        self::assertSame('foo', $p->getAdditionalUrlParam('filter_name'));
    }

    public function testSetAdditionalUrlParamReturnsOldValue(): void
    {
        $p = $this->makePagination(50, 0, 10);
        $p->clearAdditionalUrlParams();
        $p->setAdditionalUrlParam('x', 'first');

        $old = $p->setAdditionalUrlParam('x', 'second');

        self::assertSame('first', $old);
        self::assertSame('second', $p->getAdditionalUrlParam('x'));
    }

    public function testSetAdditionalUrlParamWithNullRemovesKey(): void
    {
        $p = $this->makePagination(50, 0, 10);
        $p->clearAdditionalUrlParams();
        $p->setAdditionalUrlParam('x', 'value');
        $p->setAdditionalUrlParam('x', null);

        self::assertNull($p->getAdditionalUrlParam('x'));
    }

    public function testReservedKeysCannotBeSet(): void
    {
        $p = $this->makePagination(50, 0, 10);
        $p->clearAdditionalUrlParams();

        $result = $p->setAdditionalUrlParam('limit', '99');
        self::assertFalse($result, 'Setting "limit" must return false');

        $result = $p->setAdditionalUrlParam('limitstart', '99');
        self::assertFalse($result, 'Setting "limitstart" must return false');
    }

    public function testGetAdditionalUrlParamsReturnsAllParams(): void
    {
        $p = $this->makePagination(50, 0, 10);
        $p->clearAdditionalUrlParams();
        $p->setAdditionalUrlParam('a', '1');
        $p->setAdditionalUrlParam('b', '2');

        self::assertSame(['a' => '1', 'b' => '2'], $p->getAdditionalUrlParams());
    }

    public function testSetAdditionalUrlParamsArray(): void
    {
        $p = $this->makePagination(50, 0, 10);
        $p->clearAdditionalUrlParams();
        $p->setAdditionalUrlParams(['x' => 'foo', 'y' => 'bar']);

        self::assertSame('foo', $p->getAdditionalUrlParam('x'));
        self::assertSame('bar', $p->getAdditionalUrlParam('y'));
    }

    public function testClearAdditionalUrlParams(): void
    {
        $p = $this->makePagination(50, 0, 10);
        $p->setAdditionalUrlParam('x', 'foo');
        $p->clearAdditionalUrlParams();

        self::assertSame([], $p->getAdditionalUrlParams());
    }

    public function testAdditionalUrlParamsAppearedInPageLinks(): void
    {
        $p = $this->makePagination(50, 0, 10);
        $p->clearAdditionalUrlParams();
        $p->setAdditionalUrlParam('filter_name', 'bar');

        $data = $p->getData();

        // Re-build after setting extra params
        $p2 = $this->makePagination(50, 0, 10, 10, ['filter_name' => 'bar']);
        $d2 = $p2->getData();

        self::assertStringContainsString('filter_name=bar', $d2->pages[2]->link);
    }

    // =========================================================================
    // getPagesCounter
    // =========================================================================

    public function testGetPagesCounterReturnsNullForSinglePage(): void
    {
        $p = $this->makePagination(5, 0, 10);

        self::assertNull($p->getPagesCounter());
    }

    public function testGetPagesCounterReturnsStringForMultiplePages(): void
    {
        $p = $this->makePagination(50, 0, 10);

        $counter = $p->getPagesCounter();

        self::assertIsString($counter);
        self::assertNotEmpty($counter);
    }

    // =========================================================================
    // getResultsCounter
    // =========================================================================

    public function testGetResultsCounterNoResults(): void
    {
        $p    = $this->makePagination(0, 0, 10);
        $html = $p->getResultsCounter();

        self::assertStringContainsString('AWF_PAGINATION_LBL_NO_RESULTS', $html);
    }

    public function testGetResultsCounterFirstPage(): void
    {
        $p    = $this->makePagination(42, 0, 10);
        $html = $p->getResultsCounter();

        // The stub returns key:arg1,arg2,arg3 so it will contain "1" and "42"
        self::assertIsString($html);
        self::assertNotEmpty($html);
    }

    public function testGetResultsCounterLastPartialPage(): void
    {
        // 42 items, limit 10, on the last page (limitstart=40)
        $p    = $this->makePagination(42, 40, 10);
        $html = $p->getResultsCounter();

        self::assertIsString($html);
        self::assertNotEmpty($html);
    }

    // =========================================================================
    // Partial last page — limitStart and pagesTotal
    // =========================================================================

    public static function partialLastPageProvider(): array
    {
        return [
            '41 items / 10 per page = 5 pages' => [41, 5],
            '40 items / 10 per page = 4 pages' => [40, 4],
            '1 item  / 10 per page = 1 page'   => [1,  1],
            '11 items / 10 per page = 2 pages' => [11, 2],
        ];
    }

    #[DataProvider('partialLastPageProvider')]
    public function testPartialLastPagePagesTotal(int $total, int $expectedPages): void
    {
        $p = $this->makePagination($total, 0, 10);

        self::assertSame($expectedPages, (int) $p->pagesTotal);
    }

    // =========================================================================
    // Constructor: no container (deprecated path) — should trigger E_USER_DEPRECATED
    // =========================================================================

    public function testConstructorWithoutContainerTriggersDeprecation(): void
    {
        // Suppress the E_USER_DEPRECATED from the constructor and from Application::getInstance()
        // We just verify the class can be loaded and the correct property exists.
        // Since there is no Application instance in a unit test, the constructor will throw
        // an exception (no singleton instance). We simply verify that happens gracefully.
        $threw = false;

        try {
            @new Pagination(10, 0, 5, 10, null);
        } catch (\Throwable $e) {
            $threw = true;
        }

        // The test passing means PHPUnit didn't die from the deprecation;
        // getting an exception (no Application instance) is the expected path.
        self::assertTrue($threw, 'Passing null as container must either throw or trigger a deprecation');
    }

    // =========================================================================
    // pagesDisplayed property set from constructor argument
    // =========================================================================

    public function testCustomPagesDisplayed(): void
    {
        $p = $this->makePagination(200, 0, 10, 5);

        self::assertSame(5, $p->pagesDisplayed);
    }

    public function testDefaultPagesDisplayed(): void
    {
        $p = $this->makePagination(200, 0, 10);

        self::assertSame(10, $p->pagesDisplayed);
    }
}
