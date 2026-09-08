<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\Array;

use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\FieldDependency;
use Innobrain\Structure\Dtos\FieldFilter;
use Innobrain\Structure\Dtos\PermittedValue;

trait ConvertsFieldToArray
{
    /**
     * @return array<string, mixed>
     */
    public function convertField(Field $field): array
    {
        return $this->normalize([
            'key' => $field->key,
            'label' => $field->label,
            'type' => $field->type->value,
            'length' => $field->length,
            'permittedValues' => $field->permittedValues
                ->map(fn (PermittedValue $permittedValue): array => $this->convertPermittedValue($permittedValue))
                ->all(),
            'default' => $field->default,
            'filters' => $field->filters
                ->map(fn (FieldFilter $filter): array => $this->convertFieldFilter($filter))
                ->all(),
            'dependencies' => $field->dependencies
                ->map(fn (FieldDependency $dependency): array => $this->convertFieldDependency($dependency))
                ->all(),
            'compoundFields' => $field->compoundFields->all(),
            'fieldMeasureFormat' => $field->fieldMeasureFormat?->value,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function convertPermittedValue(PermittedValue $permittedValue): array
    {
        return [
            'key' => $permittedValue->key,
            'label' => $permittedValue->label,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function convertFieldFilter(FieldFilter $filter): array
    {
        return $this->normalize([
            'name' => $filter->name,
            'config' => $filter->config->all(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function convertFieldDependency(FieldDependency $dependency): array
    {
        return [
            'permittedValueKey' => $dependency->permittedValueKey,
            'parentFieldValue' => $dependency->parentFieldValue,
        ];
    }
}
