<?php

declare(strict_types=1);

namespace Innobrain\Structure\Dtos;

use Illuminate\Support\Collection;

readonly class FieldFilter
{
    /**
     * @param  Collection<string, string[]>  $config
     */
    public function __construct(
        public string $name,
        public Collection $config,
    ) {}
}
