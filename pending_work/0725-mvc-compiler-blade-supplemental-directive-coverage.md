# [0725] Mvc\Compiler\Blade — supplemental directive coverage

**Phase:** Phase 4 — Core MVC
**Target source:** `src/Mvc/Compiler/Blade.php`
**Test file to create:** `tests/Unit/Mvc/Compiler/BladeDirectivesTest.php`

## Scope
Add coverage for directives NOT already covered by the existing BladeCompilerTest: @repeatable/@yieldRepeatable/@repeatableOverride, @each, @push/@stack, @inlineCss/@inlineJs, @jhtml/@html, @route/@media/@token, custom extend() directives, and the regex-fallback path.

## Notes & gotchas
First read tests/Unit/Mvc/Compiler/BladeCompilerTest.php to see what's already covered and avoid duplication. Assert compiled PHP output strings.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Mvc\Compiler\Blade — supplemental directive coverage`.
