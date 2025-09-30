<?php

declare(strict_types=1);

namespace Innobrain\Structure\Collections;

use Illuminate\Support\Collection;
use Innobrain\Structure\Builders\FieldFilterBuilder;
use Innobrain\Structure\DTOs\Field;

/**
 * @extends Collection<string, Field>
 */
final class FieldCollection extends Collection
{
    public function whereMatchesFilters(): FieldFilterBuilder
    {
        return new FieldFilterBuilder($this);
    }
}
