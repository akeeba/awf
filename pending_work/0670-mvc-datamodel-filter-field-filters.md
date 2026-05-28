# [0670] Mvc\DataModel\Filter\* — field filters

**Phase:** Phase 4 — Core MVC
**Target source:** `src/Mvc/DataModel/Filter/{AbstractFilter,Boolean,Date,Number,Text,Relation}.php`
**Test file to create:** `tests/Unit/Mvc/DataModel/FilterTest.php`

## Scope
Cover each filter's WHERE-fragment generation: Number (exact/range/operators), Text (LIKE/partial/exact), Boolean, Date (ranges), and the AbstractFilter factory/dispatch.

## Notes & gotchas
Inject a fixture DataModel/driver for quoting. Assert generated SQL fragments. Relation filter overlaps with [[0685]].

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Mvc\DataModel\Filter\* — field filters`.
