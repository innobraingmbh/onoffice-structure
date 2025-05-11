<?php

declare(strict_types=1);

namespace Innobrain\Structure\DTOs;

use Illuminate\Support\Collection;

class FieldFilters
{
    /**
     * @param  Collection<string, FieldFilter>  $filters  A collection of FieldFilter DTOs, keyed by FieldFilter->name.
     */
    public function __construct(
        public readonly Collection $filters,
    ) {}
}
