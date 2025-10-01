<?php

declare(strict_types=1);

namespace Innobrain\Structure\Dtos;

use Illuminate\Support\Collection;
use Innobrain\Structure\Concerns\HasConverter;
use Innobrain\Structure\Contracts\Convertible;

readonly class FieldFilter implements Convertible
{
    use HasConverter;

    /**
     * @param  Collection<string, string[]>  $config
     */
    public function __construct(
        public string $name,
        public Collection $config,
    ) {}
}
