<?php

declare(strict_types=1);

namespace Innobrain\Structure\Dtos;

use Innobrain\Structure\Concerns\HasConverter;
use Innobrain\Structure\Contracts\Convertible;

class PermittedValue implements Convertible
{
    use HasConverter;

    public function __construct(
        public readonly string $key,
        public readonly string $label,
    ) {}
}
