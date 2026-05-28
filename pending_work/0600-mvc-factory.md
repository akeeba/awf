# [0600] Mvc\Factory

**Phase:** Phase 4 — Core MVC
**Target source:** `src/Mvc/Factory.php`
**Test file to create:** `tests/Unit/Mvc/FactoryTest.php`

## Scope
Cover class-name resolution (exact → singular → plural → Default fallback) for Model/View/Controller, namespace pattern \{App}\{Type}\{Name}, and view-engine selection.

## Notes & gotchas
Use a fake application namespace with stub Model/View/Controller classes defined in the test (or a fixtures dir). Inject a minimal Container.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Mvc\Factory`.
