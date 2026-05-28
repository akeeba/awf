# [0645] Mvc\Engine\BladeEngine + CompilingEngine

**Phase:** Phase 4 — Core MVC
**Target source:** `src/Mvc/Engine/BladeEngine.php, src/Mvc/Engine/CompilingEngine.php`
**Test file to create:** `tests/Unit/Mvc/Engine/BladeEngineTest.php`

## Scope
Cover that a .blade.php fixture is compiled (via the Blade compiler) and rendered, that the compiled cache is written under tmp/, and recompilation on source change.

## Notes & gotchas
Point the compiler cache at a temp dir. Reuses the existing Blade compiler (already tested in BladeCompilerTest).

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Mvc\Engine\BladeEngine + CompilingEngine`.
