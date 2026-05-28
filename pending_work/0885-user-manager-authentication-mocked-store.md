# [0885] User\Manager + Authentication (mocked store)

**Phase:** Phase 5 — Everything else
**Target source:** `src/User/Manager.php, src/User/Authentication.php`
**Test file to create:** `tests/Unit/User/ManagerTest.php`

## Scope
Cover user creation/loading via an injected store, password verification, login success/failure exceptions, and privilege resolution.

## Notes & gotchas
Inject a fake user-storage/db double so no real database is needed. Relates to [[0880]].

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add User\Manager + Authentication (mocked store)`.
