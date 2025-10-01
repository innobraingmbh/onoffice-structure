<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\JsonSchema;

use Innobrain\Structure\Dtos\FieldDependency;

trait ConvertsFieldDependencyToJsonSchema
{
    /**
     * @return array<string, mixed>
     */
    public function convertFieldDependency(FieldDependency $fieldDependency): array
    {
        // Dependencies could be used to determine required fields
        // For now, we'll return metadata that can be used later
        return [
            'field' => $fieldDependency->dependentFieldKey,
            'value' => $fieldDependency->dependentFieldValue,
        ];
    }
}
