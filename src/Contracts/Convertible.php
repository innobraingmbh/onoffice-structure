<?php

declare(strict_types=1);

namespace Innobrain\Structure\Contracts;

use Innobrain\Structure\Converters\Concerns\ConvertStrategy;

/**
 * Marks any object that can be converted using a ConvertStrategy.
 */
interface Convertible
{
    public function convert(ConvertStrategy $strategy): mixed;
}
