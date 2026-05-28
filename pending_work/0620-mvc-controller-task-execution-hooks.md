# [0620] Mvc\Controller — task execution & hooks

**Phase:** Phase 4 — Core MVC
**Target source:** `src/Mvc/Controller.php`
**Test file to create:** `tests/Unit/Mvc/ControllerTaskTest.php`

## Scope
Cover execute($task) and the hook chain onBeforeExecute → onBefore{Task} → task → onAfter{Task} → onAfterExecute, task-to-method mapping, and the default task.

## Notes & gotchas
Use a Controller subclass defined in the test with spy task methods recording call order. Inject a minimal Container. Split sibling: [[0625]].

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Mvc\Controller — task execution & hooks`.
