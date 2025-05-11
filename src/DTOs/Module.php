<?php

declare(strict_types=1);

namespace Innobrain\Structure\DTOs;

use Illuminate\Support\Collection;
use Innobrain\Structure\Enums\FieldConfigurationModule;

readonly class Module
{
    /**
     * @param  FieldConfigurationModule  $key  The technical key/identifier of the module.
     * @param  string  $label  The human-readable label for the module.
     * @param  Collection<string, Field>  $fields  A collection of Field DTOs, keyed by their technical name (Field->key).
     */
    public function __construct(
        public FieldConfigurationModule $key,
        public string $label,
        public Collection $fields,
    ) {}
}
