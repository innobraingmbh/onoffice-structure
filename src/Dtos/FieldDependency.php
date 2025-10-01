<?php

declare(strict_types=1);

namespace Innobrain\Structure\Dtos;

use Innobrain\Structure\Concerns\HasConverter;
use Innobrain\Structure\Contracts\Convertible;

readonly class FieldDependency implements Convertible
{
    use HasConverter;

    public function __construct(
        public string $dependentFieldKey,
        public string $dependentFieldValue,
    ) {}
}
