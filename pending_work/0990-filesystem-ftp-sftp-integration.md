# [0990] Filesystem FTP/SFTP — integration

**Phase:** Phase 6 — Optional integration tests (opt-in, run last)
**Target source:** `src/Filesystem/{Ftp,Sftp,Hybrid}.php`
**Test file to create:** `tests/Integration/Filesystem/RemoteFsTest.php`

## Scope
Against a real (or local test) FTP/SFTP server configured via env vars: connect, upload/download/list/delete/mkdir, and the Hybrid fallback logic.

## Notes & gotchas
Skips unless AWF_TEST_FTP_* / AWF_TEST_SFTP_* env vars present. Requires ext-ftp / ext-ssh2. Lowest priority.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Filesystem FTP/SFTP — integration`.
