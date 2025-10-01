<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\Array;

use Innobrain\Structure\Dtos\FieldDependency;

trait ConvertsFieldDependencyToArray
{
    /**
     * @return array<string, mixed>
     */
    public function convertFieldDependency(FieldDependency $fieldDependency): array
    {
        return $this->normalize([
            'dependentFieldKey' => $fieldDependency->dependentFieldKey,
            'dependentFieldValue' => $fieldDependency->dependentFieldValue,
        ]);
    }
}
