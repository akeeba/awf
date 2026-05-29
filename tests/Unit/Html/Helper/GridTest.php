<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Html\Helper;

use Awf\Container\Container;
use Awf\Html\Helper\Basic;
use Awf\Html\Helper\Grid;
use Awf\Html\HtmlService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Grid::class)]
#[CoversClass(Basic::class)]
class GridTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeContainer(): Container
    {
        return new Container(
            [
                'application_name'     => 'unittest',
                'applicationNamespace' => '\\UnitTest',
                'session_segment_name' => 'unittest_segment',
                'filesystemBase'       => '/dev/null',
                'basePath'             => '/dev/null',
                'templatePath'         => '/dev/null',
                'languagePath'         => '/dev/null',
                'temporaryPath'        => '/tmp',
                'sqlPath'              => '/dev/null',
            ]
        );
    }

    /**
     * Build a Grid helper wired to a container that also has the Basic helper
     * registered in the HTML service (needed for checkAll → basic.tooltipText).
     */
    private function makeGrid(): Grid
    {
        $container = $this->makeContainer();

        $basic = new Basic();
        $basic->setContainer($container);

        $htmlService = new HtmlService($container);
        $htmlService->registerHelper('basic', $basic);
        $container['html'] = $htmlService;

        $grid = new Grid();
        $grid->setContainer($container);

        return $grid;
    }

    private function makeBasic(): Basic
    {
        $container = $this->makeContainer();

        $basic = new Basic();
        $basic->setContainer($container);

        return $basic;
    }

    // =========================================================================
    // Grid::getJavascriptPrefix / setJavascriptPrefix
    // =========================================================================

    public function testGetJavascriptPrefixReturnsDefault(): void
    {
        $grid = $this->makeGrid();

        self::assertSame('akeeba.System.', $grid->getJavascriptPrefix());
    }

    public function testSetJavascriptPrefixChangesPrefix(): void
    {
        $grid = $this->makeGrid();
        $grid->setJavascriptPrefix('myApp.');

        self::assertSame('myApp.', $grid->getJavascriptPrefix());
    }

    // =========================================================================
    // Grid::sort
    // =========================================================================

    public function testSortReturnsAnchorElement(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->sort('TITLE', 'name', 'asc', '');

        self::assertStringStartsWith('<a ', $html);
        self::assertStringContainsString('</a>', $html);
    }

    public function testSortContainsTitleText(): void
    {
        $grid = $this->makeGrid();

        // Language falls back to returning key uppercased when not found.
        $html = $grid->sort('MY_COLUMN', 'name', 'asc', '');

        self::assertStringContainsString('MY_COLUMN', $html);
    }

    public function testSortNotSelectedHasNoSortIcon(): void
    {
        $grid = $this->makeGrid();

        // 'name' column is not the selected order ('id')
        $html = $grid->sort('TITLE', 'name', 'asc', 'id');

        self::assertStringNotContainsString('<span class="fa fa-', $html);
    }

    public function testSortSelectedAscHasCaretUpIcon(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->sort('TITLE', 'name', 'asc', 'name');

        self::assertStringContainsString('fa-caret-up', $html);
    }

    public function testSortSelectedDescHasCaretDownIcon(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->sort('TITLE', 'name', 'desc', 'name');

        self::assertStringContainsString('fa-caret-down', $html);
    }

    /**
     * When the column IS currently selected and direction is 'asc', clicking
     * should flip to 'desc', and vice-versa.
     */
    public function testSortSelectedColumnFlipsDirection(): void
    {
        $grid = $this->makeGrid();

        // Currently asc → onclick should contain 'desc'
        $htmlAsc = $grid->sort('TITLE', 'name', 'asc', 'name');
        self::assertStringContainsString("'desc'", $htmlAsc);

        // Currently desc → onclick should contain 'asc'
        $htmlDesc = $grid->sort('TITLE', 'name', 'desc', 'name');
        self::assertStringContainsString("'asc'", $htmlDesc);
    }

    /**
     * When the column is NOT selected the new_direction param is used.
     */
    public function testSortUnselectedColumnUsesNewDirection(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->sort('TITLE', 'name', 'asc', 'id', null, 'desc');

        self::assertStringContainsString("'desc'", $html);
    }

    public function testSortUsesDefaultJavascriptPrefix(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->sort('TITLE', 'name', 'asc', '');

        self::assertStringContainsString('akeeba.System.tableOrdering', $html);
    }

    public function testSortUsesCustomOrderingJsFunction(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->sort('TITLE', 'name', 'asc', '', null, 'asc', '', 'MyApp.reorder');

        self::assertStringContainsString('MyApp.reorder', $html);
        self::assertStringNotContainsString('akeeba.System.tableOrdering', $html);
    }

    public function testSortHasTooltipClass(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->sort('TITLE', 'name', 'asc', '');

        self::assertStringContainsString('hasTooltip', $html);
    }

    public function testSortTipAttributeUsedAsTitleWhenProvided(): void
    {
        $grid = $this->makeGrid();

        // The language service returns the key uppercased when not found.
        $html = $grid->sort('TITLE', 'name', 'asc', '', null, 'asc', 'MY_TIP_KEY');

        self::assertStringContainsString('MY_TIP_KEY', $html);
    }

    public function testSortNullDirectionDefaultsToAsc(): void
    {
        $grid = $this->makeGrid();

        // direction=null should be treated as 'asc'
        $html = $grid->sort('TITLE', 'name', null, 'name');

        self::assertStringContainsString('fa-caret-up', $html);
    }

    public function testSortAdditionalAttribsMerged(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->sort('TITLE', 'name', 'asc', '', null, 'asc', '', '', ['data-foo' => 'bar']);

        self::assertStringContainsString('data-foo="bar"', $html);
    }

    public function testSortAdditionalAttribsCanOverrideDefaults(): void
    {
        $grid = $this->makeGrid();

        // Extra attribs are merged AFTER defaults, so they override them.
        $html = $grid->sort('TITLE', 'name', 'asc', '', null, 'asc', '', '', ['class' => 'custom-class']);

        self::assertStringContainsString('class="custom-class"', $html);
    }

    // =========================================================================
    // Grid::checkAll
    // =========================================================================

    public function testCheckAllReturnsInputElement(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->checkAll();

        self::assertStringContainsString('<input ', $html);
        self::assertStringContainsString('type="checkbox"', $html);
    }

    public function testCheckAllDefaultName(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->checkAll();

        self::assertStringContainsString('name="checkall-toggle"', $html);
    }

    public function testCheckAllCustomName(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->checkAll('my-toggle');

        self::assertStringContainsString('name="my-toggle"', $html);
    }

    public function testCheckAllDefaultAction(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->checkAll();

        self::assertStringContainsString('akeeba.System.checkAll(this)', $html);
    }

    public function testCheckAllCustomAction(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->checkAll('toggle', 'SOME_TIP', 'MyApp.checkAll(this)');

        self::assertStringContainsString('MyApp.checkAll(this)', $html);
    }

    public function testCheckAllHasTooltipClass(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->checkAll();

        self::assertStringContainsString('hasTooltip', $html);
    }

    public function testCheckAllAdditionalAttribs(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->checkAll('toggle', 'TIP', '', ['data-custom' => 'yes']);

        self::assertStringContainsString('data-custom="yes"', $html);
    }

    // =========================================================================
    // Grid::id
    // =========================================================================

    public function testIdReturnsLabelAndCheckbox(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->id(0, 42);

        self::assertStringContainsString('<label ', $html);
        self::assertStringContainsString('<input ', $html);
        self::assertStringContainsString('type="checkbox"', $html);
    }

    public function testIdCheckboxIdMatchesRowNum(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->id(3, 99);

        self::assertStringContainsString('id="cb3"', $html);
    }

    public function testIdLabelForMatchesCheckboxId(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->id(5, 10);

        self::assertStringContainsString('for="cb5"', $html);
    }

    public function testIdCheckboxValueIsRecordId(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->id(0, 77);

        self::assertStringContainsString('value="77"', $html);
    }

    public function testIdDefaultName(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->id(0, 1);

        self::assertStringContainsString('name="cid[]"', $html);
    }

    public function testIdCustomName(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->id(0, 1, false, 'myids');

        self::assertStringContainsString('name="myids[]"', $html);
    }

    public function testIdNotCheckedOutHasNoDisabled(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->id(0, 1, false);

        self::assertStringNotContainsString('disabled', $html);
    }

    public function testIdCheckedOutAddsDisabled(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->id(0, 1, true);

        self::assertStringContainsString('disabled="disabled"', $html);
    }

    public function testIdDefaultOnclickContainsDefaultPrefix(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->id(0, 1);

        self::assertStringContainsString('akeeba.System.isChecked', $html);
    }

    public function testIdCustomCheckedJs(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->id(0, 1, false, 'cid', 'MyApp.isChecked');

        self::assertStringContainsString('MyApp.isChecked', $html);
    }

    public function testIdCustomAltLabel(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->id(0, 1, false, 'cid', '', 'Select Row');

        self::assertStringContainsString('Select Row', $html);
    }

    public function testIdLabelHasVisuallyHiddenClass(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->id(0, 1);

        self::assertStringContainsString('visually-hidden', $html);
    }

    public function testIdLabelComesBeforeCheckbox(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->id(0, 1);

        $labelPos   = strpos($html, '<label');
        $checkboxPos = strpos($html, '<input');

        self::assertLessThan($checkboxPos, $labelPos);
    }

    public function testIdAdditionalCheckboxAttribs(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->id(0, 1, false, 'cid', '', '', ['data-row' => '42']);

        self::assertStringContainsString('data-row="42"', $html);
    }

    public function testIdAdditionalLabelAttribs(): void
    {
        $grid = $this->makeGrid();

        $html = $grid->id(0, 1, false, 'cid', '', '', [], ['data-label' => 'row-label']);

        self::assertStringContainsString('data-label="row-label"', $html);
    }

    // =========================================================================
    // Basic::link
    // =========================================================================

    public function testLinkReturnsAnchor(): void
    {
        $basic = $this->makeBasic();

        $html = $basic->link('https://example.com', 'Click me');

        self::assertSame('<a href="https://example.com" >Click me</a>', $html);
    }

    public function testLinkWithAttribs(): void
    {
        $basic = $this->makeBasic();

        $html = $basic->link('https://example.com', 'Click me', ['target' => '_blank', 'class' => 'btn']);

        self::assertStringContainsString('href="https://example.com"', $html);
        self::assertStringContainsString('target="_blank"', $html);
        self::assertStringContainsString('class="btn"', $html);
        self::assertStringContainsString('Click me', $html);
    }

    public function testLinkWithNullAttribs(): void
    {
        $basic = $this->makeBasic();

        $html = $basic->link('/foo', 'Foo', null);

        self::assertStringContainsString('href="/foo"', $html);
        self::assertStringContainsString('Foo', $html);
    }

    // =========================================================================
    // Basic::iframe
    // =========================================================================

    public function testIframeReturnsIframeElement(): void
    {
        $basic = $this->makeBasic();

        $html = $basic->iframe('https://example.com', 'myframe');

        self::assertStringContainsString('<iframe', $html);
        self::assertStringContainsString('src="https://example.com"', $html);
        self::assertStringContainsString('name="myframe"', $html);
        self::assertStringContainsString('</iframe>', $html);
    }

    public function testIframeNoFramesMessageIncluded(): void
    {
        $basic = $this->makeBasic();

        $html = $basic->iframe('https://example.com', 'f', null, 'Iframes not supported');

        self::assertStringContainsString('Iframes not supported', $html);
    }

    public function testIframeWithAttribs(): void
    {
        $basic = $this->makeBasic();

        $html = $basic->iframe('https://example.com', 'f', ['width' => '800', 'height' => '600']);

        self::assertStringContainsString('width="800"', $html);
        self::assertStringContainsString('height="600"', $html);
    }

    // =========================================================================
    // Basic::image
    // =========================================================================

    public function testImageReturnsImgElement(): void
    {
        $basic = $this->makeBasic();

        $html = $basic->image('/path/to/image.png', 'Alt text');

        self::assertStringContainsString('<img', $html);
        self::assertStringContainsString('src="/path/to/image.png"', $html);
        self::assertStringContainsString('alt="Alt text"', $html);
    }

    public function testImageWithAttribsArray(): void
    {
        $basic = $this->makeBasic();

        $html = $basic->image('/img.png', 'Alt', ['class' => 'responsive']);

        self::assertStringContainsString('class="responsive"', $html);
    }

    // =========================================================================
    // Basic::tooltipText
    // =========================================================================

    public function testTooltipTextEmptyWhenBothEmpty(): void
    {
        $basic = $this->makeBasic();

        self::assertSame('', $basic->tooltipText('', ''));
    }

    public function testTooltipTextReturnsTitleOnlyWhenNoContent(): void
    {
        $basic = $this->makeBasic();

        // No translation loaded, so language->text returns the key uppercased.
        // title='Hello', content='' → returns 'HELLO'
        $result = $basic->tooltipText('Hello', '', false, false);

        self::assertSame('Hello', $result);
    }

    public function testTooltipTextReturnsContentOnlyWhenTitleEmpty(): void
    {
        $basic = $this->makeBasic();

        $result = $basic->tooltipText('', 'Some content', false, false);

        self::assertSame('Some content', $result);
    }

    public function testTooltipTextReturnsBoldTitleWhenTitleEqualsContent(): void
    {
        $basic = $this->makeBasic();

        $result = $basic->tooltipText('Same', 'Same', false, false);

        self::assertSame('<strong>Same</strong>', $result);
    }

    public function testTooltipTextReturnsTitleAndContentWhenBothSet(): void
    {
        $basic = $this->makeBasic();

        $result = $basic->tooltipText('Title', 'Body', false, false);

        self::assertSame('<strong>Title</strong><br />Body', $result);
    }

    public function testTooltipTextSplitsDoubleColonFormat(): void
    {
        $basic = $this->makeBasic();

        // 'Title::Content' should be split
        $result = $basic->tooltipText('Title::Content', '', false, false);

        self::assertStringContainsString('<strong>Title</strong>', $result);
        self::assertStringContainsString('Content', $result);
    }

    public function testTooltipTextEscapesQuotes(): void
    {
        $basic = $this->makeBasic();

        // escape=true (default), translate=false
        $result = $basic->tooltipText('Say "hi"', 'Say "hello"', false, true);

        self::assertStringContainsString('Say &quot;hi&quot;', $result);
        self::assertStringContainsString('Say &quot;hello&quot;', $result);
    }

    // =========================================================================
    // Basic::setFormatOptions (deprecated)
    // =========================================================================

    public function testSetFormatOptionsTriggersDeprecation(): void
    {
        $basic = $this->makeBasic();

        $triggered = false;

        set_error_handler(
            function (int $errno) use (&$triggered): bool {
                if ($errno === E_USER_DEPRECATED)
                {
                    $triggered = true;
                }

                return true;
            },
            E_USER_DEPRECATED
        );

        $basic->setFormatOptions([]);

        restore_error_handler();

        self::assertTrue($triggered, 'Expected E_USER_DEPRECATED to be triggered by setFormatOptions().');
    }
}
