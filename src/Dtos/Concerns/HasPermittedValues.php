<?php

declare(strict_types=1);

namespace Innobrain\Structure\Dtos\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Innobrain\Structure\Dtos\PermittedValue;

/**
 * @property-read Collection<string, PermittedValue> $permittedValues
 */
trait HasPermittedValues
{
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

    /**
     * Numeric keys become integers here because they are used as array keys.
     *
     * @return array<int|string, string>
     */
    public function permittedValueLabels(): array
    {
        return $this->permittedValues
            ->mapWithKeys(fn (PermittedValue $permittedValue): array => [$permittedValue->key => $permittedValue->label])
            ->all();
    }

    public function labelFor(string $permittedValueKey): ?string
    {
        return $this->permittedValues
            ->first(fn (PermittedValue $permittedValue): bool => $permittedValue->key === $permittedValueKey)
            ?->label;
    }

    /**
     * Find the key of a permitted value from user input, which may be the key
     * itself or its label in any casing.
     */
    public function permittedValueKeyFor(string $keyOrLabel): ?string
    {
        if ($this->containsPermittedValue($keyOrLabel)) {
            return $keyOrLabel;
        }

        $needle = Str::lower(trim($keyOrLabel));

        $permittedValue = $this->permittedValues->first(fn (PermittedValue $permittedValue): bool => Str::lower($permittedValue->key) === $needle)
            ?? $this->permittedValues->first(fn (PermittedValue $permittedValue): bool => Str::lower($permittedValue->label) === $needle);

        return $permittedValue?->key;
    }

    public function containsPermittedValue(string $permittedValueKey): bool
    {
        return $this->permittedValues->contains(static fn (PermittedValue $permittedValue) => $permittedValue->key === $permittedValueKey);
    }

    public function doesntContainPermittedValue(string $permittedValueKey): bool
    {
        return ! $this->containsPermittedValue($permittedValueKey);
    }

    /**
     * Whether a submitted value is allowed for this field. A field without
     * permitted values accepts anything, null is always allowed since the
     * validation rules are nullable, and a multi-select accepts an array of keys.
     */
    public function permits(mixed $value): bool
    {
        if ($value === null || ! $this->hasPermittedValues()) {
            return true;
        }

        if (is_array($value)) {
            return collect($value)->every($this->permits(...));
        }

        return is_scalar($value) && $this->containsPermittedValue((string) $value);
    }
}
