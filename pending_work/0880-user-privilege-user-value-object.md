# [0880] User\Privilege + User value object

**Phase:** Phase 5 — Everything else
**Target source:** `src/User/Privilege.php, src/User/User.php`
**Test file to create:** `tests/Unit/User/UserTest.php`

## Scope
Cover the User value object (id/name/email/params/getId), privilege get/set, and the privilege-aware authorisation checks on the user.

## Notes & gotchas
Construct users directly; no DB. Manager (DB-backed) is a separate task ([[0885]]).

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add User\Privilege + User value object`.
