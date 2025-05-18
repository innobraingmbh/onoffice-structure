<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters;

use Innobrain\Structure\Concerns\ConvertsToArray;
use Innobrain\Structure\Contracts\ConvertStrategy;
use Innobrain\Structure\DTOs\Field;
use Innobrain\Structure\DTOs\FieldDependency;
use Innobrain\Structure\DTOs\FieldFilter;
use Innobrain\Structure\DTOs\Module;
use Innobrain\Structure\DTOs\PermittedValue;

/**
 * Concrete converter that turns DTOs into nested arrays.
 */
final readonly class ArrayConvertStrategy implements ConvertStrategy
{
    use ConvertsToArray;

    public function __construct(private bool $dropEmpty = false) {}

    /* ---------- leaf DTOs ---------- */

    public function convertPermittedValue(PermittedValue $pv): array
    {
        return $this->normalize([
            'key' => $pv->key,
            'label' => $pv->label,
        ]);
    }

    public function convertFieldDependency(FieldDependency $fd): array
    {
        return $this->normalize([
            'dependentFieldKey' => $fd->dependentFieldKey,
            'dependentFieldValue' => $fd->dependentFieldValue,
        ]);
    }

    public function convertFieldFilter(FieldFilter $ff): array
    {
        return $this->normalize([
            'name' => $ff->name,
            'config' => $ff->config->toArray(),
        ]);
    }

    /* ---------- aggregate DTOs ---------- */

    public function convertField(Field $field): array
    {
        return $this->normalize([
            'key' => $field->key,
            'label' => $field->label,
            'type' => $field->type->value,
            'length' => $field->length,
            'permittedValues' => $field->permittedValues
                ->map(fn ($pv) => $pv->convert($this))
                ->toArray(),
            'default' => $field->default,
            'filters' => $field->filters
                ->map(fn ($f) => $f->convert($this))
                ->toArray(),
            'dependencies' => $field->dependencies
                ->map(fn ($d) => $d->convert($this))
                ->toArray(),
            'compoundFields' => $field->compoundFields->toArray(),
            'fieldMeasureFormat' => $field->fieldMeasureFormat,
        ]);
    }

    public function convertModule(Module $module): array
    {
        return $this->normalize([
            'key' => $module->key->value,
            'label' => $module->label,
            'fields' => $module->fields
                ->map(fn ($f) => $f->convert($this))
                ->toArray(),
        ]);
    }

    /* ---------- internals ---------- */

    private function normalize(array $payload): array
    {
        return $this->dropEmpty ? $this->filterEmptyRecursive($payload) : $payload;
    }
}
