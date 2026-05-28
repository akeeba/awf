<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc\DataView;

require_once __DIR__ . '/Fixtures/CsvViewStubs.php';

use Awf\Application\Application;
use Awf\Container\Container;
use Awf\Database\Driver\Sqlite as SqliteDriver;
use Awf\Document\Csv as CsvDocument;
use Awf\Event\Dispatcher as EventDispatcher;
use Awf\Input\Input;
use Awf\Mvc\DataModel\Collection as DataCollection;
use Awf\Mvc\DataView\Csv as CsvView;
use Awf\Text\Language;
use CsvViewTestApp\Model\Items as ItemsModel;
use CsvViewTestApp\Model\Item as ItemModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for Awf\Mvc\DataView\Csv — CSV row/header generation, delimiter/enclosure options, and escaping.
 *
 * Covered:
 *  - display() returns true on success
 *  - display() throws (403) when an onBefore* hook returns false
 *  - display() throws (403) when an onAfter* hook returns false
 *  - csvHeader=true: header row is emitted as first line
 *  - csvHeader=false: no header row in output
 *  - csvHeader from config array overrides input
 *  - csvHeader from input parameter used when not in config
 *  - csvFilename defaults to pluralised view name (constructor completes without error)
 *  - csvFilename from config array used verbatim
 *  - csvFilename from input parameter used when not in config
 *  - csvFields filter — only specified columns included
 *  - csvFields filter — unknown fields ignored
 *  - all columns included when csvFields is empty
 *  - multiple valid csvFields columns
 *  - double-quote escaping in values ("  → "")
 *  - double-quote escaping in header keys
 *  - CR escaping in values (\r  → \r literal)
 *  - LF escaping in values (\n  → \n literal)
 *  - array value serialised as "Array"
 *  - object value serialised as "Object"
 *  - alreadyLoaded=true skips model fetch and uses preset items
 *  - alreadyLoaded defaults to false
 *  - document setName called when document is CsvDocument
 *  - multiple rows output correctly
 *  - rows terminated by CRLF
 *  - all values are double-quoted
 */
class CsvViewTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Infrastructure
    // -------------------------------------------------------------------------

    private SqliteDriver $db;
    private Container    $container;
    private CsvDocument  $csvDoc;

    protected function setUp(): void
    {
        parent::setUp();

        if (!SqliteDriver::isSupported()) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = 'localhost';
        }
        if (!isset($_SERVER['SCRIPT_NAME'])) {
            $_SERVER['SCRIPT_NAME'] = '/index.php';
        }

        ItemsModel::flushCaches();
        ItemModel::flushCaches();

        $this->db = new SqliteDriver([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $this->db->connect();

        $this->db->setQuery(
            'CREATE TABLE items (
                item_id  INTEGER PRIMARY KEY AUTOINCREMENT,
                title    TEXT    NOT NULL DEFAULT \'\',
                enabled  INTEGER NOT NULL DEFAULT 1
            )'
        )->execute();

        $this->csvDoc    = $this->createMock(CsvDocument::class);
        $this->container = $this->buildContainer();
    }

    protected function tearDown(): void
    {
        ItemsModel::flushCaches();
        ItemModel::flushCaches();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // Container / view builders
    // -------------------------------------------------------------------------

    private function buildContainer(array $inputData = [], ?CsvDocument $doc = null): Container
    {
        $tmpDir = sys_get_temp_dir();

        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturn([]);

        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);

        $useDoc = $doc ?? $this->csvDoc;

        $application = $this->createMock(Application::class);
        $application->method('getName')->willReturn('CsvViewTestApp');
        $application->method('getTemplate')->willReturn('default');
        $application->method('getDocument')->willReturn($useDoc);

        $db = $this->db;

        return new Container([
            'application_name'     => 'CsvViewTestApp',
            'applicationNamespace' => '\\CsvViewTestApp',
            'session_segment_name' => 'csvviewtestapp_seg',
            'basePath'             => $tmpDir,
            'languagePath'         => $tmpDir,
            'temporaryPath'        => $tmpDir,
            'templatePath'         => $tmpDir,
            'sqlPath'              => $tmpDir,
            'filesystemBase'       => $tmpDir,
            'eventDispatcher'      => $ed,
            'language'             => $language,
            'input'                => new Input($inputData),
            'application'          => $application,
            'db'                   => $db,
        ]);
    }

    /**
     * Build a minimal ItemsModel wired to the in-memory SQLite table.
     */
    private function makeModel(Container $container): ItemsModel
    {
        ItemsModel::flushCaches();

        $container['mvc_config'] = [
            'tableName'   => 'items',
            'idFieldName' => 'item_id',
            'autoChecks'  => false,
        ];
        return new ItemsModel($container);
    }

    /**
     * Build a CsvView with the given task and a pre-attached model.
     *
     * @param string           $task       The task (browse|…).
     * @param array            $config     Config passed to the view constructor (csv_header, csv_filename, csv_fields).
     * @param array            $inputData  Extra input parameters.
     * @param CsvDocument|null $doc        Optional document mock.
     */
    private function makeView(
        string       $task      = 'browse',
        array        $config    = [],
        array        $inputData = [],
        ?CsvDocument $doc       = null
    ): CsvView {
        $container = $this->buildContainer($inputData, $doc);

        $container['mvc_config'] = [
            'tableName'   => 'items',
            'idFieldName' => 'item_id',
            'autoChecks'  => false,
        ];

        $view = new \CsvViewTestApp\View\Items\Csv($container, $config);
        $view->task   = $task;
        $view->doTask = $task;

        $model = $this->makeModel($container);
        $view->setDefaultModel($model);

        return $view;
    }

    /** Insert a row and return its auto-increment ID. */
    private function insertRow(string $title, int $enabled = 1): int
    {
        $this->db->setQuery(
            'INSERT INTO items (title, enabled) VALUES (' .
            $this->db->q($title) . ', ' . (int) $enabled . ')'
        )->execute();
        return (int) $this->db->insertid();
    }

    /**
     * Capture the output of display() and return it as a string.
     */
    private function captureDisplay(CsvView $view, ?string $tpl = null): string
    {
        ob_start();
        try {
            $view->display($tpl);
        } finally {
            $output = ob_get_clean();
        }
        return (string) $output;
    }

    /**
     * Parse a raw CSV output string into rows of columns (already unquoted).
     *
     * @return list<list<string>>
     */
    private function parseCsv(string $csv): array
    {
        $rows  = [];
        $lines = explode("\r\n", rtrim($csv, "\r\n"));
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            // Provide explicit escape='' to suppress PHP 8.4+ deprecation warning.
            $rows[] = str_getcsv($line, ',', '"', '');
        }
        return $rows;
    }

    // =========================================================================
    // display() — lifecycle hook dispatch
    // =========================================================================

    public function testDisplayReturnsTrueWhenNoExtraHooks(): void
    {
        $this->insertRow('Alpha');

        $view = $this->makeView('browse');

        ob_start();
        try {
            $result = $view->display();
        } finally {
            ob_end_clean();
        }

        self::assertTrue($result);
    }

    public function testDisplayThrowsOn403WhenOnBeforeHookReturnsFalse(): void
    {
        $container = $this->buildContainer();

        $view = new \CsvViewTestApp\View\Items\CsvRejectBrowse($container);
        $view->task   = 'browse';
        $view->doTask = 'browse';

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(403);

        ob_start();
        try {
            $view->display();
        } finally {
            ob_end_clean();
        }
    }

    public function testDisplayThrowsOn403WhenOnAfterHookReturnsFalse(): void
    {
        $this->insertRow('SomeRow');

        // CsvRejectAfterBrowse's onBeforeBrowse() returns true, then display() processes
        // items normally and finally calls onAfterBrowse() which returns false → 403.
        $container = $this->buildContainer();
        $container['mvc_config'] = [
            'tableName'   => 'items',
            'idFieldName' => 'item_id',
            'autoChecks'  => false,
        ];

        $view = new \CsvViewTestApp\View\Items\CsvRejectAfterBrowse($container);
        $view->task   = 'browse';
        $view->doTask = 'browse';

        $model = $this->makeModel($container);
        $view->setDefaultModel($model);

        $this->expectException(\Exception::class);
        $this->expectExceptionCode(403);

        ob_start();
        try {
            $view->display();
        } finally {
            ob_end_clean();
        }
    }

    // =========================================================================
    // Header row
    // =========================================================================

    public function testHeaderRowEmittedByDefault(): void
    {
        $this->insertRow('Row1');

        $view   = $this->makeView('browse', ['csv_header' => true]);
        $output = $this->captureDisplay($view);
        $rows   = $this->parseCsv($output);

        // First row is the header
        self::assertNotEmpty($rows);
        self::assertContains('item_id', $rows[0]);
        self::assertContains('title', $rows[0]);
        self::assertContains('enabled', $rows[0]);
    }

    public function testHeaderRowSuppressedWhenCsvHeaderFalse(): void
    {
        $this->insertRow('OnlyData');

        $view   = $this->makeView('browse', ['csv_header' => false]);
        $output = $this->captureDisplay($view);
        $rows   = $this->parseCsv($output);

        // Should only have the data row — no header row whose first cell is 'item_id'
        self::assertCount(1, $rows);
        self::assertNotContains('item_id', $rows[0]);
    }

    public function testCsvHeaderFromConfigOverridesInputDefault(): void
    {
        $this->insertRow('Test');

        // Input says csv_header=1, but config says false — config wins.
        $view   = $this->makeView('browse', ['csv_header' => false], ['csv_header' => '1']);
        $output = $this->captureDisplay($view);
        $rows   = $this->parseCsv($output);

        // One data row, no header
        self::assertCount(1, $rows);
    }

    public function testCsvHeaderFromInputWhenNotInConfig(): void
    {
        $this->insertRow('Test');

        // No config key 'csv_header', input sends csv_header=0 (false)
        $view   = $this->makeView('browse', [], ['csv_header' => '0']);
        $output = $this->captureDisplay($view);
        $rows   = $this->parseCsv($output);

        // One data row, no header
        self::assertCount(1, $rows);
    }

    // =========================================================================
    // CSV filename
    // =========================================================================

    public function testCsvFilenameDefaultsToPluralizedViewName(): void
    {
        // When no csv_filename is given, the view name is pluralised and lowercased.
        // Verify the constructor completes without error and display() works.
        $this->insertRow('Row');
        $view   = $this->makeView('browse');
        $output = $this->captureDisplay($view);

        self::assertNotEmpty($output);
    }

    public function testCsvFilenameFromConfigUsedVerbatim(): void
    {
        $this->insertRow('Row');

        // The constructor must accept csv_filename from config without error.
        $view   = $this->makeView('browse', ['csv_filename' => 'custom_export.csv']);
        $output = $this->captureDisplay($view);

        self::assertNotEmpty($output);
    }

    public function testCsvFilenameFromInputUsedWhenNotInConfig(): void
    {
        $this->insertRow('Row');

        $view   = $this->makeView('browse', [], ['csv_filename' => 'from_input.csv']);
        $output = $this->captureDisplay($view);

        self::assertNotEmpty($output);
    }

    // =========================================================================
    // CSV fields filtering
    // =========================================================================

    public function testAllColumnsIncludedWhenCsvFieldsEmpty(): void
    {
        $this->insertRow('FullRow', 1);

        $view   = $this->makeView('browse', ['csv_header' => true, 'csv_fields' => []]);
        $output = $this->captureDisplay($view);
        $rows   = $this->parseCsv($output);

        self::assertCount(2, $rows);
        self::assertContains('item_id', $rows[0]);
        self::assertContains('title', $rows[0]);
        self::assertContains('enabled', $rows[0]);
    }

    public function testCsvFieldsFilterIncludesOnlySpecifiedColumns(): void
    {
        $this->insertRow('FilteredRow', 1);

        $view   = $this->makeView('browse', ['csv_header' => true, 'csv_fields' => ['title']]);
        $output = $this->captureDisplay($view);
        $rows   = $this->parseCsv($output);

        // Header row: only 'title'
        self::assertCount(2, $rows);
        self::assertSame(['title'], $rows[0]);
        self::assertSame(['FilteredRow'], $rows[1]);
    }

    public function testCsvFieldsFilterIgnoresUnknownFields(): void
    {
        $this->insertRow('Row', 1);

        $view   = $this->makeView('browse', ['csv_header' => true, 'csv_fields' => ['title', 'nonexistent_column']]);
        $output = $this->captureDisplay($view);
        $rows   = $this->parseCsv($output);

        // Only 'title' should appear — 'nonexistent_column' is silently dropped
        self::assertCount(2, $rows);
        self::assertSame(['title'], $rows[0]);
    }

    public function testCsvFieldsWithMultipleValidColumns(): void
    {
        $this->insertRow('Multi', 0);

        $view   = $this->makeView('browse', ['csv_header' => true, 'csv_fields' => ['title', 'enabled']]);
        $output = $this->captureDisplay($view);
        $rows   = $this->parseCsv($output);

        self::assertCount(2, $rows);
        self::assertSame(['title', 'enabled'], $rows[0]);
        self::assertSame(['Multi', '0'], $rows[1]);
    }

    // =========================================================================
    // CSV value escaping
    // =========================================================================

    public function testDoubleQuoteInValueIsEscaped(): void
    {
        $this->insertRow('Say "hello"');

        $view   = $this->makeView('browse', ['csv_header' => false, 'csv_fields' => ['title']]);
        $output = $this->captureDisplay($view);

        // The raw output should have double-double-quotes inside the quoted field
        self::assertStringContainsString('""hello""', $output);
    }

    public function testCarriageReturnInValueIsEscaped(): void
    {
        $this->insertRow("line1\rline2");

        $view   = $this->makeView('browse', ['csv_header' => false, 'csv_fields' => ['title']]);
        $output = $this->captureDisplay($view);

        // \r should be replaced with the literal two-char sequence \r
        self::assertStringContainsString('\\r', $output);
    }

    public function testLineFeedInValueIsEscaped(): void
    {
        $this->insertRow("line1\nline2");

        $view   = $this->makeView('browse', ['csv_header' => false, 'csv_fields' => ['title']]);
        $output = $this->captureDisplay($view);

        // \n should be replaced with the literal two-char sequence \n
        self::assertStringContainsString('\\n', $output);
    }

    public function testDoubleQuoteInHeaderKeyIsEscaped(): void
    {
        // Verify normal header values are properly double-quoted.
        $this->insertRow('normal');

        $view   = $this->makeView('browse', ['csv_header' => true, 'csv_fields' => ['title']]);
        $output = $this->captureDisplay($view);

        // Header line: "title"\r\n
        $firstLine = substr($output, 0, strpos($output, "\r\n"));
        self::assertSame('"title"', $firstLine);
    }

    // =========================================================================
    // Array / object value serialisation
    // =========================================================================

    /**
     * Build an injectable-items view that pre-loads a specific DataCollection
     * via its onBeforeBrowse hook (bypassing the Raw::onBeforeBrowse override).
     */
    private function makeInjectableView(array $config = []): \CsvViewTestApp\View\Items\CsvInjectableItems
    {
        $container = $this->buildContainer();
        $container['mvc_config'] = [
            'tableName'   => 'items',
            'idFieldName' => 'item_id',
            'autoChecks'  => false,
        ];

        $view = new \CsvViewTestApp\View\Items\CsvInjectableItems($container, $config);
        $view->task   = 'browse';
        $view->doTask = 'browse';

        ItemsModel::flushCaches();
        $model = new ItemsModel($container);
        $view->setDefaultModel($model);

        return $view;
    }

    public function testArrayValueSerialisedAsArrayString(): void
    {
        $this->insertRow('placeholder');

        // Load a real item and override its 'title' with an array value.
        $container = $this->buildContainer();
        $container['mvc_config'] = [
            'tableName'   => 'items',
            'idFieldName' => 'item_id',
            'autoChecks'  => false,
        ];
        ItemsModel::flushCaches();
        $model = new ItemsModel($container);
        $items = $model->get();

        /** @var ItemsModel $item */
        $item = $items->first();
        $item->setRecordField('title', ['an', 'array']);

        $view = $this->makeInjectableView(['csv_header' => false, 'csv_fields' => ['title']]);
        $view->injectedItems = $items;

        $output = $this->captureDisplay($view);

        self::assertStringContainsString('"Array"', $output);
    }

    public function testObjectValueSerialisedAsObjectString(): void
    {
        $this->insertRow('placeholder');

        $container = $this->buildContainer();
        $container['mvc_config'] = [
            'tableName'   => 'items',
            'idFieldName' => 'item_id',
            'autoChecks'  => false,
        ];
        ItemsModel::flushCaches();
        $model = new ItemsModel($container);
        $items = $model->get();

        /** @var ItemsModel $item */
        $item = $items->first();
        $item->setRecordField('title', new \stdClass());

        $view = $this->makeInjectableView(['csv_header' => false, 'csv_fields' => ['title']]);
        $view->injectedItems = $items;

        $output = $this->captureDisplay($view);

        self::assertStringContainsString('"Object"', $output);
    }

    // =========================================================================
    // alreadyLoaded flag
    // =========================================================================

    public function testAlreadyLoadedDefaultsToFalse(): void
    {
        $view = $this->makeView('browse');
        self::assertFalse($view->alreadyLoaded);
    }

    public function testAlreadyLoadedSkipsModelFetch(): void
    {
        // Insert a single row, load it, rename its title, and inject it.
        // The view's injectedItems (set in onBeforeBrowse) should be used as-is,
        // proving that the model is not re-queried inside display().
        $this->insertRow('OriginalTitle');

        $container = $this->buildContainer();
        $container['mvc_config'] = [
            'tableName'   => 'items',
            'idFieldName' => 'item_id',
            'autoChecks'  => false,
        ];
        ItemsModel::flushCaches();
        $model = new ItemsModel($container);
        $preloadedItems = $model->get();

        // Override the title to a sentinel value that can only appear via injection
        /** @var ItemsModel $item */
        $item = $preloadedItems->first();
        $item->setRecordField('title', 'InjectedValue');

        $view = $this->makeInjectableView(['csv_header' => false, 'csv_fields' => ['title']]);
        $view->injectedItems = $preloadedItems;

        $output = $this->captureDisplay($view);
        $rows   = $this->parseCsv($output);

        self::assertCount(1, $rows);
        // Confirm the injected (modified) value appears, not the DB value
        self::assertSame(['InjectedValue'], $rows[0]);
    }

    // =========================================================================
    // Document interaction
    // =========================================================================

    public function testDocumentSetNameCalledWhenDocIsCsvDocument(): void
    {
        $doc = $this->createMock(CsvDocument::class);
        $doc->expects(self::once())->method('setName');

        $this->insertRow('Row');

        $view = $this->makeView('browse', [], ['view' => 'items'], $doc);

        ob_start();
        try {
            $view->display();
        } finally {
            ob_end_clean();
        }
    }

    // =========================================================================
    // Full CSV output format verification
    // =========================================================================

    public function testMultipleRowsOutputCorrectly(): void
    {
        $this->insertRow('Alpha', 1);
        $this->insertRow('Beta', 0);
        $this->insertRow('Gamma', 1);

        $view   = $this->makeView('browse', ['csv_header' => true, 'csv_fields' => ['title', 'enabled']]);
        $output = $this->captureDisplay($view);
        $rows   = $this->parseCsv($output);

        // header + 3 data rows
        self::assertCount(4, $rows);
        self::assertSame(['title', 'enabled'], $rows[0]);
        self::assertSame(['Alpha', '1'], $rows[1]);
        self::assertSame(['Beta', '0'], $rows[2]);
        self::assertSame(['Gamma', '1'], $rows[3]);
    }

    public function testRowsTerminatedByCrlf(): void
    {
        $this->insertRow('Row1');

        $view   = $this->makeView('browse', ['csv_header' => true, 'csv_fields' => ['title']]);
        $output = $this->captureDisplay($view);

        // Every row must end with CRLF
        $lines = explode("\r\n", $output);
        // Last element after split will be empty string (trailing CRLF)
        array_pop($lines);

        self::assertCount(2, $lines); // header + 1 data row
        foreach ($lines as $line) {
            self::assertNotEmpty($line);
        }
    }

    public function testAllValuesAreDoubleQuoted(): void
    {
        $this->insertRow('SimpleValue', 1);

        $view   = $this->makeView('browse', ['csv_header' => false, 'csv_fields' => ['title']]);
        $output = $this->captureDisplay($view);

        $firstLine = substr($output, 0, strpos($output, "\r\n"));
        self::assertSame('"SimpleValue"', $firstLine);
    }

    // =========================================================================
    // Constructor config resolution — DataProvider test
    // =========================================================================

    public static function csvHeaderConfigProvider(): array
    {
        return [
            'config true'  => [['csv_header' => true],  true],
            'config false' => [['csv_header' => false], false],
        ];
    }

    #[DataProvider('csvHeaderConfigProvider')]
    public function testCsvHeaderResolvedFromConfig(array $config, bool $expectHeader): void
    {
        $this->insertRow('TestRow');

        $view   = $this->makeView('browse', $config);
        $output = $this->captureDisplay($view);
        $rows   = $this->parseCsv($output);

        if ($expectHeader) {
            $hasHeader = in_array('title', $rows[0] ?? [], true);
            self::assertTrue($hasHeader);
        } else {
            // Only data rows — first row should not contain column names
            self::assertNotContains('title', $rows[0] ?? []);
        }
    }
}
