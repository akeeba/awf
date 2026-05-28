# [0695] Mvc\TreeModel (SQLite)

**Phase:** Phase 4 — Core MVC
**Target source:** `src/Mvc/TreeModel.php`
**Test file to create:** `tests/Unit/Mvc/TreeModelTest.php`

## Scope
Cover nested-set operations: insertAsChild/insertAsSibling, move, delete subtree, getRoot/getPath/getDescendants, and lft/rgt integrity after mutations.

## Notes & gotchas
Build a small nested-set table in SQLite and assert lft/rgt values after each operation. This is intricate — verify tree integrity invariants.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Mvc\TreeModel (SQLite)`.
