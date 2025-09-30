<?php

declare(strict_types=1);

namespace Innobrain\Structure\Dtos;

use Illuminate\Support\Collection;
use Innobrain\Structure\Concerns\HasConverter;
use Innobrain\Structure\Contracts\Convertible;

class FieldFilter implements Convertible
{
    use HasConverter;

    /**
     * @param  Collection<string, string[]>  $config
     */
    public function __construct(
        public readonly string $name,
        public readonly Collection $config,
    ) {}
}
