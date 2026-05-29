<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Document;

use Awf\Container\Container;
use Awf\Document\Menu\Item;
use Awf\Document\Menu\MenuManager;
use Awf\Document\Toolbar\Button;
use Awf\Document\Toolbar\Toolbar;
use Awf\Router\Router;
use Awf\Uri\Uri;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Awf\Document\Toolbar\{Toolbar,Button} and
 * Awf\Document\Menu\{MenuManager,Item}.
 *
 * Covered:
 *  Button
 *  - Construction from options array (title, icon, class, id, onClick, url)
 *  - getId() auto-derives from title when no id is set
 *  - getId() uses explicit id when set
 *  - setId / getClass / getIcon / getTitle / getOnClick / getUrl round-trips
 *
 *  Toolbar
 *  - setTitle / getTitle
 *  - addButton() keyed by getId()
 *  - addButtonFromDefinition() delegates to Button+addButton()
 *  - getButtons() returns accumulated buttons
 *  - removeButton() removes by Button id
 *  - removeButtonByName() removes by raw name
 *  - removeButtonByName() on missing name is a no-op
 *  - setButtons() replaces the list; clearButtons() empties it
 *  - findButton() happy path returns the Button
 *  - findButton() throws on missing name
 *
 *  Item
 *  - Construction with name+title; missing name raises exception; missing both
 *    title and titleHandler raises exception
 *  - getName() / getTitle() / getGroup() / getIcon() / getParent() / getShow()
 *    / getOrder() / getOnClick() / getParams() round-trips
 *  - setName() sanitises via Filter::clean('cmd')
 *  - getParams($asQueryString=true) builds a query string
 *  - setShow() accepts string and array; getShow() reflects the list
 *  - setTitleHandler() with string callable, array callable, invalid value
 *  - getTitle() resolves titleHandler when title is empty
 *  - getUrl() returns custom url when set
 *  - getUrl() builds from params via router when no custom url
 *  - addChild() / getChildren() / removeChild() / resetChildren()
 *  - isActive() – custom-url exact match
 *  - isActive() – params match current URI
 *  - isActive() – no match (different param value)
 *  - isActive() – no params and URL mismatch → false
 *
 *  MenuManager
 *  - addItem() / findItem() happy path
 *  - findItem() throws on missing name
 *  - addItemFromDefinition() creates+adds
 *  - removeItem() / removeItemByName() / clear()
 *  - getMenuItems() builds a ROOT tree filtered by menu type
 *  - getMenuItems() respects group filter
 *  - getMenuItems() orders children by Item::order
 *  - getMenuItems() nests children under their parent
 *  - isEnabled() defaults to true
 *  - disableMenu() / enableMenu() toggle
 */
class ToolbarMenuTest extends TestCase
{
    // =========================================================================
    // Infrastructure helpers
    // =========================================================================

    private Container $container;

    protected function setUp(): void
    {
        // Reset the Uri singleton cache between tests so that isActive() tests
        // do not bleed into each other.
        $ref = new \ReflectionClass(Uri::class);
        $prop = $ref->getProperty('instances');
        $prop->setValue(null, []);

        $this->container = $this->makeContainer();
    }

    protected function tearDown(): void
    {
        $ref = new \ReflectionClass(Uri::class);
        $prop = $ref->getProperty('instances');
        $prop->setValue(null, []);
    }

    private function makeContainer(): Container
    {
        $tmpDir = sys_get_temp_dir();

        // Minimal router mock: route() echoes back its input unchanged.
        $router = $this->createMock(Router::class);
        $router->method('route')->willReturnArgument(0);

        return new Container([
            'application_name'     => 'TestApp',
            'applicationNamespace' => '\\TestApp',
            'session_segment_name' => 'testapp_seg',
            'basePath'             => $tmpDir,
            'languagePath'         => $tmpDir,
            'temporaryPath'        => $tmpDir,
            'templatePath'         => $tmpDir,
            'sqlPath'              => $tmpDir,
            'filesystemBase'       => $tmpDir,
            'router'               => $router,
        ]);
    }

    /** Helper: build a minimal valid Item. */
    private function makeItem(string $name, string $title, array $extra = []): Item
    {
        return new Item(array_merge(['name' => $name, 'title' => $title], $extra), $this->container);
    }

    /** Helper: build a minimal Toolbar (no application / document needed). */
    private function makeToolbar(): Toolbar
    {
        return new Toolbar($this->container);
    }

    /** Helper: build a MenuManager. */
    private function makeMenuManager(): MenuManager
    {
        return new MenuManager($this->container);
    }

    // =========================================================================
    // Button — construction + getters
    // =========================================================================

    public function testButtonConstructionFromOptions(): void
    {
        $button = new Button([
            'class'   => 'btn-primary',
            'icon'    => 'icon-save',
            'title'   => 'Save',
            'id'      => 'save-btn',
            'onClick' => 'doSave()',
            'url'     => 'https://example.com',
        ]);

        self::assertSame('btn-primary', $button->getClass());
        self::assertSame('icon-save', $button->getIcon());
        self::assertSame('Save', $button->getTitle());
        self::assertSame('save-btn', $button->getId());
        self::assertSame('doSave()', $button->getOnClick());
        self::assertSame('https://example.com', $button->getUrl());
    }

    public function testButtonDefaultsAreEmptyStrings(): void
    {
        $button = new Button(['title' => 'Test']);

        self::assertSame('', $button->getClass());
        self::assertSame('', $button->getIcon());
        self::assertSame('', $button->getOnClick());
        self::assertSame('', $button->getUrl());
    }

    public function testButtonGetIdAutoDerivesFromTitleWhenIdIsEmpty(): void
    {
        // Filter::clean with 'cmd' keeps alphanumeric + hyphen/underscore
        $button = new Button(['title' => 'Hello World']);

        // The id is derived via Filter::clean($title, 'cmd') — spaces are stripped
        $id = $button->getId();
        self::assertNotEmpty($id);
        // Should not contain a space
        self::assertStringNotContainsString(' ', $id);
    }

    public function testButtonGetIdUsesExplicitIdOverTitle(): void
    {
        $button = new Button(['title' => 'My Title', 'id' => 'explicit-id']);

        self::assertSame('explicit-id', $button->getId());
    }

    public function testButtonUnknownOptionsAreIgnored(): void
    {
        // Should not throw
        $button = new Button(['title' => 'Test', 'nonExistentOption' => 'value']);

        self::assertSame('Test', $button->getTitle());
    }

    // =========================================================================
    // Button — setters
    // =========================================================================

    public function testButtonSettersRoundTrip(): void
    {
        $button = new Button(['title' => 'Initial']);
        $button->setClass('custom-class');
        $button->setIcon('custom-icon');
        $button->setTitle('Updated Title');
        $button->setId('custom-id');
        $button->setOnClick('alert(1)');
        $button->setUrl('http://test.local/');

        self::assertSame('custom-class', $button->getClass());
        self::assertSame('custom-icon', $button->getIcon());
        self::assertSame('Updated Title', $button->getTitle());
        self::assertSame('custom-id', $button->getId());
        self::assertSame('alert(1)', $button->getOnClick());
        self::assertSame('http://test.local/', $button->getUrl());
    }

    // =========================================================================
    // Toolbar — title
    // =========================================================================

    public function testToolbarSetAndGetTitle(): void
    {
        $toolbar = $this->makeToolbar();
        $toolbar->setTitle('Page Title');

        self::assertSame('Page Title', $toolbar->getTitle());
    }

    public function testToolbarDefaultTitleIsEmptyString(): void
    {
        $toolbar = $this->makeToolbar();

        self::assertSame('', $toolbar->getTitle());
    }

    // =========================================================================
    // Toolbar — button management
    // =========================================================================

    public function testToolbarAddButtonStoresButton(): void
    {
        $toolbar = $this->makeToolbar();
        $button  = new Button(['title' => 'Save', 'id' => 'save']);

        $toolbar->addButton($button);

        self::assertArrayHasKey('save', $toolbar->getButtons());
        self::assertSame($button, $toolbar->getButtons()['save']);
    }

    public function testToolbarAddButtonKeyedByGetId(): void
    {
        $toolbar = $this->makeToolbar();
        $button  = new Button(['title' => 'Delete', 'id' => 'delete-btn']);

        $toolbar->addButton($button);

        self::assertArrayHasKey('delete-btn', $toolbar->getButtons());
    }

    public function testToolbarAddButtonFromDefinition(): void
    {
        $toolbar = $this->makeToolbar();
        $toolbar->addButtonFromDefinition(['title' => 'Apply', 'id' => 'apply']);

        $buttons = $toolbar->getButtons();

        self::assertArrayHasKey('apply', $buttons);
        self::assertInstanceOf(Button::class, $buttons['apply']);
    }

    public function testToolbarGetButtonsReturnsAllAddedButtons(): void
    {
        $toolbar = $this->makeToolbar();
        $toolbar->addButton(new Button(['title' => 'A', 'id' => 'a']));
        $toolbar->addButton(new Button(['title' => 'B', 'id' => 'b']));

        self::assertCount(2, $toolbar->getButtons());
    }

    public function testToolbarSetButtonsReplacesExistingList(): void
    {
        $toolbar = $this->makeToolbar();
        $toolbar->addButton(new Button(['title' => 'Old', 'id' => 'old']));

        $newButton = new Button(['title' => 'New', 'id' => 'new']);
        $toolbar->setButtons(['new' => $newButton]);

        self::assertCount(1, $toolbar->getButtons());
        self::assertArrayHasKey('new', $toolbar->getButtons());
    }

    public function testToolbarClearButtonsEmptiesList(): void
    {
        $toolbar = $this->makeToolbar();
        $toolbar->addButton(new Button(['title' => 'X', 'id' => 'x']));
        $toolbar->clearButtons();

        self::assertCount(0, $toolbar->getButtons());
    }

    public function testToolbarRemoveButtonRemovesById(): void
    {
        $toolbar = $this->makeToolbar();
        $button  = new Button(['title' => 'Save', 'id' => 'save']);
        $toolbar->addButton($button);
        $toolbar->removeButton($button);

        self::assertArrayNotHasKey('save', $toolbar->getButtons());
    }

    public function testToolbarRemoveButtonByNameRemovesByKey(): void
    {
        $toolbar = $this->makeToolbar();
        $toolbar->addButton(new Button(['title' => 'Delete', 'id' => 'delete']));
        $toolbar->removeButtonByName('delete');

        self::assertArrayNotHasKey('delete', $toolbar->getButtons());
    }

    public function testToolbarRemoveButtonByNameOnMissingNameIsNoOp(): void
    {
        $toolbar = $this->makeToolbar();
        $toolbar->addButton(new Button(['title' => 'Save', 'id' => 'save']));

        // Should not throw
        $toolbar->removeButtonByName('nonexistent');

        self::assertCount(1, $toolbar->getButtons());
    }

    public function testToolbarFindButtonReturnsByName(): void
    {
        $toolbar = $this->makeToolbar();
        $button  = new Button(['title' => 'Edit', 'id' => 'edit']);
        $toolbar->addButton($button);

        $found = $toolbar->findButton('edit');

        self::assertSame($button, $found);
    }

    public function testToolbarFindButtonThrowsOnMissingName(): void
    {
        $toolbar = $this->makeToolbar();

        $this->expectException(\Exception::class);

        $toolbar->findButton('nonexistent');
    }

    // =========================================================================
    // Item — construction validation
    // =========================================================================

    public function testItemConstructionWithNameAndTitle(): void
    {
        $item = $this->makeItem('home', 'Home');

        self::assertSame('home', $item->getName());
        self::assertSame('Home', $item->getTitle());
    }

    public function testItemConstructionWithMissingNameThrows(): void
    {
        $this->expectException(\Exception::class);

        new Item(['title' => 'Orphan'], $this->container);
    }

    public function testItemConstructionWithMissingBothTitleAndHandlerThrows(): void
    {
        $this->expectException(\Exception::class);

        new Item(['name' => 'noTitle'], $this->container);
    }

    public function testItemConstructionWithTitleHandlerAndNoTitleIsValid(): void
    {
        // titleHandler must be a string function name or [class, method] array.
        // We use a global function name that returns the item's name as title.
        $item = new Item(
            ['name' => 'dynamic', 'titleHandler' => 'strtolower'],
            $this->container
        );

        // The titleHandler is stored; getTitle() will call strtolower($this) — but
        // we only need the constructor not to throw here.
        self::assertSame('strtolower', $item->getTitleHandler());
    }

    // =========================================================================
    // Item — field round-trips
    // =========================================================================

    public function testItemGetAndSetName(): void
    {
        $item = $this->makeItem('original', 'Title');
        $item->setName('updated_name');

        self::assertSame('updated_name', $item->getName());
    }

    public function testItemSetNameSanitisesViaCmdFilter(): void
    {
        $item = $this->makeItem('clean', 'Title');
        $item->setName('has spaces & special!chars');

        // Filter::clean('cmd') strips non-alphanumeric chars (except underscore/dash)
        $name = $item->getName();
        self::assertStringNotContainsString(' ', $name);
        self::assertStringNotContainsString('&', $name);
        self::assertStringNotContainsString('!', $name);
    }

    public function testItemGetAndSetTitle(): void
    {
        $item = $this->makeItem('test', 'Original Title');
        $item->setTitle('New Title');

        self::assertSame('New Title', $item->getTitle());
    }

    public function testItemGetAndSetGroup(): void
    {
        $item = $this->makeItem('foo', 'Foo');
        $item->setGroup('admin');

        self::assertSame('admin', $item->getGroup());
    }

    public function testItemGetAndSetIcon(): void
    {
        $item = $this->makeItem('bar', 'Bar');
        $item->setIcon('icon-gear');

        self::assertSame('icon-gear', $item->getIcon());
    }

    public function testItemGetAndSetParent(): void
    {
        $item = $this->makeItem('child', 'Child');
        $item->setParent('parent');

        self::assertSame('parent', $item->getParent());
    }

    public function testItemGetAndSetOrder(): void
    {
        $item = $this->makeItem('ordered', 'Ordered');
        $item->setOrder(42);

        self::assertSame(42, $item->getOrder());
    }

    public function testItemGetAndSetOnClick(): void
    {
        $item = $this->makeItem('clickable', 'Clickable');
        $item->setOnClick('doAction()');

        self::assertSame('doAction()', $item->getOnClick());
    }

    public function testItemDefaultShowIsMain(): void
    {
        $item = $this->makeItem('nav', 'Nav');

        self::assertContains('main', $item->getShow());
    }

    public function testItemSetShowWithArrayReplacesDefault(): void
    {
        $item = $this->makeItem('nav', 'Nav');
        $item->setShow(['submenu', 'sidebar']);

        self::assertSame(['submenu', 'sidebar'], $item->getShow());
    }

    public function testItemSetShowWithStringIsWrappedInArray(): void
    {
        $item = $this->makeItem('nav', 'Nav');
        $item->setShow('submenu');

        self::assertSame(['submenu'], $item->getShow());
    }

    // =========================================================================
    // Item — params
    // =========================================================================

    public function testItemGetAndSetParams(): void
    {
        $item = $this->makeItem('list', 'List');
        $item->setParams(['view' => 'items', 'layout' => 'default']);

        self::assertSame(['view' => 'items', 'layout' => 'default'], $item->getParams());
    }

    public function testItemGetParamsAsQueryString(): void
    {
        $item = $this->makeItem('list', 'List');
        $item->setParams(['view' => 'items', 'task' => 'browse']);

        $qs = $item->getParams(true);

        self::assertStringContainsString('view=items', $qs);
        self::assertStringContainsString('task=browse', $qs);
    }

    public function testItemGetParamsAsQueryStringReturnsEmptyStringForNoParams(): void
    {
        $item = $this->makeItem('empty', 'Empty');

        self::assertSame('', $item->getParams(true));
    }

    // =========================================================================
    // Item — titleHandler
    // =========================================================================

    public static function titleHandlerProvider(): array
    {
        return [
            'string callable sets titleHandler' => [
                'handler'  => 'strtoupper',
                'expected' => true,   // truthy check: handler is stored
            ],
        ];
    }

    public function testItemSetTitleHandlerStringStoresHandler(): void
    {
        $item = $this->makeItem('th', 'Initial');
        $item->setTitleHandler('strtoupper');

        self::assertSame('strtoupper', $item->getTitleHandler());
    }

    public function testItemSetTitleHandlerArrayStoresToElements(): void
    {
        $item = $this->makeItem('th2', 'Initial');
        $item->setTitleHandler(['SomeClass', 'someMethod']);

        self::assertSame(['SomeClass', 'someMethod'], $item->getTitleHandler());
    }

    public function testItemSetTitleHandlerInvalidValueStoresEmptyString(): void
    {
        $item = $this->makeItem('th3', 'Initial');
        $item->setTitleHandler(12345);

        self::assertSame('', $item->getTitleHandler());
    }

    public function testItemGetTitleInvokesTitleHandlerWhenTitleIsEmpty(): void
    {
        // We need a real callable that getTitle() will invoke. titleHandler must
        // be passed as a string (function name) or [class, method] array.
        // Use a [ClassName, staticMethod] pair pointing to a helper defined below.
        $item = new Item(
            ['name' => 'dynamic', 'titleHandler' => [ToolbarMenuTestHelper::class, 'getItemTitle']],
            $this->container
        );

        self::assertSame('Computed Title', $item->getTitle());
    }

    public function testItemGetTitlePrefersTitleOverHandler(): void
    {
        $item = $this->makeItem('both', 'Explicit Title');
        // Set a handler — but since title is already set, it should be ignored.
        $item->setTitleHandler([ToolbarMenuTestHelper::class, 'getItemTitle']);

        self::assertSame('Explicit Title', $item->getTitle());
    }

    // =========================================================================
    // Item — URL
    // =========================================================================

    public function testItemGetUrlReturnsCustomUrlWhenSet(): void
    {
        $item = $this->makeItem('external', 'External');
        $item->setUrl('https://example.com/custom');

        self::assertSame('https://example.com/custom', $item->getUrl());
    }

    public function testItemGetUrlBuildsFromParamsViaRouter(): void
    {
        $item = $this->makeItem('params-url', 'Params URL');
        $item->setParams(['view' => 'items', 'task' => 'browse']);

        // Our container's router mock returns its input unchanged.
        $url = $item->getUrl();

        // The constructed URL must include the param key-value pairs.
        self::assertStringContainsString('view=items', $url);
        self::assertStringContainsString('task=browse', $url);
    }

    // =========================================================================
    // Item — children
    // =========================================================================

    public function testItemAddChildStoresChild(): void
    {
        $parent = $this->makeItem('parent', 'Parent');
        $child  = $this->makeItem('child', 'Child');

        $parent->addChild($child);

        self::assertArrayHasKey('child', $parent->getChildren());
    }

    public function testItemRemoveChildRemovesFromChildren(): void
    {
        $parent = $this->makeItem('parent', 'Parent');
        $child  = $this->makeItem('child', 'Child');
        $parent->addChild($child);
        $parent->removeChild($child);

        self::assertArrayNotHasKey('child', $parent->getChildren());
    }

    public function testItemRemoveChildOnMissingChildIsNoOp(): void
    {
        $parent = $this->makeItem('parent', 'Parent');
        $child  = $this->makeItem('ghost', 'Ghost');

        // Should not throw
        $parent->removeChild($child);

        self::assertSame([], $parent->getChildren());
    }

    public function testItemResetChildrenEmptiesAll(): void
    {
        $parent = $this->makeItem('parent', 'Parent');
        $parent->addChild($this->makeItem('c1', 'C1'));
        $parent->addChild($this->makeItem('c2', 'C2'));
        $parent->resetChildren();

        self::assertSame([], $parent->getChildren());
    }

    // =========================================================================
    // Item — isActive()
    // =========================================================================

    public function testItemIsActiveReturnsTrueForExactCustomUrlMatch(): void
    {
        $uri = 'http://localhost/index.php?view=items';
        // Pre-populate the Uri singleton cache with a specific URL
        $uriObj = Uri::getInstance($uri);

        $item = $this->makeItem('active-url', 'Active');
        $item->setUrl($uri);

        // Replace the 'SERVER' singleton with the same URI
        $ref  = new \ReflectionClass(Uri::class);
        $prop = $ref->getProperty('instances');
        $prop->setValue(null, array_merge($prop->getValue(null), ['SERVER' => $uriObj]));

        self::assertTrue($item->isActive());
    }

    public function testItemIsActiveReturnsFalseForDifferentCustomUrl(): void
    {
        $uri = 'http://localhost/index.php?view=items';
        $uriObj = Uri::getInstance($uri);

        $item = $this->makeItem('inactive-url', 'Inactive');
        $item->setUrl('http://localhost/other');

        $ref  = new \ReflectionClass(Uri::class);
        $prop = $ref->getProperty('instances');
        $prop->setValue(null, array_merge($prop->getValue(null), ['SERVER' => $uriObj]));

        self::assertFalse($item->isActive());
    }

    public function testItemIsActiveReturnsTrueWhenParamsMatchCurrentUri(): void
    {
        $uri = 'http://localhost/index.php?view=items&task=browse';
        $uriObj = Uri::getInstance($uri);

        $ref  = new \ReflectionClass(Uri::class);
        $prop = $ref->getProperty('instances');
        $prop->setValue(null, array_merge($prop->getValue(null), ['SERVER' => $uriObj]));

        $item = $this->makeItem('params-active', 'Params Active');
        $item->setParams(['view' => 'items', 'task' => 'browse']);

        self::assertTrue($item->isActive());
    }

    public function testItemIsActiveReturnsFalseWhenParamsMismatch(): void
    {
        $uri = 'http://localhost/index.php?view=items&task=browse';
        $uriObj = Uri::getInstance($uri);

        $ref  = new \ReflectionClass(Uri::class);
        $prop = $ref->getProperty('instances');
        $prop->setValue(null, array_merge($prop->getValue(null), ['SERVER' => $uriObj]));

        $item = $this->makeItem('params-inactive', 'Params Inactive');
        $item->setParams(['view' => 'other']);

        self::assertFalse($item->isActive());
    }

    public function testItemIsActiveReturnsFalseForEmptyParamsAndNoUrlMatch(): void
    {
        $uri = 'http://localhost/index.php?view=items';
        $uriObj = Uri::getInstance($uri);

        $ref  = new \ReflectionClass(Uri::class);
        $prop = $ref->getProperty('instances');
        $prop->setValue(null, array_merge($prop->getValue(null), ['SERVER' => $uriObj]));

        $item = $this->makeItem('noparams', 'No Params');
        // no custom url, no params → should not be active

        self::assertFalse($item->isActive());
    }

    // =========================================================================
    // MenuManager — basic CRUD
    // =========================================================================

    public function testMenuManagerAddAndFindItem(): void
    {
        $mm   = $this->makeMenuManager();
        $item = $this->makeItem('home', 'Home');

        $mm->addItem($item);

        self::assertSame($item, $mm->findItem('home'));
    }

    public function testMenuManagerFindItemThrowsOnMissingName(): void
    {
        $mm = $this->makeMenuManager();

        $this->expectException(\Exception::class);

        $mm->findItem('nonexistent');
    }

    public function testMenuManagerAddItemFromDefinition(): void
    {
        $mm = $this->makeMenuManager();
        $mm->addItemFromDefinition(['name' => 'about', 'title' => 'About']);

        $item = $mm->findItem('about');

        self::assertInstanceOf(Item::class, $item);
        self::assertSame('about', $item->getName());
    }

    public function testMenuManagerRemoveItemByItem(): void
    {
        $mm   = $this->makeMenuManager();
        $item = $this->makeItem('removable', 'Removable');
        $mm->addItem($item);
        $mm->removeItem($item);

        $this->expectException(\Exception::class);
        $mm->findItem('removable');
    }

    public function testMenuManagerRemoveItemByName(): void
    {
        $mm = $this->makeMenuManager();
        $mm->addItemFromDefinition(['name' => 'bye', 'title' => 'Bye']);
        $mm->removeItemByName('bye');

        $this->expectException(\Exception::class);
        $mm->findItem('bye');
    }

    public function testMenuManagerRemoveItemByNameOnMissingIsNoOp(): void
    {
        $mm = $this->makeMenuManager();
        $mm->addItemFromDefinition(['name' => 'keep', 'title' => 'Keep']);

        // Should not throw
        $mm->removeItemByName('does-not-exist');

        // Original item should still be findable
        self::assertInstanceOf(Item::class, $mm->findItem('keep'));
    }

    public function testMenuManagerClearRemovesAllItems(): void
    {
        $mm = $this->makeMenuManager();
        $mm->addItemFromDefinition(['name' => 'a', 'title' => 'A']);
        $mm->addItemFromDefinition(['name' => 'b', 'title' => 'B']);
        $mm->clear();

        $this->expectException(\Exception::class);
        $mm->findItem('a');
    }

    // =========================================================================
    // MenuManager — getMenuItems() tree building
    // =========================================================================

    public function testGetMenuItemsReturnsRootItemWithNoChildren(): void
    {
        $mm = $this->makeMenuManager();

        $root = $mm->getMenuItems('main');

        self::assertInstanceOf(Item::class, $root);
        self::assertSame([], $root->getChildren());
    }

    public function testGetMenuItemsFiltersItemsByMenuType(): void
    {
        $mm = $this->makeMenuManager();

        // This item belongs to 'main'
        $mainItem = $this->makeItem('main-only', 'Main Only');
        $mainItem->setShow(['main']);
        $mm->addItem($mainItem);

        // This item belongs to 'sidebar'
        $sideItem = $this->makeItem('side-only', 'Side Only');
        $sideItem->setShow(['sidebar']);
        $mm->addItem($sideItem);

        $mainRoot = $mm->getMenuItems('main');
        $children = $mainRoot->getChildren();

        self::assertArrayHasKey('main-only', $children);
        self::assertArrayNotHasKey('side-only', $children);
    }

    public function testGetMenuItemsFiltersItemsByGroup(): void
    {
        $mm = $this->makeMenuManager();

        $adminItem = $this->makeItem('admin-item', 'Admin Item', ['group' => 'admin']);
        $mm->addItem($adminItem);

        $guestItem = $this->makeItem('guest-item', 'Guest Item', ['group' => 'guest']);
        $mm->addItem($guestItem);

        $root = $mm->getMenuItems('main', 'admin');
        $children = $root->getChildren();

        self::assertArrayHasKey('admin-item', $children);
        self::assertArrayNotHasKey('guest-item', $children);
    }

    public function testGetMenuItemsOrdersChildrenByOrder(): void
    {
        $mm = $this->makeMenuManager();

        $last  = $this->makeItem('last', 'Last',   ['order' => 30]);
        $first = $this->makeItem('first', 'First', ['order' => 10]);
        $mid   = $this->makeItem('mid', 'Mid',     ['order' => 20]);

        $mm->addItem($last);
        $mm->addItem($first);
        $mm->addItem($mid);

        $root     = $mm->getMenuItems('main');
        $children = $root->getChildren();
        $keys     = array_keys($children);

        self::assertSame(['first', 'mid', 'last'], $keys);
    }

    public function testGetMenuItemsNestsChildrenUnderParent(): void
    {
        $mm = $this->makeMenuManager();

        $parent = $this->makeItem('parent', 'Parent');
        $child  = $this->makeItem('child', 'Child', ['parent' => 'parent']);

        $mm->addItem($parent);
        $mm->addItem($child);

        $root           = $mm->getMenuItems('main');
        $topChildren    = $root->getChildren();

        self::assertArrayHasKey('parent', $topChildren);
        self::assertArrayNotHasKey('child', $topChildren);

        $parentChildren = $topChildren['parent']->getChildren();

        self::assertArrayHasKey('child', $parentChildren);
    }

    // =========================================================================
    // MenuManager — enable/disable
    // =========================================================================

    public function testMenuManagerIsEnabledDefaultsToTrue(): void
    {
        $mm = $this->makeMenuManager();

        self::assertTrue($mm->isEnabled('main'));
    }

    public function testMenuManagerDisableMenuReturnsFalse(): void
    {
        $mm = $this->makeMenuManager();
        $mm->disableMenu('main');

        self::assertFalse($mm->isEnabled('main'));
    }

    public function testMenuManagerEnableMenuReturnsTrueAfterDisable(): void
    {
        $mm = $this->makeMenuManager();
        $mm->disableMenu('main');
        $mm->enableMenu('main');

        self::assertTrue($mm->isEnabled('main'));
    }

    public function testMenuManagerDisableAndEnableIndependentMenus(): void
    {
        $mm = $this->makeMenuManager();
        $mm->disableMenu('sidebar');

        self::assertTrue($mm->isEnabled('main'));
        self::assertFalse($mm->isEnabled('sidebar'));
    }
}

/**
 * Helper class used by ToolbarMenuTest for titleHandler tests.
 * Defined in the same namespace so the fully-qualified name resolves cleanly.
 */
class ToolbarMenuTestHelper
{
    public static function getItemTitle(Item $item): string
    {
        return 'Computed Title';
    }
}
