# Changelog

All notable changes to `onoffice-structure` will be documented in this file.

## Unreleased

### Breaking

- `Field::$fieldMeasureFormat` is now a `FieldMeasureFormat` enum instead of a string. Unknown formats parse to `null`.
- Module conversions for Laravel rules and JSON Schema skip non-writable fields (hints, dividing lines, compound fields). Use `Field::isWritable()` to check.
- Removed the Prism schema converter and the `prism-php/prism` dependency. Use the JSON Schema converter for structured output.
- Laravel rules no longer emit `required_if` from field dependencies. Dependencies map permitted values to their parent field's value, not fields to values.
- `FieldDependency::$dependentFieldKey` and `$dependentFieldValue` are renamed to `$permittedValueKey` and `$parentFieldValue`. The array converter emits the new names.
- `ConvertStrategy` moved to `Innobrain\Structure\Contracts` and only declares `convertField()` and `convertModule()`. `BaseConvertStrategy`, the `HasConverter` trait and the `convertPermittedValue()`, `convertFieldDependency()` and `convertFieldFilter()` methods are gone. `PermittedValue`, `FieldDependency` and `FieldFilter` no longer implement `Convertible`.
- Requesting an unknown module key from `getModules()` or `retrieveForClient()` throws an `InvalidArgumentException` instead of silently returning nothing.

### Fixed

- JSON Schema enums kept numeric permitted value keys as strings instead of integers.
- A field default of `"0"` is no longer parsed as `null`.

### Added

- `FieldMeasureFormat` enum.
- `Field::isWritable()` and `FieldCollection::writable()`.
- `Field::permittedValueKeys()` returns the permitted value keys as strings, even when they are numeric.
- `Field::withPermittedValuesFor()` narrows a field's permitted values by a parent field value using its dependencies, e.g. `objekttyp` for an `objektart`.
- `getModules()` and `retrieveForClient()` accept `FieldConfigurationModule` cases as well as keys.
- `Field` only requires `key`, `label` and `type`; the remaining constructor parameters have defaults.
- Workbench probe commands for exploring the live field configuration.
- `FieldType::isLayoutOnly()` and `FieldType::isSelect()`.
- `Field::permittedValueLabels()`, `Field::labelFor()`, `Field::permittedValueKeyFor()` and `Field::permits()`.
- `FieldCollection::find()` and `Module::field()` for case-insensitive field lookups, `ModulesCollection::module()` for exact module lookups.
- `FieldCollection::sanitize()` drops single items of multi-select arrays instead of rejecting the whole entry, and `FieldCollection::violations()` reports what was dropped and why.
- `Structure::serializableClasses()` for the `cache.serializable_classes` config.
- `Structure::fake()` / `FieldConfiguration::fake()` and the `FieldFactory` / `ModuleFactory` test helpers.

## v3.1.0 - 2026-09-04

- chore: allow `innobrain/laravel-onoffice-adapter` ^2.0 alongside ^1.10

## v3.0.0 - 2026-07-08

### What's Changed

* refactor: laravel json schema by @kauffinger in https://github.com/innobraingmbh/onoffice-structure/pull/25

**Full Changelog**: https://github.com/innobraingmbh/onoffice-structure/compare/v2.1.0...v3.0.0

## v2.1.0 - 2026-06-26

### What's Changed

* added support for more languages by @andre-onoffice in https://github.com/innobraingmbh/onoffice-structure/pull/24

### New Contributors

* @andre-onoffice made their first contribution in https://github.com/innobraingmbh/onoffice-structure/pull/24

**Full Changelog**: https://github.com/innobraingmbh/onoffice-structure/compare/v2.0.0...v2.1.0

## v2.0.0 - 2026-06-15

### What's Changed

* feat: add realDataTypes support and five new FieldType cases by @kauffinger in https://github.com/innobraingmbh/onoffice-structure/pull/23

**Full Changelog**: https://github.com/innobraingmbh/onoffice-structure/compare/v1.4.0...v2.0.0

## v1.4.0 - 2026-04-09

### What's Changed

* chore: improve docs by @kauffinger in https://github.com/innobraingmbh/onoffice-structure/pull/16
* Support Laravel v13 by @danielgnh in https://github.com/innobraingmbh/onoffice-structure/pull/19

### New Contributors

* @danielgnh made their first contribution in https://github.com/innobraingmbh/onoffice-structure/pull/19

**Full Changelog**: https://github.com/innobraingmbh/onoffice-structure/compare/v1.3.0...v1.4.0

## v1.3.0 - 2026-02-07

### What's Changed

* chore(deps): bump actions/checkout from 5 to 6 by @dependabot[bot] in https://github.com/innobraingmbh/onoffice-structure/pull/13
* chore(deps): bump dependabot/fetch-metadata from 2.4.0 to 2.5.0 by @dependabot[bot] in https://github.com/innobraingmbh/onoffice-structure/pull/15
* Add helper functions for field collections by @Katalam in https://github.com/innobraingmbh/onoffice-structure/pull/14

**Full Changelog**: https://github.com/innobraingmbh/onoffice-structure/compare/v1.2.0...v1.3.0

## v1.2.0 - 2025-11-04

### What's Changed

* feat: enable manual override of required fields if necessary by @kauffinger in https://github.com/innobraingmbh/onoffice-structure/pull/12

**Full Changelog**: https://github.com/innobraingmbh/onoffice-structure/compare/v1.1.2...v1.2.0

## v1.1.2 - 2025-11-02

* chore: support italian

**Full Changelog**: https://github.com/innobraingmbh/onoffice-structure/compare/v1.1.1...v1.1.2

## v1.1.1 - 2025-11-02

* chore: docblocks for facades

**Full Changelog**: https://github.com/innobraingmbh/onoffice-structure/compare/v1.1.0...v1.1.1

## v1.1.0 - 2025-11-02

### What's Changed

* chore(deps): bump stefanzweifel/git-auto-commit-action from 6 to 7 by @dependabot[bot] in https://github.com/innobraingmbh/onoffice-structure/pull/10
* feat: allow passing the user language by @kauffinger in https://github.com/innobraingmbh/onoffice-structure/pull/11

### New Contributors

* @kauffinger made their first contribution in https://github.com/innobraingmbh/onoffice-structure/pull/11

**Full Changelog**: https://github.com/innobraingmbh/onoffice-structure/compare/v1.0.1...v1.1.0

## v1.0.1 - 2025-10-18

* allow prism up to v1

**Full Changelog**: https://github.com/innobraingmbh/onoffice-structure/compare/v1.0.0...v1.0.1

## v1.0.0 - 2025-10-01

### What's Changed

* Add JsonSchema Converter by @Katalam in https://github.com/innobraingmbh/onoffice-structure/pull/6
* [1.x] Structure changes by @Katalam in https://github.com/innobraingmbh/onoffice-structure/pull/7

### New Contributors

* @Katalam made their first contribution in https://github.com/innobraingmbh/onoffice-structure/pull/6

**Full Changelog**: https://github.com/innobraingmbh/onoffice-structure/compare/v0.2.0...v1.0.0

## v0.2.0 - 2025-09-21

* feat: filter fields

**Full Changelog**: https://github.com/innobraingmbh/onoffice-structure/compare/v0.1.3...v0.2.0

## v0.1.3 - 2025-09-19

**Full Changelog**: https://github.com/innobraingmbh/onoffice-structure/compare/v0.0.2...v0.1.3

## v0.0.2 - 2025-09-19

### What's Changed

* chore(deps): bump aglipanci/laravel-pint-action from 2.5 to 2.6 by @dependabot[bot] in https://github.com/innobraingmbh/onoffice-structure/pull/3

**Full Changelog**: https://github.com/innobraingmbh/onoffice-structure/compare/v0.0.1...v0.0.2

## v0.0.1 - 2025-05-24

### What's Changed

* Bump dependabot/fetch-metadata from 2.3.0 to 2.4.0 by @dependabot in https://github.com/innobraingmbh/onoffice-structure/pull/1

### New Contributors

* @dependabot made their first contribution in https://github.com/innobraingmbh/onoffice-structure/pull/1

**Full Changelog**: https://github.com/innobraingmbh/onoffice-structure/commits/v0.0.1
