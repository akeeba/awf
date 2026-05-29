<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Html\Helper;

use Awf\Container\Container;
use Awf\Html\Helper\Accordion;
use Awf\Html\Helper\Tabs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Tabs::class)]
#[CoversClass(Accordion::class)]
class TabsAccordionTest extends TestCase
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

    private function makeTabs(): Tabs
    {
        $tabs = new Tabs();
        $tabs->setContainer($this->makeContainer());
        return $tabs;
    }

    private function makeAccordion(): Accordion
    {
        $accordion = new Accordion();
        $accordion->setContainer($this->makeContainer());
        return $accordion;
    }

    // =========================================================================
    // Tabs helper
    // =========================================================================

    // -------------------------------------------------------------------------
    // Tabs::getName
    // -------------------------------------------------------------------------

    public function testTabsGetNameReturnsLowercaseClassName(): void
    {
        self::assertSame('tabs', $this->makeTabs()->getName());
    }

    // -------------------------------------------------------------------------
    // Tabs::start — nav style
    // -------------------------------------------------------------------------

    public function testTabsStartDefaultUsesNavStyle(): void
    {
        $html = $this->makeTabs()->start();

        self::assertStringContainsString('nav-nav', $html);
        self::assertStringContainsString('class="nav', $html);
    }

    public function testTabsStartPillsUsePillsStyle(): void
    {
        $html = $this->makeTabs()->start(true);

        self::assertStringContainsString('nav-pills', $html);
        self::assertStringNotContainsString('nav-nav', $html);
    }

    public function testTabsStartFalseExplicitlyUsesNavStyle(): void
    {
        $html = $this->makeTabs()->start(false);

        self::assertStringContainsString('nav-nav', $html);
    }

    public function testTabsStartOutputsOpeningUlTag(): void
    {
        $html = $this->makeTabs()->start();

        self::assertStringContainsString('<ul', $html);
    }

    // -------------------------------------------------------------------------
    // Tabs::addNav
    // -------------------------------------------------------------------------

    public function testTabsAddNavContainsId(): void
    {
        $html = $this->makeTabs()->addNav('my-tab', 'My Title');

        self::assertStringContainsString('my-tab', $html);
    }

    public function testTabsAddNavContainsTitle(): void
    {
        $html = $this->makeTabs()->addNav('my-tab', 'My Title');

        self::assertStringContainsString('My Title', $html);
    }

    public function testTabsAddNavHrefPointsToId(): void
    {
        $html = $this->makeTabs()->addNav('tab-xyz', 'Label');

        self::assertStringContainsString('href="#tab-xyz"', $html);
    }

    public function testTabsAddNavContainsDataToggleTab(): void
    {
        $html = $this->makeTabs()->addNav('t1', 'Title');

        self::assertStringContainsString('data-toggle="tab"', $html);
    }

    public function testTabsAddNavContainsListItem(): void
    {
        $html = $this->makeTabs()->addNav('t1', 'Title');

        self::assertStringContainsString('<li>', $html);
        self::assertStringContainsString('</li>', $html);
    }

    public function testTabsAddNavSpecialCharsInTitleArePreserved(): void
    {
        $html = $this->makeTabs()->addNav('t1', '<b>Bold</b>');

        self::assertStringContainsString('<b>Bold</b>', $html);
    }

    // -------------------------------------------------------------------------
    // Tabs::startContent
    // -------------------------------------------------------------------------

    public function testTabsStartContentClosesUl(): void
    {
        $html = $this->makeTabs()->startContent();

        self::assertStringContainsString('</ul>', $html);
    }

    public function testTabsStartContentOpensTabContentDiv(): void
    {
        $html = $this->makeTabs()->startContent();

        self::assertStringContainsString('class="tab-content"', $html);
    }

    // -------------------------------------------------------------------------
    // Tabs::tab
    // -------------------------------------------------------------------------

    public function testTabsTabContainsIdAttribute(): void
    {
        $html = $this->makeTabs()->tab('panel-one');

        self::assertStringContainsString('id="panel-one"', $html);
    }

    public function testTabsTabContainsTabPaneClass(): void
    {
        $html = $this->makeTabs()->tab('panel-one');

        self::assertStringContainsString('tab-pane', $html);
    }

    public function testTabsTabNotActiveByDefault(): void
    {
        $html = $this->makeTabs()->tab('panel-one');

        // When not active, the active class should not be present (or be empty)
        // The output contains class="tab-pane  " (with empty active + fade strings)
        // so we check the active keyword is not a standalone class value
        self::assertStringNotContainsString('class="tab-pane active', $html);
    }

    public function testTabsTabActiveContainsActiveClass(): void
    {
        $html = $this->makeTabs()->tab('panel-one', true);

        self::assertStringContainsString('active', $html);
    }

    public function testTabsTabActiveFadeContainsInClass(): void
    {
        $html = $this->makeTabs()->tab('panel-one', true, true);

        self::assertStringContainsString('in', $html);
        self::assertStringContainsString('fade', $html);
    }

    public function testTabsTabInactiveWithFadeContainsFadeButNotIn(): void
    {
        $html = $this->makeTabs()->tab('panel-one', false, true);

        // When inactive but fade=true, there's no 'in' class (only 'fade')
        self::assertStringContainsString('fade', $html);
        // 'in' as part of 'active in' should not appear since not active
        self::assertStringNotContainsString('active in', $html);
    }

    public function testTabsTabActiveNoFadeNoInClass(): void
    {
        $html = $this->makeTabs()->tab('panel-one', true, false);

        self::assertStringContainsString('active', $html);
        // Without fade, 'in' should not appear as a separate class token
        self::assertStringNotContainsString('active in', $html);
    }

    // -------------------------------------------------------------------------
    // Tabs::end
    // -------------------------------------------------------------------------

    public function testTabsEndClosesContentDiv(): void
    {
        $html = $this->makeTabs()->end();

        self::assertStringContainsString('</div>', $html);
    }

    // -------------------------------------------------------------------------
    // Tabs: full round-trip markup structure
    // -------------------------------------------------------------------------

    public function testTabsFullRoundTripHasCoherentStructure(): void
    {
        $t = $this->makeTabs();

        $html = $t->start()
            . $t->addNav('tab1', 'Tab One')
            . $t->addNav('tab2', 'Tab Two')
            . $t->startContent()
            . $t->tab('tab1', true)
            . '<p>Content 1</p>'
            . $t->tab('tab2')
            . '<p>Content 2</p>'
            . $t->end();

        self::assertStringContainsString('nav-nav', $html);
        self::assertStringContainsString('href="#tab1"', $html);
        self::assertStringContainsString('href="#tab2"', $html);
        self::assertStringContainsString('id="tab1"', $html);
        self::assertStringContainsString('id="tab2"', $html);
        self::assertStringContainsString('Content 1', $html);
        self::assertStringContainsString('Content 2', $html);
    }

    // =========================================================================
    // Accordion helper
    // =========================================================================

    // -------------------------------------------------------------------------
    // Accordion::getName
    // -------------------------------------------------------------------------

    public function testAccordionGetNameReturnsLowercaseClassName(): void
    {
        self::assertSame('accordion', $this->makeAccordion()->getName());
    }

    // -------------------------------------------------------------------------
    // Accordion::start
    // -------------------------------------------------------------------------

    public function testAccordionStartContainsPanelGroupClass(): void
    {
        $html = $this->makeAccordion()->start('my-accordion');

        self::assertStringContainsString('panel-group', $html);
    }

    public function testAccordionStartContainsId(): void
    {
        $html = $this->makeAccordion()->start('my-accordion');

        self::assertStringContainsString('id="my-accordion"', $html);
    }

    public function testAccordionStartOpensDiv(): void
    {
        $html = $this->makeAccordion()->start('my-accordion');

        self::assertStringContainsString('<div', $html);
    }

    // -------------------------------------------------------------------------
    // Accordion::end
    // -------------------------------------------------------------------------

    public function testAccordionEndClosesOuterDiv(): void
    {
        $html = $this->makeAccordion()->end();

        self::assertStringContainsString('</div>', $html);
    }

    // -------------------------------------------------------------------------
    // Accordion::panel (static method)
    // -------------------------------------------------------------------------

    public function testAccordionPanelContainsTitle(): void
    {
        $html = Accordion::panel('My Panel Title', 'panel1', 'accordion1');

        self::assertStringContainsString('My Panel Title', $html);
    }

    public function testAccordionPanelContainsPanelId(): void
    {
        $html = Accordion::panel('Title', 'panel-abc', 'acc1');

        self::assertStringContainsString('id="panel-abc"', $html);
        self::assertStringContainsString('href="#panel-abc"', $html);
    }

    public function testAccordionPanelContainsAccordionId(): void
    {
        $html = Accordion::panel('Title', 'p1', 'accordion-xyz');

        self::assertStringContainsString('data-parent="#accordion-xyz"', $html);
    }

    public function testAccordionPanelDefaultStyleIsDefault(): void
    {
        $html = Accordion::panel('Title', 'p1', 'acc1');

        self::assertStringContainsString('panel-default', $html);
    }

    public function testAccordionPanelCustomStyleIsApplied(): void
    {
        foreach (['warning', 'info', 'success', 'danger'] as $style)
        {
            $html = Accordion::panel('Title', 'p1', 'acc1', $style);
            self::assertStringContainsString('panel-' . $style, $html, "Expected panel-$style in output");
        }
    }

    public function testAccordionPanelClosedByDefault(): void
    {
        $html = Accordion::panel('Title', 'p1', 'acc1');

        // When closed, the 'in' class should not be present in the collapse div
        // The output contains "collapse " with an empty $in variable
        self::assertStringNotContainsString('collapse in', $html);
    }

    public function testAccordionPanelOpenContainsInClass(): void
    {
        $html = Accordion::panel('Title', 'p1', 'acc1', 'default', true);

        self::assertStringContainsString('collapse in', $html);
    }

    public function testAccordionPanelContainsDataToggleCollapse(): void
    {
        $html = Accordion::panel('Title', 'p1', 'acc1');

        self::assertStringContainsString('data-toggle="collapse"', $html);
    }

    public function testAccordionPanelContainsPanelBodyStructure(): void
    {
        $html = Accordion::panel('Title', 'p1', 'acc1');

        self::assertStringContainsString('panel-heading', $html);
        self::assertStringContainsString('panel-body', $html);
        self::assertStringContainsString('panel-title', $html);
    }

    public function testAccordionPanelSpecialCharsInTitleArePreserved(): void
    {
        $html = Accordion::panel('<em>Emphasis</em>', 'p1', 'acc1');

        self::assertStringContainsString('<em>Emphasis</em>', $html);
    }

    // -------------------------------------------------------------------------
    // Accordion: full round-trip markup structure
    // -------------------------------------------------------------------------

    public function testAccordionFullRoundTripHasCoherentStructure(): void
    {
        $a = $this->makeAccordion();
        $accordionId = 'test-accordion';

        $html = $a->start($accordionId)
            . Accordion::panel('Panel 1', 'p1', $accordionId, 'default', true)
            . '<p>Content 1</p>'
            . Accordion::panel('Panel 2', 'p2', $accordionId)
            . '<p>Content 2</p>'
            . $a->end();

        self::assertStringContainsString('id="test-accordion"', $html);
        self::assertStringContainsString('Panel 1', $html);
        self::assertStringContainsString('Panel 2', $html);
        self::assertStringContainsString('id="p1"', $html);
        self::assertStringContainsString('id="p2"', $html);
        self::assertStringContainsString('Content 1', $html);
        self::assertStringContainsString('Content 2', $html);
        // First panel is open
        self::assertStringContainsString('collapse in', $html);
        // The accordion uses data-parent pointing to the accordion id
        self::assertStringContainsString('data-parent="#test-accordion"', $html);
    }
}
