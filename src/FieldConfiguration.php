<?php

declare(strict_types=1);

namespace Innobrain\Structure;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Innobrain\OnOfficeAdapter\Facades\FieldRepository;
use Innobrain\Structure\DTOs\Field;
use Innobrain\Structure\DTOs\FieldDependencies;
use Innobrain\Structure\DTOs\FieldDependency;
use Innobrain\Structure\DTOs\FieldFilter;
use Innobrain\Structure\DTOs\FieldFilters;
use Innobrain\Structure\DTOs\Module;
use Innobrain\Structure\DTOs\PermittedValue;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\FieldType;

class FieldConfiguration
{
    /**
     * @return Collection<string, Module>
     */
    public function retrieveForClient(string $token, string $secret, string $apiClaim = ''): Collection
    {
        $moduleCases = FieldConfigurationModule::cases();
        $moduleValues = collect($moduleCases)->map(fn (FieldConfigurationModule $module) => $module->value)->toArray();

        $rawModulesData = FieldRepository::query()
            ->withCredentials($token, $secret, $apiClaim)
            ->withModules($moduleValues)
            ->parameters([
                'labels' => true,
                'language' => 'DEU',
                'showfieldfilters' => true,
                'showfielddependencies' => true,
                'showFieldMeasureFormat' => true,
            ])
            ->get();

        $modules = new Collection;

        foreach ($rawModulesData as $moduleKey => $moduleData) {
            if (! isset($moduleData['elements']) || ! is_array($moduleData['elements'])) {
                continue;
            }

            $moduleKey = $moduleData['id'] ?? $moduleKey;
            $moduleEnum = FieldConfigurationModule::tryFrom((string) $moduleKey);
            if (! $moduleEnum) {
                // Or handle error: log, skip, throw exception
                continue;
            }

            $moduleLabel = Arr::get($moduleData, 'label', ucfirst((string) $moduleKey));
            $parsedFields = $this->parseFields(Arr::get($moduleData, 'elements', []));

            $modules->put($moduleEnum->value, new Module(
                key: $moduleEnum,
                label: $moduleLabel,
                fields: $parsedFields
            ));
        }

        return $modules;
    }

    /**
     * @param  array<string, mixed>  $fieldsData
     * @return Collection<string, Field>
     */
    private function parseFields(array $fieldsData): Collection
    {
        $fields = new Collection;
        foreach ($fieldsData as $fieldKey => $fieldData) {
            if (! is_array($fieldData) || $fieldKey === 'label') { // 'label' at module level, not a field
                continue;
            }

            $fieldType = FieldType::tryFrom((string) Arr::get($fieldData, 'type', ''));
            if (! $fieldType) {
                // Handle unknown field type: skip, log, default, or throw
                continue;
            }

            $fields->put((string) $fieldKey, new Field(
                key: (string) $fieldKey,
                label: (string) Arr::get($fieldData, 'label', ucfirst((string) $fieldKey)),
                type: $fieldType,
                length: Arr::get($fieldData, 'length') ? (int) Arr::get($fieldData, 'length') : null,
                permittedValues: $this->parsePermittedValues(Arr::get($fieldData, 'permittedvalues', [])),
                default: Arr::get($fieldData, 'default') ? (string) Arr::get($fieldData, 'default') : null,
                filters: $this->parseFieldFilters(Arr::get($fieldData, 'filters', [])),
                dependencies: $this->parseFieldDependencies(Arr::get($fieldData, 'dependencies', [])),
                compoundFields: collect(Arr::get($fieldData, 'compoundFields', [])),
                fieldMeasureFormat: Arr::get($fieldData, 'fieldMeasureFormat') ? (string) Arr::get($fieldData, 'fieldMeasureFormat') : null
            ));
        }

        return $fields;
    }

    /**
     * @return Collection<string, PermittedValue>
     */
    private function parsePermittedValues(mixed $permittedValuesData): Collection
    {
        if (! is_array($permittedValuesData)) {
            return new Collection;
        }

        $permittedValues = new Collection;
        foreach ($permittedValuesData as $key => $label) {
            $permittedValues->put((string) $key, new PermittedValue(
                key: (string) $key,
                label: (string) $label
            ));
        }

        return $permittedValues;
    }

    private function parseFieldFilters(mixed $filtersData): FieldFilters
    {
        if (! is_array($filtersData)) {
            return new FieldFilters(new Collection);
        }

        $filters = new Collection;
        foreach ($filtersData as $filterName => $filterConfig) {
            // Ensure filterConfig is an array before passing to collect
            $configCollection = new Collection;
            if (is_array($filterConfig)) {
                foreach ($filterConfig as $key => $value) {
                    $configCollection->put((string) $key, is_array($value) ? $value : [(string) $value]);
                }
            }

            $filters->put((string) $filterName, new FieldFilter(
                name: (string) $filterName,
                config: $configCollection
            ));
        }

        return new FieldFilters($filters);
    }

    private function parseFieldDependencies(mixed $dependenciesData): FieldDependencies
    {
        // As per critical note, this parsing is simplified and might need refinement.
        // The example showed a map 'dependencies' => [ 'stellplatz' => 'parken', ... ]
        // Current FieldDependency DTO is {dependentFieldKey, dependentFieldValue}
        // This simple map structure suggests FieldDependency might be:
        // new FieldDependency(dependentFieldKey: $key_from_map, dependentFieldValue: $value_from_map)
        // The current FieldDependencies DTO is a Collection<FieldDependency>.
        // If the API truly returns a simple map, the DTOs might be misaligned or the parsing here is too simplistic.
        // For now, assuming dependenciesData could be a list of such key-value pairs which can be mapped to FieldDependency.
        // If it's a direct map like ['fieldA' => 'valueA', 'fieldB' => 'valueB'], this parsing will need adjustment.

        $dependencies = new Collection;
        if (is_array($dependenciesData)) {
            foreach ($dependenciesData as $key => $value) {
                // This assumes the $key is the 'dependentFieldKey' and $value is 'dependentFieldValue'
                // This is a common pattern but needs verification against actual API response structure.
                if (is_string($key) && is_string($value)) {
                    $dependencies->add(new FieldDependency(
                        dependentFieldKey: $key,
                        dependentFieldValue: $value
                    ));
                }
                // If $dependenciesData is an array of arrays, e.g., [['field' => 'key1', 'value' => 'val1'], ...]
                // then the parsing logic would be different.
            }
        }

        return new FieldDependencies($dependencies);
    }
}
