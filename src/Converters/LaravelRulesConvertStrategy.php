<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters;

use Innobrain\Structure\Contracts\ConvertStrategy;
use Innobrain\Structure\DTOs\Field;
use Innobrain\Structure\DTOs\FieldDependency;
use Innobrain\Structure\DTOs\FieldFilter;
use Innobrain\Structure\DTOs\Module;
use Innobrain\Structure\DTOs\PermittedValue;
use Innobrain\Structure\Enums\FieldType;

/**
 * Convert the package's DTOs into Laravel validation-rule arrays / strings.
 *
 * Typical usage:
 *   $rules = $modules->convert(new LaravelRulesConvertStrategy());          // all modules
 *   $address = $modules['address']->convert(new LaravelRulesConvertStrategy(pipeSyntax:false));
 *
 * The strategy returns:
 *   • Module   ⇒ array<string, string|array>   (field key → rule list)
 *   • Field    ⇒ string (pipe syntax)  or array<string>  depending on $pipeSyntax
 *
 * Multi-select fields automatically receive an additional "{fieldKey}.*" rule
 * with the permitted-values "in:" constraint so that each submitted item is validated.
 */
final readonly class LaravelRulesConvertStrategy implements ConvertStrategy
{
    /**
     * @param  bool  $pipeSyntax  true ➜ 'string|max:80|nullable',  false ➜ ['string', 'max:80', 'nullable']
     * @param  bool  $includeNullable  true ➜ append 'nullable' when a field has no default
     */
    public function __construct(
        private bool $pipeSyntax = true,
        private bool $includeNullable = true,
    ) {}

    /* ---------------------------------------------------------------------
     * ConvertStrategy – leaf DTOs
     * ------------------------------------------------------------------- */

    public function convertPermittedValue(PermittedValue $pv): mixed
    {
        // Not required for this strategy – handled at Field level.
        return [];
    }

    public function convertFieldDependency(FieldDependency $fd): mixed
    {
        return [];
    }

    public function convertFieldFilter(FieldFilter $ff): mixed
    {
        return [];
    }

    /* ---------------------------------------------------------------------
     * ConvertStrategy – aggregates
     * ------------------------------------------------------------------- */

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
     * @return array<string, string|array>
     *
     * The returned array is keyed by field key. For multi-select fields an
     * additional "{fieldKey}.*" element is added so each item receives the
     * proper "in:" check.
     */
    public function convertModule(Module $module): mixed
    {
        $result = [];

        foreach ($module->fields as $fieldKey => $field) {
            /** @var Field $field */
            $result[$fieldKey] = $this->convertField($field);

            // Extra rules per item for multi-selects
            if ($field->type === FieldType::MultiSelect && $field->permittedValues->isNotEmpty()) {
                $itemRules = [
                    'in:'.implode(',', $field->permittedValues->keys()->toArray()),
                ];
                $result[$fieldKey.'.*'] = $this->pipeOrArray($itemRules);
            }
        }

        return $result;
    }

    /* ---------------------------------------------------------------------
     * Internal helpers
     * ------------------------------------------------------------------- */

    /** @return string[] */
    private function baseRules(Field $field): array
    {
        return match ($field->type) {
            FieldType::VarChar,
            FieldType::Text,
            FieldType::Blob => ['string'],
            FieldType::Integer => ['integer'],
            FieldType::Float => ['numeric'],
            FieldType::Boolean => ['boolean'],
            FieldType::Date,
            FieldType::DateTime => ['date'],
            FieldType::SingleSelect => ['string'],
            FieldType::MultiSelect => ['array', 'distinct'],
        };
    }

    /** @return string[] */
    private function lengthRule(Field $field): array
    {
        if ($field->length && $field->type === FieldType::VarChar) {
            return ['max:'.$field->length];
        }

        return [];
    }

    /** @return string[] */
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

    /** @return string[] */
    private function dependencyRules(Field $field): array
    {
        if ($field->dependencies->isEmpty()) {
            return [];
        }

        return $field->dependencies
            ->map(fn (FieldDependency $d) => 'required_if:'.$d->dependentFieldKey.','.$d->dependentFieldValue)
            ->toArray();
    }

    /** @param  string[]  $rules */
    private function pipeOrArray(array $rules): string|array
    {
        return $this->pipeSyntax ? implode('|', $rules) : $rules;
    }
}
