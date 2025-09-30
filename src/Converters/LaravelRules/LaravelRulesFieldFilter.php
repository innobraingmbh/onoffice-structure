<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\LaravelRules;

use Innobrain\Structure\DTOs\FieldFilter;

trait LaravelRulesFieldFilter
{
    public function convertFieldFilter(FieldFilter $fieldFilter): array
    {
        return [];
    }
}
