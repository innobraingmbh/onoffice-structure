<?php

declare(strict_types=1);

namespace Innobrain\Structure\Contracts;

interface Convertible
{
    public function convert(ConvertStrategy $strategy): mixed;
}
