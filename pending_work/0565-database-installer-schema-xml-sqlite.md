# [0565] Database\Installer — schema XML (SQLite)

**Phase:** Phase 3 — Database subsystem (SQLite in-memory + pure builders)
**Target source:** `src/Database/Installer.php`
**Test file to create:** `tests/Unit/Database/InstallerTest.php`

## Scope
Cover parsing of the schema XML manifest, version/condition evaluation, and applying CREATE/ALTER actions against an in-memory SQLite DB.

## Notes & gotchas
Create small XML schema fixtures under tests/Unit/Database/_data. Use the Sqlite driver. Focus on the parse + decision logic; SQLite supports enough DDL to validate apply().

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Database\Installer — schema XML (SQLite)`.
