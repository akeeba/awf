# [0570] Database\Iterator (SQLite)

**Phase:** Phase 3 — Database subsystem (SQLite in-memory + pure builders)
**Target source:** `src/Database/Iterator/AbstractIterator.php, src/Database/Iterator/Sqlite.php, src/Database/Iterator/Pdo.php`
**Test file to create:** `tests/Unit/Database/IteratorTest.php`

## Scope
Cover Iterator interface contract over a real SQLite result set: foreach, current/key/next/rewind/valid, count, and that rows hydrate to the requested class.

## Notes & gotchas
Seed an in-memory SQLite table and iterate. Builds on [[0540]].

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Database\Iterator (SQLite)`.
