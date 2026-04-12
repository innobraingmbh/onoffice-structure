<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\Array;

use Illuminate\Support\Collection;
use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\FieldDependency;
use Innobrain\Structure\Dtos\FieldFilter;
use Innobrain\Structure\Dtos\Module;
use Innobrain\Structure\Dtos\PermittedValue;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\FieldType;

final readonly class ArrayHydrator
{
    /** @param array<string, mixed> $data */
    public static function hydrate(array $data): ModulesCollection
    {
        $modules = [];

        foreach ($data as $moduleData) {
            $modules[] = self::hydrateModule($moduleData);
        }

        return new ModulesCollection($modules);
    }

    /** @param array<string, mixed> $moduleData */
    private static function hydrateModule(array $moduleData): Module
    {
        $fields = new FieldCollection;

        foreach (data_get($moduleData, 'fields', []) as $fieldKey => $fieldData) {
            $fields[$fieldKey] = self::hydrateField($fieldData);
        }

        return new Module(
            key: FieldConfigurationModule::from(data_get($moduleData, 'key')),
            label: data_get($moduleData, 'label', ''),
            fields: $fields,
        );
    }

    /** @param array<string, mixed> $fieldData */
    private static function hydrateField(array $fieldData): Field
    {
        $permittedValues = new Collection;
        foreach (data_get($fieldData, 'permittedValues', []) as $pvKey => $pvData) {
            $permittedValues[$pvKey] = new PermittedValue(
                key: data_get($pvData, 'key', ''),
                label: data_get($pvData, 'label', ''),
            );
        }

        $filters = new Collection;
        foreach (data_get($fieldData, 'filters', []) as $filterKey => $filterData) {
            $filters[$filterKey] = new FieldFilter(
                name: data_get($filterData, 'name', ''),
                config: collect(data_get($filterData, 'config', [])),
            );
        }

        $dependencies = new Collection;
        foreach (data_get($fieldData, 'dependencies', []) as $depData) {
            $dependencies[] = new FieldDependency(
                dependentFieldKey: data_get($depData, 'dependentFieldKey', ''),
                dependentFieldValue: data_get($depData, 'dependentFieldValue', ''),
            );
        }

        return new Field(
            key: data_get($fieldData, 'key', ''),
            label: data_get($fieldData, 'label', ''),
            type: FieldType::from(data_get($fieldData, 'type')),
            length: data_get($fieldData, 'length'),
            permittedValues: $permittedValues,
            default: data_get($fieldData, 'default'),
            filters: $filters,
            dependencies: $dependencies,
            compoundFields: collect(data_get($fieldData, 'compoundFields', [])),
            fieldMeasureFormat: data_get($fieldData, 'fieldMeasureFormat'),
        );
    }
}
