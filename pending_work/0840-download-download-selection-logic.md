# [0840] Download\Download (selection logic)

**Phase:** Phase 5 — Everything else
**Target source:** `src/Download/Download.php, src/Download/Adapter/AbstractAdapter.php`
**Test file to create:** `tests/Unit/Download/DownloadTest.php`

## Scope
Cover adapter auto-selection (curl vs fopen availability), option handling, and getFileSize/header parsing helpers that don't hit the network.

## Notes & gotchas
Real network downloads (Curl/Fopen GET) are DEFERRED to integration. Only test selection + pure helpers here; mock or skip network calls.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Download\Download (selection logic)`.
