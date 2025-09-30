<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\LaravelRules;

use Innobrain\Structure\DTOs\PermittedValue;

trait LaravelRulesPermittedValue
{
    public function convertPermittedValue(PermittedValue $permittedValue): array
    {
        // Not required for this strategy – handled at Field level.
        return [];
    }
}
