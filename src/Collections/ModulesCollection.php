<?php

declare(strict_types=1);

namespace Innobrain\Structure\Collections;

use Illuminate\Support\Collection;
use Innobrain\Structure\Contracts\Convertible;
use Innobrain\Structure\Contracts\ConvertStrategy;
use Innobrain\Structure\Dtos\Module;

/**
 * @extends Collection<string, Module>
 */
final class ModulesCollection extends Collection implements Convertible
{
    /**
     * @return array<string, mixed>
     */
    public function convert(ConvertStrategy $strategy): array
    {
        return $this->map(fn (Module $module) => $module->convert($strategy))->toArray();
    }
}
