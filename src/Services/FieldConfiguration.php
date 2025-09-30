<?php

declare(strict_types=1);

namespace Innobrain\Structure\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\OnOfficeAdapter\Facades\FieldRepository;
use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\DTOs\Field;
use Innobrain\Structure\DTOs\FieldDependency;
use Innobrain\Structure\DTOs\FieldFilter;
use Innobrain\Structure\DTOs\Module;
use Innobrain\Structure\DTOs\PermittedValue;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\FieldType;

use function is_array;
use function is_string;
use function ucfirst;

class FieldConfiguration
{
    /**
     * Retrieve the field configuration for a given client.
     */
    public function retrieveForClient(OnOfficeApiCredentials $credentials, array $only = []): ModulesCollection
    {
        $moduleCases = FieldConfigurationModule::cases();
        $moduleValues = collect($moduleCases)
            ->map(fn (FieldConfigurationModule $module) => $module->value)
            ->toArray();

        if (count($only) > 0) {
            $moduleValues = array_filter($moduleValues, fn (string $value) => in_array($value, $only, true));
        }

        $rawModulesData = FieldRepository::query()
            ->withCredentials($credentials)
            ->withModules($moduleValues)
            ->parameters([
                'labels' => true,
                'language' => 'DEU',
                'showfieldfilters' => true,
                'showfielddependencies' => true,
                'showFieldMeasureFormat' => true,
            ])
            ->get();

        $modules = new ModulesCollection;

        foreach ($rawModulesData as $moduleKey => $moduleData) {
            if (! isset($moduleData['elements'])) {
                continue;
            }
            if (! is_array($moduleData['elements'])) {
                continue;
            }

            $moduleKey = $moduleData['id'] ?? $moduleKey;
            $moduleEnum = FieldConfigurationModule::tryFrom((string) $moduleKey);

            if (! $moduleEnum) {
                continue; // skip unknown modules
            }

            $moduleLabel = Arr::get($moduleData, 'label', ucfirst((string) $moduleKey));
            $parsedFields = $this->parseFields(Arr::get($moduleData, 'elements', []));

            $modules->put($moduleEnum->value, new Module(
                key: $moduleEnum,
                label: $moduleLabel,
                fields: $parsedFields,
            ));
        }

        return $modules;
    }

    /**
     * @param  array<string, mixed>  $fieldsData
     */
    private function parseFields(array $fieldsData): FieldCollection
    {
        $fields = new FieldCollection;

        foreach ($fieldsData as $fieldKey => $fieldData) {
            if (! is_array($fieldData)) {
                continue;
            }
            if ($fieldKey === 'label') {
                continue;
            }
            $fieldType = FieldType::tryFrom((string) Arr::get($fieldData, 'type', ''));

            if (! $fieldType) {
                continue; // unknown field type
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
                fieldMeasureFormat: Arr::get($fieldData, 'fieldMeasureFormat') ? (string) Arr::get($fieldData, 'fieldMeasureFormat') : null,
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
                label: (string) $label,
            ));
        }

        return $permittedValues;
    }

    private function parseFieldFilters(mixed $filtersData): Collection
    {
        if (! is_array($filtersData)) {
            return new Collection;
        }

        $filters = new Collection;

        foreach ($filtersData as $filterName => $filterConfig) {
            $configCollection = new Collection;

            if (is_array($filterConfig)) {
                foreach ($filterConfig as $key => $value) {
                    $configCollection->put(
                        (string) $key,
                        is_array($value) ? $value : [(string) $value],
                    );
                }
            }

            $filters->put((string) $filterName, new FieldFilter(
                name: (string) $filterName,
                config: $configCollection,
            ));
        }

        return $filters;
    }

    private function parseFieldDependencies(mixed $dependenciesData): Collection
    {
        $dependencies = new Collection;

        if (is_array($dependenciesData)) {
            foreach ($dependenciesData as $key => $value) {
                if (is_string($key) && is_string($value)) {
                    $dependencies->add(new FieldDependency(
                        dependentFieldKey: $key,
                        dependentFieldValue: $value,
                    ));
                }
            }
        }

        return $dependencies;
    }
}
