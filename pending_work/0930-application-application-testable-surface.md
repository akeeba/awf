# [0930] Application\Application (testable surface)

**Phase:** Phase 5 — Everything else
**Target source:** `src/Application/Application.php`
**Test file to create:** `tests/Unit/Application/ApplicationTest.php`

## Scope
Cover construction/getInstance, getContainer, getName, template handling, and the route→dispatch→render orchestration with stubbed collaborators.

## Notes & gotchas
Heavy class — stub router/dispatcher/document via the container. Don't perform a real HTTP render. Cover what's reachable without real I/O; defer the rest with markTestSkipped + a note.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Application\Application (testable surface)`.
