# [0800] Autoloader\Autoloader

**Phase:** Phase 5 — Everything else
**Target source:** `src/Autoloader/Autoloader.php`
**Test file to create:** `tests/Unit/Autoloader/AutoloaderTest.php`

## Scope
Cover PSR-4 prefix registration, class→path mapping, addMap/addPrefix, and the file-resolution logic.

## Notes & gotchas
Use a fixtures dir with dummy classes; don't pollute the real autoloader (unregister in tearDown).

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Autoloader\Autoloader`.
