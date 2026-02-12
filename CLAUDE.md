# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AWF (Akeeba Web Framework) is a PHP MVC framework library for building standalone web applications and WordPress plugins. It lives under the `Awf\` namespace with PSR-4 autoloading from `src/`.

## Build & Development Commands

```bash
# Install dependencies
composer install

# Run Rector (automated PHP upgrades, targets PHP 8.4)
vendor/bin/rector process src/

# Dry-run Rector to preview changes
vendor/bin/rector process src/ --dry-run
```

There are no built-in test suites, linting, or CI pipelines in this repository. It is a library consumed by other Akeeba projects.

## Architecture

### Container-Centric DI

The `Container` (extends Pimple) is the central hub. All services are lazy-loaded closures registered via service providers in `src/Container/Defaults/`. Key services:

| Service | Description |
|---------|-------------|
| `application` | Application instance |
| `appConfig` | Configuration registry |
| `db` | Database driver |
| `dispatcher` | Request dispatcher |
| `eventDispatcher` | Observer-pattern event system |
| `router` | URL routing |
| `input` | Request parameter handling |
| `session` / `segment` | Session management |
| `mvcFactory` | Factory for creating Models/Views/Controllers |
| `html` | HTML helper service |
| `helper` | Application helper service |
| `language` | Translation/localization |
| `dateFactory` | Date object factory |
| `mailer` | PHPMailer wrapper |
| `blade` | Blade template compiler |
| `userManager` | User auth & privileges |

The Container also manages application paths (`basePath`, `templatePath`, `languagePath`, `temporaryPath`) with fallback to PHP constants using a configurable `constantPrefix` (default `APATH_`).

### Request Lifecycle

```
Application::route()      → Router parses URL into query vars
Application::dispatch()   → Dispatcher creates Controller via mvcFactory
Dispatcher::dispatch()    → Controller::execute($task) with before/after hooks
Controller::display()     → Binds Model to View, renders template
Application::render()     → Document wraps output (HTML/JSON/CSV/Raw)
```

### MVC Layer

**Models** (`src/Mvc/Model.php`): State management with magic property access (`$model->limit` maps to `setState`/`getState`). `DataModel` extends this with full ORM: CRUD, relationships (hasOne, belongsTo, hasMany, belongsToMany), query filtering, and database table mapping.

**Controllers** (`src/Mvc/Controller.php`): Task-based execution with automatic hook chain: `onBeforeExecute` → `onBefore{Task}` → task method → `onAfter{Task}` → `onAfterExecute`. `DataController` adds RESTful CRUD tasks (browse, read, add, edit, save, delete).

**Views** (`src/Mvc/View.php`): Supports Blade (`.blade.php`) and PHP (`.php`) templates. Template lookup follows view path → fallback theme paths. DataView variants: Html, Json, Csv, Raw.

**MVC Factory** (`src/Mvc/Factory.php`): Resolves class names with Inflector (tries exact → singular → plural → Default fallback). Namespace pattern: `\{App}\{Type}\{Name}`.

### Key Patterns

**ContainerAwareInterface + ContainerAwareTrait**: Used by all major components for DI. Access the container via `$this->getContainer()`.

**Event System** (`src/Event/`): Observer pattern. The EventDispatcher triggers events; Observers define handler methods that are auto-detected via reflection. Events fire at controller, model, view, and dispatcher lifecycle points.

**HTML Helpers** (`src/Html/`): Registered as services via `HtmlService`. Access through `$container->html->helperName->method()`. Helper classes in `src/Html/Helper/` implement `HtmlHelperInterface`.

**Application Helpers** (`src/Helper/`): Non-static helpers implementing `HelperInterface` + `ContainerAwareInterface`. Access via `$container->helper->name->method()`.

### Database Layer

Abstracted drivers in `src/Database/Driver/` (Mysqli, Pdomysql, Postgresql, Pgsql, Sqlite, Sqlsrv, Sqlazure). Query builder in `src/Database/Query/` with driver-specific subclasses. Schema management via `src/Database/Installer.php`.

### Language System

INI-file based translations loaded from `language/` directory. The `Language` class (`src/Text/Language.php`) handles loading, fallback to default language, and post-processing callbacks. `Text` (`src/Text/Text.php`) is a static convenience wrapper.

## Code Conventions

- PHP 7.2+ / 8.0+ compatibility required (see `composer.json`)
- Rector configured for PHP 8.4 modernization
- License: GPL-3.0-or-later
- All source under `Awf\` namespace, PSR-4 from `src/`
- Global helper functions in `src/Utils/helpers.php` (autoloaded via Composer)
- Deprecated features trigger `E_USER_DEPRECATED` errors (not exceptions)
- The framework bundles its own copy of Pimple in `src/Pimple/` (namespaced under `Awf\Pimple`)
