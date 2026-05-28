# [0890] Html\HtmlService + Html facade

**Phase:** Phase 5 — Everything else
**Target source:** `src/Html/HtmlService.php, src/Html/Html.php, src/Html/AbstractHelper.php`
**Test file to create:** `tests/Unit/Html/HtmlServiceTest.php`

## Scope
Cover helper resolution by name through the service, container injection, the $container->html->helper->method() access path, and registering a custom helper.

## Notes & gotchas
Use a fake helper implementing HtmlHelperInterface. Inject minimal Container.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Html\HtmlService + Html facade`.
