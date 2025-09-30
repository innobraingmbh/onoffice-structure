<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\PrismSchema;

use Innobrain\Structure\DTOs\FieldDependency;

trait PrismSchemaFieldDependency
{
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
