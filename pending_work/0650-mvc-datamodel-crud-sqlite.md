# [0650] Mvc\DataModel — CRUD (SQLite)

**Phase:** Phase 4 — Core MVC
**Target source:** `src/Mvc/DataModel.php`
**Test file to create:** `tests/Unit/Mvc/DataModel/DataModelCrudTest.php`

## Scope
Cover load/find/findOrFail, save (insert vs update), delete/trash, reset, and the auto-detection of table/key from the table name. Against in-memory SQLite.

## Notes & gotchas
Big class — this task covers ONLY persistence/CRUD. Create a fixture DataModel subclass + SQLite table in setUp. Siblings: [[0655]] [[0660]]. Use the SQLite driver from phase 3.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Mvc\DataModel — CRUD (SQLite)`.
