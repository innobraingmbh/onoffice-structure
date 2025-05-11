<?php

declare(strict_types=1);

namespace Innobrain\Structure\DTOs;

use Illuminate\Support\Collection;

class FieldDependencies
{
    /**
     * @param  Collection<FieldDependency>  $dependencies
     */
    public function __construct(
        public readonly Collection $dependencies = new Collection,
    ) {}
}
