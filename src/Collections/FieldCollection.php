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
     * @param  Collection<string, string>  $data
     * @return Collection<string, string>
     */
    public function sanitize(Collection $data): Collection
    {
        return $data->only($this->keys())
            ->reject(function (string $value, string $key) {
                $field = $this->get($key);

                return $field->hasPermittedValues() && $field->doesntContainPermittedValue($value);
            });
    }
}
