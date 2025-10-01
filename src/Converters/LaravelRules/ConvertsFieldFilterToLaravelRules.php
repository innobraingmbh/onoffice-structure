<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\LaravelRules;

use Innobrain\Structure\Dtos\FieldFilter;

trait ConvertsFieldFilterToLaravelRules
{
    /**
     * @return array<string, mixed>
     */
    public function convertFieldFilter(FieldFilter $fieldFilter): array
    {
        return [];
    }
}
