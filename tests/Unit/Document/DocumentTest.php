<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Document;

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Document\Csv;
use Awf\Document\Document;
use Awf\Document\Html;
use Awf\Document\Json;
use Awf\Document\Raw;
use Awf\Text\Language;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Unit tests for Awf\Document\Document and its concrete format subclasses
 * (Html, Json, Csv, Raw).
 *
 * Covered:
 *  - Document::getInstance() — factory selection by type
 *  - Document::getInstance() — singleton caching per type key
 *  - Document::getInstance() — fallback to Html for unknown types
 *  - Document::getInstance() — custom class prefix
 *  - buffer set/get
 *  - MIME type defaults per format subclass
 *  - MIME type override via setMimeType / getMimeType
 *  - HTTP header accumulation: addHTTPHeader, getHTTPHeader, getHTTPHeaders
 *  - HTTP header overwrite flag
 *  - HTTP header removal: removeHTTPHeader
 *  - getHTTPHeader default value
 *  - script/style/declaration collectors
 *  - addScriptOptions / getScriptOptions (merge + replace + key lookup)
 *  - lang() stores translated key into scriptOptions
 *  - document name (null by default; set/get)
 *  - Json::useHashes getter / setter
 *  - addModule() registers type=module script
 *  - addScriptDeclaration appends with CR separator on duplicate type
 *  - addStyleDeclaration appends with CR separator on duplicate type
 */
class DocumentTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Infrastructure
    // -------------------------------------------------------------------------

    private Container $container;

    protected function setUp(): void
    {
        $this->container = $this->makeContainer();
        $this->resetDocumentInstances();
    }

    protected function tearDown(): void
    {
        $this->resetDocumentInstances();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeContainer(): Container
    {
        $tmpDir = sys_get_temp_dir();

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => strtolower($k));

        $application = $this->createMock(Application::class);
        $application->method('getName')->willReturn('TestApp');

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
            'language'             => $language,
            'application'          => $application,
        ]);
    }

    /**
     * Reset the private static Document::$instances cache between tests so
     * that factory tests are independent.
     */
    private function resetDocumentInstances(): void
    {
        $ref = new ReflectionClass(Document::class);
        $prop = $ref->getProperty('instances');
        $prop->setValue(null, []);
    }

    // -------------------------------------------------------------------------
    // Document::getInstance() — factory & singleton
    // -------------------------------------------------------------------------

    public function testGetInstanceReturnsHtmlDocumentForHtmlType(): void
    {
        $doc = Document::getInstance('html', $this->container);

        self::assertInstanceOf(Html::class, $doc);
    }

    public function testGetInstanceReturnsJsonDocumentForJsonType(): void
    {
        $doc = Document::getInstance('json', $this->container);

        self::assertInstanceOf(Json::class, $doc);
    }

    public function testGetInstanceReturnsCsvDocumentForCsvType(): void
    {
        $doc = Document::getInstance('csv', $this->container);

        self::assertInstanceOf(Csv::class, $doc);
    }

    public function testGetInstanceReturnsRawDocumentForRawType(): void
    {
        $doc = Document::getInstance('raw', $this->container);

        self::assertInstanceOf(Raw::class, $doc);
    }

    public function testGetInstanceFallsBackToHtmlForUnknownType(): void
    {
        $doc = Document::getInstance('bogus', $this->container);

        self::assertInstanceOf(Html::class, $doc);
    }

    public function testGetInstanceReturnsSameInstanceOnSecondCall(): void
    {
        $first  = Document::getInstance('json', $this->container);
        $second = Document::getInstance('json', $this->container);

        self::assertSame($first, $second, 'getInstance must return a cached singleton per type key.');
    }

    public function testGetInstanceReturnsDistinctInstancesForDifferentTypes(): void
    {
        $json = Document::getInstance('json', $this->container);
        $csv  = Document::getInstance('csv',  $this->container);

        self::assertNotSame($json, $csv);
    }

    public function testGetInstanceUsesCustomClassPrefix(): void
    {
        // Custom prefix that does NOT have a matching class → should fall back to \Awf\Document\Html
        $doc = Document::getInstance('html', $this->container, '\\NonExistentPrefix');

        // The fallback class is \Awf\Document\Html regardless of prefix
        self::assertInstanceOf(Html::class, $doc);
    }

    // -------------------------------------------------------------------------
    // Buffer
    // -------------------------------------------------------------------------

    public function testBufferIsEmptyByDefault(): void
    {
        $doc = new Json($this->container);

        self::assertSame('', $doc->getBuffer());
    }

    public function testSetBufferStoresValue(): void
    {
        $doc = new Json($this->container);
        $doc->setBuffer('{"hello":"world"}');

        self::assertSame('{"hello":"world"}', $doc->getBuffer());
    }

    public function testSetBufferReturnsFluentInterface(): void
    {
        $doc    = new Raw($this->container);
        $return = $doc->setBuffer('payload');

        self::assertSame($doc, $return);
    }

    public function testSetBufferOverwritesPreviousValue(): void
    {
        $doc = new Raw($this->container);
        $doc->setBuffer('first');
        $doc->setBuffer('second');

        self::assertSame('second', $doc->getBuffer());
    }

    // -------------------------------------------------------------------------
    // MIME type defaults per format
    // -------------------------------------------------------------------------

    public static function mimeTypeDefaultsProvider(): array
    {
        return [
            'Html default mime'  => [Html::class,  'text/html'],
            'Json default mime'  => [Json::class,  'application/json'],
            'Csv default mime'   => [Csv::class,   'text/csv'],
            'Raw default mime'   => [Raw::class,   'text/plain'],
        ];
    }

    #[DataProvider('mimeTypeDefaultsProvider')]
    public function testDefaultMimeType(string $class, string $expectedMime): void
    {
        /** @var Document $doc */
        $doc = new $class($this->container);

        self::assertSame($expectedMime, $doc->getMimeType());
    }

    // -------------------------------------------------------------------------
    // MIME type override
    // -------------------------------------------------------------------------

    public function testSetMimeTypeChangesMimeType(): void
    {
        $doc = new Json($this->container);
        $doc->setMimeType('application/ld+json');

        self::assertSame('application/ld+json', $doc->getMimeType());
    }

    // -------------------------------------------------------------------------
    // HTTP headers
    // -------------------------------------------------------------------------

    public function testHttpHeadersAreEmptyByDefault(): void
    {
        $doc = new Raw($this->container);

        self::assertSame([], $doc->getHTTPHeaders());
    }

    public function testAddHttpHeaderStoresHeader(): void
    {
        $doc = new Raw($this->container);
        $doc->addHTTPHeader('X-Foo', 'bar');

        self::assertSame('bar', $doc->getHTTPHeader('X-Foo'));
    }

    public function testAddHttpHeaderOverwritesByDefault(): void
    {
        $doc = new Raw($this->container);
        $doc->addHTTPHeader('X-Foo', 'original');
        $doc->addHTTPHeader('X-Foo', 'updated');

        self::assertSame('updated', $doc->getHTTPHeader('X-Foo'));
    }

    public function testAddHttpHeaderDoesNotOverwriteWhenFlagIsFalse(): void
    {
        $doc = new Raw($this->container);
        $doc->addHTTPHeader('X-Foo', 'original');
        $doc->addHTTPHeader('X-Foo', 'updated', false);

        self::assertSame('original', $doc->getHTTPHeader('X-Foo'));
    }

    public function testGetHttpHeaderReturnsDefaultWhenNotSet(): void
    {
        $doc = new Raw($this->container);

        self::assertSame('fallback', $doc->getHTTPHeader('X-Missing', 'fallback'));
        self::assertNull($doc->getHTTPHeader('X-Missing'));
    }

    public function testRemoveHttpHeaderDeletesHeader(): void
    {
        $doc = new Raw($this->container);
        $doc->addHTTPHeader('X-Foo', 'bar');
        $doc->removeHTTPHeader('X-Foo');

        self::assertNull($doc->getHTTPHeader('X-Foo'));
    }

    public function testRemoveNonExistentHttpHeaderDoesNotThrow(): void
    {
        $doc = new Raw($this->container);
        $doc->removeHTTPHeader('X-Does-Not-Exist');   // must not throw

        self::assertSame([], $doc->getHTTPHeaders());
    }

    public function testGetHttpHeadersReturnsAllHeaders(): void
    {
        $doc = new Raw($this->container);
        $doc->addHTTPHeader('X-A', '1');
        $doc->addHTTPHeader('X-B', '2');

        self::assertSame(['X-A' => '1', 'X-B' => '2'], $doc->getHTTPHeaders());
    }

    // -------------------------------------------------------------------------
    // Document name
    // -------------------------------------------------------------------------

    public function testNameIsNullByDefault(): void
    {
        $doc = new Json($this->container);

        self::assertNull($doc->getName());
    }

    public function testSetNameStoresName(): void
    {
        $doc = new Json($this->container);
        $doc->setName('export');

        self::assertSame('export', $doc->getName());
    }

    public function testSetNameAcceptsNull(): void
    {
        $doc = new Json($this->container);
        $doc->setName('export');
        $doc->setName(null);

        self::assertNull($doc->getName());
    }

    // -------------------------------------------------------------------------
    // Script / style assets
    // -------------------------------------------------------------------------

    public function testAddScriptStoresScript(): void
    {
        $doc = new Html($this->container);
        $doc->addScript('https://example.com/app.js');

        $scripts = $doc->getScripts();

        self::assertArrayHasKey('https://example.com/app.js', $scripts);
        self::assertSame('text/javascript', $scripts['https://example.com/app.js']['mime']);
        self::assertFalse($scripts['https://example.com/app.js']['before']);
        self::assertFalse($scripts['https://example.com/app.js']['defer']);
        self::assertFalse($scripts['https://example.com/app.js']['async']);
    }

    public function testAddScriptBeforeFlag(): void
    {
        $doc = new Html($this->container);
        $doc->addScript('https://example.com/app.js', true);

        self::assertTrue($doc->getScripts()['https://example.com/app.js']['before']);
    }

    public function testAddModuleRegistersTypeModule(): void
    {
        $doc = new Html($this->container);
        $doc->addModule('https://example.com/mod.js');

        $scripts = $doc->getScripts();

        self::assertArrayHasKey('https://example.com/mod.js', $scripts);
        self::assertSame('module', $scripts['https://example.com/mod.js']['mime']);
    }

    public function testAddStyleSheetStoresStylesheet(): void
    {
        $doc = new Html($this->container);
        $doc->addStyleSheet('https://example.com/style.css');

        $styles = $doc->getStyles();

        self::assertArrayHasKey('https://example.com/style.css', $styles);
        self::assertSame('text/css', $styles['https://example.com/style.css']['mime']);
    }

    public function testAddScriptDeclarationStoresContent(): void
    {
        $doc = new Html($this->container);
        $doc->addScriptDeclaration('var x = 1;');

        self::assertSame(['text/javascript' => 'var x = 1;'], $doc->getScriptDeclarations());
    }

    public function testAddScriptDeclarationAppendsOnSameType(): void
    {
        $doc = new Html($this->container);
        $doc->addScriptDeclaration('var a = 1;');
        $doc->addScriptDeclaration('var b = 2;');

        $decls = $doc->getScriptDeclarations();

        self::assertStringContainsString('var a = 1;', $decls['text/javascript']);
        self::assertStringContainsString('var b = 2;', $decls['text/javascript']);
        // separated by CR (chr(13))
        self::assertStringContainsString(chr(13), $decls['text/javascript']);
    }

    public function testAddScriptDeclarationUsesTypeLowercaseKey(): void
    {
        $doc = new Html($this->container);
        $doc->addScriptDeclaration('var x = 1;', 'Text/JavaScript');

        $decls = $doc->getScriptDeclarations();

        self::assertArrayHasKey('text/javascript', $decls);
    }

    public function testAddStyleDeclarationStoresContent(): void
    {
        $doc = new Html($this->container);
        $doc->addStyleDeclaration('body { color: red; }');

        self::assertSame(['text/css' => 'body { color: red; }'], $doc->getStyleDeclarations());
    }

    public function testAddStyleDeclarationAppendsOnSameType(): void
    {
        $doc = new Html($this->container);
        $doc->addStyleDeclaration('body { color: red; }');
        $doc->addStyleDeclaration('h1 { font-size: 2em; }');

        $decls = $doc->getStyleDeclarations();

        self::assertStringContainsString('body { color: red; }', $decls['text/css']);
        self::assertStringContainsString('h1 { font-size: 2em; }', $decls['text/css']);
        self::assertStringContainsString(chr(13), $decls['text/css']);
    }

    // -------------------------------------------------------------------------
    // Script options
    // -------------------------------------------------------------------------

    public function testAddScriptOptionsStoresOptions(): void
    {
        $doc = new Html($this->container);
        $doc->addScriptOptions('myKey', ['foo' => 'bar']);

        self::assertSame(['foo' => 'bar'], $doc->getScriptOptions('myKey'));
    }

    public function testAddScriptOptionsMergesByDefault(): void
    {
        $doc = new Html($this->container);
        $doc->addScriptOptions('myKey', ['a' => 1]);
        $doc->addScriptOptions('myKey', ['b' => 2]);

        self::assertSame(['a' => 1, 'b' => 2], $doc->getScriptOptions('myKey'));
    }

    public function testAddScriptOptionsReplaceWhenMergeFalse(): void
    {
        $doc = new Html($this->container);
        $doc->addScriptOptions('myKey', ['a' => 1]);
        $doc->addScriptOptions('myKey', ['b' => 2], false);

        self::assertSame(['b' => 2], $doc->getScriptOptions('myKey'));
    }

    public function testGetScriptOptionsWithNullKeyReturnsAll(): void
    {
        $doc = new Html($this->container);
        $doc->addScriptOptions('k1', ['x' => 1]);
        $doc->addScriptOptions('k2', ['y' => 2]);

        $all = $doc->getScriptOptions();

        self::assertArrayHasKey('k1', $all);
        self::assertArrayHasKey('k2', $all);
    }

    public function testGetScriptOptionsReturnsEmptyArrayForMissingKey(): void
    {
        $doc = new Html($this->container);

        self::assertSame([], $doc->getScriptOptions('nonexistent'));
    }

    // -------------------------------------------------------------------------
    // lang() helper
    // -------------------------------------------------------------------------

    public function testLangStoresTranslatedKeyInScriptOptions(): void
    {
        $doc = new Html($this->container);
        // Language mock returns strtolower($key); key HELLO_WORLD → 'hello_world'
        $doc->lang('HELLO_WORLD');

        $opts = $doc->getScriptOptions('akeeba.text');

        self::assertArrayHasKey('HELLO_WORLD', $opts);
        self::assertSame('hello_world', $opts['HELLO_WORLD']);
    }

    public function testLangMergesMultipleKeys(): void
    {
        $doc = new Html($this->container);
        $doc->lang('KEY_A');
        $doc->lang('KEY_B');

        $opts = $doc->getScriptOptions('akeeba.text');

        self::assertArrayHasKey('KEY_A', $opts);
        self::assertArrayHasKey('KEY_B', $opts);
    }

    // -------------------------------------------------------------------------
    // Json-specific: useHashes
    // -------------------------------------------------------------------------

    public function testJsonUseHashesDefaultsToTrue(): void
    {
        $doc = new Json($this->container);

        self::assertTrue($doc->getUseHashes());
    }

    public function testJsonSetUseHashesToFalse(): void
    {
        $doc = new Json($this->container);
        $doc->setUseHashes(false);

        self::assertFalse($doc->getUseHashes());
    }

    public function testJsonSetUseHashesBackToTrue(): void
    {
        $doc = new Json($this->container);
        $doc->setUseHashes(false);
        $doc->setUseHashes(true);

        self::assertTrue($doc->getUseHashes());
    }

    public function testJsonSetUseHashesCoercesToBool(): void
    {
        $doc = new Json($this->container);
        $doc->setUseHashes(0);

        self::assertFalse($doc->getUseHashes());

        $doc->setUseHashes(1);

        self::assertTrue($doc->getUseHashes());
    }

    // -------------------------------------------------------------------------
    // Render side-effects (headers only — no actual output / real headers)
    // -------------------------------------------------------------------------

    /**
     * Json::render() must call addHTTPHeader('Content-Type', 'application/json')
     * before outputting. We verify by checking getHTTPHeader after capture.
     */
    public function testJsonRenderSetsContentTypeHeader(): void
    {
        $doc = new Json($this->container);
        $doc->setBuffer('{}');
        $doc->setUseHashes(false);

        ob_start();
        $doc->render();
        ob_end_clean();

        self::assertSame('application/json', $doc->getHTTPHeader('Content-Type'));
    }

    public function testJsonRenderSetsContentDispositionWhenNameIsSet(): void
    {
        $doc = new Json($this->container);
        $doc->setBuffer('{}');
        $doc->setName('export');
        $doc->setUseHashes(false);

        ob_start();
        $doc->render();
        ob_end_clean();

        self::assertSame(
            'attachment; filename="export.json"',
            $doc->getHTTPHeader('Content-Disposition')
        );
    }

    public function testJsonRenderDoesNotSetContentDispositionWhenNameIsNull(): void
    {
        $doc = new Json($this->container);
        $doc->setBuffer('{}');
        $doc->setUseHashes(false);

        ob_start();
        $doc->render();
        ob_end_clean();

        self::assertNull($doc->getHTTPHeader('Content-Disposition'));
    }

    public function testJsonRenderOutputsHashesWhenUseHashesIsTrue(): void
    {
        $doc = new Json($this->container);
        $doc->setBuffer('{"key":"value"}');
        $doc->setUseHashes(true);

        ob_start();
        $doc->render();
        $output = ob_get_clean();

        self::assertSame('###{"key":"value"}###', $output);
    }

    public function testJsonRenderOutputsRawBufferWhenUseHashesIsFalse(): void
    {
        $doc = new Json($this->container);
        $doc->setBuffer('{"key":"value"}');
        $doc->setUseHashes(false);

        ob_start();
        $doc->render();
        $output = ob_get_clean();

        self::assertSame('{"key":"value"}', $output);
    }

    public function testCsvRenderSetsContentTypeHeader(): void
    {
        $doc = new Csv($this->container);
        $doc->setBuffer("a,b\n1,2");

        ob_start();
        $doc->render();
        ob_end_clean();

        self::assertSame('text/csv', $doc->getHTTPHeader('Content-Type'));
    }

    public function testCsvRenderSetsContentDispositionWhenNameIsSet(): void
    {
        $doc = new Csv($this->container);
        $doc->setBuffer("a,b\n1,2");
        $doc->setName('report');

        ob_start();
        $doc->render();
        ob_end_clean();

        self::assertSame(
            'attachment; filename="report.csv"',
            $doc->getHTTPHeader('Content-Disposition')
        );
    }

    public function testCsvRenderOutputsBuffer(): void
    {
        $doc = new Csv($this->container);
        $doc->setBuffer("col1,col2\nval1,val2");

        ob_start();
        $doc->render();
        $output = ob_get_clean();

        self::assertSame("col1,col2\nval1,val2", $output);
    }

    public function testRawRenderSetsContentTypeHeader(): void
    {
        $doc = new Raw($this->container);
        $doc->setBuffer('plain text');

        ob_start();
        $doc->render();
        ob_end_clean();

        self::assertSame('text/plain', $doc->getHTTPHeader('Content-Type'));
    }

    public function testRawRenderSetsContentDispositionWhenNameIsSet(): void
    {
        $doc = new Raw($this->container);
        $doc->setBuffer('data');
        $doc->setName('output.txt');

        ob_start();
        $doc->render();
        ob_end_clean();

        // Raw uses the name as-is (no extension appended)
        self::assertSame(
            'attachment; filename="output.txt"',
            $doc->getHTTPHeader('Content-Disposition')
        );
    }

    public function testRawRenderOutputsBuffer(): void
    {
        $doc = new Raw($this->container);
        $doc->setBuffer('hello raw');

        ob_start();
        $doc->render();
        $output = ob_get_clean();

        self::assertSame('hello raw', $output);
    }
}
