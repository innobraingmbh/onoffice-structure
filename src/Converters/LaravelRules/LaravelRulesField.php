<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\LaravelRules;

use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\FieldDependency;
use Innobrain\Structure\Enums\FieldType;

trait LaravelRulesField
{
    public function convertField(Field $field): mixed
    {
        $rules = array_merge(
            $this->baseRules($field),
            $this->lengthRule($field),
            $this->inRule($field),
            $this->dependencyRules($field),
        );

        if ($this->includeNullable && $field->default === null) {
            $rules[] = 'nullable';
        }

        $rules = array_values(array_unique($rules));

        return $this->pipeOrArray($rules);
    }

    /**
     * @return string[]
     */
    private function baseRules(Field $field): array
    {
        return match ($field->type) {
            FieldType::VarChar,
            FieldType::Text,
            FieldType::Blob,
            FieldType::SingleSelect => ['string'],
            FieldType::Integer => ['integer'],
            FieldType::Float => ['numeric'],
            FieldType::Boolean => ['boolean'],
            FieldType::Date,
            FieldType::DateTime => ['date'],
            FieldType::MultiSelect => ['array', 'distinct'],
        };
    }

    /**
     * @return string[]
     */
    private function lengthRule(Field $field): array
    {
        if ($field->length && $field->type === FieldType::VarChar) {
            return ['max:'.$field->length];
        }

        return [];
    }

    /**
     * @return string[]
     */
    private function inRule(Field $field): array
    {
        if ($field->permittedValues->isEmpty()) {
            return [];
        }

        // For multi-selects we add the in-rule on the item level, not the array itself.
        if ($field->type === FieldType::MultiSelect) {
            return [];
        }

        $values = $field->permittedValues->keys()->toArray();

        return ['in:'.implode(',', $values)];
    }

    /**
     * @return string[]
     */
    private function dependencyRules(Field $field): array
    {
        if ($field->dependencies->isEmpty()) {
            return [];
        }

        return $field->dependencies
            ->map(fn (FieldDependency $dependency) => 'required_if:'.$dependency->dependentFieldKey.','.$dependency->dependentFieldValue)
            ->toArray();
    }
}
