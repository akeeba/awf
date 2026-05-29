<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Text;

use Awf\Application\Configuration;
use Awf\Container\Container;
use Awf\Session\Manager as SessionManager;
use Awf\Text\Language;
use Awf\Text\Text;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Awf\Text\Text — static facade over Language.
 *
 * The tests inject a real Language (loaded with fixture strings) into Text via
 * setContainer(), then reset the static state in tearDown().
 */
class TextTest extends TestCase
{
    /** Absolute path to the language fixture directory. */
    private string $dataDir;

    private Container $container;

    protected function setUp(): void
    {
        $this->dataDir  = __DIR__ . '/_data';
        $this->container = $this->makeContainer();

        // Inject the container so Text knows where to find the language service.
        Text::setContainer($this->container);
    }

    protected function tearDown(): void
    {
        // Reset static state so other tests are not affected.
        Text::setContainer($this->makeContainer());
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function makeContainer(string $defaultLang = 'en-GB'): Container
    {
        $session = $this->createMock(SessionManager::class);
        $session->method('isStarted')->willReturn(false);

        $container = new Container([
            'application_name'     => 'TestApp',
            'applicationNamespace' => '\\TestApp',
            'session_segment_name' => 'testapp_seg',
            'basePath'             => $this->dataDir ?? sys_get_temp_dir(),
            'languagePath'         => $this->dataDir ?? sys_get_temp_dir(),
            'temporaryPath'        => sys_get_temp_dir(),
            'templatePath'         => $this->dataDir ?? sys_get_temp_dir(),
            'sqlPath'              => $this->dataDir ?? sys_get_temp_dir(),
            'filesystemBase'       => $this->dataDir ?? sys_get_temp_dir(),
            'session'              => $session,
        ]);

        $appConfig = $this->createMock(Configuration::class);
        $appConfig->method('get')->willReturnCallback(
            static function (string $key, $default = null) use ($defaultLang) {
                if ($key === 'language') {
                    return $defaultLang;
                }

                return $default;
            }
        );

        $container['appConfig'] = $appConfig;

        return $container;
    }

    /**
     * Create a Language instance pre-loaded with the en-GB fixture, and wire it
     * into the container so Text delegates to it.
     */
    private function loadEnGb(): Language
    {
        $lang = new Language($this->container);
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);
        $this->container['language'] = $lang;

        return $lang;
    }

    // -------------------------------------------------------------------------
    // setContainer / getContainer
    // -------------------------------------------------------------------------

    public function testGetContainerReturnsWhatWasSet(): void
    {
        self::assertSame($this->container, Text::getContainer());
    }

    public function testSetContainerReplacesContainer(): void
    {
        $other = $this->makeContainer();
        Text::setContainer($other);

        self::assertSame($other, Text::getContainer());
    }

    // -------------------------------------------------------------------------
    // _() — basic translation
    // -------------------------------------------------------------------------

    public function testUnderscoreReturnsTranslatedString(): void
    {
        $this->loadEnGb();

        self::assertSame('Hello, World!', Text::_('GREETING'));
    }

    public function testUnderscoreIsCaseInsensitive(): void
    {
        $this->loadEnGb();

        self::assertSame('Hello, World!', Text::_('greeting'));
        self::assertSame('Hello, World!', Text::_('Greeting'));
    }

    public function testUnderscoreReturnsMissingKeyAsIs(): void
    {
        // No language loaded — key is returned uppercased.
        self::assertSame('MISSING_KEY', Text::_('MISSING_KEY'));
        self::assertSame('MISSING_KEY', Text::_('missing_key'));
    }

    public function testUnderscoreInterpretsBackSlashesByDefault(): void
    {
        $this->loadEnGb();

        $result = Text::_('ITEM_WITH_TABS');
        self::assertSame("Line1\tLine2", $result);
    }

    public function testUnderscoreJsSafeModeAddsSlashes(): void
    {
        $this->loadEnGb();

        // ITEM_WITH_QQ contains a double-quote.
        $result = Text::_('ITEM_WITH_QQ', true, false);
        self::assertStringContainsString('\\"', $result);
    }

    public function testUnderscoreWithInterpretBackSlashesFalseReturnsRaw(): void
    {
        $this->loadEnGb();

        $result = Text::_('ITEM_WITH_TABS', false, false);
        // Raw value should still contain a literal backslash.
        self::assertStringContainsString('\\', $result);
    }

    // -------------------------------------------------------------------------
    // _() delegates directly to Language::text()
    // -------------------------------------------------------------------------

    public function testUnderscoreDelegatesToLanguageText(): void
    {
        $lang = $this->createMock(Language::class);
        $lang->expects(self::once())
            ->method('text')
            ->with('GREETING', false, true)
            ->willReturn('mocked translation');

        $this->container['language'] = $lang;

        self::assertSame('mocked translation', Text::_('GREETING'));
    }

    // -------------------------------------------------------------------------
    // sprintf()
    // -------------------------------------------------------------------------

    public function testSprintfSubstitutesArguments(): void
    {
        $this->loadEnGb();

        $result = Text::sprintf('FORMATTED', 'Alice', 5);
        self::assertSame('Hello, Alice! You have 5 messages.', $result);
    }

    public function testSprintfWithNoArguments(): void
    {
        $this->loadEnGb();

        self::assertSame('Goodbye!', Text::sprintf('FAREWELL'));
    }

    public function testSprintfWithMissingKeyUsesKeyAsFormatString(): void
    {
        // No language loaded.
        $result = Text::sprintf('UNKNOWN_KEY');
        self::assertSame('UNKNOWN_KEY', $result);
    }

    public function testSprintfDelegatesToLanguageSprintf(): void
    {
        $lang = $this->createMock(Language::class);
        $lang->expects(self::once())
            ->method('sprintf')
            ->with('FORMATTED', 'Bob', 3)
            ->willReturn('Hello, Bob! You have 3 messages.');

        $this->container['language'] = $lang;

        self::assertSame('Hello, Bob! You have 3 messages.', Text::sprintf('FORMATTED', 'Bob', 3));
    }

    // -------------------------------------------------------------------------
    // plural()
    // -------------------------------------------------------------------------

    public function testPluralUsesCountSpecificKeyWhenAvailable(): void
    {
        $this->loadEnGb();

        self::assertSame('One apple has been eaten.', Text::plural('APPLES_N', 1));
    }

    public function testPluralUsesZeroSpecificKey(): void
    {
        $this->loadEnGb();

        self::assertSame('No apples have been eaten.', Text::plural('APPLES_N', 0));
    }

    public function testPluralFallsBackToBaseKey(): void
    {
        $this->loadEnGb();

        // APPLES_N_5 does not exist; should use APPLES_N with sprintf.
        self::assertSame('5 apples have been eaten.', Text::plural('APPLES_N', 5));
    }

    public function testPluralWithMissingBaseKey(): void
    {
        // Neither NOPE_N_3 nor NOPE_N exists.
        $result = Text::plural('NOPE_N', 3);
        self::assertSame('NOPE_N', $result);
    }

    public function testPluralDelegatesToLanguagePlural(): void
    {
        $lang = $this->createMock(Language::class);
        $lang->expects(self::once())
            ->method('plural')
            ->with('APPLES_N', 5)
            ->willReturn('5 apples have been eaten.');

        $this->container['language'] = $lang;

        self::assertSame('5 apples have been eaten.', Text::plural('APPLES_N', 5));
    }

    // -------------------------------------------------------------------------
    // hasKey()
    // -------------------------------------------------------------------------

    public function testHasKeyReturnsTrueForLoadedKey(): void
    {
        $this->loadEnGb();

        self::assertTrue(Text::hasKey('GREETING'));
        self::assertTrue(Text::hasKey('greeting')); // case-insensitive via Language::hasKey
    }

    public function testHasKeyReturnsFalseForMissingKey(): void
    {
        $this->loadEnGb();

        self::assertFalse(Text::hasKey('DOES_NOT_EXIST'));
    }

    public function testHasKeyReturnsFalseWhenNoFileLoaded(): void
    {
        // Inject a fresh, empty Language that has never loaded any files.
        $emptyLang = new Language($this->container);
        $this->container['language'] = $emptyLang;

        self::assertFalse(Text::hasKey('GREETING'));
    }

    // -------------------------------------------------------------------------
    // getScriptStrings() — returns [] when document is unavailable
    // -------------------------------------------------------------------------

    public function testGetScriptStringsReturnsArrayWhenNoApp(): void
    {
        // There is no Application registered; the method catches the exception.
        $result = Text::getScriptStrings();
        self::assertIsArray($result);
    }

    // -------------------------------------------------------------------------
    // script() — deprecated, triggers E_USER_DEPRECATED
    // -------------------------------------------------------------------------

    public function testScriptTriggersDeprecationNotice(): void
    {
        $deprecationTriggered = false;
        $message              = '';

        $previous = set_error_handler(
            static function (int $errno, string $errstr) use (&$deprecationTriggered, &$message): bool {
                if ($errno === E_USER_DEPRECATED) {
                    $deprecationTriggered = true;
                    $message              = $errstr;
                }

                return true; // suppress further handling
            }
        );

        try {
            Text::script('GREETING');
        } catch (\Throwable $e) {
            // Ignore any subsequent exception caused by missing Application.
        } finally {
            restore_error_handler();
        }

        self::assertTrue($deprecationTriggered, 'Text::script() should trigger E_USER_DEPRECATED');
        self::assertStringContainsString('deprecated', strtolower($message));
    }
}
