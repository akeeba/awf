# [0980] DataModel + relations — real-server integration

**Phase:** Phase 6 — Optional integration tests (opt-in, run last)
**Target source:** `src/Mvc/DataModel.php and relations`
**Test file to create:** `tests/Integration/Mvc/DataModelIntegrationTest.php`

## Scope
Run the DataModel CRUD + all four relation types + TreeModel against the real MySQL (and/or PG) connection from [[0950]], to validate behaviour the SQLite unit tests can't guarantee (collation, FK, concurrent autoincrement).

## Notes & gotchas
Reuse fixtures conceptually from [[0650]]/[[0680]]/[[0685]]/[[0695]] but create real tables. Skips without DB env vars.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add DataModel + relations — real-server integration`.
