# [0995] Mailer — integration

**Phase:** Phase 6 — Optional integration tests (opt-in, run last)
**Target source:** `src/Mailer/Mailer.php`
**Test file to create:** `tests/Integration/Mailer/MailerTest.php`

## Scope
Cover the PHPMailer wrapper end-to-end against a catch-all SMTP (e.g. Mailpit/Mailhog) configured via env: build a message, set recipients/attachments/HTML+alt body, and send; assert via the catcher's API or the mail transcript.

## Notes & gotchas
Skips unless AWF_TEST_SMTP_* env vars set. The pure config/builder surface of Mailer that doesn't send may optionally get a small Unit test too, but sending is integration-only.

## Definition of done
- [ ] Test class created at the path above, namespace `Awf\Tests\…` mirroring the source path, `declare(strict_types=1)`.
- [ ] Follow the conventions in the existing `tests/Unit/Inflector/InflectorTest.php` and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php` (PHPUnit 11 attributes such as `#[DataProvider]`, no annotations).
- [ ] Meaningful assertions: happy path **and** edge cases **and** error/exception conditions.
- [ ] `vendor/bin/phpunit --testsuite Unit` is fully green (no failures, no errors, no risky/warning tests you introduced).
- [ ] This task file deleted from `pending_work/`.
- [ ] One commit containing the new test (and the deletion), message style: `test: add Mailer — integration`.
