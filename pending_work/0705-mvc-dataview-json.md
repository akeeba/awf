# [0705] Mvc\DataView\Json

**Phase:** Phase 4 — Core MVC
**Target source:** `src/Mvc/DataView/Json.php`
**Test file to create:** `tests/Unit/Mvc/DataView/JsonViewTest.php`

## Scope
Cover JSON serialisation of model data, the browse/read output shapes, and content-type handling.

## Notes & gotchas
Feed a fixture model/data array; assert decoded JSON structure.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Mvc\DataView\Json`.
