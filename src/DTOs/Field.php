<?php

declare(strict_types=1);

namespace Innobrain\Structure\DTOs;

use Illuminate\Support\Collection;
use Innobrain\Structure\Enums\FieldType;

class Field
{
    /**
     * @param  string  $key  Technical name of the field.
     * @param  string  $label  Human-readable label of the field.
     * @param  FieldType  $type  Type of the field.
     * @param  int|null  $length  Max length for certain field types.
     * @param  Collection<string, PermittedValue>  $permittedValues  Permitted values for select-like fields, keyed by PermittedValue->key.
     * @param  string|null  $default  Default value for the field.
     * @param  FieldFilters  $filters  Filters applicable to/for this field.
     * @param  FieldDependencies  $dependencies  Dependencies of this field.
     * @param  Collection<int, string>  $compoundFields  List of field keys that this field is a compound of.
     * @param  string|null  $fieldMeasureFormat  Specific measure/format information for the field.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly FieldType $type,
        public readonly ?int $length,
        public readonly Collection $permittedValues,
        public readonly ?string $default,
        public readonly FieldFilters $filters,
        public readonly FieldDependencies $dependencies,
        public readonly Collection $compoundFields,
        public readonly ?string $fieldMeasureFormat
    ) {}
}
