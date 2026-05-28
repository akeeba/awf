# [0540] Database\Driver — lifecycle & factory (SQLite)

**Phase:** Phase 3 — Database subsystem (SQLite in-memory + pure builders)
**Target source:** `src/Database/Driver.php`
**Test file to create:** `tests/Unit/Database/DriverLifecycleTest.php`

## Scope
Cover Driver::getInstance() factory selection, connect()/connected()/disconnect(), setQuery()/getQuery(), and option handling, exercised against an in-memory SQLite database.

## Notes & gotchas
Use the Sqlite driver with ':memory:'. Skip gracefully if ext-pdo_sqlite/ext-sqlite3 missing. This is the base for [[0545]] [[0550]].

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Database\Driver — lifecycle & factory (SQLite)`.
