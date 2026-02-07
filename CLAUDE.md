# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

`innobraingmbh/onoffice-structure` is a Laravel package that extracts onOffice enterprise field configurations via `innobrain/laravel-onoffice-adapter` into structured DTOs, which can then be converted into various formats (arrays, Laravel validation rules, Prism schemas, JSON Schema).

## Commands

```bash
composer test              # Run Pest tests
composer test-coverage     # Run tests with coverage
composer analyse           # PHPStan static analysis (level 8)
composer format            # Run Rector then Pint
vendor/bin/pest --filter="test name"  # Run a single test
```

## Architecture

### Strategy Pattern + DTOs

The core pattern is **readonly DTOs** that implement `Convertible` and use the `HasConverter` trait. Conversion is delegated to strategy objects via reflection (`convert` + class basename → method name).

```
Field->convert($strategy)  →  $strategy->convertField($field)
Module->convert($strategy) →  $strategy->convertModule($module)
```

**DTOs** (`src/Dtos/`): `Module`, `Field`, `PermittedValue`, `FieldDependency`, `FieldFilter` — all readonly, immutable.

**Converter strategies** (`src/Converters/`):
- `ArrayConvertStrategy` — nested arrays, optional empty filtering
- `LaravelRulesConvertStrategy` — Laravel validation rules (pipe or array syntax)
- `PrismSchemaConvertStrategy` — Prism PHP schemas for AI tooling
- `JsonSchemaConvertStrategy` — JSON Schema format

Each strategy implements `ConvertStrategy` and extends `BaseConvertStrategy`. Converter-specific traits live in the same converter namespace directory (architecture-tested).

### Services

- **`Structure`** — main entry point with fluent API: `Structure::forClient($credentials)->getModules($only, $language)`
- **`FieldConfiguration`** — parses raw onOffice API responses into DTO hierarchies

Both have facades in `src/Facades/`.

### Collections

- `ModulesCollection` and `FieldCollection` extend `Illuminate\Support\Collection` and implement `Convertible` for batch conversions.

### Enums

All enums are **string-backed** (enforced by arch tests): `FieldConfigurationModule` (11 modules), `FieldType` (10 types), `Language` (6 languages).

## Code Standards

- PHP 8.4+, `declare(strict_types=1)` in every file
- Pint with Laravel preset + strict rules (strict comparison, protected→private, ordered class elements, global namespace imports)
- Rector with Laravel sets, dead code removal, code quality, early return
- PHPStan level 8
- 100% type coverage required in CI
- Architecture tests enforce: readonly DTOs, string-backed enums, converter trait isolation, no debugging functions

## Testing

Uses Pest with `Orchestra\Testbench`. Test stubs (mock API JSON responses) are in `tests/Stubs/`. Architecture rules are in `tests/ArchTest.php`.
