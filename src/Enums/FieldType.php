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

    // Real data types returned by the onOffice API when realDataTypes=true.
    // Without that parameter these fields are returned as type "text".
    case User = 'user';
    case File = 'datei';
    case RedHint = 'redhint';
    case BlackHint = 'blackhint';
    case DividingLine = 'dividingline';

    /**
     * Hints and dividing lines only structure the form and carry no data.
     */
    public function isLayoutOnly(): bool
    {
        return match ($this) {
            self::RedHint, self::BlackHint, self::DividingLine => true,
            default => false,
        };
    }

    public function isSelect(): bool
    {
        return match ($this) {
            self::SingleSelect, self::MultiSelect => true,
            default => false,
        };
    }
}
