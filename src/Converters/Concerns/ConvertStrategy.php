<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\Concerns;

use Innobrain\Structure\DTOs\Field;
use Innobrain\Structure\DTOs\FieldDependency;
use Innobrain\Structure\DTOs\FieldFilter;
use Innobrain\Structure\DTOs\Module;
use Innobrain\Structure\DTOs\PermittedValue;

/**
 * Type-safe converter strategy contract – one method per DTO.
 */
interface ConvertStrategy
{
    public function convertPermittedValue(PermittedValue $pv): mixed;

    public function convertFieldDependency(FieldDependency $fd): mixed;

    public function convertFieldFilter(FieldFilter $ff): mixed;

    public function convertField(Field $field): mixed;

    public function convertModule(Module $module): mixed;
}
