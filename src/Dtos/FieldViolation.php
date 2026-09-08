<?php

declare(strict_types=1);

namespace Innobrain\Structure\Dtos;

use Innobrain\Structure\Enums\ViolationReason;

readonly class FieldViolation
{
    public function __construct(
        public string $fieldKey,
        public mixed $value,
        public ViolationReason $reason,
    ) {}
}
