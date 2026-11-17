<?php

declare(strict_types=1);

namespace Awf\Tests\Unit\Database;

use Awf\Database\Driver;
use Awf\Database\Driver\Mysqli;
use Awf\Database\Driver\Pdomysql;
use Awf\Database\Driver\Postgresql;
use Awf\Database\Driver\Sqlite;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * How the drivers handle the table name prefix: substitution inside quoted identifiers, and prefixes that would break
 * out of a quoted identifier.
 *
 * No connection is made; none of the drivers connects in its constructor.
 */
#[CoversClass(Driver::class)]
#[CoversClass(Postgresql::class)]
class TablePrefixTest extends TestCase
{
    /**
     * @return array<string, array{class-string<Driver>, string, string}>
     *         Driver class, the database driver's 'driver' option, identifier quote character.
     */
    public static function driverProvider(): array
    {
        return [
            'mysqli'     => [Mysqli::class, 'mysqli', '`'],
            'pdomysql'   => [Pdomysql::class, 'pdomysql', '`'],
            'sqlite'     => [Sqlite::class, 'sqlite', '`'],
            'postgresql' => [Postgresql::class, 'postgresql', '"'],
        ];
    }

    /**
     * Every combination of driver and a prefix which contains an identifier quote character.
     *
     * @return array<string, array{class-string<Driver>, string, string}>
     */
    public static function badPrefixProvider(): array
    {
        $ret = [];

        foreach (self::driverProvider() as $name => [$class, $driver]) {
            $ret[$name . ' backtick']     = [$class, $driver, 'web`app_'];
            $ret[$name . ' double quote'] = [$class, $driver, 'web"app_'];
        }

        return $ret;
    }

    #[DataProvider('driverProvider')]
    public function testQuotedPlaceholderIsReplacedWithHyphenatedPrefix(string $class, string $driver, string $q): void
    {
        $db = $this->makeDriver($class, $driver, 'web-app_');

        $sql = $db->replacePrefix('SELECT * FROM ' . $db->qn('#__foo') . ' WHERE a = \'#__literal\'');

        self::assertSame("SELECT * FROM {$q}web-app_foo{$q} WHERE a = '#__literal'", $sql);
    }

    #[DataProvider('badPrefixProvider')]
    public function testPrefixWithIdentifierQuoteIsRejected(string $class, string $driver, string $prefix): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makeDriver($class, $driver, $prefix);
    }

    #[DataProvider('driverProvider')]
    public function testHyphenatedPrefixIsAccepted(string $class, string $driver): void
    {
        self::assertSame('web-app_', $this->makeDriver($class, $driver, 'web-app_')->getPrefix());
    }

    #[DataProvider('driverProvider')]
    public function testEmptyPrefixIsAccepted(string $class, string $driver): void
    {
        self::assertSame('', $this->makeDriver($class, $driver, '')->getPrefix());
    }

    /**
     * @return array<string, array{string, string}>  Query, expected result with the prefix "web-app_".
     */
    public static function postgresqlReplacePrefixProvider(): array
    {
        return [
            'placeholder at the very start'          => [
                '#__foo WHERE a = 1',
                'web-app_foo WHERE a = 1',
            ],
            'placeholder at the start, then literal' => [
                "#__foo WHERE a = '#__lit'",
                "web-app_foo WHERE a = '#__lit'",
            ],
            'query starts with a quote'              => [
                "'#__lit' = a AND b IN (SELECT id FROM #__bar)",
                "'#__lit' = a AND b IN (SELECT id FROM web-app_bar)",
            ],
            'segment after a literal starts with it' => [
                "SELECT 'a'#__foo",
                "SELECT 'a'web-app_foo",
            ],
            'currval at the very start'              => [
                "currval('#__seq') + 1 FROM #__foo",
                "currval('web-app_seq') + 1 FROM web-app_foo",
            ],
            'literal after currval is not rewritten' => [
                "SELECT currval('#__s') FROM #__t WHERE a = '#__lit'",
                "SELECT currval('web-app_s') FROM web-app_t WHERE a = '#__lit'",
            ],
            'keyword inside user data'               => [
                "INSERT INTO #__t (note) VALUES ('call nextval to get #__id')",
                "INSERT INTO web-app_t (note) VALUES ('call nextval to get #__id')",
            ],
            'literal after nextval is not rewritten' => [
                "SELECT nextval('#__s'), '#__a' FROM #__t",
                "SELECT nextval('web-app_s'), '#__a' FROM web-app_t",
            ],
            'setval with whitespace'                 => [
                "SELECT setval( '#__s' , 5) FROM #__t WHERE a = '#__x'",
                "SELECT setval( 'web-app_s' , 5) FROM web-app_t WHERE a = '#__x'",
            ],
            'keyword in literal, then a real call'   => [
                "SELECT 'nextval(' , nextval('#__s'), '#__x'",
                "SELECT 'nextval(' , nextval('web-app_s'), '#__x'",
            ],
            'literal that follows a keyword-literal' => [
                "SELECT 'nextval(''#__a'')', '#__b'",
                "SELECT 'nextval(''#__a'')', '#__b'",
            ],
            'doubled quote inside a literal'         => [
                "SELECT 'it''s #__x', a FROM #__t WHERE b = '#__y'",
                "SELECT 'it''s #__x', a FROM web-app_t WHERE b = '#__y'",
            ],
            'backslash-escaped quote in a literal'   => [
                "SELECT 'it\\'s #__x', a FROM #__t WHERE b = '#__y'",
                "SELECT 'it\\'s #__x', a FROM web-app_t WHERE b = '#__y'",
            ],
            'literal ending in an escaped backslash' => [
                "SELECT 'a\\\\' FROM #__t WHERE b = '#__y'",
                "SELECT 'a\\\\' FROM web-app_t WHERE b = '#__y'",
            ],
            'sequence name with an escaped quote'    => [
                "SELECT nextval('#__s\\'x') FROM #__t WHERE b = '#__y'",
                "SELECT nextval('web-app_s\\'x') FROM web-app_t WHERE b = '#__y'",
            ],
            'unterminated literal'                   => [
                "SELECT #__a, '#__b",
                "SELECT web-app_a, '#__b",
            ],
        ];
    }

    #[DataProvider('postgresqlReplacePrefixProvider')]
    public function testPostgresqlReplacePrefixHandlesMatchesAtPositionZero(string $query, string $expected): void
    {
        $db = $this->makeDriver(Postgresql::class, 'postgresql', 'web-app_');

        self::assertSame($expected, $db->replacePrefix($query));
    }

    /**
     * @param class-string<Driver> $class
     */
    private function makeDriver(string $class, string $driver, string $prefix): Driver
    {
        return new $class([
            'driver'   => $driver,
            'host'     => 'localhost',
            'database' => $driver === 'sqlite' ? ':memory:' : 'awf_test',
            'prefix'   => $prefix,
        ]);
    }
}
