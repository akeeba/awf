# [0690] Mvc\DataModel\RelationManager + Relation base

**Phase:** Phase 4 — Core MVC
**Target source:** `src/Mvc/DataModel/RelationManager.php, src/Mvc/DataModel/Relation.php`
**Test file to create:** `tests/Unit/Mvc/DataModel/RelationManagerTest.php`

## Scope
Cover relation registration/resolution by name, the magic relation accessor, getData/eager-load orchestration, and the relation-not-found exception.

## Notes & gotchas
Use fixtures from [[0680]] [[0685]]. Focus on the manager dispatch logic.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Mvc\DataModel\RelationManager + Relation base`.
