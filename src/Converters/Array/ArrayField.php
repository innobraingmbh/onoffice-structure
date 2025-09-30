<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\Array;

use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\FieldDependency;
use Innobrain\Structure\Dtos\FieldFilter;
use Innobrain\Structure\Dtos\PermittedValue;

trait ArrayField
{
    /**
     * @return array<string, mixed>
     */
    public function convertField(Field $field): array
    {
        return $this->normalize([
            'key' => $field->key,
            'label' => $field->label,
            'type' => $field->type->value,
            'length' => $field->length,
            'permittedValues' => $field->permittedValues
                ->map(fn (PermittedValue $pv) => $pv->convert($this))
                ->toArray(),
            'default' => $field->default,
            'filters' => $field->filters
                ->map(fn (FieldFilter $f) => $f->convert($this))
                ->toArray(),
            'dependencies' => $field->dependencies
                ->map(fn (FieldDependency $d) => $d->convert($this))
                ->toArray(),
            'compoundFields' => $field->compoundFields->toArray(),
            'fieldMeasureFormat' => $field->fieldMeasureFormat,
        ]);
    }
}
