# [0835] Document\Toolbar + Menu

**Phase:** Phase 5 — Everything else
**Target source:** `src/Document/Toolbar/{Toolbar,Button}.php, src/Document/Menu/{MenuManager,Item}.php`
**Test file to create:** `tests/Unit/Document/ToolbarMenuTest.php`

## Scope
Cover toolbar button add/remove/render data, and menu item tree construction/active-item detection.

## Notes & gotchas
Assert the data structures, not rendered HTML where avoidable.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Document\Toolbar + Menu`.
