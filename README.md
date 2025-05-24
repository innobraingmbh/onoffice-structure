# onOffice Structure Extractor for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/innobraingmbh/onoffice-structure.svg?style=flat-square)](https://packagist.org/packages/innobraingmbh/onoffice-structure)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/innobraingmbh/onoffice-structure/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/innobraingmbh/onoffice-structure/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/innobraingmbh/onoffice-structure/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/innobraingmbh/onoffice-structure/actions?query=workflow%3A"Fix+PHP+Code+Style+Issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/innobraingmbh/onoffice-structure.svg?style=flat-square)](https://packagist.org/packages/innobraingmbh/onoffice-structure)

This package provides a structured way to extract and work with the onOffice enterprise field configuration (Modul- und Feldkonfiguration) within a Laravel application. It fetches the configuration via the [innobrain/laravel-onoffice-adapter](https://github.com/innobraingmbh/laravel-onoffice-adapter) and transforms it into a collection of Data Transfer Objects (DTOs). These DTOs can then be converted into various formats, such as arrays or Laravel validation rules, using a flexible strategy pattern.

## Features

*   Fetch onOffice field configurations for various modules (Address, Estate, etc.).
*   Structured DTOs for Modules, Fields, Permitted Values, Dependencies, and Filters.
*   Convert DTOs to arrays.
*   Convert DTOs to Laravel validation rules.
*   Extensible converter strategy pattern.
*   Facade for easy access.
*   Configuration file for future extensions.
*   Includes a basic Artisan command.

## Installation

You can install the package via Composer:

```bash
composer require innobraingmbh/onoffice-structure
```

## Configuration

You can publish the configuration file using:

```bash
php artisan vendor:publish --provider="Innobrain\Structure\StructureServiceProvider" --tag="onoffice-structure-config"
```

This will publish the `onoffice-structure.php` file to your `config` directory. Currently, this file is a placeholder for future configuration options.

You can publish the migration file using:
```bash
php artisan vendor:publish --provider="Innobrain\Structure\StructureServiceProvider" --tag="onoffice-structure-migrations"
```
This will publish the `create_onoffice_structure_table.php.stub` migration. _(Note: The package's current functionality does not heavily rely on this table, but it's provided for potential future use or custom extensions.)_

You can publish the views (currently a `.gitkeep` placeholder) using:
```bash
php artisan vendor:publish --provider="Innobrain\Structure\StructureServiceProvider" --tag="onoffice-structure-views"
```

## Usage

The primary way to interact with the package is through the `Structure` facade or by injecting the `Innobrain\Structure\Structure` class.

### Fetching Structure Data

To fetch the field configuration, you need to provide `OnOfficeApiCredentials` from the `innobrain/laravel-onoffice-adapter` package.

```php
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Facades\Structure;
use Innobrain\Structure\Collections\ModulesCollection;

// Instantiate your API credentials
$credentials = new OnOfficeApiCredentials('your-token', 'your-secret');

// Fetch the structure
$modulesCollection = Structure::forClient($credentials)->get();

// $modulesCollection is an instance of Innobrain\Structure\Collections\ModulesCollection
// which extends Illuminate\Support\Collection. It contains Module DTOs.

foreach ($modulesCollection as $moduleKey => $module) {
    echo "Module: " . $module->label . " (" . $module->key->value . ")\n";
    foreach ($module->fields as $fieldKey => $field) {
        echo "  Field: " . $field->label . " (" . $field->key . ") - Type: " . $field->type->value . "\n";
    }
}
```

You can also access specific modules:

```php
use Innobrain\Structure\Enums\FieldConfigurationModule;

$addressModule = $modulesCollection->get(FieldConfigurationModule::Address->value);
if ($addressModule) {
    // Work with the address module
}
```

### Direct Field Configuration Access

If you only need to retrieve the configuration without the `Structure` wrapper, you can use the `FieldConfiguration` facade or class:

```php
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Facades\FieldConfiguration;

$credentials = new OnOfficeApiCredentials('your-token', 'your-secret');
$modules = FieldConfiguration::retrieveForClient($credentials);
```

### Converting Data

The DTOs and `ModulesCollection` implement the `Convertible` interface, allowing them to be transformed using a `ConvertStrategy`.

#### 1. Array Conversion (`ArrayConvertStrategy`)

This strategy converts the DTOs into nested arrays.

```php
use Innobrain\Structure\Converters\ArrayConvertStrategy;

// Convert the entire collection of modules
$strategy = new ArrayConvertStrategy(dropEmpty: false); // or true to remove null/empty values
$arrayOfModules = $modulesCollection->convert($strategy);

// Convert a single module
$addressModuleArray = $addressModule->convert($strategy);

// Convert a single field
$emailField = $addressModule->fields->get('Email');
$emailFieldArray = $emailField->convert($new ArrayConvertStrategy());
```

The `ArrayConvertStrategy` constructor accepts a `bool $dropEmpty` (default `false`). If `true`, it will recursively remove keys with `null`, empty string, or empty array values from the output.

#### 2. Laravel Validation Rules Conversion (`LaravelRulesConvertStrategy`)

This strategy converts module or field DTOs into Laravel validation rules.

```php
use Innobrain\Structure\Converters\LaravelRulesConvertStrategy;

// For a specific module (e.g., Address)
$addressModule = $modulesCollection->get(FieldConfigurationModule::Address->value);

// Get rules as pipe-separated strings (default), including 'nullable' for fields without defaults
$strategyPipe = new LaravelRulesConvertStrategy(pipeSyntax: true, includeNullable: true);
$addressValidationRules = $addressModule->convert($strategyPipe);
/*
Example output for $addressValidationRules:
[
    'KdNr' => 'integer|nullable',
    'Email' => 'string|max:100|nullable',
    'Beziehung' => 'array|distinct|nullable',
    'Beziehung.*' => 'in:0,1,2,3', // if Beziehung is a multiselect
    // ... other fields
]
*/

// Get rules as arrays, excluding 'nullable' by default
$strategyArray = new LaravelRulesConvertStrategy(pipeSyntax: false, includeNullable: false);
$addressValidationRulesArray = $addressModule->convert($strategyArray);
/*
Example output for $addressValidationRulesArray:
[
    'KdNr' => ['integer'],
    'Email' => ['string', 'max:100'],
    'Beziehung' => ['array', 'distinct'],
    'Beziehung.*' => ['in:0,1,2,3'],
    // ... other fields
]
*/

// Convert a single field
$emailField = $addressModule->fields->get('Email');
$emailFieldRules = $emailField->convert($strategyPipe); // e.g., 'string|max:100|nullable'
```

Constructor options for `LaravelRulesConvertStrategy`:
*   `bool $pipeSyntax` (default `true`): If `true`, rules are returned as a pipe-separated string (e.g., `'string|max:80|nullable'`). If `false`, rules are an array (e.g., `['string', 'max:80', 'nullable']`).
*   `bool $includeNullable` (default `true`): If `true`, the `'nullable'` rule is automatically added to fields that do not have a default value defined in the onOffice configuration.

## Data Transfer Objects (DTOs)

The package uses the following DTOs to represent the structure:

*   `Innobrain\Structure\DTOs\Module`: Represents a module (e.g., Address, Estate).
    *   `key`: `FieldConfigurationModule` (enum)
    *   `label`: `string`
    *   `fields`: `Illuminate\Support\Collection` of `Field` DTOs.
*   `Innobrain\Structure\DTOs\Field`: Represents a field within a module.
    *   `key`: `string`
    *   `label`: `string`
    *   `type`: `FieldType` (enum)
    *   `length`: `?int`
    *   `permittedValues`: `Illuminate\Support\Collection` of `PermittedValue` DTOs.
    *   `default`: `?string`
    *   `filters`: `Illuminate\Support\Collection` of `FieldFilter` DTOs.
    *   `dependencies`: `Illuminate\Support\Collection` of `FieldDependency` DTOs.
    *   `compoundFields`: `Illuminate\Support\Collection` of strings.
    *   `fieldMeasureFormat`: `?string`
*   `Innobrain\Structure\DTOs\PermittedValue`: Represents a permitted value for select fields.
    *   `key`: `string`
    *   `label`: `string`
*   `Innobrain\Structure\DTOs\FieldDependency`: Represents a dependency between fields.
    *   `dependentFieldKey`: `string`
    *   `dependentFieldValue`: `string`
*   `Innobrain\Structure\DTOs\FieldFilter`: Represents a filter configuration for a field.
    *   `name`: `string`
    *   `config`: `Illuminate\Support\Collection`

All DTOs implement `Innobrain\Structure\Contracts\Convertible`.

## Enums

*   `Innobrain\Structure\Enums\FieldConfigurationModule`: Defines the available onOffice modules (e.g., `Address`, `Estate`).
*   `Innobrain\Structure\Enums\FieldType`: Defines the types of fields (e.g., `VarChar`, `Integer`, `MultiSelect`).

## Collections

*   `Innobrain\Structure\Collections\ModulesCollection`: A custom collection that extends `Illuminate\Support\Collection` and holds `Module` DTOs. It also implements `Convertible`.

## Artisan Command

The package includes a basic Artisan command:

```bash
php artisan onoffice-structure
```
Currently, this command has a placeholder implementation.

## Testing

To run the test suite:

```bash
composer test
```

To run tests with coverage:

```bash
composer test-coverage
```

To run static analysis (PHPStan):
```bash
composer analyse
```

To format code (Rector & Pint):
```bash
composer format
```

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](https://github.com/innobraingmbh/onoffice-structure/.github/CONTRIBUTING.md) (if available) or open an issue/pull request.
For bug reports, please use the [Bug Report Template](.github/ISSUE_TEMPLATE/bug.yml).

## Security Vulnerabilities

If you discover a security vulnerability within this package, please send an e-mail to Konstantin Auffinger via the email address in `composer.json`. All security vulnerabilities will be promptly addressed.

## Credits

-   [Konstantin Auffinger](https://github.com/kauffinger)
-   All Contributors

This package was generated using [Spatie's Laravel Package Tools](https://github.com/spatie/laravel-package-tools).

## License

This is proprietary to InnoBrain GmbH & Konstantin Auffinger. There is no license. It is only source-available.
