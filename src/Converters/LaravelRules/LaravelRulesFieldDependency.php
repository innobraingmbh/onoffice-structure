<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\LaravelRules;

use Innobrain\Structure\Dtos\FieldDependency;

trait LaravelRulesFieldDependency
{
    /**
     * @return array<string, mixed>
     */
    public function convertFieldDependency(FieldDependency $fieldDependency): array
    {
        return [];
    }
}
