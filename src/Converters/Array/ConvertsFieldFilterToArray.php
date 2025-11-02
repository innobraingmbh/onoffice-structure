<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\Array;

use Innobrain\Structure\Dtos\FieldFilter;

trait ConvertsFieldFilterToArray
{
    /**
     * @return array<string, mixed>
     */
    public function convertFieldFilter(FieldFilter $fieldFilter): array
    {
        return $this->normalize([
            'name' => $fieldFilter->name,
            'config' => $fieldFilter->config->all(),
        ]);
    }
}
