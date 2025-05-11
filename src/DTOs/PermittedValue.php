<?php

declare(strict_types=1);

namespace Innobrain\Structure\DTOs;

class PermittedValue
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
    ) {}
}
