<?php

declare(strict_types=1);

namespace Innobrain\Structure\Dtos;

use Illuminate\Support\Collection;
use Innobrain\Structure\Concerns\HasConverter;
use Innobrain\Structure\Contracts\Convertible;
use Innobrain\Structure\Enums\FieldType;

readonly class Field implements Convertible
{
    use HasConverter;

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
        public ?int $length,
        public Collection $permittedValues,
        public ?string $default,
        public Collection $filters,
        public Collection $dependencies,
        public Collection $compoundFields,
        public ?string $fieldMeasureFormat
    ) {}

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
     * Check if this field matches the provided filter values
     *
     * @param  array<string, string>  $filterValues  Array of filter keys and their values
     */
    public function matchesFilters(array $filterValues): bool
    {
        if ($this->filters->isEmpty()) {
            return true;
        }

        // Check each filter on this field
        foreach ($this->filters as $filter) {
            /** @phpstan-ignore-next-line */
            if (! $filter instanceof FieldFilter) {
                continue;
            }

            // Check each filter configuration
            foreach ($filter->config as $filterKey => $allowedValues) {
                // If we have a value for this filter key
                // Check if the current value is in the allowed values
                if (isset($filterValues[$filterKey]) && ! in_array($filterValues[$filterKey], $allowedValues, true)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns true if the field has permitted values
     */
    public function hasPermittedValues(): bool
    {
        return $this->permittedValues->isNotEmpty();
    }

    /**
     * Returns true if the field contains the given permitted value key.
     */
    public function containsPermittedValue(string $permittedValueKey): bool
    {
        return $this->permittedValues->contains(static fn (PermittedValue $permittedValue) => $permittedValue->key === $permittedValueKey);
    }

    public function doesntContainPermittedValue(string $permittedValueKey): bool
    {
        return ! $this->containsPermittedValue($permittedValueKey);
    }
}
