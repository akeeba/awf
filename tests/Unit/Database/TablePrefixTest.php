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
