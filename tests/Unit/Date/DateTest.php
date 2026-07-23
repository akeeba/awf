<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Date;

use Awf\Container\Container;
use Awf\Date\Date;
use Awf\Text\Language;
use DateTime;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Awf\Date\Date.
 *
 * All tests pin to a fixed reference timestamp so they are deterministic
 * regardless of when they run.
 *
 * Reference: 2023-06-15 12:34:56 UTC (Thursday, week 24, day-of-year 165)
 */
class DateTest extends TestCase
{
    /** Unix timestamp for the reference date: 2023-06-15 12:34:56 UTC */
    private const REF_STAMP = 1686832496;

    /** ISO-8601 string equivalent of the reference date */
    private const REF_ISO = '2023-06-15T12:34:56+00:00';

    private Container $container;

    // -------------------------------------------------------------------------
    // Set-up / tear-down
    // -------------------------------------------------------------------------

    protected function setUp(): void
    {
        // Build a minimal Container.  The Date class only needs the `language`
        // service (for day/month name translation), so we register a real
        // Language object backed by no actual INI files — it will return the
        // key unchanged for every text() call, which is fine for our tests.
        $this->container = $this->makeContainer();
    }

    protected function tearDown(): void
    {
        // Restore the static GMT/stz state between tests by resetting the
        // class statics via new construction — there is no public reset API,
        // but the constructor recreates them when they are empty.  Because
        // they are never actually null after the first construction, we only
        // need to ensure the default timezone is left in a clean state.
        date_default_timezone_set('UTC');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeContainer(): Container
    {
        $dataDir = sys_get_temp_dir();

        // Minimal set of constructor keys the Container needs to boot without
        // throwing on missing paths.  We only exercise Date functionality so
        // many services are never accessed.
        $container = new Container([
            'application_name'     => 'TestApp',
            'applicationNamespace' => '\\TestApp',
            'session_segment_name' => 'testapp_seg',
            'basePath'             => $dataDir,
            'languagePath'         => $dataDir,
            'temporaryPath'        => $dataDir,
            'templatePath'         => $dataDir,
            'sqlPath'              => $dataDir,
            'filesystemBase'       => $dataDir,
        ]);

        // Replace the lazy language service with a simple stub that returns
        // the raw key back, simulating "no translation loaded".
        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static fn(string $key) => $key);
        $container['language'] = $language;

        return $container;
    }

    /**
     * Create a Date object for the fixed reference timestamp in UTC.
     */
    private function makeRefDate(?string $tz = null): Date
    {
        return new Date((string) self::REF_STAMP, $tz, $this->container);
    }

    // -------------------------------------------------------------------------
    // Construction — from string, from timestamp, from DateTimeZone
    // -------------------------------------------------------------------------

    public function testConstructFromUnixTimestamp(): void
    {
        $date = $this->makeRefDate();
        self::assertSame(self::REF_STAMP, $date->toUnix());
    }

    public function testConstructFromIsoString(): void
    {
        $date = new Date(self::REF_ISO, 'UTC', $this->container);
        self::assertSame(self::REF_STAMP, $date->toUnix());
    }

    public function testConstructFromNow(): void
    {
        $before = time();
        $date   = new Date('now', 'UTC', $this->container);
        $after  = time();

        // The created date's unix timestamp must be within the window.
        self::assertGreaterThanOrEqual($before, $date->toUnix());
        self::assertLessThanOrEqual($after, $date->toUnix());
    }

    public function testConstructWithStringTimezone(): void
    {
        $date = new Date(self::REF_ISO, 'America/New_York', $this->container);
        // Same moment in time — unix timestamps are timezone-agnostic.
        self::assertSame(self::REF_STAMP, $date->toUnix());
    }

    public function testConstructWithDateTimeZoneObject(): void
    {
        $tz   = new DateTimeZone('Europe/Athens');
        $date = new Date(self::REF_ISO, $tz, $this->container);
        self::assertSame(self::REF_STAMP, $date->toUnix());
    }

    public function testConstructNullTimezoneDefaultsToGmt(): void
    {
        // When the date string carries +00:00, PHP's DateTime parser stores
        // the offset as '+00:00' rather than the named 'GMT' zone, even if
        // we passed a GMT DateTimeZone as the constructor hint.
        $date = new Date(self::REF_ISO, null, $this->container);
        // UTC is represented either as 'GMT' or '+00:00' depending on input.
        $tzName = $date->getTimezone()->getName();
        self::assertTrue(
            $tzName === 'GMT' || $tzName === '+00:00' || $tzName === 'UTC',
            "Expected a UTC-equivalent timezone, got: $tzName"
        );
    }

    // -------------------------------------------------------------------------
    // __toString
    // -------------------------------------------------------------------------

    public function testToStringUsesStaticFormat(): void
    {
        $orig = Date::$format;
        Date::$format = 'Y-m-d H:i:s';

        $date   = $this->makeRefDate();
        $result = (string) $date;

        Date::$format = $orig;

        self::assertSame('2023-06-15 12:34:56', $result);
    }

    public function testToStringRespectsCustomStaticFormat(): void
    {
        $orig = Date::$format;
        Date::$format = 'Y/m/d';

        $date   = $this->makeRefDate();
        $result = (string) $date;

        Date::$format = $orig;

        self::assertSame('2023/06/15', $result);
    }

    // -------------------------------------------------------------------------
    // format()
    // -------------------------------------------------------------------------

    public function testFormatGmtReturnsUtcValues(): void
    {
        $date = $this->makeRefDate();
        // format($f, local=false) must give UTC values.
        self::assertSame('2023-06-15', $date->format('Y-m-d'));
        self::assertSame('12:34:56', $date->format('H:i:s'));
    }

    public function testFormatLocalReturnsTimezoneAdjustedValues(): void
    {
        // Use a plain (non-offset) datetime string so that the given timezone
        // is interpreted as the LOCAL time.  Athens in summer is UTC+3, so
        // 12:34:56 EEST = 09:34:56 UTC.  The local format should return
        // the original 12:34:56.
        $date = new Date('2023-06-15 12:34:56', 'Europe/Athens', $this->container);
        self::assertSame('12:34:56', $date->format('H:i:s', true));
        // UTC view should show 09:34:56
        self::assertSame('09:34:56', $date->format('H:i:s', false));
    }

    public function testFormatDoesNotTranslateWhenTranslateFalse(): void
    {
        $date   = $this->makeRefDate();
        $result = $date->format('l', false, false);
        // PHP's native 'l' for 2023-06-15 (Thursday) — no translation applied.
        self::assertSame('Thursday', $result);
    }

    public function testFormatDefaultDoesNotTranslate(): void
    {
        // Translation is opt-in: without an explicit $translate argument the
        // day/month name tokens are emitted verbatim (English), matching native
        // DateTime::format(). This is what keeps machine formats (e.g. DATE_RSS)
        // parseable. A language that actually translates must NOT be consulted.
        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static function (string $key): string {
            return match ($key) {
                'Thursday' => 'Donnerstag',
                'June'     => 'Juni',
                default    => $key,
            };
        });
        $this->container['language'] = $language;

        $date = $this->makeRefDate();

        // No third argument → default (false) → English, untranslated.
        self::assertSame('Thursday', $date->format('l'));
        self::assertSame('June', $date->format('F'));
        // DATE_RSS must round-trip: no localised tokens leak into the output.
        self::assertSame('Thu, 15 Jun 2023 12:34:56 +0000', $date->format(DATE_RSS));
    }

    public function testFormatTranslateDayAbbr(): void
    {
        // Language stub returns keys unchanged, so abbreviated day will be the
        // English abbreviation that PHP would normally produce.
        $date   = $this->makeRefDate();
        $result = $date->format('D', false, true);
        // 2023-06-15 is Thursday → 'Thu'
        self::assertSame('Thu', $result);
    }

    public function testFormatTranslateDayName(): void
    {
        $date   = $this->makeRefDate();
        $result = $date->format('l', false, true);
        self::assertSame('Thursday', $result);
    }

    public function testFormatTranslateMonthAbbr(): void
    {
        $date   = $this->makeRefDate();
        $result = $date->format('M', false, true);
        // June abbreviated → 'Jun'
        self::assertSame('Jun', $result);
    }

    public function testFormatTranslateMonthName(): void
    {
        $date   = $this->makeRefDate();
        $result = $date->format('F', false, true);
        // June full name
        self::assertSame('June', $result);
    }

    public function testFormatTranslationReturnsTranslatedValueWhenDifferent(): void
    {
        // Replace the language stub with one that actually translates.
        $language = $this->createMock(Language::class);
        $language->method('text')->willReturnCallback(static function (string $key): string {
            return match ($key) {
                'Thursday' => 'Donnerstag',
                default    => $key,
            };
        });

        $this->container['language'] = $language;

        $date   = $this->makeRefDate();
        $result = $date->format('l', false, true);
        self::assertSame('Donnerstag', $result);
    }

    // -------------------------------------------------------------------------
    // Magic property access
    // -------------------------------------------------------------------------

    public static function magicPropertyProvider(): array
    {
        return [
            // [property, expected value] for 2023-06-15 12:34:56 UTC
            'daysinmonth'  => ['daysinmonth', '30'],      // June has 30 days
            'dayofweek'    => ['dayofweek', '4'],          // ISO-8601: Thursday = 4
            'dayofyear'    => ['dayofyear', '165'],        // 0-indexed
            'isleapyear'   => ['isleapyear', false],       // 2023 is not a leap year
            'day'          => ['day', '15'],
            'hour'         => ['hour', '12'],
            'minute'       => ['minute', '34'],
            'second'       => ['second', '56'],
            'month'        => ['month', '06'],
            'ordinal'      => ['ordinal', 'th'],            // 15th
            'week'         => ['week', '24'],
            'year'         => ['year', '2023'],
        ];
    }

    #[DataProvider('magicPropertyProvider')]
    public function testMagicPropertyAccess(string $property, mixed $expected): void
    {
        $date  = $this->makeRefDate();
        $value = $date->$property;
        self::assertSame($expected, $value);
    }

    public function testMagicPropertyUndefinedTriggersNotice(): void
    {
        $date = $this->makeRefDate();

        $triggered = false;
        set_error_handler(static function () use (&$triggered): bool {
            $triggered = true;
            return true;
        }, E_USER_NOTICE);

        $value = $date->nonExistentProperty;

        restore_error_handler();

        self::assertTrue($triggered, 'Expected E_USER_NOTICE for undefined magic property');
        self::assertNull($value);
    }

    // -------------------------------------------------------------------------
    // getOffsetFromGMT
    // -------------------------------------------------------------------------

    public function testGetOffsetFromGmtSeconds(): void
    {
        $date = $this->makeRefDate('UTC');
        self::assertSame(0.0, $date->getOffsetFromGMT(false));
    }

    public function testGetOffsetFromGmtHours(): void
    {
        $date = $this->makeRefDate('UTC');
        self::assertSame(0.0, $date->getOffsetFromGMT(true));
    }

    public function testGetOffsetFromGmtPositiveOffset(): void
    {
        // Asia/Kolkata is UTC+5:30 (19800 seconds)
        $date = new Date(self::REF_ISO, 'Asia/Kolkata', $this->container);
        self::assertSame(19800.0, $date->getOffsetFromGMT(false));
        // 5.5 hours
        self::assertSame(5.5, $date->getOffsetFromGMT(true));
    }

    public function testGetOffsetFromGmtNegativeOffset(): void
    {
        // America/New_York in summer: UTC-4 (-14400 seconds)
        $date = new Date(self::REF_ISO, 'America/New_York', $this->container);
        self::assertSame(-14400.0, $date->getOffsetFromGMT(false));
        self::assertSame(-4.0, $date->getOffsetFromGMT(true));
    }

    // -------------------------------------------------------------------------
    // setTimezone
    // -------------------------------------------------------------------------

    public function testSetTimezoneUpdatesInternalTz(): void
    {
        $date  = $this->makeRefDate('UTC');
        $newTz = new DateTimeZone('Europe/Paris');
        $date->setTimezone($newTz);

        self::assertSame('Europe/Paris', $date->getTimezone()->getName());
    }

    public function testSetTimezoneAffectsLocalFormat(): void
    {
        $date  = $this->makeRefDate('UTC');
        $newTz = new DateTimeZone('Asia/Tokyo'); // UTC+9
        $date->setTimezone($newTz);

        // Local time should be 21:34:56 (+9 hours).
        self::assertSame('21:34:56', $date->format('H:i:s', true));
    }

    // -------------------------------------------------------------------------
    // toUnix
    // -------------------------------------------------------------------------

    public function testToUnix(): void
    {
        $date = $this->makeRefDate();
        self::assertSame(self::REF_STAMP, $date->toUnix());
    }

    public function testToUnixReturnsInt(): void
    {
        $date = $this->makeRefDate();
        self::assertIsInt($date->toUnix());
    }

    // -------------------------------------------------------------------------
    // toISO8601 / toISO8601_WrongPHP
    // -------------------------------------------------------------------------

    public function testToISO8601GmtDefaultsToUtc(): void
    {
        $date   = $this->makeRefDate();
        $result = $date->toISO8601();
        self::assertSame('2023-06-15T12:34:56+00:00', $result);
    }

    public function testToISO8601LocalWithOffset(): void
    {
        // Use a plain datetime string (no UTC offset suffix) with a timezone.
        // Athens in summer is UTC+3, so 12:34:56 local = 09:34:56 UTC.
        $date   = new Date('2023-06-15 12:34:56', 'Europe/Athens', $this->container);
        $result = $date->toISO8601(true);
        self::assertSame('2023-06-15T12:34:56+03:00', $result);
    }

    public function testToISO8601WrongPhpGmt(): void
    {
        $date   = $this->makeRefDate();
        $result = $date->toISO8601_WrongPHP();
        // Format Y-m-d\TH:i:sO with UTC offset +0000
        self::assertSame('2023-06-15T12:34:56+0000', $result);
    }

    // -------------------------------------------------------------------------
    // toRFC822 / toRFC2822 / toAtom / toRSS / toW3C
    // -------------------------------------------------------------------------

    public function testToRfc822Gmt(): void
    {
        $date   = $this->makeRefDate();
        $result = $date->toRFC822();
        // RFC 2822 format: Thu, 15 Jun 2023 12:34:56 +0000
        self::assertSame('Thu, 15 Jun 2023 12:34:56 +0000', $result);
    }

    public function testToRfc2822MatchesRfc822(): void
    {
        $date = $this->makeRefDate();
        self::assertSame($date->toRFC822(), $date->toRFC2822());
    }

    public function testToAtomGmt(): void
    {
        $date   = $this->makeRefDate();
        $result = $date->toAtom();
        // Atom = RFC 3339 = same as ISO8601 with +00:00
        self::assertSame('2023-06-15T12:34:56+00:00', $result);
    }

    public function testToRssGmt(): void
    {
        $date   = $this->makeRefDate();
        $result = $date->toRSS();
        // RSS uses RFC 2822
        self::assertSame('Thu, 15 Jun 2023 12:34:56 +0000', $result);
    }

    public function testToW3CGmt(): void
    {
        $date   = $this->makeRefDate();
        $result = $date->toW3C();
        self::assertSame('2023-06-15T12:34:56+00:00', $result);
    }

    // -------------------------------------------------------------------------
    // Additional RFC variants
    // -------------------------------------------------------------------------

    public function testToRfc3339MatchesIso8601(): void
    {
        $date = $this->makeRefDate();
        self::assertSame($date->toISO8601(), $date->toRFC3339());
    }

    public function testToRfc3339ExtendedContainsMilliseconds(): void
    {
        $date   = $this->makeRefDate();
        $result = $date->toRFC3339Extended();
        // RFC3339_EXTENDED includes millisecond fraction: 2023-06-15T12:34:56.000+00:00
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}[+-]\d{2}:\d{2}$/', $result);
    }

    public function testToRfc850Gmt(): void
    {
        $date   = $this->makeRefDate();
        $result = $date->toRFC850();
        // RFC 850: Thursday, 15-Jun-23 12:34:56 GMT
        self::assertMatchesRegularExpression('/Thursday, 15-Jun-23 12:34:56 GMT/', $result);
    }

    public function testToRfc1036Gmt(): void
    {
        $date   = $this->makeRefDate();
        $result = $date->toRFC1036();
        // RFC 1036: Thu, 15 Jun 23 12:34:56 +0000
        self::assertMatchesRegularExpression('/Thu, 15 Jun 23 12:34:56 \+0000/', $result);
    }

    public function testToRfc1123Gmt(): void
    {
        $date   = $this->makeRefDate();
        $result = $date->toRFC1123();
        // RFC 1123: PHP formats the GMT offset as '+0000' when the timezone
        // stored is the numeric offset '+00:00' (from parsing the ISO input)
        // rather than the named zone 'GMT'.
        self::assertMatchesRegularExpression('/Thu, 15 Jun 2023 12:34:56 (GMT|\+0000)/', $result);
    }

    public function testToCookieGmt(): void
    {
        $date   = $this->makeRefDate();
        $result = $date->toCookie();
        // Cookie format: Thursday, 15-Jun-2023 12:34:56 GMT
        self::assertMatchesRegularExpression('/Thursday, 15-Jun-2023 12:34:56 GMT/', $result);
    }

    // -------------------------------------------------------------------------
    // toISO8601Expanded
    // -------------------------------------------------------------------------

    public function testToISO8601ExpandedReturnsValidIsoString(): void
    {
        $date   = $this->makeRefDate();
        $result = $date->toISO8601Expanded();
        // Should be a valid ISO-8601 string regardless of PHP version.
        self::assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $result);
    }

    // -------------------------------------------------------------------------
    // Edge cases — leap year, month boundary, etc.
    // -------------------------------------------------------------------------

    public function testLeapYearDetection(): void
    {
        // 2024 is a leap year; 2023 is not.
        $leapDate    = new Date('2024-02-29 00:00:00', 'UTC', $this->container);
        $nonLeapDate = new Date('2023-01-01 00:00:00', 'UTC', $this->container);

        self::assertTrue($leapDate->isleapyear);
        self::assertFalse($nonLeapDate->isleapyear);
    }

    public function testDayOfYearAtYearStart(): void
    {
        $date = new Date('2023-01-01 00:00:00', 'UTC', $this->container);
        self::assertSame('0', $date->dayofyear); // 0-indexed
    }

    public function testDayOfYearAtYearEnd(): void
    {
        $date = new Date('2023-12-31 00:00:00', 'UTC', $this->container);
        self::assertSame('364', $date->dayofyear); // 0-indexed, non-leap year
    }

    public function testMonthPropertyJanuary(): void
    {
        $date = new Date('2023-01-01 00:00:00', 'UTC', $this->container);
        self::assertSame('01', $date->month);
    }

    public function testDaysinmonthForFebruary(): void
    {
        // February 2023 has 28 days (non-leap year)
        $date = new Date('2023-02-01 00:00:00', 'UTC', $this->container);
        self::assertSame('28', $date->daysinmonth);
    }

    public function testDaysinmonthForFebruaryLeapYear(): void
    {
        // February 2024 has 29 days (leap year)
        $date = new Date('2024-02-01 00:00:00', 'UTC', $this->container);
        self::assertSame('29', $date->daysinmonth);
    }

    public function testOrdinalSuffix(): void
    {
        $cases = [
            ['2023-06-01 00:00:00', 'st'],  // 1st
            ['2023-06-02 00:00:00', 'nd'],  // 2nd
            ['2023-06-03 00:00:00', 'rd'],  // 3rd
            ['2023-06-04 00:00:00', 'th'],  // 4th
            ['2023-06-11 00:00:00', 'th'],  // 11th
            ['2023-06-12 00:00:00', 'th'],  // 12th
            ['2023-06-21 00:00:00', 'st'],  // 21st
            ['2023-06-22 00:00:00', 'nd'],  // 22nd
            ['2023-06-23 00:00:00', 'rd'],  // 23rd
        ];

        foreach ($cases as [$dateStr, $expected]) {
            $date = new Date($dateStr, 'UTC', $this->container);
            self::assertSame($expected, $date->ordinal, "Ordinal for $dateStr");
        }
    }

    // -------------------------------------------------------------------------
    // Month name translations
    // -------------------------------------------------------------------------

    public static function monthNamesProvider(): array
    {
        // [month number 1-12, expected full name, expected abbreviation]
        return [
            'January'   => [1, 'January',   'Jan'],
            'February'  => [2, 'February',  'Feb'],
            'March'     => [3, 'March',      'Mar'],
            'April'     => [4, 'April',      'Apr'],
            'May'       => [5, 'May',        'May'],
            'June'      => [6, 'June',       'Jun'],
            'July'      => [7, 'July',       'Jul'],
            'August'    => [8, 'August',     'Aug'],
            'September' => [9, 'September',  'Sep'],
            'October'   => [10, 'October',   'Oct'],
            'November'  => [11, 'November',  'Nov'],
            'December'  => [12, 'December',  'Dec'],
        ];
    }

    #[DataProvider('monthNamesProvider')]
    public function testMonthNames(int $month, string $expectedFull, string $expectedAbbr): void
    {
        $monthStr = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
        $date     = new Date("2023-{$monthStr}-01 00:00:00", 'UTC', $this->container);

        self::assertSame($expectedFull, $date->format('F', false, true));
        self::assertSame($expectedAbbr, $date->format('M', false, true));
    }

    // -------------------------------------------------------------------------
    // Day name translations
    // -------------------------------------------------------------------------

    public static function dayNamesProvider(): array
    {
        // 2023-06-11 (Sunday) through 2023-06-17 (Saturday)
        return [
            'Sunday'    => ['2023-06-11', 'Sunday',    'Sun'],
            'Monday'    => ['2023-06-12', 'Monday',    'Mon'],
            'Tuesday'   => ['2023-06-13', 'Tuesday',   'Tue'],
            'Wednesday' => ['2023-06-14', 'Wednesday', 'Wed'],
            'Thursday'  => ['2023-06-15', 'Thursday',  'Thu'],
            'Friday'    => ['2023-06-16', 'Friday',    'Fri'],
            'Saturday'  => ['2023-06-17', 'Saturday',  'Sat'],
        ];
    }

    #[DataProvider('dayNamesProvider')]
    public function testDayNames(string $dateStr, string $expectedFull, string $expectedAbbr): void
    {
        $date = new Date("{$dateStr} 00:00:00", 'UTC', $this->container);

        self::assertSame($expectedFull, $date->format('l', false, true));
        self::assertSame($expectedAbbr, $date->format('D', false, true));
    }

    // -------------------------------------------------------------------------
    // Deprecated constructor warning when container is omitted
    // -------------------------------------------------------------------------

    public function testConstructorDeprecationWhenContainerOmitted(): void
    {
        // When no container is passed the constructor triggers E_USER_DEPRECATED.
        // We capture it and ensure the correct warning is fired.
        // We cannot fully test this without a live Application instance, so we
        // just verify that the warning IS triggered.
        $triggered = false;

        set_error_handler(static function (int $errno, string $errstr) use (&$triggered): bool {
            if ($errno === E_USER_DEPRECATED) {
                $triggered = true;
            }

            // Return true so PHP doesn't propagate the error further.
            return true;
        }, E_USER_DEPRECATED | E_ALL);

        try {
            // This will trigger the deprecation and then try to call
            // Application::getInstance() which will throw — we only care that
            // the deprecation was triggered first.
            new Date('now', 'UTC');
        } catch (\Throwable $e) {
            // Expected — Application is not bootstrapped in tests.
        } finally {
            restore_error_handler();
        }

        self::assertTrue($triggered, 'Expected E_USER_DEPRECATED when container is not provided');
    }
}
