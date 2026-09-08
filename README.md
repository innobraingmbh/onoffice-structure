# onOffice Structure Extractor for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/innobraingmbh/onoffice-structure.svg?style=flat-square)](https://packagist.org/packages/innobraingmbh/onoffice-structure)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/innobraingmbh/onoffice-structure/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/innobraingmbh/onoffice-structure/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/innobraingmbh/onoffice-structure/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/innobraingmbh/onoffice-structure/actions?query=workflow%3A"Fix+PHP+Code+Style+Issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/innobraingmbh/onoffice-structure.svg?style=flat-square)](https://packagist.org/packages/innobraingmbh/onoffice-structure)

Extract and work with the onOffice enterprise field configuration (Modul- und Feldkonfiguration) in Laravel. The package fetches configurations via [innobrain/laravel-onoffice-adapter](https://github.com/innobraingmbh/laravel-onoffice-adapter), transforms them into readonly DTOs, and converts them into various output formats using a strategy pattern.

## Features

- Fetch field configurations for all onOffice modules (Address, Estate, AgentsLog, Calendar, Email, File, News, Intranet, Project, Task, User)
- Readonly DTOs for Modules, Fields, Permitted Values, Dependencies, and Filters
- Convert to arrays, Laravel validation rules, or JSON Schema
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

// Fetch specific modules in a specific language. Enum cases and plain keys
// both work; an unknown key throws an InvalidArgumentException.
$modules = Structure::forClient($credentials)->getModules(
    only: [FieldConfigurationModule::Address, 'estate'],
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

### Writable Fields and Dependent Permitted Values

Hints, dividing lines and compound fields cannot be written to the API. `FieldCollection::writable()` drops them:

```php
$writable = $estateModule->fields->writable();
```

Some permitted values depend on the value of a parent field. In the estate module the `objekttyp` values depend on `objektart`. `Field::withPermittedValuesFor()` narrows a field to the permitted values allowed for a parent value:

```php
$objekttyp = $estateModule->fields->get('objekttyp')->withPermittedValuesFor('haus');
// $objekttyp->permittedValues now only contains house types such as "bungalow"
```

### Looking Up Fields and Permitted Values

The API accepts field keys in any casing, so field lookups fall back to a case-insensitive match. Module keys are fixed, so `module()` matches exactly:

```php
$field = $estateModule->field('ort');            // finds "Ort"
$field = $modules->module('estate')?->field('ort');
```

`Field` resolves permitted values by key or label, which is handy for user or AI input:

```php
$objektart = $estateModule->field('objektart');

$objektart->permittedValueLabels();              // ['haus' => 'Haus', 'wohnung' => 'Wohnung', ...]
$objektart->labelFor('haus');                    // 'Haus'
$objektart->permittedValueKeyFor('Wohnung');     // 'wohnung'
$objektart->permits('villa');                    // false
```

### Sanitizing Input Data

`sanitize()` removes keys that don't match known fields and values that are not permitted. Items of a multi-select array are removed individually:

```php
$sanitized = $addressModule->fields->sanitize(collect([
    'Email' => 'test@example.com',
    'unknownField' => 'value',      // removed: not in field collection
    'Beziehung' => ['1', '999'],    // '999' removed: not a permitted value
]));
```

`violations()` reports what `sanitize()` would drop, as `FieldViolation` DTOs with a `ViolationReason`:

```php
foreach ($addressModule->fields->violations($input) as $violation) {
    Log::info("{$violation->fieldKey}: {$violation->reason->value}", ['value' => $violation->value]);
}
```

### Caching

The DTOs serialize cleanly, so a `ModulesCollection` can be cached as-is. If your cache store restricts unserializable classes, allow the package's classes in `config/cache.php`:

```php
'serializable_classes' => \Innobrain\Structure\Services\Structure::serializableClasses(),
```

### Converting Data

`Module`, `Field` and `ModulesCollection` implement `Convertible` and can be transformed using a `ConvertStrategy`.

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

Module conversions for Laravel rules and JSON Schema only include writable fields (`FieldCollection::writable()`). Hints and dividing lines carry no data, and compound fields such as `Plz-Ort` are set through their individual fields, so they are left out. Converting such a field directly still works.

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

Implement `ConvertStrategy` with `convertField` and `convertModule`:

```php
use Innobrain\Structure\Contracts\ConvertStrategy;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\Module;

final readonly class MyConvertStrategy implements ConvertStrategy
{
    public function convertModule(Module $module): mixed { /* ... */ }
    public function convertField(Field $field): mixed { /* ... */ }
}

$result = $module->convert(new MyConvertStrategy());
```

## Testing Your Application

`Structure::fake()` (or `FieldConfiguration::fake()`) replaces the API call with canned modules for the rest of the test. The factories build DTOs without spelling out every constructor argument:

```php
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Facades\Structure;
use Innobrain\Structure\Testing\FieldFactory;
use Innobrain\Structure\Testing\ModuleFactory;

$fake = Structure::fake([
    ModuleFactory::new(FieldConfigurationModule::Estate)->fields(
        FieldFactory::new('Ort'),
        FieldFactory::singleSelect('objektart', ['haus' => 'Haus', 'wohnung' => 'Wohnung'])->default('haus'),
        FieldFactory::singleSelect('objekttyp', ['einfamilienhaus' => 'Einfamilienhaus'])
            ->dependencies(['einfamilienhaus' => 'haus']),
    )->make(),
]);

// ... run the code under test ...

$fake->assertRetrieved(FieldConfigurationModule::Estate);
```

The fake narrows to the requested modules and still throws for unknown module keys. The same modules are returned for every language.

Both factories are `Conditionable`, so `->when($condition, fn ($field) => $field->default('haus'))` works as on query builders.

## DTOs

All DTOs are readonly. `Field` only requires `key`, `label` and `type`; the other properties default to `null` or an empty collection.

| DTO | Key Properties |
|-----|---------------|
| `Module` | `key` (FieldConfigurationModule), `label`, `fields` (FieldCollection) |
| `Field` | `key`, `label`, `type` (FieldType), `length`, `permittedValues`, `default`, `filters`, `dependencies`, `compoundFields`, `fieldMeasureFormat` (FieldMeasureFormat) |
| `PermittedValue` | `key`, `label` |
| `FieldDependency` | `permittedValueKey`, `parentFieldValue` (the permitted value is only available when the parent field holds that value, e.g. `objekttyp` => `objektart`) |
| `FieldFilter` | `name`, `config` |
| `FieldViolation` | `fieldKey`, `value`, `reason` (ViolationReason) |

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
