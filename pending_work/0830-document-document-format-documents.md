# [0830] Document\Document + format documents

**Phase:** Phase 5 — Everything else
**Target source:** `src/Document/{Document,Html,Json,Csv,Raw}.php`
**Test file to create:** `tests/Unit/Document/DocumentTest.php`

## Scope
Cover document factory selection by type, buffer set/get, MIME/content-type per format, and header accumulation. One nested section per format.

## Notes & gotchas
Don't emit real headers — assert via getters. Inject minimal Container.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Document\Document + format documents`.
