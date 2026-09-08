<?php

declare(strict_types=1);

namespace Innobrain\Structure\Enums;

enum FieldMeasureFormat: string
{
    case None = 'DATA_TYPE_NONE';
    case Numeric = 'DATA_TYPE_NUMERIC';
    case Monetary = 'DATA_TYPE_MONETARY';
    case MonetaryOrText = 'DATA_TYPE_MONETARY_OR_TEXT';
    case Area = 'DATA_TYPE_AREA';
    case Date = 'DATA_TYPE_DATE';
    case User = 'DATA_TYPE_USER';
    case EnergyRequired = 'DATA_TYPE_ENERGY_REQUIRED';
}
