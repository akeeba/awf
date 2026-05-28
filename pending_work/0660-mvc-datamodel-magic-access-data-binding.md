# [0660] Mvc\DataModel — magic access & data binding

**Phase:** Phase 4 — Core MVC
**Target source:** `src/Mvc/DataModel.php`
**Test file to create:** `tests/Unit/Mvc/DataModel/DataModelDataTest.php`

## Scope
Cover __get/__set/__isset over fields, bind()/getData/toArray, getFieldValue/setFieldValue, and known-field guarding.

## Notes & gotchas
Use a fixture model; minimal SQLite table for column metadata. Complements [[0650]] [[0655]].

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Mvc\DataModel — magic access & data binding`.
