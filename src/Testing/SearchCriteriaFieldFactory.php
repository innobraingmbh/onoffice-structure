<?php

declare(strict_types=1);

namespace Innobrain\Structure\Testing;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Innobrain\Structure\Dtos\PermittedValue;
use Innobrain\Structure\Dtos\SearchCriteriaField;
use Innobrain\Structure\Enums\EstateClassification;
use Innobrain\Structure\Enums\SearchCriteriaFieldType;

/**
 * Builds SearchCriteriaField DTOs for tests without spelling out every constructor argument.
 */
final class SearchCriteriaFieldFactory
{
    use Conditionable;

    private ?string $label = null;

    private string $category = '';

    private bool $isRange = false;

    private ?string $rangeFromLabel = null;

    private ?string $rangeToLabel = null;

    private bool $mustMatchByDefault = false;

    private bool $isMandatory = false;

    /** @var array<int|string, string> */
    private array $permittedValues = [];

    /** @var array<string, array<int, string>> */
    private array $appliesTo = [];

    private function __construct(
        private readonly string $key,
        private readonly ?SearchCriteriaFieldType $type,
    ) {}

    public static function new(string $key, ?SearchCriteriaFieldType $type = null): self
    {
        return new self($key, $type);
    }

    public static function range(string $key, SearchCriteriaFieldType $type = SearchCriteriaFieldType::Float): self
    {
        $factory = self::new($key, $type);
        $factory->isRange = true;

        return $factory;
    }

    /**
     * @param  array<int|string, string>  $permittedValues  key => label
     */
    public static function singleSelect(string $key, array $permittedValues): self
    {
        return self::new($key, SearchCriteriaFieldType::SingleSelect)->permittedValues($permittedValues);
    }

    /**
     * @param  array<int|string, string>  $permittedValues  key => label
     */
    public static function multiSelect(string $key, array $permittedValues): self
    {
        return self::new($key, SearchCriteriaFieldType::MultiSelect)->permittedValues($permittedValues);
    }

    public static function regions(SearchCriteriaFieldType $type = SearchCriteriaFieldType::RegionsDisplayLive): self
    {
        return self::new(SearchCriteriaField::REGIONS_KEY, $type);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function category(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function rangeLabels(string $from, string $to): self
    {
        $this->rangeFromLabel = $from;
        $this->rangeToLabel = $to;

        return $this;
    }

    public function mustMatchByDefault(bool $mustMatchByDefault = true): self
    {
        $this->mustMatchByDefault = $mustMatchByDefault;

        return $this;
    }

    public function mandatory(bool $isMandatory = true): self
    {
        $this->isMandatory = $isMandatory;

        return $this;
    }

    /**
     * @param  array<int|string, string>  $permittedValues  key => label
     */
    public function permittedValues(array $permittedValues): self
    {
        $this->permittedValues = $permittedValues;

        return $this;
    }

    public function appliesTo(EstateClassification $classification, string ...$values): self
    {
        $this->appliesTo[$classification->value] = array_values($values);

        return $this;
    }

    public function make(): SearchCriteriaField
    {
        return new SearchCriteriaField(
            key: $this->key,
            label: $this->label ?? Str::ucfirst($this->key),
            category: $this->category,
            rawType: $this->type->value ?? '',
            type: $this->type,
            isRange: $this->isRange,
            rangeFromLabel: $this->rangeFromLabel,
            rangeToLabel: $this->rangeToLabel,
            mustMatchByDefault: $this->mustMatchByDefault,
            isMandatory: $this->isMandatory,
            permittedValues: collect($this->permittedValues)
                ->mapWithKeys(fn (string $label, int|string $key): array => [(string) $key => new PermittedValue((string) $key, $label)]),
            appliesTo: collect($this->appliesTo),
        );
    }
}
