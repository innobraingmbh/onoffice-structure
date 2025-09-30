<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\PrismSchema;

use Innobrain\Structure\Dtos\PermittedValue;

trait PrismSchemaPermittedValue
{
    public function convertPermittedValue(PermittedValue $permittedValue): mixed
    {
        // Permitted values are handled at the Field level as enum options
        return $permittedValue->key;
    }
}
