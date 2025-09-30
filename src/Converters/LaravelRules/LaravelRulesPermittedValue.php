<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\LaravelRules;

use Innobrain\Structure\Dtos\PermittedValue;

trait LaravelRulesPermittedValue
{
    /**
     * @return array<string, mixed>
     */
    public function convertPermittedValue(PermittedValue $permittedValue): array
    {
        // Not required for this strategy – handled at Field level.
        return [];
    }
}
