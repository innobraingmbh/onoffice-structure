<?php

declare(strict_types=1);

namespace Innobrain\Structure\Collections;

use Illuminate\Support\Collection;
use Innobrain\Structure\Concerns\HasConverter;
use Innobrain\Structure\Contracts\Convertible;
use Innobrain\Structure\Converters\Concerns\ConvertStrategy;
use Innobrain\Structure\Dtos\Module;

/**
 * @extends Collection<string, Module>
 */
final class ModulesCollection extends Collection implements Convertible
{
    use HasConverter;

    public function convert(ConvertStrategy $strategy): array
    {
        // Override default trait behaviour: convert every module inside.
        return $this->map(fn (Module $module) => $module->convert($strategy))->toArray();
    }
}
