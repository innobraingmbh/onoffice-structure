<?php

declare(strict_types=1);

namespace Innobrain\Structure\Contracts;

/**
 * Marks any object that can be converted using a ConvertStrategy.
 */
interface Convertible
{
    public function convert(ConvertStrategy $strategy): mixed;
}
