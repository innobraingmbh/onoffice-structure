<?php

declare(strict_types=1);

namespace Innobrain\Structure\Dtos;

readonly class PermittedValue
{
    public function __construct(
        public string $key,
        public string $label,
    ) {}
}
