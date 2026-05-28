# [0960] MySQLi driver — real-server integration

**Phase:** Phase 6 — Optional integration tests (opt-in, run last)
**Target source:** `src/Database/Driver/Mysqli.php (+ Pdomysql)`
**Test file to create:** `tests/Integration/Database/MysqliDriverTest.php`

## Scope
Against a real MySQL/MariaDB server: connect, create/drop a temp table, full CRUD, transactions, getTableColumns/getTableList, prefix replacement, and UTF8MB4 handling. Mirror the SQLite coverage from phase 3 but on the real driver.

## Notes & gotchas
Extends the [[0950]] base case; skips unless AWF_TEST_MYSQL_* env vars are set. Create + drop everything inside the test so it's idempotent.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add MySQLi driver — real-server integration`.
