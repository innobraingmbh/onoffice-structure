<?php

declare(strict_types=1);

namespace Innobrain\Structure\Dtos;

use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Concerns\HasConverter;
use Innobrain\Structure\Contracts\Convertible;
use Innobrain\Structure\Enums\FieldConfigurationModule;

readonly class Module implements Convertible
{
    use HasConverter;

    public function __construct(
        public FieldConfigurationModule $key,
        public string $label,
        public FieldCollection $fields,
    ) {}
}
