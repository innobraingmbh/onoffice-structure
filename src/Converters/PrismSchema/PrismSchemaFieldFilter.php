<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\PrismSchema;

use Innobrain\Structure\DTOs\FieldFilter;

trait PrismSchemaFieldFilter
{
    public function convertFieldFilter(FieldFilter $fieldFilter): array
    {
        // Filters aren't directly represented in Prism schemas
        // Return metadata for potential future use
        return [
            'name' => $fieldFilter->name,
            'config' => $fieldFilter->config->toArray(),
        ];
    }
}
