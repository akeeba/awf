# [0635] Mvc\ViewTemplateFinder

**Phase:** Phase 4 — Core MVC
**Target source:** `src/Mvc/ViewTemplateFinder.php`
**Test file to create:** `tests/Unit/Mvc/ViewTemplateFinderTest.php`

## Scope
Cover template-path lookup order (view path → fallback theme paths), extension preference, and the not-found behaviour.

## Notes & gotchas
Use a fixtures dir tree with templates in multiple locations and assert which file wins.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Mvc\ViewTemplateFinder`.
