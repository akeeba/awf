# [0680] Mvc\DataModel — HasOne / HasMany relations (SQLite)

**Phase:** Phase 4 — Core MVC
**Target source:** `src/Mvc/DataModel/Relation/HasOne.php, src/Mvc/DataModel/Relation/HasMany.php`
**Test file to create:** `tests/Unit/Mvc/DataModel/Relation/HasManyTest.php`

## Scope
Cover defining and lazy/eager loading of hasOne and hasMany relations, the generated foreign-key queries, and relation result hydration.

## Notes & gotchas
Two fixture models + two SQLite tables with a FK. Builds on [[0650]]. Siblings: [[0685]] [[0690]].

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Mvc\DataModel — HasOne / HasMany relations (SQLite)`.
