<?php

declare(strict_types=1);

namespace Innobrain\Structure\Collections;

use Illuminate\Support\Collection;
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
        $field = $this->get($key);

        if ($field instanceof Field) {
            return $field;
        }

        $needle = mb_strtolower($key);

        return $this->first(fn (Field $field): bool => mb_strtolower($field->key) === $needle);
    }

    /**
     * Drop unknown fields and values that are not permitted. Items of a
     * multi-select array are dropped individually.
     *
     * @param  Collection<string, mixed>  $data
     * @return Collection<string, mixed>
     */
    public function sanitize(Collection $data): Collection
    {
        return $data->intersectByKeys($this)
            ->map(function (mixed $value, string $key): mixed {
                /** @var Field $field */
                $field = $this->get($key);

                return is_array($value)
                    ? array_values(array_filter($value, $field->permits(...)))
                    : $value;
            })
            ->reject(function (mixed $value, string $key): bool {
                /** @var Field $field */
                $field = $this->get($key);

                return ! $field->permits($value);
            });
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
            $field = $this->get($key);

            if (! $field instanceof Field) {
                $violations->push(new FieldViolation($key, $value, ViolationReason::UnknownField));

                continue;
            }

            foreach (is_array($value) ? $value : [$value] as $item) {
                if (! $field->permits($item)) {
                    $violations->push(new FieldViolation($key, $item, ViolationReason::ValueNotPermitted));
                }
            }
        }

        return $violations;
    }
}
