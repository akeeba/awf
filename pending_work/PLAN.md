# AWF unit-test build-out — master plan

This directory is a **work queue**. Each `NNNN-*.md` file is one self-contained
test-generation task: a single class (large classes are split by method group),
sized so one agent can implement it, get the suite green, and commit it in one go.

`pending_work/` is `.gitignore`d — the queue itself is never committed, only the
tests it produces.

## How the queue works
- Tasks are processed in **ascending number order** (lowest filename number first).
- Numbers leave gaps (increments of 5/10) so new tasks can be inserted between
  existing ones without renumbering.
- When a task is finished, its `.md` file is **deleted** and the new test is
  committed. The queue is empty when all `.md` files are gone.

## Priority order (the numbering encodes it)
| Range | Phase | Area |
|-------|-------|------|
| 0010 | 0 | **Prerequisite:** remove the unmaintained Sqlsrv/Sqlazure package |
| 0100–0130 | 1 | **Pimple & Container** — the DI linchpin of every AWF app |
| 0200–0440 | 2 | **Utility features** — Utils, Uri, Timer, Text, Registry, Input, Helper, Filesystem, Event, Encrypt, Date |
| 0500–0570 | 3 | **Database** — pure query builders + real round-trips on in-memory SQLite |
| 0600–0725 | 4 | **Core MVC** — Factory, Model, Controller, View, engines, DataModel + relations, DataController, DataViews |
| 0800–0940 | 5 | **Everything else** — Autoloader, Router, Dispatcher, Document, Download, Pagination, Session, User, Html helpers, Application, Exceptions |
| 0950–0995 | 6 | **Optional integration tests** — real MySQLi & PostgreSQL, FTP/SFTP, Mailer (skipped unless env vars are set) |

## Testing strategy decisions (already agreed)
1. **Granularity:** one class per task; giant classes (DataModel, Database/Query,
   Database/Driver, View, Blade) are split into multiple numbered tasks by method group.
2. **Database:** unit-test the pure query builders fully (no DB), and exercise the
   real `Driver`/`DataModel` code paths against an **in-memory SQLite** database
   (`:memory:`). Live-server-only behaviour is covered by the **optional integration
   suite** (phase 6) against real MySQLi & PostgreSQL, skipped unless env vars are set.
3. **Sqlsrv/Sqlazure is removed** (task 0010) — unmaintained and irrelevant to Akeeba
   software. This must run first.
4. **Hard-to-test code** (FTP/SFTP, Mailer, network downloads, live DB drivers) is
   *deferred to the integration phase*, not mocked into brittle unit tests. Unit tasks
   cover only the parts with no external dependency.

## Conventions every test must follow
- Mirror `src/` layout under `tests/Unit/` (and `tests/Integration/` for phase 6),
  namespace `Awf\Tests\Unit\…`.
- `declare(strict_types=1);`, PHPUnit 11 **attributes** (`#[DataProvider]`, etc.) — no
  doc-comment annotations. Model the existing `tests/Unit/Inflector/InflectorTest.php`
  and `tests/Unit/Mvc/Compiler/BladeCompilerTest.php`.
- Each test must cover the **happy path, edge cases, and error/exception conditions**.
- Fixtures (language INI files, schema XML, template files, dummy classes) go in a
  `_data/` subfolder next to the test.
- A task is done only when `vendor/bin/phpunit --testsuite Unit` is **fully green**.

## Running the queue
See `RUNNER_PROMPT.md` for the prompt that drives a subagent through the queue,
one task per commit, until it is empty.
