<?php

declare(strict_types=1);

namespace Innobrain\Structure\Collections;

use Illuminate\Support\Collection;
use Innobrain\Structure\Builders\FieldFilterBuilder;
use Innobrain\Structure\Dtos\Field;

/**
 * @extends Collection<string, Field>
 */
final class FieldCollection extends Collection
{
    public function whereMatchesFilters(): FieldFilterBuilder
    {
        return new FieldFilterBuilder($this);
    }

    /**
     * Check if a field with the given key name exists in the collection.
     */
    public function hasField(string $fieldKeyName): bool
    {
        return $this->contains(static fn (Field $field) => $field->key === $fieldKeyName);
    }

    /**
     * Check if a field with the given key name does not exist in the collection.
     */
    public function doesntHaveField(string $fieldKeyName): bool
    {
        return ! $this->hasField($fieldKeyName);
    }

    /**
     * Remove data entries that do not correspond to any field in the collection
     * or have values not permitted by their respective fields.
     *
     * @param  Collection<string, mixed>  $data
     * @return Collection<string, mixed>
     */
    public function removeDataNotPresentInCollection(Collection $data): Collection
    {
        return $data->map(function (mixed $value, string $key) {
            if ($this->doesntHaveField($key)) {
                return null;
            }
            $field = $this->first(static fn (Field $field) => $field->key === $key);
            assert($field instanceof Field);

            if ($field->hasPermittedValues() && $field->doesntContainPermittedValue($value)) {
                return null;
            }

            return $value;
        })->filter();
    }
}
