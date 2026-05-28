<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Mvc;

// Load stub MVC classes that live under the fake \FactoryTestApp\… namespace.
// The file uses bracketed namespace syntax (required when mixing namespaces),
// which cannot be combined with an unbracketed declaration in the same file.
require_once __DIR__ . '/Fixtures/FactoryStubs.php';

use Awf\Container\Container;
use Awf\Input\Input;
use Awf\Mvc\Controller;
use Awf\Mvc\Factory;
use Awf\Mvc\Model;
use Awf\Mvc\View;
use Awf\Text\Language;
use Awf\Event\Dispatcher as EventDispatcher;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for Awf\Mvc\Factory — class-name resolution and object construction.
 *
 * Stub app namespaces are defined in tests/Unit/Mvc/Fixtures/FactoryStubs.php:
 *   \FactoryTestApp\…   — full set of controllers, models, and views
 *   \FactoryTestApp2\…  — only a plural controller (Widgets); models/views absent
 */
class FactoryTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Set-up / tear-down
    // -------------------------------------------------------------------------

    private array $serverBackup = [];

    protected function setUp(): void
    {
        // The View constructor triggers Awf\Uri\Uri which reads $_SERVER['HTTP_HOST']
        // when REQUEST_URI is absent.  Seed minimal values so Uri does not emit
        // an "Undefined array key" notice.
        $this->serverBackup = [
            'HTTP_HOST'    => $_SERVER['HTTP_HOST']    ?? null,
            'REQUEST_URI'  => $_SERVER['REQUEST_URI']  ?? null,
            'SCRIPT_NAME'  => $_SERVER['SCRIPT_NAME']  ?? null,
        ];
        $_SERVER['HTTP_HOST']   = 'localhost';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
    }

    protected function tearDown(): void
    {
        foreach ($this->serverBackup as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a minimal Container sufficient for Model / View / Controller
     * construction without hitting the filesystem or a real application.
     *
     * @param string $namespace  PHP namespace used as applicationNamespace
     * @param string $appName    Scalar application_name
     */
    private function makeContainer(
        string $namespace = '\\FactoryTestApp',
        string $appName   = 'FactoryTestApp'
    ): Container {
        $tmpDir = sys_get_temp_dir();

        // EventDispatcher stub — Model/View/Controller constructors call trigger() on it.
        // We mock it so we don't need to pass a fully-built Container to its constructor.
        $ed = $this->createMock(EventDispatcher::class);
        $ed->method('trigger')->willReturn([]);

        // Language stub — returns the key unchanged.
        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $k) => $k);

        // Input stub — no superglobal reads.
        $input = new Input([]);

        // Application stub — only getName() and getTemplate() are called.
        $application = $this->createMock(\Awf\Application\Application::class);
        $application->method('getName')->willReturn($appName);
        $application->method('getTemplate')->willReturn('default');

        return new Container([
            'application_name'     => $appName,
            'applicationNamespace' => $namespace,
            'session_segment_name' => strtolower($appName) . '_seg',
            'basePath'             => $tmpDir,
            'languagePath'         => $tmpDir,
            'temporaryPath'        => $tmpDir,
            'templatePath'         => $tmpDir,
            'sqlPath'              => $tmpDir,
            'filesystemBase'       => $tmpDir,
            // Override lazy services with stubs so nothing hits disk/DB/session.
            'eventDispatcher'      => $ed,
            'language'             => $language,
            'input'                => $input,
            'application'          => $application,
        ]);
    }

    /** Build a Factory backed by the \FactoryTestApp namespace. */
    private function makeFactory(
        string $namespace = '\\FactoryTestApp',
        string $appName   = 'FactoryTestApp'
    ): Factory {
        return new Factory($this->makeContainer($namespace, $appName));
    }

    // -------------------------------------------------------------------------
    // Factory construction & language wiring
    // -------------------------------------------------------------------------

    public function testFactoryImplementsContainerAware(): void
    {
        self::assertInstanceOf(
            \Awf\Container\ContainerAwareInterface::class,
            $this->makeFactory()
        );
    }

    public function testFactoryGetLanguageFallsBackToContainer(): void
    {
        self::assertInstanceOf(Language::class, $this->makeFactory()->getLanguage());
    }

    public function testFactoryGetLanguageUsesInjectedLanguage(): void
    {
        $container  = $this->makeContainer();
        $factory    = new Factory($container);
        $customLang = $this->createMock(Language::class);

        $factory->setLanguage($customLang);

        self::assertSame($customLang, $factory->getLanguage());
    }

    // -------------------------------------------------------------------------
    // makeController — exact class match
    // -------------------------------------------------------------------------

    public function testMakeControllerExactMatch(): void
    {
        $ctrl = $this->makeFactory()->makeController('item');

        self::assertInstanceOf(\FactoryTestApp\Controller\Item::class, $ctrl);
    }

    // -------------------------------------------------------------------------
    // makeController — plural fallback
    // -------------------------------------------------------------------------

    public function testMakeControllerPluralFallback(): void
    {
        // \FactoryTestApp2 only has Widgets (plural); pass the singular "widget".
        $ctrl = $this->makeFactory('\\FactoryTestApp2', 'FactoryTestApp2')
                     ->makeController('widget');

        self::assertInstanceOf(\FactoryTestApp2\Controller\Widgets::class, $ctrl);
    }

    // -------------------------------------------------------------------------
    // makeController — DefaultController fallback
    // -------------------------------------------------------------------------

    public function testMakeControllerDefaultFallback(): void
    {
        // "unknown" has no matching class in \FactoryTestApp → DefaultController.
        $ctrl = $this->makeFactory()->makeController('unknown');

        self::assertInstanceOf(\FactoryTestApp\Controller\DefaultController::class, $ctrl);
    }

    // -------------------------------------------------------------------------
    // makeController — RuntimeException when nothing found
    // -------------------------------------------------------------------------

    public function testMakeControllerThrowsWhenNothingFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Controller not found/');

        // \FactoryTestApp2 has no DefaultController, so "unknown" throws.
        $this->makeFactory('\\FactoryTestApp2', 'FactoryTestApp2')
             ->makeController('unknown');
    }

    // -------------------------------------------------------------------------
    // makeController — null name reads from input 'view' key
    // -------------------------------------------------------------------------

    public function testMakeControllerNullUsesInputView(): void
    {
        $container      = $this->makeContainer();
        $container['input'] = new Input(['view' => 'item']);
        $factory        = new Factory($container);

        $ctrl = $factory->makeController(null);

        self::assertInstanceOf(\FactoryTestApp\Controller\Item::class, $ctrl);
    }

    // -------------------------------------------------------------------------
    // makeController — custom Language is passed to the returned object
    // -------------------------------------------------------------------------

    public function testMakeControllerPassesCustomLanguage(): void
    {
        $factory    = $this->makeFactory();
        $customLang = $this->createMock(Language::class);

        $ctrl = $factory->makeController('item', $customLang);

        self::assertSame($customLang, $ctrl->getLanguage());
    }

    // -------------------------------------------------------------------------
    // makeModel — exact class match
    // -------------------------------------------------------------------------

    public function testMakeModelExactMatch(): void
    {
        $model = $this->makeFactory()->makeModel('item');

        self::assertInstanceOf(\FactoryTestApp\Model\Item::class, $model);
    }

    // -------------------------------------------------------------------------
    // makeModel — DefaultModel fallback
    // -------------------------------------------------------------------------

    public function testMakeModelDefaultFallback(): void
    {
        $model = $this->makeFactory()->makeModel('unknown');

        self::assertInstanceOf(\FactoryTestApp\Model\DefaultModel::class, $model);
    }

    // -------------------------------------------------------------------------
    // makeModel — RuntimeException when nothing found
    // -------------------------------------------------------------------------

    public function testMakeModelThrowsWhenNothingFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Model not found/');

        $this->makeFactory('\\FactoryTestApp2', 'FactoryTestApp2')
             ->makeModel('unknown');
    }

    // -------------------------------------------------------------------------
    // makeModel — null name reads from input 'view' key
    // -------------------------------------------------------------------------

    public function testMakeModelNullUsesInputView(): void
    {
        $container          = $this->makeContainer();
        $container['input'] = new Input(['view' => 'item']);
        $factory            = new Factory($container);

        $model = $factory->makeModel(null);

        self::assertInstanceOf(\FactoryTestApp\Model\Item::class, $model);
    }

    // -------------------------------------------------------------------------
    // makeModel — custom Language passed to returned object
    // -------------------------------------------------------------------------

    public function testMakeModelPassesCustomLanguage(): void
    {
        $factory    = $this->makeFactory();
        $customLang = $this->createMock(Language::class);

        $model = $factory->makeModel('item', $customLang);

        self::assertSame($customLang, $model->getLanguage());
    }

    // -------------------------------------------------------------------------
    // makeModel — legacy mvc_config triggers E_USER_DEPRECATED
    // -------------------------------------------------------------------------

    public function testMakeModelWithMvcConfigDeprecated(): void
    {
        $container               = $this->makeContainer();
        $container['mvc_config'] = ['modelTemporaryInstance' => true];
        $factory                 = new Factory($container);

        $triggered = false;
        set_error_handler(
            static function (int $errno) use (&$triggered): bool {
                if ($errno === E_USER_DEPRECATED) {
                    $triggered = true;
                }

                return true; // suppress — do not escalate to exception
            },
            E_USER_DEPRECATED
        );

        $model = $factory->makeModel('item');

        restore_error_handler();

        self::assertTrue($triggered, 'Expected E_USER_DEPRECATED to be triggered');
        self::assertInstanceOf(Model::class, $model);
    }

    // -------------------------------------------------------------------------
    // makeTempModel — returns a stateless clone
    // -------------------------------------------------------------------------

    public function testMakeTempModelReturnsModel(): void
    {
        $model = $this->makeFactory()->makeTempModel('item');

        self::assertInstanceOf(\FactoryTestApp\Model\Item::class, $model);
    }

    public function testMakeTempModelHasSavestateOff(): void
    {
        $model = $this->makeFactory()->makeTempModel('item');

        // savestate(0) sets the protected $_savestate flag to false.
        // Cast to array so we can read it without using setAccessible() (deprecated in PHP 8.5+).
        $data = (array) $model;
        // Protected properties are keyed with a "\x00*\x00" prefix.
        $key = "\x00*\x00_savestate";
        self::assertArrayHasKey($key, $data, '$_savestate key not found in model state');
        self::assertFalse($data[$key], '$_savestate should be false after savestate(0)');
    }

    // -------------------------------------------------------------------------
    // makeView — exact name + type match (item / html)
    // -------------------------------------------------------------------------

    public function testMakeViewExactMatch(): void
    {
        $view = $this->makeFactory()->makeView('item', 'html');

        self::assertInstanceOf(\FactoryTestApp\View\Item\Html::class, $view);
    }

    // -------------------------------------------------------------------------
    // makeView — DefaultView fallback within named view (item / xml)
    // -------------------------------------------------------------------------

    public function testMakeViewDefaultViewForNamedView(): void
    {
        $view = $this->makeFactory()->makeView('item', 'xml');

        self::assertInstanceOf(\FactoryTestApp\View\Item\DefaultView::class, $view);
    }

    // -------------------------------------------------------------------------
    // makeView — Default/Json cross-view fallback (noview / json)
    // -------------------------------------------------------------------------

    public function testMakeViewDefaultNamespaceForType(): void
    {
        // "noview" does not have its own view class; json maps to Default\Json.
        $view = $this->makeFactory()->makeView('noview', 'json');

        self::assertInstanceOf(\FactoryTestApp\View\Default\Json::class, $view);
    }

    // -------------------------------------------------------------------------
    // makeView — RuntimeException when nothing found
    // -------------------------------------------------------------------------

    public function testMakeViewThrowsWhenNothingFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/View not found/');

        // \FactoryTestApp2 has no view classes at all.
        $this->makeFactory('\\FactoryTestApp2', 'FactoryTestApp2')
             ->makeView('noview', 'noformat');
    }

    // -------------------------------------------------------------------------
    // makeView — null name / type reads from input
    // -------------------------------------------------------------------------

    public function testMakeViewNullParamsUseInput(): void
    {
        $container          = $this->makeContainer();
        $container['input'] = new Input(['view' => 'item', 'format' => 'html']);
        $factory            = new Factory($container);

        $view = $factory->makeView(null, null);

        self::assertInstanceOf(\FactoryTestApp\View\Item\Html::class, $view);
    }

    // -------------------------------------------------------------------------
    // makeView — custom Language passed to returned object
    // -------------------------------------------------------------------------

    public function testMakeViewPassesCustomLanguage(): void
    {
        $factory    = $this->makeFactory();
        $customLang = $this->createMock(Language::class);

        $view = $factory->makeView('item', 'html', $customLang);

        self::assertSame($customLang, $view->getLanguage());
    }
}
