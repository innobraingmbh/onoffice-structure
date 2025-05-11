<?php

declare(strict_types=1);

namespace Innobrain\Structure\Enums;

enum FieldType: string
{
    case VarChar = 'varchar';
    case Integer = 'integer';
    case MultiSelect = 'multiselect';
    case SingleSelect = 'singleselect';
    case Date = 'date';
    case DateTime = 'datetime';
    case Text = 'text';
    case Blob = 'blob';
    case Boolean = 'boolean';
    case Float = 'float';
}
