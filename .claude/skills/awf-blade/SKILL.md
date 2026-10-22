---
name: awf-blade
description: Reference for AWF's Blade template compiler (src/Mvc/Compiler/Blade.php). Read before writing or editing any .blade.php template in this repository. AWF's Blade is a custom, independent reimplementation inspired by Laravel's syntax - it is NOT Laravel Blade. Lists every directive AWF implements, the AWF-specific directives that do not exist in Laravel (@lang, @route, @media, @css, @js, @token, @repeatable, ...), and the modern Laravel Blade features AWF does NOT support (@component, @auth, @error, @class, @once, {!! !!}, ...). Use whenever a question involves Blade directives, template inheritance, sections, stacks, or the compiled template cache.
---

# AWF Blade templates

AWF's Blade implementation (`src/Mvc/Compiler/Blade.php`) is a **custom, independent
reimplementation** inspired by Laravel's Blade syntax - it is not a fork or port of Laravel's
code. Assume nothing from Laravel Blade works unless it is listed below.

### Implemented Directives

**Template inheritance & sections**
- `@extends('name')`, `@section('name')` / `@endsection` / `@stop` / `@overwrite` / `@show` / `@append`
- `@yield('name')`, `@yield('name', 'default')`
- `@push('stack')` / `@endpush`, `@stack('name')`
- `@include('name')`, `@each('partial', $items, 'var')`

**Control flow**
- `@if()` / `@elseif()` / `@else` / `@endif`
- `@unless()` / `@endunless`
- `@for()` / `@endfor`, `@foreach()` / `@endforeach`, `@while()` / `@endwhile`
- `@forelse()` / `@empty` / `@endforelse`

**Echo**
- `{{ $var }}` — raw unescaped output
- `{{{ $var }}}` — HTML-escaped output (three braces)
- `{{ $var or 'default' }}` — with fallback (raw)
- `{{-- comment --}}` — compiled away

**AWF-specific directives (not in Laravel)**
- `@lang('KEY')` — translate via `$this->getLanguage()->text()`
- `@sprintf('KEY', $args)` — formatted translation
- `@plural('KEY', $count)` — plural translation
- `@token` — emit CSRF token value
- `@route('path', $params)` — generate URL via router
- `@media('path')` — resolve media path via `Template::parsePath()`
- `@css('file')`, `@js('file')` — enqueue CSS/JS assets
- `@inlineCss('style')`, `@inlineJs('script')` — inline asset blocks
- `@jhtml('helper.method', $args)` / `@html(...)` — call an HTML helper
- `@repeatable('name', $arg…)` / `@endrepeatable` — define a reusable block, unless it's already defined
- `@yieldRepeatable('name', $arg…)` — invoke a repeatable block
- `@repeatableOverride('name', $arg…)` / `@endrepeatable` — (re)define a block unconditionally; closed by
  `@endrepeatable` (there is no `@endrepeatableOverride`)

Custom directives can be registered with `$blade->extend(callable $compiler)`.

### Repeatables

Repeatables are closures stored at **runtime** in the View's `$repeatableMap`, keyed by the md5 of the
lowercased name expression. The compiled output never depends on other templates compiled before it.

- `@repeatable` compiles to `$this->repeatableMap['<key>'] ??= function(…) {` — the **first** definition
  on a View instance wins. Defining the same plain repeatable twice on one View keeps the first one.
- `@repeatableOverride` compiles to `$this->repeatableMap['<key>'] = function(…) {` — always wins over
  plain definitions that run after it.
- Overriding a parent's block: the child does `@extends('Parent')` + `@repeatableOverride('name', …)`.
  `@extends` compiles to a footer `loadAnyTemplate()` call, so the child body runs **before** the parent on
  the same View; the parent's plain `@repeatable` then leaves the override in place.
- The map lives as long as the View instance: rendering several templates on one View shares their
  repeatables (first plain definition wins; an override sticks).
- `@yieldRepeatable` of an undefined block throws `RuntimeException('Repeatable "name" is not defined')`.
  Anything thrown inside the block is wrapped in `RuntimeException('Error calling repeatable "name"', 500)`
  with the original as `previous`. The View wraps both in an `ErrorException` naming the template.

### Not Implemented (present in modern Laravel Blade)

The following Laravel Blade features **do not exist** in AWF:

- Component system: `@component`, `@slot`, `@props`, `@aware`, `@fragment`, `<x:component />`
- Authorization: `@auth` / `@endauth`, `@guest` / `@endguest`, `@can`, `@cannot`
- Error display: `@error` / `@enderror`
- Environment guards: `@env`, `@production`
- Form attribute helpers: `@checked`, `@selected`, `@disabled`, `@readonly`, `@required`
- CSS/class helpers: `@class`, `@style`
- Deduplication: `@once` / `@endonce`, `@pushOnce` / `@endPushOnce`
- Stack prepend: `@prepend` / `@endprepend`
- Conditional includes: `@includeIf`, `@includeWhen`, `@includeFirst`
- Section checks: `@hasSection`
- Raw echo shorthand: `{!! $var !!}` (Laravel-style)
- Livewire integration

### Compiler Notes

- The compiler uses PHP's `token_get_all()` when available (preferred) and falls back to regex-based compilation on hosts without the Tokenizer extension.
- Compiled templates are cached under `tmp/` with two-level folder structure; the cache is invalidated on source file modification and the compiled file's OPcache entry is busted automatically.
- The cache identifier is `sha1(path . "\0" . Blade::OUTPUT_FORMAT_VERSION)` (`VersionedCompilerInterface`). **Bump `OUTPUT_FORMAT_VERSION` whenever the compiled output changes incompatibly**, so stale compiled files are never reused. Compilers not implementing the interface keep the plain `sha1(path)` identifier.
- Content tag delimiters can be customised via `setContentTags()` / `setEscapedContentTags()` if the defaults conflict with another template syntax.

