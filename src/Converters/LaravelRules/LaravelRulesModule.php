<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\LaravelRules;

use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\Module;
use Innobrain\Structure\Enums\FieldType;

trait LaravelRulesModule
{
    /**
     * The returned array is keyed by field key. For multi-select fields an
     * additional "{fieldKey}.*" element is added so each item receives the
     * proper "in:" check.
     *
     * @return array<string, mixed>
     */
    public function convertModule(Module $module): array
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
}
