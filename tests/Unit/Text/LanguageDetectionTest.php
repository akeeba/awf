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
 * Unit tests for the language auto-detection internals of Awf\Text\Language:
 * getAcceptedLanguages(), getKnownLanguages() and findMostRelevantLanguage().
 *
 * All three methods are private; they are exercised through reflection.
 */
class LanguageDetectionTest extends TestCase
{
    /** Temporary directories created by a test, removed in tearDown(). */
    private array $tempDirs = [];

    /** The value of $_SERVER['HTTP_ACCEPT_LANGUAGE'] before the test ran. */
    private ?string $oldAcceptLanguage = null;

    protected function setUp(): void
    {
        $this->oldAcceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;

        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    }

    protected function tearDown(): void
    {
        if ($this->oldAcceptLanguage === null) {
            unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        } else {
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $this->oldAcceptLanguage;
        }

        foreach ($this->tempDirs as $dir) {
            $this->removeDirectory($dir);
        }

        $this->tempDirs = [];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a minimal Container. appConfig returns $defaultLang for the `language` key and the session manager
     * reports isStarted() === false.
     */
    private function makeContainer(string $defaultLang = 'en-GB', ?string $languagePath = null): Container
    {
        $session = $this->createMock(SessionManager::class);
        $session->method('isStarted')->willReturn(false);

        $languagePath ??= __DIR__ . '/_data';

        $container = new Container([
            'application_name'     => 'TestApp',
            'applicationNamespace' => '\\TestApp',
            'session_segment_name' => 'testapp_seg',
            'basePath'             => __DIR__ . '/_data',
            'languagePath'         => $languagePath,
            'temporaryPath'        => sys_get_temp_dir(),
            'templatePath'         => __DIR__ . '/_data',
            'sqlPath'              => __DIR__ . '/_data',
            'filesystemBase'       => __DIR__ . '/_data',
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

    private function makeLanguage(string $defaultLang = 'en-GB', ?string $languagePath = null): Language
    {
        return new Language($this->makeContainer($defaultLang, $languagePath));
    }

    /**
     * Create a temporary language directory. Entries ending in a slash are created as directories, everything else
     * as an empty file. Parent directories are created automatically.
     *
     * @param   string[]  $entries  Paths relative to the temporary directory.
     *
     * @return  string  The absolute path to the temporary directory.
     */
    private function makeLangDir(array $entries = []): string
    {
        $base = sys_get_temp_dir() . '/awf_langdetect_' . uniqid();

        mkdir($base, 0777, true);

        $this->tempDirs[] = $base;

        foreach ($entries as $entry) {
            $path = $base . '/' . ltrim($entry, '/');

            if (str_ends_with($entry, '/')) {
                mkdir($path, 0777, true);

                continue;
            }

            $parent = dirname($path);

            if (!is_dir($parent)) {
                mkdir($parent, 0777, true);
            }

            file_put_contents($path, '');
        }

        return $base;
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }

        @rmdir($dir);
    }

    private function getAcceptedLanguages(Language $lang, ?string $header, ?string $default = null): array
    {
        $rm = new \ReflectionMethod(Language::class, 'getAcceptedLanguages');

        return $rm->invoke($lang, $header, $default);
    }

    private function getKnownLanguages(Language $lang, ?string $languagePath): array
    {
        $rm = new \ReflectionMethod(Language::class, 'getKnownLanguages');

        return $rm->invoke($lang, $languagePath);
    }

    private function findMostRelevantLanguage(Language $lang, array $accepted, array $known): ?string
    {
        $rm = new \ReflectionMethod(Language::class, 'findMostRelevantLanguage');

        return $rm->invoke($lang, $accepted, $known);
    }

    // -------------------------------------------------------------------------
    // getAcceptedLanguages — header parsing
    // -------------------------------------------------------------------------

    public static function acceptLanguageHeaderProvider(): array
    {
        // [ Accept-Language header, default language, expected result (order matters) ]
        return [
            'empty header returns the default language only'      => [
                '', 'en-GB', ['en-gb' => 1.0],
            ],
            'whitespace-only header returns the default language' => [
                "  \t ", 'en-GB', ['en-gb' => 1.0],
            ],
            'typical browser header'                              => [
                'fr-ch;q=0.3, da, en-us;q=0.8, en;q=0.5, fr;q=0.3', 'en-GB',
                [
                    'da'    => 1.0,
                    'en-us' => 0.8,
                    'en'    => 0.5,
                    'fr-ch' => 0.3,
                    'fr'    => 0.3,
                    'en-gb' => 0.001,
                ],
            ],
            'single language without a weight'                    => [
                'de', 'en-GB', ['de' => 1.0, 'en-gb' => 0.001],
            ],
            'header is normalised to lowercase'                   => [
                'EN-US;Q=0.8', 'en-GB', ['en-us' => 0.8, 'en-gb' => 0.001],
            ],
            'whitespace around items and weights is trimmed'      => [
                '  de ;  q=0.7 , fr ', 'en-GB', ['fr' => 1.0, 'de' => 0.7, 'en-gb' => 0.001],
            ],
            'empty items are skipped'                             => [
                'de,,fr', 'en-GB', ['de' => 1.0, 'fr' => 1.0, 'en-gb' => 0.001],
            ],
            'a repeated language keeps its last weight'           => [
                'de;q=0.2, de;q=0.9', 'en-GB', ['de' => 0.9, 'en-gb' => 0.001],
            ],
            'a malformed quality parameter means weight 1.0'      => [
                'de;foo=bar', 'en-GB', ['de' => 1.0, 'en-gb' => 0.001],
            ],
            'a non-numeric quality is dropped'                    => [
                'de;q=abc, fr;q=0.5', 'en-GB', ['fr' => 0.5, 'en-gb' => 0.001],
            ],
            'a quality above 1 is clamped to 1.0'                 => [
                'de;q=5', 'en-GB', ['de' => 1.0, 'en-gb' => 0.001],
            ],
            'a zero quality is dropped'                           => [
                'de;q=0, fr', 'en-GB', ['fr' => 1.0, 'en-gb' => 0.001],
            ],
            'a negative quality is dropped'                       => [
                'de;q=-1, fr', 'en-GB', ['fr' => 1.0, 'en-gb' => 0.001],
            ],
            'a quality below 0.001 is dropped'                    => [
                'de;q=0.0005, fr', 'en-GB', ['fr' => 1.0, 'en-gb' => 0.001],
            ],
            'a quality of exactly 0.001 is kept'                  => [
                'de;q=0.001', 'en-GB', ['de' => 0.001, 'en-gb' => 0.001],
            ],
            'dropping every language returns the default'         => [
                'de;q=0, fr;q=0', 'en-GB', ['en-gb' => 1.0],
            ],
            'a star is replaced with the default language'        => [
                '*;q=0.5', 'en-GB', ['en-gb' => 0.5],
            ],
            'a star does not override an explicit default'        => [
                'en-gb;q=0.5, *;q=0.9', 'en-GB', ['en-gb' => 0.5],
            ],
            'a star after another language'                       => [
                'fr;q=0.9, *;q=0.2', 'en-GB', ['fr' => 0.9, 'en-gb' => 0.2],
            ],
            'the default language keeps its explicit weight'      => [
                'en-gb;q=0.4', 'en-GB', ['en-gb' => 0.4],
            ],
            'the default language is lowercased'                  => [
                'fr;q=0.9', 'EL-GR', ['fr' => 0.9, 'el-gr' => 0.001],
            ],
        ];
    }

    #[DataProvider('acceptLanguageHeaderProvider')]
    public function testGetAcceptedLanguages(string $header, string $default, array $expected): void
    {
        $lang = $this->makeLanguage();

        // assertSame() on arrays is order-sensitive, which also asserts the weight-descending sort.
        self::assertSame($expected, $this->getAcceptedLanguages($lang, $header, $default));
    }

    public function testGetAcceptedLanguagesSortsByWeightDescending(): void
    {
        $lang   = $this->makeLanguage();
        $result = $this->getAcceptedLanguages($lang, 'de;q=0.2, fr;q=0.9, es;q=0.5', 'en-GB');

        self::assertSame(['fr', 'es', 'de', 'en-gb'], array_keys($result));
    }

    // -------------------------------------------------------------------------
    // getAcceptedLanguages — where the header and the default come from
    // -------------------------------------------------------------------------

    public function testGetAcceptedLanguagesReadsTheServerSuperglobal(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'el-gr;q=0.9, en;q=0.4';

        $lang = $this->makeLanguage();

        self::assertSame(
            ['el-gr' => 0.9, 'en' => 0.4, 'en-gb' => 0.001],
            $this->getAcceptedLanguages($lang, null, 'en-GB')
        );
    }

    public function testGetAcceptedLanguagesWithoutTheServerSuperglobalReturnsTheDefault(): void
    {
        // setUp() has already removed the superglobal.
        $lang = $this->makeLanguage();

        self::assertSame(['en-gb' => 1.0], $this->getAcceptedLanguages($lang, null, 'en-GB'));
    }

    public function testGetAcceptedLanguagesTakesTheDefaultFromTheApplicationConfiguration(): void
    {
        $lang = $this->makeLanguage('fr-FR');

        self::assertSame(['fr-fr' => 1.0], $this->getAcceptedLanguages($lang, ''));
    }

    public function testGetAcceptedLanguagesFallsBackToEnGbWhenTheConfiguredLanguageIsEmpty(): void
    {
        $lang = $this->makeLanguage('');

        self::assertSame(['en-gb' => 1.0], $this->getAcceptedLanguages($lang, ''));
    }

    public function testGetAcceptedLanguagesPrefersTheExplicitDefaultOverTheConfiguredOne(): void
    {
        $lang = $this->makeLanguage('fr-FR');

        self::assertSame(['de-de' => 1.0], $this->getAcceptedLanguages($lang, '', 'de-DE'));
    }

    // -------------------------------------------------------------------------
    // getKnownLanguages
    // -------------------------------------------------------------------------

    public function testGetKnownLanguagesReturnsIniFilesSortedAscending(): void
    {
        $dir  = $this->makeLangDir(['fr-FR.ini', 'en-GB.ini', 'de-DE.ini']);
        $lang = $this->makeLanguage();

        // asort() preserves the (directory order) keys, so we compare the values.
        self::assertSame(['de-DE', 'en-GB', 'fr-FR'], array_values($this->getKnownLanguages($lang, $dir)));
    }

    public function testGetKnownLanguagesIgnoresNonIniFiles(): void
    {
        $dir  = $this->makeLangDir(['en-GB.ini', 'README.md', 'notes.txt', '.htaccess', 'index.html']);
        $lang = $this->makeLanguage();

        self::assertSame(['en-GB'], array_values($this->getKnownLanguages($lang, $dir)));
    }

    public function testGetKnownLanguagesIgnoresDirectoriesNamedLikeIniFiles(): void
    {
        $dir  = $this->makeLangDir(['en-GB.ini', 'bogus.ini/']);
        $lang = $this->makeLanguage();

        self::assertSame(['en-GB'], array_values($this->getKnownLanguages($lang, $dir)));
    }

    public function testGetKnownLanguagesPrefersTheApplicationNameSubdirectory(): void
    {
        // The container's application_name is TestApp, hence the `testapp` subdirectory.
        $dir  = $this->makeLangDir(['en-GB.ini', 'testapp/el-GR.ini', 'testapp/fr-FR.ini']);
        $lang = $this->makeLanguage();

        self::assertSame(['el-GR', 'fr-FR'], array_values($this->getKnownLanguages($lang, $dir)));
    }

    public function testGetKnownLanguagesFallsBackToTheContainerLanguagePathWhenNullIsGiven(): void
    {
        $dir  = $this->makeLangDir(['el-GR.ini']);
        $lang = $this->makeLanguage('en-GB', $dir);

        self::assertSame(['el-GR'], array_values($this->getKnownLanguages($lang, null)));
    }

    public function testGetKnownLanguagesFallsBackToTheContainerLanguagePathWhenAnEmptyStringIsGiven(): void
    {
        $dir  = $this->makeLangDir(['el-GR.ini']);
        $lang = $this->makeLanguage('en-GB', $dir);

        self::assertSame(['el-GR'], array_values($this->getKnownLanguages($lang, '')));
    }

    public function testGetKnownLanguagesReturnsAnEmptyArrayForAnEmptyDirectory(): void
    {
        $dir  = $this->makeLangDir();
        $lang = $this->makeLanguage();

        self::assertSame([], $this->getKnownLanguages($lang, $dir));
    }

    public function testGetKnownLanguagesReturnsAnEmptyArrayForAMissingDirectory(): void
    {
        $lang = $this->makeLanguage();

        self::assertSame([], $this->getKnownLanguages($lang, sys_get_temp_dir() . '/awf_does_not_exist_' . uniqid()));
    }

    /**
     * The one-directory-per-language layout (`en-GB/en-GB.ini`) is NOT detected. This is the TODO noted in
     * getKnownLanguages(); the test pins the current behaviour so a future fix is a deliberate change.
     */
    public function testGetKnownLanguagesDoesNotDetectThePerLanguageSubdirectoryLayout(): void
    {
        $dir  = $this->makeLangDir(['en-GB/en-GB.ini', 'fr-FR/fr-FR.ini']);
        $lang = $this->makeLanguage();

        self::assertSame([], $this->getKnownLanguages($lang, $dir));
    }

    /**
     * A language file must be named `«full BCP 47 language code».ini`, e.g. `es-ES.ini`. An uppercase or mixed case
     * extension is not supported: the file IS matched by the extension check, but its extension is not stripped, so it
     * yields the nonsensical language code `es-ES.INI`.
     *
     * This test pins that garbage in produces garbage out — an unsupported file name does not quietly masquerade as a
     * usable language.
     */
    public function testGetKnownLanguagesOnlySupportsALowercaseIniExtension(): void
    {
        $dir  = $this->makeLangDir(['es-ES.INI']);
        $lang = $this->makeLanguage();

        self::assertSame(['es-ES.INI'], array_values($this->getKnownLanguages($lang, $dir)));
    }

    // -------------------------------------------------------------------------
    // findMostRelevantLanguage
    // -------------------------------------------------------------------------

    public function testFindMostRelevantLanguageWithNoAcceptedLanguagesReturnsNull(): void
    {
        $lang = $this->makeLanguage();

        self::assertNull($this->findMostRelevantLanguage($lang, [], ['en-GB', 'fr-FR']));
    }

    public function testFindMostRelevantLanguageWithNoKnownLanguagesReturnsNull(): void
    {
        $lang = $this->makeLanguage();

        self::assertNull($this->findMostRelevantLanguage($lang, ['en-gb' => 1.0], []));
    }

    public function testFindMostRelevantLanguageWithNothingAtAllReturnsNull(): void
    {
        $lang = $this->makeLanguage();

        self::assertNull($this->findMostRelevantLanguage($lang, [], []));
    }

    public function testFindMostRelevantLanguageReturnsNullWhenNothingMatches(): void
    {
        $lang = $this->makeLanguage();

        self::assertNull($this->findMostRelevantLanguage($lang, ['ja' => 1.0], ['en-GB', 'fr-FR']));
    }

    public function testFindMostRelevantLanguageReturnsTheKnownLanguageSpelling(): void
    {
        $lang = $this->makeLanguage();

        // The accepted languages are lowercase; the returned code must use the known language's own casing.
        self::assertSame(
            'el-GR',
            $this->findMostRelevantLanguage($lang, ['el-gr' => 1.0], ['el-GR', 'en-GB'])
        );
    }

    public function testFindMostRelevantLanguageMatchesTheFirstAcceptedLanguage(): void
    {
        $lang = $this->makeLanguage();

        self::assertSame(
            'fr-FR',
            $this->findMostRelevantLanguage($lang, ['fr-fr' => 0.9, 'en-gb' => 0.5], ['en-GB', 'fr-FR'])
        );
    }

    public function testFindMostRelevantLanguageSkipsUnknownLanguagesInPreferenceOrder(): void
    {
        $lang = $this->makeLanguage();

        self::assertSame(
            'fr-FR',
            $this->findMostRelevantLanguage($lang, ['ja' => 1.0, 'de' => 0.8, 'fr-fr' => 0.5], ['en-GB', 'fr-FR'])
        );
    }

    /**
     * The accepted languages are walked in array order. The method does not look at the weights, so an unsorted
     * array yields the first key that matches, not the heaviest one.
     */
    public function testFindMostRelevantLanguageUsesArrayOrderNotWeights(): void
    {
        $lang = $this->makeLanguage();

        self::assertSame(
            'fr-FR',
            $this->findMostRelevantLanguage($lang, ['fr-fr' => 0.1, 'en-gb' => 0.9], ['en-GB', 'fr-FR'])
        );
    }

    public function testFindMostRelevantLanguageMatchesAPrimarySubtagToARegionalLanguage(): void
    {
        $lang = $this->makeLanguage();

        // Accepted `el` matches known `el-GR`.
        self::assertSame('el-GR', $this->findMostRelevantLanguage($lang, ['el' => 1.0], ['el-GR', 'en-GB']));
    }

    public function testFindMostRelevantLanguageIsCaseInsensitiveOnTheAcceptedLanguages(): void
    {
        $lang = $this->makeLanguage();

        self::assertSame('el-GR', $this->findMostRelevantLanguage($lang, ['EL-GR' => 1.0], ['el-GR', 'en-GB']));
    }

    /**
     * A language range which carries a region is only satisfied by that exact language code. Per RFC 9110 §12.5.4 we
     * may only serve an arbitrary locale of a language when the client explicitly sent the region-less range.
     *
     * `Accept-Language: en-US,en;q=0.9` therefore means “en-US, or any English if you have no en-US”, and MUST return
     * en-US when we know about it — even though en-GB is the first English we know about.
     */
    public function testFindMostRelevantLanguagePrefersTheExactLocaleOverALowerRankedPrimarySubtag(): void
    {
        $lang = $this->makeLanguage();

        // Accept-Language: en-US,en;q=0.9
        self::assertSame(
            'en-US',
            $this->findMostRelevantLanguage($lang, ['en-us' => 1.0, 'en' => 0.9], ['en-GB', 'en-US'])
        );
    }

    /**
     * The counterpart: an unsatisfiable regional range must not be broadened into its own primary subtag. `en-us` does
     * not match the known en-GB, so the next accepted range wins.
     */
    public function testFindMostRelevantLanguageDoesNotBroadenAnUnsatisfiableRegionalRange(): void
    {
        $lang = $this->makeLanguage();

        // Accept-Language: en-us,fr-fr;q=0.9 — we know en-GB and fr-FR, so the French wins.
        self::assertSame(
            'fr-FR',
            $this->findMostRelevantLanguage($lang, ['en-us' => 1.0, 'fr-fr' => 0.9], ['en-GB', 'fr-FR'])
        );
    }

    /**
     * ...but if the client DID send the region-less range, it is honoured at its own place in the preference order. Any
     * English beats French here, and “any English” resolves to the first English we know about.
     */
    public function testFindMostRelevantLanguageHonoursAnExplicitPrimarySubtagInPreferenceOrder(): void
    {
        $lang = $this->makeLanguage();

        // Accept-Language: en-us,en;q=0.9,fr-fr;q=0.8 — en-US is unknown, but `en` outranks fr-FR and matches en-GB.
        self::assertSame(
            'en-GB',
            $this->findMostRelevantLanguage(
                $lang,
                ['en-us' => 1.0, 'en' => 0.9, 'fr-fr' => 0.8],
                ['en-GB', 'fr-FR']
            )
        );
    }

    /**
     * The region-less range is satisfied by the first known locale of that language, since $knownLanguages is sorted
     * ascending.
     */
    public function testFindMostRelevantLanguageSatisfiesThePrimarySubtagWithTheFirstKnownLocale(): void
    {
        $lang = $this->makeLanguage();

        // Accept-Language: en,en-US;q=0.9 — “any English” outranks en-US, and en-GB sorts first.
        self::assertSame(
            'en-GB',
            $this->findMostRelevantLanguage($lang, ['en' => 1.0, 'en-us' => 0.9], ['en-GB', 'en-US'])
        );
    }

    public function testFindMostRelevantLanguageReturnsTheExactLocaleWhenNoPrimarySubtagIsAccepted(): void
    {
        $lang = $this->makeLanguage();

        self::assertSame('en-US', $this->findMostRelevantLanguage($lang, ['en-us' => 1.0], ['en-GB', 'en-US']));
    }
}
