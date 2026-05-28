# [0815] Router\Router

**Phase:** Phase 5 — Everything else
**Target source:** `src/Router/Router.php`
**Test file to create:** `tests/Unit/Router/RouterTest.php`

## Scope
Cover adding rules, parse(url) → query vars, build(query) → url, default rule handling, and precedence between rules.

## Notes & gotchas
Inject minimal Container/Input. Builds on [[0810]].

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Router\Router`.
