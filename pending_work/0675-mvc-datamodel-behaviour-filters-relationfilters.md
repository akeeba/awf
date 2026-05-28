# [0675] Mvc\DataModel\Behaviour\Filters + RelationFilters

**Phase:** Phase 4 — Core MVC
**Target source:** `src/Mvc/DataModel/Behaviour/Filters.php, src/Mvc/DataModel/Behaviour/RelationFilters.php`
**Test file to create:** `tests/Unit/Mvc/DataModel/BehaviourFiltersTest.php`

## Scope
Cover how the behaviours hook onAfterBuildQuery to inject state-based filters into the query.

## Notes & gotchas
Use a fixture DataModel + SQLite. Relates to [[0655]] [[0670]].

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Mvc\DataModel\Behaviour\Filters + RelationFilters`.
