<?php

declare(strict_types=1);

namespace Innobrain\Structure\DTOs;

class FieldDependency
{
    public function __construct(
        public readonly string $dependentFieldKey,
        public readonly string $dependentFieldValue,
    ) {}
}
