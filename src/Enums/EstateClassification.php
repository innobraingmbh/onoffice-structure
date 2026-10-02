<?php

declare(strict_types=1);

namespace Innobrain\Structure\Enums;

/**
 * The three fields a property is classified by. onOffice scopes other fields
 * to their values, e.g. "kaufpreis" to the marketing type "kauf".
 */
enum EstateClassification: string
{
    case MarketingType = 'vermarktungsart';
    case UsageType = 'nutzungsart';
    case PropertyType = 'objektart';

    /**
     * The key a search criteria field lists its allowed values under.
     */
    public function scopeKey(): string
    {
        return match ($this) {
            self::MarketingType => 'vermarktungsarten',
            self::UsageType => 'nutzungsarten',
            self::PropertyType => 'objektarten',
        };
    }
}
