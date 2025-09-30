<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\Array;

use Innobrain\Structure\Dtos\PermittedValue;

trait ArrayPermittedValue
{
    /**
     * @return array<string, mixed>
     */
    public function convertPermittedValue(PermittedValue $permittedValue): array
    {
        return $this->normalize([
            'key' => $permittedValue->key,
            'label' => $permittedValue->label,
        ]);
    }
}
