<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\Concerns;

use Innobrain\Structure\Dtos\FieldDependency;
use Innobrain\Structure\Dtos\FieldFilter;
use Innobrain\Structure\Dtos\PermittedValue;

/**
 * Provides sensible defaults for sub-DTO conversion methods.
 *
 * Concrete strategies only need to implement convertField() and convertModule().
 * Override the other methods when needed (e.g. the Array strategy).
 */
abstract readonly class BaseConvertStrategy implements ConvertStrategy
{
    public function convertPermittedValue(PermittedValue $pv): mixed
    {
        return [];
    }

    public function convertFieldDependency(FieldDependency $fd): mixed
    {
        return [];
    }

    public function convertFieldFilter(FieldFilter $ff): mixed
    {
        return [];
    }
}
