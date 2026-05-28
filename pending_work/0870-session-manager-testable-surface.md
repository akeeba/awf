# [0870] Session\Manager (testable surface)

**Phase:** Phase 5 — Everything else
**Target source:** `src/Session/Manager.php`
**Test file to create:** `tests/Unit/Session/ManagerTest.php`

## Scope
Cover the non-PHP-session logic: configuration handling, segment retrieval, token integration, and get/set proxying.

## Notes & gotchas
Use PHP's array session save handler or mock session_* via a seam if available; skip/markSkipped the parts that require an active session header context.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Session\Manager (testable surface)`.
