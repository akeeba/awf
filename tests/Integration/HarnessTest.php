<?php

/**
 * @package   awf
 * @copyright Copyright (c) 2024-2025 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

declare(strict_types=1);

namespace Awf\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Smoke-tests for the integration test harness itself.
 *
 * These tests exercise AbstractIntegrationTestCase in isolation — no real
 * database connection is needed.  The class under test is the harness, not
 * any AWF production code.
 *
 * Tests that invoke requireMysql() / requirePostgresql() / requireSqlite()
 * are expected to be SKIPPED on machines without the env vars set; that is
 * intentional and is in fact the behaviour being verified.
 */
#[CoversClass(AbstractIntegrationTestCase::class)]
final class HarnessTest extends AbstractIntegrationTestCase
{
    // -------------------------------------------------------------------------
    // allEnvVarsPresent() — no I/O, purely logic
    // -------------------------------------------------------------------------

    public function testAllEnvVarsPresentReturnsTrueWhenVarsAreSet(): void
    {
        // We can safely use any real env var that is guaranteed to exist in
        // every Unix/Windows process.
        self::assertTrue($this->allEnvVarsPresent('PATH'));
    }

    public function testAllEnvVarsPresentReturnsFalseWhenOneVarIsMissing(): void
    {
        // AWF_DOES_NOT_EXIST__SENTINEL is intentionally not set anywhere.
        self::assertFalse($this->allEnvVarsPresent('PATH', 'AWF_DOES_NOT_EXIST__SENTINEL'));
    }

    public function testAllEnvVarsPresentReturnsFalseWhenAllVarsMissing(): void
    {
        self::assertFalse($this->allEnvVarsPresent('AWF_DOES_NOT_EXIST__SENTINEL_A', 'AWF_DOES_NOT_EXIST__SENTINEL_B'));
    }

    public function testAllEnvVarsPresentReturnsTrueForEmptyArgList(): void
    {
        // Vacuous truth: no variables to check → all are present.
        self::assertTrue($this->allEnvVarsPresent());
    }

    // -------------------------------------------------------------------------
    // anyEnvVarPresent() — no I/O, purely logic
    // -------------------------------------------------------------------------

    public function testAnyEnvVarPresentReturnsTrueWhenAtLeastOneVarIsSet(): void
    {
        self::assertTrue($this->anyEnvVarPresent('AWF_DOES_NOT_EXIST__SENTINEL', 'PATH'));
    }

    public function testAnyEnvVarPresentReturnsFalseWhenAllVarsMissing(): void
    {
        self::assertFalse($this->anyEnvVarPresent('AWF_DOES_NOT_EXIST__SENTINEL_A', 'AWF_DOES_NOT_EXIST__SENTINEL_B'));
    }

    public function testAnyEnvVarPresentReturnsFalseForEmptyArgList(): void
    {
        // No variables supplied → none are present.
        self::assertFalse($this->anyEnvVarPresent());
    }

    // -------------------------------------------------------------------------
    // requireEnv() — skips when var is absent, returns value when present
    // -------------------------------------------------------------------------

    public function testRequireEnvReturnsValueWhenVarIsSet(): void
    {
        // PATH is always non-empty on any supported platform.
        $value = $this->requireEnv('PATH', 'system PATH');
        self::assertNotEmpty($value);
    }

    public function testRequireEnvSkipsWhenVarIsAbsent(): void
    {
        // We deliberately trigger the skip path and catch it so the outer
        // test can assert that the correct exception type was thrown.
        $skipped = false;

        try {
            $this->requireEnv('AWF_DOES_NOT_EXIST__SENTINEL', 'sentinel variable');
        } catch (\PHPUnit\Framework\SkippedWithMessageException $e) {
            $skipped = true;
            self::assertStringContainsString('AWF_DOES_NOT_EXIST__SENTINEL', $e->getMessage());
            self::assertStringContainsString('sentinel variable', $e->getMessage());
        }

        self::assertTrue($skipped, 'requireEnv() must throw SkippedWithMessageException when the variable is absent.');
    }

    // -------------------------------------------------------------------------
    // requireMysql() — skips when AWF_TEST_MYSQL_DSN / USER are absent
    // -------------------------------------------------------------------------

    public function testRequireMysqlSkipsWhenEnvVarsAbsent(): void
    {
        if ($this->allEnvVarsPresent(self::ENV_MYSQL_DSN, self::ENV_MYSQL_USER)) {
            $this->markTestSkipped('MySQL env vars are set; skip-path cannot be tested on this machine.');
        }

        $skipped = false;

        try {
            $this->requireMysql();
        } catch (\PHPUnit\Framework\SkippedWithMessageException $e) {
            $skipped = true;
            self::assertStringContainsString(self::ENV_MYSQL_DSN, $e->getMessage());
        }

        self::assertTrue($skipped, 'requireMysql() must throw SkippedWithMessageException when env vars are absent.');
    }

    public function testRequireMysqlReturnsConnectionArrayWhenEnvVarsPresent(): void
    {
        if (!$this->allEnvVarsPresent(self::ENV_MYSQL_DSN, self::ENV_MYSQL_USER)) {
            $this->markTestSkipped('AWF_TEST_MYSQL_DSN and AWF_TEST_MYSQL_USER are not set.');
        }

        $conn = $this->requireMysql();

        self::assertArrayHasKey('dsn', $conn);
        self::assertArrayHasKey('user', $conn);
        self::assertArrayHasKey('pass', $conn);
        self::assertNotEmpty($conn['dsn']);
        self::assertNotEmpty($conn['user']);
    }

    // -------------------------------------------------------------------------
    // requirePostgresql() — skips when AWF_TEST_PG_DSN / USER are absent
    // -------------------------------------------------------------------------

    public function testRequirePostgresqlSkipsWhenEnvVarsAbsent(): void
    {
        if ($this->allEnvVarsPresent(self::ENV_PG_DSN, self::ENV_PG_USER)) {
            $this->markTestSkipped('PostgreSQL env vars are set; skip-path cannot be tested on this machine.');
        }

        $skipped = false;

        try {
            $this->requirePostgresql();
        } catch (\PHPUnit\Framework\SkippedWithMessageException $e) {
            $skipped = true;
            self::assertStringContainsString(self::ENV_PG_DSN, $e->getMessage());
        }

        self::assertTrue($skipped, 'requirePostgresql() must throw SkippedWithMessageException when env vars are absent.');
    }

    public function testRequirePostgresqlReturnsConnectionArrayWhenEnvVarsPresent(): void
    {
        if (!$this->allEnvVarsPresent(self::ENV_PG_DSN, self::ENV_PG_USER)) {
            $this->markTestSkipped('AWF_TEST_PG_DSN and AWF_TEST_PG_USER are not set.');
        }

        $conn = $this->requirePostgresql();

        self::assertArrayHasKey('dsn', $conn);
        self::assertArrayHasKey('user', $conn);
        self::assertArrayHasKey('pass', $conn);
        self::assertNotEmpty($conn['dsn']);
        self::assertNotEmpty($conn['user']);
    }

    // -------------------------------------------------------------------------
    // requireSqlite() — skips when AWF_TEST_SQLITE_PATH is absent
    // -------------------------------------------------------------------------

    public function testRequireSqliteSkipsWhenEnvVarAbsent(): void
    {
        if ($this->anyEnvVarPresent(self::ENV_SQLITE_PATH)) {
            $this->markTestSkipped('AWF_TEST_SQLITE_PATH is set; skip-path cannot be tested on this machine.');
        }

        $skipped = false;

        try {
            $this->requireSqlite();
        } catch (\PHPUnit\Framework\SkippedWithMessageException $e) {
            $skipped = true;
            self::assertStringContainsString(self::ENV_SQLITE_PATH, $e->getMessage());
        }

        self::assertTrue($skipped, 'requireSqlite() must throw SkippedWithMessageException when env var is absent.');
    }

    public function testRequireSqliteReturnsPathWhenEnvVarPresent(): void
    {
        if (!$this->anyEnvVarPresent(self::ENV_SQLITE_PATH)) {
            $this->markTestSkipped('AWF_TEST_SQLITE_PATH is not set.');
        }

        $path = $this->requireSqlite();

        self::assertNotEmpty($path);
        self::assertIsString($path);
    }
}
