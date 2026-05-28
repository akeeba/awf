# [0640] Mvc\Engine\PhpEngine

**Phase:** Phase 4 — Core MVC
**Target source:** `src/Mvc/Engine/PhpEngine.php, src/Mvc/Engine/AbstractEngine.php`
**Test file to create:** `tests/Unit/Mvc/Engine/PhpEngineTest.php`

## Scope
Cover rendering a plain .php template with bound variables and that output buffering/exception safety works.

## Notes & gotchas
Use a small .php template fixture.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Mvc\Engine\PhpEngine`.
