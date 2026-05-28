# [0970] PostgreSQL driver — real-server integration

**Phase:** Phase 6 — Optional integration tests (opt-in, run last)
**Target source:** `src/Database/Driver/Postgresql.php (+ Pgsql)`
**Test file to create:** `tests/Integration/Database/PostgresqlDriverTest.php`

## Scope
Against a real PostgreSQL server: connect, temp table CRUD, transactions, RETURNING/insertid, sequences, getTableColumns/getTableList. Mirror phase-3 SQLite coverage on the real driver.

## Notes & gotchas
Extends [[0950]]; skips unless AWF_TEST_PG_* env vars set. Idempotent setup/teardown.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add PostgreSQL driver — real-server integration`.
