# [0950] Integration test harness & base case

**Phase:** Phase 6 — Optional integration tests (opt-in, run last)
**Target source:** `(new) tests/Integration + phpunit suite config`
**Test file to create:** `tests/Integration/AbstractIntegrationTestCase.php`

## Scope
Create a new 'Integration' testsuite in phpunit.xml.dist pointing at tests/Integration. Add an abstract base test case that reads DB connection settings from environment variables (e.g. AWF_TEST_MYSQL_DSN / AWF_TEST_PG_DSN, user, pass) and calls markTestSkipped() when they are absent so the suite is a no-op in normal CI.

## Notes & gotchas
This task ONLY sets up the harness — no driver tests yet. Document the required env vars in the test file header. Keep the default Unit suite untouched.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Integration test harness & base case`.
