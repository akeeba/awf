# [0555] Database\Driver\Sqlite — driver specifics

**Phase:** Phase 3 — Database subsystem (SQLite in-memory + pure builders)
**Target source:** `src/Database/Driver/Sqlite.php`
**Test file to create:** `tests/Unit/Database/Driver/SqliteDriverTest.php`

## Scope
Cover Sqlite-specific overrides not covered by the base Driver tests: dropTable, renameTable, getTableCreate, escape specifics.

## Notes & gotchas
In-memory SQLite.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Database\Driver\Sqlite — driver specifics`.
