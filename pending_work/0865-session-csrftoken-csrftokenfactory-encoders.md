# [0865] Session\CsrfToken + CsrfTokenFactory + Encoders

**Phase:** Phase 5 — Everything else
**Target source:** `src/Session/CsrfToken.php, src/Session/CsrfTokenFactory.php, src/Session/Encoder/{Base32Encoder,Base64Encoder,TransparentEncoder}.php`
**Test file to create:** `tests/Unit/Session/CsrfTokenTest.php`

## Scope
Cover token generation/validation, the encoders' encode/decode round-trips, and factory wiring.

## Notes & gotchas
Pure-ish; relates to [[0425]].

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Session\CsrfToken + CsrfTokenFactory + Encoders`.
