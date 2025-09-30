<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\Array;

use Innobrain\Structure\DTOs\FieldDependency;

trait ArrayFieldDependency
{
    public function convertFieldDependency(FieldDependency $fieldDependency): array
    {
        return $this->normalize([
            'dependentFieldKey' => $fieldDependency->dependentFieldKey,
            'dependentFieldValue' => $fieldDependency->dependentFieldValue,
        ]);
    }
}
