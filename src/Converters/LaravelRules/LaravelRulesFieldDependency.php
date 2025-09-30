<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\LaravelRules;

use Innobrain\Structure\DTOs\FieldDependency;

trait LaravelRulesFieldDependency
{
    public function convertFieldDependency(FieldDependency $fieldDependency): array
    {
        return [];
    }
}
