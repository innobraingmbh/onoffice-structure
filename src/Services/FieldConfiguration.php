<?php

declare(strict_types=1);

namespace Innobrain\Structure\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\OnOfficeAdapter\Exceptions\OnOfficeException;
use Innobrain\OnOfficeAdapter\Facades\FieldRepository;
use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\FieldDependency;
use Innobrain\Structure\Dtos\FieldFilter;
use Innobrain\Structure\Dtos\Module;
use Innobrain\Structure\Dtos\PermittedValue;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\FieldMeasureFormat;
use Innobrain\Structure\Enums\FieldType;
use Innobrain\Structure\Enums\Language;
use InvalidArgumentException;

use function is_array;
use function is_string;

class FieldConfiguration
{
    /**
     * Retrieve the field configuration for a given client.
     *
     * @param  array<int, FieldConfigurationModule|string>  $only  Empty means all modules
     *
     * @throws OnOfficeException
     * @throws InvalidArgumentException when a module key is unknown
     */
    public function retrieveForClient(OnOfficeApiCredentials $credentials, array $only = [], Language $language = Language::German): ModulesCollection
    {
        $moduleValues = FieldConfigurationModule::values($only);

        $rawModulesData = FieldRepository::query()
            ->withCredentials($credentials)
            ->withModules($moduleValues)
            ->parameters([
                'labels' => true,
                'language' => $language->value,
                'showfieldfilters' => true,
                'showfielddependencies' => true,
                'showFieldMeasureFormat' => true,
                'realDataTypes' => true,
            ])
            ->get();

        $modules = new ModulesCollection;

        foreach ($rawModulesData as $moduleKey => $moduleData) {
            $module = $this->parseModule((string) ($moduleData['id'] ?? $moduleKey), $moduleData);

            if ($module instanceof Module) {
                $modules->put($module->key->value, $module);
            }
        }

        return $modules;
    }

    /**
     * The API only returns module labels in German, so the module key is
     * used as an English label instead.
     *
     * @param  array<string, mixed>  $moduleData
     */
    private function parseModule(string $moduleKey, array $moduleData): ?Module
    {
        $module = FieldConfigurationModule::tryFrom($moduleKey);
        $fieldsData = Arr::get($moduleData, 'elements');

        if (! $module instanceof FieldConfigurationModule || ! is_array($fieldsData)) {
            return null;
        }

        return new Module(
            key: $module,
            label: Str::ucfirst($moduleKey),
            fields: $this->parseFields($fieldsData),
        );
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

            /** @var array<int, string> $compoundFields */
            $compoundFields = Arr::get($fieldData, 'compoundFields', []);

            $length = (int) Arr::get($fieldData, 'length');
            $default = Arr::get($fieldData, 'default');

            $fields->put($fieldKey, new Field(
                key: $fieldKey,
                label: (string) Arr::get($fieldData, 'label', Str::ucfirst($fieldKey)),
                type: $fieldType,
                length: $length > 0 ? $length : null,
                permittedValues: $this->parsePermittedValues(Arr::get($fieldData, 'permittedvalues', [])),
                default: blank($default) ? null : (string) $default,
                filters: $this->parseFieldFilters(Arr::get($fieldData, 'filters', [])),
                dependencies: $this->parseFieldDependencies(Arr::get($fieldData, 'dependencies', [])),
                compoundFields: collect($compoundFields),
                fieldMeasureFormat: FieldMeasureFormat::tryFrom((string) Arr::get($fieldData, 'fieldMeasureFormat', '')),
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

    /**
     * @return Collection<string, FieldFilter>
     */
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

    /**
     * @return Collection<int, FieldDependency>
     */
    private function parseFieldDependencies(mixed $dependenciesData): Collection
    {
        $dependencies = new Collection;

        if (is_array($dependenciesData)) {
            foreach ($dependenciesData as $key => $value) {
                if (is_string($key) && is_string($value)) {
                    $dependencies->add(new FieldDependency(
                        permittedValueKey: $key,
                        parentFieldValue: $value,
                    ));
                }
            }
        }

        return $dependencies;
    }
}
