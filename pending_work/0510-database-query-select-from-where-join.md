# [0510] Database\Query — SELECT / FROM / WHERE / JOIN

**Phase:** Phase 3 — Database subsystem (SQLite in-memory + pure builders)
**Target source:** `src/Database/Query.php`
**Test file to create:** `tests/Unit/Database/QuerySelectTest.php`

## Scope
Cover select(), from(), where() (AND glue, array of conditions), join()/innerJoin/leftJoin/rightJoin, and the resulting SQL string via __toString.

## Notes & gotchas
Instantiate a concrete driver-less query. Use the base Query or Query\Sqlite. Assert normalised SQL strings (trim/collapse whitespace in a helper). Split siblings: [[0515]] [[0520]].

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Database\Query — SELECT / FROM / WHERE / JOIN`.
