# [0925] Application\TransparentAuthentication

**Phase:** Phase 5 — Everything else
**Target source:** `src/Application/TransparentAuthentication.php`
**Test file to create:** `tests/Unit/Application/TransparentAuthenticationTest.php`

## Scope
Cover the credential-extraction logic from request inputs/headers (HTTP basic, query, header-token methods) and the enable/disable flags.

## Notes & gotchas
Inject a stub Input/Container with synthetic request data.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Application\TransparentAuthentication`.
