<?php

declare(strict_types=1);

namespace Innobrain\Structure\Dtos;

use Illuminate\Support\Collection;
use Innobrain\Structure\Contracts\Convertible;
use Innobrain\Structure\Contracts\ConvertStrategy;
use Innobrain\Structure\Enums\FieldMeasureFormat;
use Innobrain\Structure\Enums\FieldType;

readonly class Field implements Convertible
{
    /**
     * @param  Collection<string, PermittedValue>  $permittedValues
     * @param  Collection<string, FieldFilter>  $filters
     * @param  Collection<int, FieldDependency>  $dependencies
     * @param  Collection<int, string>  $compoundFields
     */
    public function __construct(
        public string $key,
        public string $label,
        public FieldType $type,
        public ?int $length = null,
        public Collection $permittedValues = new Collection,
        public ?string $default = null,
        public Collection $filters = new Collection,
        public Collection $dependencies = new Collection,
        public Collection $compoundFields = new Collection,
        public ?FieldMeasureFormat $fieldMeasureFormat = null,
    ) {}

    public function convert(ConvertStrategy $strategy): mixed
    {
        return $strategy->convertField($this);
    }

    /**
     * @param  Collection<string, PermittedValue>  $permittedValues
     */
    public function withPermittedValues(Collection $permittedValues): self
    {
        return new self(
            key: $this->key,
            label: $this->label,
            type: $this->type,
            length: $this->length,
            permittedValues: $permittedValues,
            default: $this->default,
            filters: $this->filters,
            dependencies: $this->dependencies,
            compoundFields: $this->compoundFields,
            fieldMeasureFormat: $this->fieldMeasureFormat
        );
    }

    /**
     * Narrow the permitted values to those whose dependency requires the given
     * parent field value, e.g. the "objekttyp" values available for an
     * "objektart". A field without dependencies is returned unchanged.
     */
    public function withPermittedValuesFor(string $parentFieldValue): self
    {
        if ($this->dependencies->isEmpty()) {
            return $this;
        }

        $allowedKeys = $this->dependencies
            ->filter(fn (FieldDependency $dependency): bool => $dependency->parentFieldValue === $parentFieldValue)
            ->map(fn (FieldDependency $dependency): string => $dependency->permittedValueKey);

        return $this->withPermittedValues(
            $this->permittedValues->filter(fn (PermittedValue $permittedValue): bool => $allowedKeys->contains($permittedValue->key))
        );
    }

    /**
     * @param  array<string, string>  $filterValues
     */
    public function matchesFilters(array $filterValues): bool
    {
        foreach ($this->filters as $filter) {
            foreach ($filter->config as $filterKey => $allowedValues) {
                if (isset($filterValues[$filterKey]) && ! in_array($filterValues[$filterKey], $allowedValues, true)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Hints and dividing lines carry no data, and compound fields such as
     * "Plz-Ort" are set through their individual fields, so none of them
     * can be written to the API.
     */
    public function isWritable(): bool
    {
        if ($this->compoundFields->isNotEmpty()) {
            return false;
        }

        return ! in_array($this->type, [FieldType::RedHint, FieldType::BlackHint, FieldType::DividingLine], true);
    }

    /**
     * @phpstan-assert-if-true !null $this->default
     */
    public function hasDefault(): bool
    {
        return $this->default !== null;
    }

    public function hasPermittedValues(): bool
    {
        return $this->permittedValues->isNotEmpty();
    }

    /**
     * PHP turns numeric array keys into integers, so the keys are read from
     * the permitted values themselves to keep them strings.
     *
     * @return array<int, string>
     */
    public function permittedValueKeys(): array
    {
        return $this->permittedValues
            ->map(fn (PermittedValue $permittedValue): string => $permittedValue->key)
            ->values()
            ->all();
    }

    public function containsPermittedValue(string $permittedValueKey): bool
    {
        return $this->permittedValues->contains(static fn (PermittedValue $permittedValue) => $permittedValue->key === $permittedValueKey);
    }

    public function doesntContainPermittedValue(string $permittedValueKey): bool
    {
        return ! $this->containsPermittedValue($permittedValueKey);
    }
}
