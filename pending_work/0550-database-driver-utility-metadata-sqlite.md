# [0550] Database\Driver — utility & metadata (SQLite)

**Phase:** Phase 3 — Database subsystem (SQLite in-memory + pure builders)
**Target source:** `src/Database/Driver.php`
**Test file to create:** `tests/Unit/Database/DriverUtilityTest.php`

## Scope
Cover quote()/quoteName()/escape(), getTableList(), getTableColumns(), getTableKeys(), transactionStart/Commit/Rollback, and replacePrefix/getPrefix.

## Notes & gotchas
Against in-memory SQLite. Builds on [[0540]].

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Database\Driver — utility & metadata (SQLite)`.
