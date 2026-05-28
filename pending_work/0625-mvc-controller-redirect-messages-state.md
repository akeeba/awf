# [0625] Mvc\Controller — redirect, messages & state

**Phase:** Phase 4 — Core MVC
**Target source:** `src/Mvc/Controller.php`
**Test file to create:** `tests/Unit/Mvc/ControllerRedirectTest.php`

## Scope
Cover setRedirect()/getRedirect, message + message-type accumulation, getView()/getModel() lazy creation, and CSRF/token checking hooks.

## Notes & gotchas
Stub the Application/redirect so no real header()/exit happens (override the redirect method in a subclass). Complements [[0620]].

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Mvc\Controller — redirect, messages & state`.
