<?php

/**
 * @package   awf
 * @copyright Copyright (c)2014-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU GPL version 3 or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration\Database;

use Awf\Database\Driver\Pgsql;
use Awf\Tests\Integration\AbstractIntegrationTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

/**
 * Real-server integration tests for the PDO PostgreSQL database driver's connection handling.
 *
 * Skipped unless AWF_TEST_PG_DSN / AWF_TEST_PG_USER (and optionally AWF_TEST_PG_PASS) are set.
 */
#[CoversClass(Pgsql::class)]
final class PgsqlDriverTest extends AbstractIntegrationTestCase
{
    protected function setUp(): void
    {
        if (!Pgsql::isSupported() || !in_array('pgsql', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The pdo_pgsql extension is not available.');
        }
    }

    /**
     * Connection options for the test server, optionally without some keys.
     *
     * @param   array  $override  Keys to set, or null to remove the key.
     */
    private function options(array $override = []): array
    {
        $conn = $this->requirePostgresql();
        $dsn  = (string) preg_replace('/^[a-z]+:/i', '', $conn['dsn']);

        $options = [
            'driver'   => 'pgsql',
            'host'     => '127.0.0.1',
            'port'     => 5432,
            'user'     => $conn['user'],
            'password' => $conn['pass'],
            'database' => '',
            'prefix'   => 'awf_',
            'select'   => true,
        ];

        foreach (preg_split('/[;\s]+/', $dsn, -1, PREG_SPLIT_NO_EMPTY) as $segment) {
            [$key, $val] = explode('=', $segment, 2) + ['', ''];

            if ($key === 'host') {
                $options['host'] = $val;
            } elseif ($key === 'port') {
                $options['port'] = (int) $val;
            } elseif ($key === 'dbname') {
                $options['database'] = $val;
            }
        }

        foreach ($override as $key => $value) {
            if ($value === null) {
                unset($options[$key]);
            } else {
                $options[$key] = $value;
            }
        }

        return $options;
    }

    /**
     * Run the callback with a warning-capturing error handler.
     *
     * @return  array{0: mixed, 1: string[], 2: ?\Throwable}  Result, captured warnings/notices, thrown exception
     */
    private function captureWarnings(callable $callback): array
    {
        $warnings  = [];
        $result    = null;
        $exception = null;

        set_error_handler(
            static function (int $errno, string $errstr) use (&$warnings): bool {
                $warnings[] = $errstr;

                return true;
            },
            E_ALL
        );

        try {
            $result = $callback();
        } catch (\Throwable $e) {
            $exception = $e;
        } finally {
            restore_error_handler();
        }

        return [$result, $warnings, $exception];
    }

    public function testConnectWithoutSelectOptionRaisesNoWarning(): void
    {
        $options = $this->options(['select' => null]);

        [$db, $warnings, $exception] = $this->captureWarnings(
            static fn() => new Pgsql($options)
        );

        self::assertNull($exception);
        self::assertSame([], $warnings);
        self::assertTrue($db->connected());
    }

    public function testConnectWithoutPortOptionRaisesNoWarning(): void
    {
        $options = $this->options(['port' => null]);

        [, $warnings, $exception] = $this->captureWarnings(
            static fn() => new Pgsql($options)
        );

        self::assertSame([], $warnings);

        // Nothing listens on the default port 5432 locally, so the connection may fail; it must not warn.
        if ($exception !== null) {
            self::assertInstanceOf(RuntimeException::class, $exception);
        }
    }
}
