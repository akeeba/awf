<?php

/**
 * @package   awf
 * @copyright Copyright (c) 2024-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

/**
 * AWF Integration Test Harness
 * ==============================
 *
 * Integration tests live under tests/Integration/ and are intentionally SKIPPED
 * when the required environment variables are not set.  They exercise real
 * database connections and are meant to be run manually or in a dedicated CI
 * environment that provisions the required services.
 *
 * Required environment variables
 * --------------------------------
 *
 * MySQL / MariaDB
 *   AWF_TEST_MYSQL_DSN   PDO DSN,  e.g. "mysql:host=127.0.0.1;port=3306;dbname=awf_test"
 *   AWF_TEST_MYSQL_USER  Database username
 *   AWF_TEST_MYSQL_PASS  Database password (may be empty)
 *
 * PostgreSQL
 *   AWF_TEST_PG_DSN      PDO DSN,  e.g. "pgsql:host=127.0.0.1;port=5432;dbname=awf_test"
 *   AWF_TEST_PG_USER     Database username
 *   AWF_TEST_PG_PASS     Database password (may be empty)
 *
 * SQLite (on-disk, not :memory:)
 *   AWF_TEST_SQLITE_PATH Absolute path to a writable SQLite database file,
 *                        e.g. "/tmp/awf_integration.sqlite"
 *                        The file will be created if it does not exist.
 *
 * All variables are optional.  A test that requires a specific backend calls
 * the relevant requireMysql() / requirePostgresql() / requireSqlite() helper,
 * which skips the test immediately if the variable(s) are absent.
 *
 * Example usage in a concrete test class
 * ----------------------------------------
 *
 *   final class MyMysqlTest extends AbstractIntegrationTestCase
 *   {
 *       public function testSomething(): void
 *       {
 *           ['dsn' => $dsn, 'user' => $user, 'pass' => $pass] = $this->requireMysql();
 *           $pdo = new \PDO($dsn, $user, $pass);
 *           // ... assertions ...
 *       }
 *   }
 */

declare(strict_types=1);

namespace Awf\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Abstract base class for AWF integration tests.
 *
 * All integration tests MUST extend this class.  It provides helper methods
 * that read connection details from environment variables and call
 * markTestSkipped() when those variables are absent so that the tests are
 * completely silent in a standard CI environment without external services.
 */
abstract class AbstractIntegrationTestCase extends TestCase
{
    // -------------------------------------------------------------------------
    // Environment variable names
    // -------------------------------------------------------------------------

    /** Environment variable: MySQL/MariaDB PDO DSN */
    protected const ENV_MYSQL_DSN  = 'AWF_TEST_MYSQL_DSN';
    /** Environment variable: MySQL/MariaDB username */
    protected const ENV_MYSQL_USER = 'AWF_TEST_MYSQL_USER';
    /** Environment variable: MySQL/MariaDB password */
    protected const ENV_MYSQL_PASS = 'AWF_TEST_MYSQL_PASS';

    /** Environment variable: PostgreSQL PDO DSN */
    protected const ENV_PG_DSN  = 'AWF_TEST_PG_DSN';
    /** Environment variable: PostgreSQL username */
    protected const ENV_PG_USER = 'AWF_TEST_PG_USER';
    /** Environment variable: PostgreSQL password */
    protected const ENV_PG_PASS = 'AWF_TEST_PG_PASS';

    /** Environment variable: on-disk SQLite database file path */
    protected const ENV_SQLITE_PATH = 'AWF_TEST_SQLITE_PATH';

    // -------------------------------------------------------------------------
    // Skip helpers — call at the top of any test that needs a specific backend
    // -------------------------------------------------------------------------

    /**
     * Ensure MySQL/MariaDB connection details are present.
     *
     * @return array{dsn: string, user: string, pass: string}
     *   Ready-to-use connection parameters.
     *
     * @throws \PHPUnit\Framework\SkippedTestError
     *   When any required variable is absent.
     */
    protected function requireMysql(): array
    {
        $dsn  = (string) getenv(self::ENV_MYSQL_DSN);
        $user = (string) getenv(self::ENV_MYSQL_USER);

        if ($dsn === '' || $user === '') {
            $this->markTestSkipped(
                sprintf(
                    'MySQL integration tests require %s and %s to be set.',
                    self::ENV_MYSQL_DSN,
                    self::ENV_MYSQL_USER
                )
            );
        }

        return [
            'dsn'  => $dsn,
            'user' => $user,
            'pass' => (string) getenv(self::ENV_MYSQL_PASS),
        ];
    }

    /**
     * Ensure PostgreSQL connection details are present.
     *
     * @return array{dsn: string, user: string, pass: string}
     *   Ready-to-use connection parameters.
     *
     * @throws \PHPUnit\Framework\SkippedTestError
     *   When any required variable is absent.
     */
    protected function requirePostgresql(): array
    {
        $dsn  = (string) getenv(self::ENV_PG_DSN);
        $user = (string) getenv(self::ENV_PG_USER);

        if ($dsn === '' || $user === '') {
            $this->markTestSkipped(
                sprintf(
                    'PostgreSQL integration tests require %s and %s to be set.',
                    self::ENV_PG_DSN,
                    self::ENV_PG_USER
                )
            );
        }

        return [
            'dsn'  => $dsn,
            'user' => $user,
            'pass' => (string) getenv(self::ENV_PG_PASS),
        ];
    }

    /**
     * Ensure an on-disk SQLite file path is configured.
     *
     * Unlike the memory-based SQLite tests in the Unit suite, integration
     * tests may need a persistent, writable file to exercise schema
     * migrations or concurrent-connection behaviour.
     *
     * @return string Absolute path to the SQLite database file.
     *
     * @throws \PHPUnit\Framework\SkippedTestError
     *   When the required variable is absent.
     */
    protected function requireSqlite(): string
    {
        $path = (string) getenv(self::ENV_SQLITE_PATH);

        if ($path === '') {
            $this->markTestSkipped(
                sprintf(
                    'SQLite on-disk integration tests require %s to be set.',
                    self::ENV_SQLITE_PATH
                )
            );
        }

        return $path;
    }

    // -------------------------------------------------------------------------
    // Convenience helpers
    // -------------------------------------------------------------------------

    /**
     * Return the value of an env var, or skip the test if it is absent.
     *
     * Useful for backends not directly covered by the typed helpers above.
     *
     * @param string $varName   Environment variable name.
     * @param string $humanHint Short human-readable description used in the
     *                          skip message (e.g. "Redis DSN").
     *
     * @return string Non-empty value of the variable.
     *
     * @throws \PHPUnit\Framework\SkippedTestError When the variable is absent.
     */
    protected function requireEnv(string $varName, string $humanHint = ''): string
    {
        $value = (string) getenv($varName);

        if ($value === '') {
            $hint = $humanHint !== '' ? " ($humanHint)" : '';
            $this->markTestSkipped(
                sprintf('Integration test requires %s%s to be set.', $varName, $hint)
            );
        }

        return $value;
    }

    /**
     * Check whether ALL listed environment variables are set.
     *
     * Returns true only when every variable is non-empty; does NOT skip —
     * use this when you want conditional logic rather than an immediate skip.
     *
     * @param string ...$varNames One or more variable names.
     */
    protected function allEnvVarsPresent(string ...$varNames): bool
    {
        foreach ($varNames as $name) {
            if ((string) getenv($name) === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Check whether ANY listed environment variable is set.
     *
     * @param string ...$varNames One or more variable names.
     */
    protected function anyEnvVarPresent(string ...$varNames): bool
    {
        foreach ($varNames as $name) {
            if ((string) getenv($name) !== '') {
                return true;
            }
        }

        return false;
    }
}
