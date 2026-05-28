# [0810] Router\Rule

**Phase:** Phase 5 — Everything else
**Target source:** `src/Router/Rule.php`
**Test file to create:** `tests/Unit/Router/RuleTest.php`

## Scope
Cover a single routing rule: pattern → regex compilation, match() extracting params, and build() reversing params into a path.

## Notes & gotchas
Pure-ish; no container needed for Rule.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Router\Rule`.
