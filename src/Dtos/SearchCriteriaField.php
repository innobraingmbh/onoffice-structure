<?php

declare(strict_types=1);

namespace Innobrain\Structure\Dtos;

use Illuminate\Support\Collection;
use Innobrain\Structure\Dtos\Concerns\HasPermittedValues;
use Innobrain\Structure\Enums\EstateClassification;
use Innobrain\Structure\Enums\SearchCriteriaFieldType;

readonly class SearchCriteriaField
{
    use HasPermittedValues;

    /** onOffice documents this one field as the special case whose values are region keys. */
    public const string REGIONS_KEY = 'regionaler_zusatz';

    public const string RANGE_FROM_SUFFIX = '__von';

    public const string RANGE_TO_SUFFIX = '__bis';

    public const string STORED_RANGE_PREFIX = 'range_';

    /**
     * @param  string  $rawType  The type exactly as the API returned it
     * @param  SearchCriteriaFieldType|null  $type  Null when the API returned a type this package does not know
     * @param  bool  $mustMatchByDefault  The account presets the field as a knockout criterion
     * @param  Collection<string, PermittedValue>  $permittedValues
     * @param  Collection<string, array<int, string>>  $appliesTo  Allowed values keyed by EstateClassification value; a missing classification means no restriction
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $category,
        public string $rawType,
        public ?SearchCriteriaFieldType $type = null,
        public bool $isRange = false,
        public ?string $rangeFromLabel = null,
        public ?string $rangeToLabel = null,
        public bool $mustMatchByDefault = false,
        public bool $isMandatory = false,
        public Collection $permittedValues = new Collection,
        public Collection $appliesTo = new Collection,
    ) {}

    public function isRegions(): bool
    {
        return $this->key === self::REGIONS_KEY;
    }

    public function isClassification(): bool
    {
        return EstateClassification::tryFrom($this->key) instanceof EstateClassification;
    }

    /**
     * The key the lower bound of a range is written under, e.g. "kaufpreis__von".
     */
    public function rangeFromKey(): string
    {
        return $this->key.self::RANGE_FROM_SUFFIX;
    }

    /**
     * The key the upper bound of a range is written under, e.g. "kaufpreis__bis".
     */
    public function rangeToKey(): string
    {
        return $this->key.self::RANGE_TO_SUFFIX;
    }

    /**
     * The key a stored range is read back under as [from, to], e.g. "range_kaufpreis".
     */
    public function storedRangeKey(): string
    {
        return self::STORED_RANGE_PREFIX.$this->key;
    }
}
