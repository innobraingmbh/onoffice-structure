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

All enums are **string-backed** (enforced by arch tests): `FieldConfigurationModule` (11 modules), `FieldType` (10 types), `Language` (34 languages).

## Live API Probes

For exploring or debugging the field configuration against the real onOffice API, use Workbench Artisan commands under `workbench/app/Console/Commands/`. The `WorkbenchServiceProvider` loads `ON_OFFICE_TOKEN` / `ON_OFFICE_SECRET` from the package-root `.env` (gitignored) and pushes them into `config('onoffice.*')`, so probes call the real API the same way consumers would.

Run a probe via Testbench:

```bash
vendor/bin/testbench probe:structure
vendor/bin/testbench probe:structure --only=estate --only=address --language=ENG --fields
```

Add a new probe by dropping a command into `workbench/app/Console/Commands/` and registering it in `WorkbenchServiceProvider::boot()`. Use the package's facades (`Structure::forClient(...)->getModules(...)`) directly inside `handle()`.

This is for development feedback only, not test infrastructure.

## Code Standards

- PHP 8.4+, `declare(strict_types=1)` in every file
- Pint with Laravel preset + strict rules (strict comparison, protected→private, ordered class elements, global namespace imports)
- Rector with Laravel sets, dead code removal, code quality, early return
- PHPStan level 8
- 100% type coverage required in CI
- Architecture tests enforce: readonly DTOs, string-backed enums, converter trait isolation, no debugging functions

## Testing

Uses Pest with `Orchestra\Testbench`. Test stubs (mock API JSON responses) are in `tests/Stubs/`. Architecture rules are in `tests/ArchTest.php`.
