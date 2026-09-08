<?php

declare(strict_types=1);

namespace Innobrain\Structure\Enums;

enum ViolationReason: string
{
    case UnknownField = 'unknown_field';
    case ValueNotPermitted = 'value_not_permitted';
}
