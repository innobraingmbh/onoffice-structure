<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\JsonSchema;

use Innobrain\Structure\DTOs\PermittedValue;

trait JsonSchemaPermittedValue
{
    public function convertPermittedValue(PermittedValue $permittedValue): string
    {
        // Permitted values are handled at the Field level as enum options
        return $permittedValue->key;
    }
}
