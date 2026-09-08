<?php

declare(strict_types=1);

namespace Innobrain\Structure\Contracts;

use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\Module;

interface ConvertStrategy
{
    public function convertField(Field $field): mixed;

    public function convertModule(Module $module): mixed;
}
