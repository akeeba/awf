# Runner prompt

Paste the block below into Claude Code (in this repo) to drain the queue. It runs
one task at a time, each in a **fresh subagent** so the main context stays clean,
and loops until `pending_work/` has no task files left.

> Tip: you can also wrap it with `/loop` to keep it going across sessions, but the
> prompt already loops on its own until the queue is empty.

---

```
You are draining the unit-test work queue in `pending_work/`. Read
`pending_work/PLAN.md` once for context, then repeat the following cycle until
there are no `NNNN-*.md` task files left (ignore PLAN.md, RUNNER_PROMPT.md and any
README):

1. List `pending_work/` and pick the task file with the LOWEST leading number.
   If none exist, stop and report a summary of everything that was done.

2. Launch a SINGLE general-purpose subagent (Agent tool) to implement ONLY that
   one task. Give the subagent a self-contained prompt that includes:
   - The full path of the task file and an instruction to read it first.
   - The repo conventions: tests live under `tests/`, namespace `Awf\Tests\…`
     mirroring the `src/` path, `declare(strict_types=1)`, PHPUnit 11 attributes
     (no annotations), model the existing tests in
     `tests/Unit/Inflector/InflectorTest.php` and
     `tests/Unit/Mvc/Compiler/BladeCompilerTest.php`.
   - It must read the TARGET SOURCE FILE(S) named in the task before writing tests,
     and write tests that reflect the code's ACTUAL behaviour (do not assume Laravel
     semantics for Blade, etc.).
   - Cover happy path + edge cases + error/exception conditions. Put fixtures in a
     `_data/` folder next to the test. Use in-memory SQLite (`:memory:`) for DB
     round-trips; `markTestSkipped()` for genuinely unavailable extensions.
   - It must run `vendor/bin/phpunit --testsuite Unit` and iterate until the WHOLE
     suite is green (no failures/errors, and no new risky/warning tests). If a real
     framework bug blocks a correct test, it may fix the bug in `src/` — but it must
     NOT weaken a test just to make it pass, and must call out any src change.
   - When green, it must DELETE the task file and create ONE git commit containing
     the new test file(s), any fixtures, the task-file deletion, and any src fix,
     using message style `test: <task title>` (or `fix:`/`chore:` if appropriate,
     e.g. task 0010 is a removal). It must NOT push.
   - The subagent should report: task number, files added, test/assertion counts,
     final suite status, and anything notable (skipped tests, src changes).

3. After the subagent returns, VERIFY before trusting it: confirm the task file is
   gone, run `vendor/bin/phpunit --testsuite Unit` yourself to confirm green, and
   check `git log -1` shows the new commit. If verification fails, do NOT proceed —
   relaunch a subagent to fix the specific problem, then re-verify.

4. Go back to step 1.

Rules for you (the orchestrator):
- One task = one subagent = one commit. Never batch multiple task files together.
- Never skip ahead in the numbering. Task 0010 (remove Sqlsrv/Sqlazure) MUST be the
  first thing done.
- Never delete a task file yourself except via the verified subagent flow.
- If the same task fails twice across subagents, STOP and report the blocker to the
  user instead of looping forever.
- Keep a running tally and give a brief progress line after each completed task.
```

---

## Notes
- Phase 6 integration tasks (0950–0995) create a separate `tests/Integration`
  suite that **skips by default**; they only run when the documented `AWF_TEST_*`
  environment variables point at real services. They are safe to leave in the queue.
- If a task turns out to be bigger than expected, the subagent (or you) can split it
  into new `NNNN-*.md` files using an unused number in the gap and delete the original.
