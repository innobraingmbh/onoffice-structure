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
        return $data->intersectByKeys($this)
            ->reject(function (string $value, string $key) {
                /** @var Field $field */
                $field = $this->get($key);

                return $field->hasPermittedValues() && $field->doesntContainPermittedValue($value);
            });
    }
}
