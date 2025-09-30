<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\Concerns;

use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\FieldDependency;
use Innobrain\Structure\Dtos\FieldFilter;
use Innobrain\Structure\Dtos\Module;
use Innobrain\Structure\Dtos\PermittedValue;

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
