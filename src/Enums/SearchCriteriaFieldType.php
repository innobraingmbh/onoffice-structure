<?php

declare(strict_types=1);

namespace Innobrain\Structure\Enums;

use Illuminate\Support\Str;

/**
 * The search criteria endpoint has its own type vocabulary: selects are named
 * like in the field configuration, data types arrive as a URN such as
 * "urn:onoffice-de-ns:smart:2.5:dbAccess:dataType:float", and the regions
 * field reports how its values are displayed instead of a data type.
 */
enum SearchCriteriaFieldType: string
{
    case SingleSelect = 'singleselect';
    case MultiSelect = 'multiselect';
    case Float = 'float';
    case Decimal = 'decimal';

    case RegionsDisplayAll = 'displayAll';
    case RegionsDisplayLive = 'displayLive';
    case RegionsLimitExceeded = 'limitExceeded';

    public static function tryFromRaw(string $rawType): ?self
    {
        return self::tryFrom(Str::afterLast($rawType, ':'));
    }
}
