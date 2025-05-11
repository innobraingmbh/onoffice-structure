<?php

declare(strict_types=1);

namespace Innobrain\Structure\DTOs;

use Illuminate\Support\Collection;

class FieldFilter
{
    /**
     * @param  string  $name  The name/key of the filter (e.g., 'Anrede-Titel').
     * @param  Collection<string, string[]>  $config  The configuration for the filter (e.g., collect(['kontaktkategorien' => ['', 'value1']])).
     */
    public function __construct(
        public readonly string $name,
        public readonly Collection $config,
    ) {}
}
