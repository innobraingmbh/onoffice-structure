<?php

declare(strict_types=1);

namespace Innobrain\Structure\DTOs;

use Illuminate\Support\Collection;
use Innobrain\Structure\Concerns\HasConverter;
use Innobrain\Structure\Contracts\Convertible;
use Innobrain\Structure\Enums\FieldConfigurationModule;

readonly class Module implements Convertible
{
    use HasConverter;

    /**
     * @param  Collection<string, Field>  $fields
     */
    public function __construct(
        public FieldConfigurationModule $key,
        public string $label,
        public Collection $fields,
    ) {}
}
