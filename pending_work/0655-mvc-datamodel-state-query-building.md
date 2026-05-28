# [0655] Mvc\DataModel — state & query building

**Phase:** Phase 4 — Core MVC
**Target source:** `src/Mvc/DataModel.php`
**Test file to create:** `tests/Unit/Mvc/DataModel/DataModelQueryTest.php`

## Scope
Cover buildQuery(), state filters → WHERE generation, where()/whereRaw, get()/getItemsArray, count(), ordering/limit, and scopes.

## Notes & gotchas
Assert generated SQL and/or result sets against SQLite. Complements [[0650]].

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Mvc\DataModel — state & query building`.
