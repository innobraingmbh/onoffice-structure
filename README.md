# onOffice Structure Extractor for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/innobraingmbh/onoffice-structure.svg?style=flat-square)](https://packagist.org/packages/innobraingmbh/onoffice-structure)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/innobraingmbh/onoffice-structure/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/innobraingmbh/onoffice-structure/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/innobraingmbh/onoffice-structure/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/innobraingmbh/onoffice-structure/actions?query=workflow%3A"Fix+PHP+Code+Style+Issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/innobraingmbh/onoffice-structure.svg?style=flat-square)](https://packagist.org/packages/innobraingmbh/onoffice-structure)

Extract and work with the onOffice enterprise field configuration (Modul- und Feldkonfiguration) in Laravel. The package fetches configurations via [innobrain/laravel-onoffice-adapter](https://github.com/innobraingmbh/laravel-onoffice-adapter), transforms them into readonly DTOs, and converts them into various output formats using a strategy pattern.

## Features

- Fetch field configurations for all onOffice modules (Address, Estate, AgentsLog, Calendar, Email, File, News, Intranet, Project, Task, User)
- Readonly DTOs for Modules, Fields, Permitted Values, Dependencies, and Filters
- Convert to arrays, Laravel validation rules, [Prism PHP](https://prismphp.com/) schemas, or JSON Schema
- Filter fields by configuration-based conditions with a fluent builder
- Sanitize input data against field definitions and permitted values
- Multi-language support (German, English, French, Spanish, Italian, Croatian)
- Extensible converter strategy pattern for custom output formats

## Installation

```bash
composer require innobraingmbh/onoffice-structure
```

You can optionally publish the configuration file:

```bash
php artisan vendor:publish --provider="Innobrain\Structure\StructureServiceProvider" --tag="onoffice-structure-config"
```

## Usage

### Fetching Structure Data

Use the `Structure` facade or inject `Innobrain\Structure\Services\Structure`:

```php
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\Language;
use Innobrain\Structure\Facades\Structure;

$credentials = new OnOfficeApiCredentials('your-token', 'your-secret');

// Fetch all modules (defaults to German labels)
$modules = Structure::forClient($credentials)->getModules();

// Fetch specific modules in a specific language
$modules = Structure::forClient($credentials)->getModules(
    only: [FieldConfigurationModule::Address->value, FieldConfigurationModule::Estate->value],
    language: Language::English,
);

// Iterate over modules and fields
foreach ($modules as $moduleKey => $module) {
    echo "Module: {$module->label} ({$module->key->value})\n";
    foreach ($module->fields as $fieldKey => $field) {
        echo "  Field: {$field->label} ({$field->key}) - Type: {$field->type->value}\n";
    }
}
```

### Filtering Fields

Fields can have filter configurations that determine their visibility based on other field values. Use the fluent `FieldFilterBuilder` to narrow down fields:

```php
$addressModule = $modules->get(FieldConfigurationModule::Address->value);

$filteredFields = $addressModule->fields
    ->whereMatchesFilters()
    ->where('Art', '2')       // only fields visible when Art = 2
    ->when($someCondition, fn ($builder) => $builder->where('ArtDaten', '1'))
    ->get();
```

### Sanitizing Input Data

Remove keys that don't match known fields or have invalid permitted values:

```php
$sanitized = $addressModule->fields->sanitize(collect([
    'Email' => 'test@example.com',
    'unknownField' => 'value',      // removed: not in field collection
    'Beziehung' => '999',           // removed: not a permitted value
]));
```

### Converting Data

All DTOs and collections implement `Convertible` and can be transformed using a `ConvertStrategy`.

#### Array Conversion

```php
use Innobrain\Structure\Converters\Array\ArrayConvertStrategy;

$strategy = new ArrayConvertStrategy(dropEmpty: true); // remove null/empty values

$allModulesArray = $modules->convert($strategy);
$moduleArray = $addressModule->convert($strategy);
$fieldArray = $addressModule->fields->get('Email')->convert($strategy);
```

#### Laravel Validation Rules

```php
use Innobrain\Structure\Converters\LaravelRules\LaravelRulesConvertStrategy;

// Pipe-separated strings with nullable (default)
$strategy = new LaravelRulesConvertStrategy(pipeSyntax: true, includeNullable: true);
$rules = $addressModule->convert($strategy);
// ['KdNr' => 'integer|nullable', 'Email' => 'string|max:100|nullable', ...]

// Array syntax without nullable
$strategy = new LaravelRulesConvertStrategy(pipeSyntax: false, includeNullable: false);
$rules = $addressModule->convert($strategy);
// ['KdNr' => ['integer'], 'Email' => ['string', 'max:100'], ...]

// Multi-select fields automatically get a wildcard rule:
// 'Beziehung' => 'array|distinct|nullable', 'Beziehung.*' => 'in:0,1,2,3'
```

#### Prism Schema (for AI tooling)

```php
use Innobrain\Structure\Converters\PrismSchema\PrismSchemaConvertStrategy;

$strategy = new PrismSchemaConvertStrategy(
    includeNullable: true,      // mark fields without defaults as nullable
    includeDescriptions: true,  // use field labels as descriptions
);

$schema = $addressModule->convert($strategy);
// Returns an ObjectSchema usable with Prism's structured output
```

Field type mapping: `VarChar/Text/Blob` -> `StringSchema`, `Integer/Float` -> `NumberSchema`, `Boolean` -> `BooleanSchema`, `Date/DateTime` -> `StringSchema` (with format hint), `SingleSelect` -> `EnumSchema`, `MultiSelect` -> `ArraySchema<EnumSchema>`.

#### JSON Schema

```php
use Innobrain\Structure\Converters\JsonSchema\JsonSchemaConvertStrategy;

$strategy = new JsonSchemaConvertStrategy(
    includeNullable: true,
    includeDescriptions: true,
);

$schema = $addressModule->convert($strategy);
// Returns a JsonSchema ObjectType
```

### Writing a Custom Converter

Implement `ConvertStrategy` (or extend `BaseConvertStrategy`) and add `convertField`, `convertModule`, etc. methods matching the DTO class names:

```php
use Innobrain\Structure\Converters\Concerns\BaseConvertStrategy;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\Module;

final readonly class MyConvertStrategy extends BaseConvertStrategy
{
    public function convertModule(Module $module): mixed { /* ... */ }
    public function convertField(Field $field): mixed { /* ... */ }
}

$result = $module->convert(new MyConvertStrategy());
```

## DTOs

All DTOs are readonly and implement `Convertible`.

| DTO | Key Properties |
|-----|---------------|
| `Module` | `key` (FieldConfigurationModule), `label`, `fields` (FieldCollection) |
| `Field` | `key`, `label`, `type` (FieldType), `length`, `permittedValues`, `default`, `filters`, `dependencies`, `compoundFields`, `fieldMeasureFormat` |
| `PermittedValue` | `key`, `label` |
| `FieldDependency` | `dependentFieldKey`, `dependentFieldValue` |
| `FieldFilter` | `name`, `config` |

## Testing

```bash
composer test              # Run tests
composer test-coverage     # Run tests with coverage
composer analyse           # PHPStan static analysis
composer format            # Rector + Pint formatting
```

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Please open an issue or pull request. For bug reports, use the [Bug Report Template](.github/ISSUE_TEMPLATE/bug.yml).

## Security Vulnerabilities

If you discover a security vulnerability, please send an e-mail to Konstantin Auffinger via the email address in `composer.json`. All security vulnerabilities will be promptly addressed.

## Credits

- [Konstantin Auffinger](https://github.com/kauffinger)
- [All Contributors](../../contributors)

Built with [Spatie's Laravel Package Tools](https://github.com/spatie/laravel-package-tools).

## License

The MIT License (MIT). Please see [composer.json](composer.json) for more information.
