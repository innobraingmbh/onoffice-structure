<?php

declare(strict_types=1);

namespace Innobrain\Structure\Concerns;

use Innobrain\Structure\Contracts\ConvertStrategy;
use LogicException;

use function class_basename;
use function method_exists;

/**
 * Implements Convertible::convert() once for every DTO via reflection.
 */
trait HasConverter
{
    public function convert(ConvertStrategy $strategy): mixed
    {
        $method = 'convert'.class_basename(static::class);

        throw_unless(method_exists($strategy, $method), LogicException::class, "Strategy missing {$method}() for ".static::class);

        return $strategy->{$method}($this);
    }
}
