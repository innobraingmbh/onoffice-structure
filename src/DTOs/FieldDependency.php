<?php

declare(strict_types=1);

namespace Innobrain\Structure\DTOs;

use Innobrain\Structure\Concerns\HasConverter;
use Innobrain\Structure\Contracts\Convertible;

class FieldDependency implements Convertible
{
    use HasConverter;

    public function __construct(
        public readonly string $dependentFieldKey,
        public readonly string $dependentFieldValue,
    ) {}
}
