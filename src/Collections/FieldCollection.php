<?php

declare(strict_types=1);

namespace Innobrain\Structure\Collections;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Innobrain\Structure\Builders\FieldFilterBuilder;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\FieldViolation;
use Innobrain\Structure\Enums\ViolationReason;

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
     * Only the fields that can be written to the API, see Field::isWritable().
     */
    public function writable(): self
    {
        return $this->filter(fn (Field $field): bool => $field->isWritable());
    }

    /**
     * Find a field by key, falling back to a case-insensitive match since the
     * API accepts keys like "ort" for "Ort".
     */
    public function find(string $key): ?Field
    {
        return $this->get($key)
            ?? $this->first(fn (Field $field): bool => Str::lower($field->key) === Str::lower($key));
    }

    /**
     * Drop unknown fields and values that are not permitted. Fields are matched
     * like find() and returned under their canonical key. Items of a
     * multi-select array are dropped individually, so an array whose items were
     * all dropped is kept as an empty array.
     *
     * @param  Collection<string, mixed>  $data
     * @return Collection<string, mixed>
     */
    public function sanitize(Collection $data): Collection
    {
        $sanitized = new Collection;

        foreach ($data as $key => $value) {
            $field = $this->find($key);

            if (! $field instanceof Field) {
                continue;
            }

            if (is_array($value)) {
                $value = collect($value)->filter($field->permits(...))->values()->all();
            }

            if ($field->permits($value)) {
                $sanitized->put($field->key, $value);
            }
        }

        return $sanitized;
    }

    /**
     * Everything sanitize() would drop, with the reason why.
     *
     * @param  Collection<string, mixed>  $data
     * @return Collection<int, FieldViolation>
     */
    public function violations(Collection $data): Collection
    {
        $violations = new Collection;

        foreach ($data as $key => $value) {
            $field = $this->find($key);

            if (! $field instanceof Field) {
                $violations->push(new FieldViolation($key, $value, ViolationReason::UnknownField));

                continue;
            }

            foreach (Arr::wrap($value) as $item) {
                if (! $field->permits($item)) {
                    $violations->push(new FieldViolation($field->key, $item, ViolationReason::ValueNotPermitted));
                }
            }
        }

        return $violations;
    }
}
