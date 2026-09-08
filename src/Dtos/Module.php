<?php

declare(strict_types=1);

namespace Innobrain\Structure\Dtos;

use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Contracts\Convertible;
use Innobrain\Structure\Contracts\ConvertStrategy;
use Innobrain\Structure\Enums\FieldConfigurationModule;

readonly class Module implements Convertible
{
    public function __construct(
        public FieldConfigurationModule $key,
        public string $label,
        public FieldCollection $fields,
    ) {}

    public function field(string $key): ?Field
    {
        return $this->fields->find($key);
    }

    public function convert(ConvertStrategy $strategy): mixed
    {
        return $strategy->convertModule($this);
    }
}
