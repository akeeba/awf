# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Blade Templates

AWF's Blade implementation (`src/Mvc/Compiler/Blade.php`) is a **custom, independent reimplementation** inspired by Laravel's Blade syntax — it is not a fork or port of Laravel's code. Assume no Laravel Blade feature works unless it is documented as implemented.

Before writing or editing any `.blade.php` template, read the `awf-blade` skill (`.claude/skills/awf-blade/SKILL.md`) for the full directive inventory, the AWF-specific directives, and the Laravel features AWF does not support.

## Code Conventions

- PHP 7.4+ / 8.0+ compatibility required (see `composer.json`)
- Deprecated features trigger `E_USER_DEPRECATED` errors (not exceptions)
- The framework bundles its own copy of Pimple in `src/Pimple/` (namespaced under `Awf\Pimple`)
