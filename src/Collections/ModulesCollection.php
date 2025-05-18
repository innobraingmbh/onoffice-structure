<?php

declare(strict_types=1);

namespace Innobrain\Structure\Collections;

use Illuminate\Support\Collection;
use Innobrain\Structure\Concerns\HasConverter;
use Innobrain\Structure\Contracts\Convertible;
use Innobrain\Structure\Contracts\ConvertStrategy;

/**
 * @extends Collection<int, \Innobrain\Structure\DTOs\Module>
 */
final class ModulesCollection extends Collection implements Convertible
{
    use HasConverter;

    public function convert(ConvertStrategy $strategy): array
    {
        // Override default trait behaviour: convert every module inside.
        return $this->map(fn ($module) => $module->convert($strategy))->toArray();
    }
}
