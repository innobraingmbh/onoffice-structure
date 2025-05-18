<?php

declare(strict_types=1);

namespace Innobrain\Structure\DTOs;

use Illuminate\Support\Collection;
use Innobrain\Structure\Concerns\HasConverter;
use Innobrain\Structure\Contracts\Convertible;
use Innobrain\Structure\Enums\FieldType;

class Field implements Convertible
{
    use HasConverter;

    /**
     * @param  Collection<string, PermittedValue>  $permittedValues
     * @param  Collection<string, FieldFilter>  $filters
     * @param  Collection<int, FieldDependency>  $dependencies
     * @param  Collection<int, string>  $compoundFields
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly FieldType $type,
        public readonly ?int $length,
        public readonly Collection $permittedValues,
        public readonly ?string $default,
        public readonly Collection $filters,
        public readonly Collection $dependencies,
        public readonly Collection $compoundFields,
        public readonly ?string $fieldMeasureFormat
    ) {}
}
