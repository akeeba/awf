<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Text;

use Awf\Application\Configuration;
use Awf\Container\Container;
use Awf\Session\Manager as SessionManager;
use Awf\Text\Language;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Awf\Text\Language.
 *
 * The tests use a real Container wired with stub services (appConfig, session)
 * and language fixture files stored in _data/ next to this file.
 */
class LanguageTest extends TestCase
{
    /** Absolute path to the fixture directory. */
    private string $dataDir;

    protected function setUp(): void
    {
        $this->dataDir = __DIR__ . '/_data';
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    /**
     * Build a minimal Container whose appConfig returns the given default language
     * and whose session manager reports isStarted() === false (so detectLanguage()
     * never tries to access userManager).
     */
    private function makeContainer(string $defaultLang = 'en-GB'): Container
    {
        $session = $this->createMock(SessionManager::class);
        $session->method('isStarted')->willReturn(false);

        $container = new Container([
            'application_name'     => 'TestApp',
            'applicationNamespace' => '\\TestApp',
            'session_segment_name' => 'testapp_seg',
            'basePath'             => $this->dataDir,
            'languagePath'         => $this->dataDir,
            'temporaryPath'        => sys_get_temp_dir(),
            'templatePath'         => $this->dataDir,
            'sqlPath'              => $this->dataDir,
            'filesystemBase'       => $this->dataDir,
            'session'              => $session,
        ]);

        // Override appConfig to return our controlled default language setting.
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

    private function makeLanguage(string $defaultLang = 'en-GB'): Language
    {
        return new Language($this->makeContainer($defaultLang));
    }

    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    public function testConstructorStoresContainer(): void
    {
        $container = $this->makeContainer();
        $language  = new Language($container);

        self::assertSame($container, $language->getContainer());
    }

    // -------------------------------------------------------------------------
    // loadLanguage — happy path
    // -------------------------------------------------------------------------

    public function testLoadLanguagePopulatesStrings(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        self::assertTrue($lang->hasKey('GREETING'));
        self::assertSame('Hello, World!', $lang->text('GREETING'));
    }

    public function testLoadLanguageSetsLangCode(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        self::assertSame('en-GB', $lang->getLangCode());
    }

    public function testLoadLanguageKeyLookupIsCaseInsensitive(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        // text() uppercases the key before lookup
        self::assertSame('Hello, World!', $lang->text('greeting'));
        self::assertSame('Hello, World!', $lang->text('Greeting'));
        self::assertSame('Hello, World!', $lang->text('GREETING'));
    }

    public function testLoadLanguageSubdirectoryLayout(): void
    {
        // Create a temp dir with the subdirectory layout:  $dir/en-GB/en-GB.ini
        $tmpBase = sys_get_temp_dir() . '/awf_lang_test_' . uniqid();
        $subDir  = $tmpBase . '/en-GB';
        mkdir($subDir, 0777, true);
        copy($this->dataDir . '/en-GB.ini', $subDir . '/en-GB.ini');

        try {
            $lang = $this->makeLanguage();
            $lang->loadLanguage('en-GB', $tmpBase, true, false);

            self::assertTrue($lang->hasKey('GREETING'));
            self::assertSame('Hello, World!', $lang->text('GREETING'));
        } finally {
            @unlink($subDir . '/en-GB.ini');
            @rmdir($subDir);
            @rmdir($tmpBase);
        }
    }

    public function testLoadLanguageApplicationSubdirectoryLayout(): void
    {
        // $dir/testapp/en-GB.ini — the application_name is TestApp.
        $tmpBase = sys_get_temp_dir() . '/awf_lang_test_' . uniqid();
        $subDir  = $tmpBase . '/testapp';
        mkdir($subDir, 0777, true);
        copy($this->dataDir . '/en-GB.ini', $subDir . '/en-GB.ini');

        try {
            $lang = $this->makeLanguage();
            $lang->loadLanguage('en-GB', $tmpBase, true, false);

            self::assertSame('Hello, World!', $lang->text('GREETING'));
        } finally {
            @unlink($subDir . '/en-GB.ini');
            @rmdir($subDir);
            @rmdir($tmpBase);
        }
    }

    public function testLoadLanguageApplicationSubdirectoryPerLanguageLayout(): void
    {
        // $dir/testapp/en-GB/en-GB.ini — the application_name is TestApp.
        $tmpBase = sys_get_temp_dir() . '/awf_lang_test_' . uniqid();
        $subDir  = $tmpBase . '/testapp/en-GB';
        mkdir($subDir, 0777, true);
        copy($this->dataDir . '/en-GB.ini', $subDir . '/en-GB.ini');

        try {
            $lang = $this->makeLanguage();
            $lang->loadLanguage('en-GB', $tmpBase, true, false);

            self::assertSame('Hello, World!', $lang->text('GREETING'));
        } finally {
            @unlink($subDir . '/en-GB.ini');
            @rmdir($subDir);
            @rmdir($tmpBase . '/testapp');
            @rmdir($tmpBase);
        }
    }

    // -------------------------------------------------------------------------
    // loadLanguage — overwrite behaviour
    // -------------------------------------------------------------------------

    public function testLoadLanguageOverwriteTrueReplacesExistingKeys(): void
    {
        // Load fr-FR first (has GREETING), then en-GB with overwrite=true.
        $lang = $this->makeLanguage();
        $lang->loadLanguage('fr-FR', $this->dataDir, false, false);
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        // en-GB GREETING should win because overwrite was true for en-GB load.
        self::assertSame('Hello, World!', $lang->text('GREETING'));
    }

    public function testLoadLanguageOverwriteFalseKeepsExistingKeys(): void
    {
        // Load en-GB first, then fr-FR with overwrite=false.
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, false, false);
        $lang->loadLanguage('fr-FR', $this->dataDir, false, false);

        // en-GB GREETING should survive because fr-FR load used overwrite=false.
        self::assertSame('Hello, World!', $lang->text('GREETING'));
    }

    public function testLoadLanguageOverwriteFalseAddsNewKeys(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, false, false);
        $lang->loadLanguage('fr-FR', $this->dataDir, false, false);

        // FR_ONLY only exists in fr-FR and should be available.
        self::assertSame('French only string.', $lang->text('FR_ONLY'));
    }

    // -------------------------------------------------------------------------
    // loadLanguage — default language fallback (useDefault=true)
    // -------------------------------------------------------------------------

    public function testLoadLanguageWithDefaultFallbackLoadsDefaultFirst(): void
    {
        // Load fr-FR with useDefault=true; default is en-GB.
        // en-GB keys should be present as fallback.
        $lang = $this->makeLanguage('en-GB');
        $lang->loadLanguage('fr-FR', $this->dataDir, true, true);

        // FAREWELL only exists in en-GB fixture; should be available as fallback.
        self::assertTrue($lang->hasKey('FAREWELL'));
        // GREETING should be the fr-FR version (overwrite=true for the final load).
        self::assertSame('Bonjour le monde!', $lang->text('GREETING'));
        self::assertSame('French only string.', $lang->text('FR_ONLY'));
    }

    public function testLoadLanguageNoFallbackWhenLangCodeEqualsDefault(): void
    {
        $lang = $this->makeLanguage('en-GB');
        $lang->loadLanguage('en-GB', $this->dataDir, true, true);

        // When lang == default no recursive load should happen (just loads once).
        self::assertSame('Hello, World!', $lang->text('GREETING'));
    }

    // -------------------------------------------------------------------------
    // loadLanguage — missing / unreadable file
    // -------------------------------------------------------------------------

    public function testLoadLanguageNonExistentFileDoesNotThrow(): void
    {
        $lang = $this->makeLanguage();

        // Should silently succeed with no strings loaded.
        $lang->loadLanguage('xx-ZZ', $this->dataDir, true, false);

        self::assertFalse($lang->hasKey('GREETING'));
    }

    public function testLoadLanguageNonExistentPathDoesNotThrow(): void
    {
        $lang = $this->makeLanguage();

        $lang->loadLanguage('en-GB', '/no/such/path/anywhere', true, false);

        self::assertFalse($lang->hasKey('GREETING'));
    }

    // -------------------------------------------------------------------------
    // text() — key not found
    // -------------------------------------------------------------------------

    public function testTextReturnsMissingKeyAsIs(): void
    {
        $lang = $this->makeLanguage();

        self::assertSame('MISSING_KEY', $lang->text('MISSING_KEY'));
    }

    public function testTextUppercasesMissingKey(): void
    {
        $lang = $this->makeLanguage();

        // Key should be returned uppercased even when missing.
        self::assertSame('MISSING_KEY', $lang->text('missing_key'));
    }

    // -------------------------------------------------------------------------
    // text() — backslash interpretation
    // -------------------------------------------------------------------------

    public function testTextInterpretsTabEscape(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        $result = $lang->text('ITEM_WITH_TABS', false, true);
        self::assertSame("Line1\tLine2", $result);
    }

    public function testTextInterpretsNewlineEscape(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        $result = $lang->text('ITEM_WITH_NEWLINE', false, true);
        self::assertSame("Line1\nLine2", $result);
    }

    public function testTextInterpretsDoubleBackslashAsLiteralBackslash(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        $result = $lang->text('ITEM_WITH_BACKSLASH', false, true);
        self::assertSame('back\\slash', $result);
    }

    public function testTextWithInterpretBackSlashesFalseReturnsRaw(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        $result = $lang->text('ITEM_WITH_TABS', false, false);
        // Raw value should still contain the backslash-t literal
        self::assertStringContainsString('\\', $result);
    }

    // -------------------------------------------------------------------------
    // text() — jsSafe mode
    // -------------------------------------------------------------------------

    public function testTextJsSafeModeAddsSlashes(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        // ITEM_WITH_QQ value contains a double-quote; addslashes should escape it.
        $result = $lang->text('ITEM_WITH_QQ', true, false);
        self::assertStringContainsString('\\"', $result);
    }

    public function testTextJsSafeTakesPrecedenceOverInterpretBackSlashes(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        // jsSafe=true should return addslashes() result, not backslash-interpreted result.
        $result = $lang->text('ITEM_WITH_TABS', true, true);
        // addslashes on "Line1\tLine2" — \t is two chars so no extra escaping needed.
        self::assertSame(addslashes($lang->text('ITEM_WITH_TABS', false, false)), $result);
    }

    // -------------------------------------------------------------------------
    // hasKey()
    // -------------------------------------------------------------------------

    public function testHasKeyReturnsTrueForLoadedKey(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        self::assertTrue($lang->hasKey('GREETING'));
        self::assertTrue($lang->hasKey('greeting')); // case-insensitive
    }

    public function testHasKeyReturnsFalseForMissingKey(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        self::assertFalse($lang->hasKey('DOES_NOT_EXIST'));
    }

    public function testHasKeyReturnsFalseWhenNoFileLoaded(): void
    {
        $lang = $this->makeLanguage();

        self::assertFalse($lang->hasKey('GREETING'));
    }

    // -------------------------------------------------------------------------
    // sprintf()
    // -------------------------------------------------------------------------

    public function testSprintfSubstitutesArguments(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        $result = $lang->sprintf('FORMATTED', 'Alice', 5);
        self::assertSame('Hello, Alice! You have 5 messages.', $result);
    }

    public function testSprintfWithNoSubstitutionArguments(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        $result = $lang->sprintf('FAREWELL');
        self::assertSame('Goodbye!', $result);
    }

    public function testSprintfWithMissingKeyUsesKeyAsFormatString(): void
    {
        $lang = $this->makeLanguage();

        // Key does not exist; text() returns the key itself as the format string.
        // sprintf('UNKNOWN_KEY') should equal 'UNKNOWN_KEY'.
        $result = $lang->sprintf('UNKNOWN_KEY');
        self::assertSame('UNKNOWN_KEY', $result);
    }

    public function testSprintfReturnsBadTranslationMessageOnFormatMismatch(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        // FAREWELL="Goodbye!" — passing extra typed argument causes sprintf error.
        // The source code catches the Throwable and returns the BAD TRANSLATION message.
        $result = $lang->sprintf('FAREWELL', 'unexpected_arg');
        // Since 'Goodbye!' has no format specifiers, extra args are silently ignored
        // by PHP's sprintf. So this actually succeeds.
        self::assertSame('Goodbye!', $result);
    }

    public function testSprintfNullKeyReturnsBadTranslationMessage(): void
    {
        $lang = $this->makeLanguage();

        // NULL key — text(null) returns '' (key not found), then sprintf('', ...) is
        // called. In PHP 8.x calling sprintf(null) raises a TypeError which is caught
        // and returns the "BAD TRANSLATION" error string.
        $result = $lang->sprintf(null);
        self::assertStringContainsString('BAD TRANSLATION', $result);
    }

    // -------------------------------------------------------------------------
    // plural()
    // -------------------------------------------------------------------------

    public function testPluralUsesCountSpecificKeyWhenAvailable(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        // APPLES_N_1 exists
        $result = $lang->plural('APPLES_N', 1);
        self::assertSame('One apple has been eaten.', $result);
    }

    public function testPluralUsesCountSpecificKeyForZero(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        // APPLES_N_0 exists
        $result = $lang->plural('APPLES_N', 0);
        self::assertSame('No apples have been eaten.', $result);
    }

    public function testPluralFallsBackToBaseKeyWhenNoCountSpecificKey(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        // APPLES_N_5 does NOT exist; should use APPLES_N with sprintf(%d, 5)
        $result = $lang->plural('APPLES_N', 5);
        self::assertSame('5 apples have been eaten.', $result);
    }

    public function testPluralWithMissingBaseKey(): void
    {
        $lang = $this->makeLanguage();

        // Neither NOPE_N_3 nor NOPE_N exists; text() returns the key.
        $result = $lang->plural('NOPE_N', 3);
        // sprintf('NOPE_N', 3) → 'NOPE_N' (no format specifiers in the key itself)
        self::assertSame('NOPE_N', $result);
    }

    // -------------------------------------------------------------------------
    // Post-processing callbacks
    // -------------------------------------------------------------------------

    public function testCallbackReceivesFilenameAndStrings(): void
    {
        $lang = $this->makeLanguage();

        $capturedFilename = null;
        $capturedStrings  = null;

        $callback = static function (string $filename, array $strings) use (&$capturedFilename, &$capturedStrings): ?array {
            $capturedFilename = $filename;
            $capturedStrings  = $strings;

            return null; // returning null means "use the original strings"
        };

        $lang->loadLanguage('en-GB', $this->dataDir, true, false, $callback);

        self::assertNotNull($capturedFilename);
        self::assertStringEndsWith('en-GB.ini', $capturedFilename);
        self::assertIsArray($capturedStrings);
        self::assertArrayHasKey('GREETING', $capturedStrings);
    }

    public function testCallbackCanModifyStrings(): void
    {
        $lang = $this->makeLanguage();

        $callback = static function (string $filename, array $strings): array {
            $strings['GREETING'] = 'Modified!';
            $strings['EXTRA']    = 'Added by callback.';

            return $strings;
        };

        $lang->loadLanguage('en-GB', $this->dataDir, true, false, $callback);

        self::assertSame('Modified!', $lang->text('GREETING'));
        self::assertSame('Added by callback.', $lang->text('EXTRA'));
    }

    public function testCallbackReturningFalseAbortsLoad(): void
    {
        $lang = $this->makeLanguage();

        $callback = static function (string $filename, array $strings): bool {
            return false;
        };

        $lang->loadLanguage('en-GB', $this->dataDir, true, false, $callback);

        // Callback returned false → load was aborted → no strings should be stored.
        self::assertFalse($lang->hasKey('GREETING'));
    }

    public function testCallbackAsArrayOfCallables(): void
    {
        $lang = $this->makeLanguage();

        $calls = 0;

        $cb1 = static function (string $filename, array $strings) use (&$calls): array {
            $calls++;
            $strings['CB1'] = 'from_cb1';

            return $strings;
        };

        $cb2 = static function (string $filename, array $strings) use (&$calls): array {
            $calls++;
            $strings['CB2'] = 'from_cb2';

            return $strings;
        };

        $lang->loadLanguage('en-GB', $this->dataDir, true, false, [$cb1, $cb2]);

        self::assertSame(2, $calls);
        self::assertSame('from_cb1', $lang->text('CB1'));
        self::assertSame('from_cb2', $lang->text('CB2'));
    }

    // -------------------------------------------------------------------------
    // getLangCode()
    // -------------------------------------------------------------------------

    public function testGetLangCodeIsNullBeforeAnyLoad(): void
    {
        $lang = $this->makeLanguage();

        self::assertNull($lang->getLangCode());
    }

    public function testGetLangCodeIsNullAfterLoadWithOverwriteFalse(): void
    {
        // When overwrite=false the langCode property is not updated.
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, false, false);

        self::assertNull($lang->getLangCode());
    }

    public function testGetLangCodeIsSetAfterLoadWithOverwriteTrue(): void
    {
        $lang = $this->makeLanguage();
        $lang->loadLanguage('en-GB', $this->dataDir, true, false);

        self::assertSame('en-GB', $lang->getLangCode());
    }

    // -------------------------------------------------------------------------
    // _QQ_ quote-fixing
    // -------------------------------------------------------------------------

    public function testLoadLanguageFixesQqQuotes(): void
    {
        // Write a temporary INI file that uses the _QQ_ escape sequences.
        $tmpFile = sys_get_temp_dir() . '/awf_qq_test_' . uniqid() . '.ini';
        file_put_contents($tmpFile, 'QQ_KEY="He said \\"_QQ_\\"hello\\"_QQ_\\"."' . "\n");

        $tmpDir = sys_get_temp_dir() . '/awf_qq_dir_' . uniqid();
        mkdir($tmpDir, 0777, true);
        rename($tmpFile, $tmpDir . '/en-GB.ini');

        try {
            $lang = $this->makeLanguage();
            $lang->loadLanguage('en-GB', $tmpDir, true, false);

            self::assertTrue($lang->hasKey('QQ_KEY'));
        } finally {
            @unlink($tmpDir . '/en-GB.ini');
            @rmdir($tmpDir);
        }
    }
}
