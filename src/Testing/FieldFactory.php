<?php

declare(strict_types=1);

namespace Innobrain\Structure\Testing;

use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\FieldDependency;
use Innobrain\Structure\Dtos\FieldFilter;
use Innobrain\Structure\Dtos\PermittedValue;
use Innobrain\Structure\Enums\FieldMeasureFormat;
use Innobrain\Structure\Enums\FieldType;

/**
 * Builds Field DTOs for tests without spelling out every constructor argument.
 */
final class FieldFactory
{
    private ?string $label = null;

    private ?int $length = null;

    private ?string $default = null;

    /** @var array<string, string> */
    private array $permittedValues = [];

    /** @var array<string, array<string, array<string>>> */
    private array $filters = [];

    /** @var array<string, string> */
    private array $dependencies = [];

    /** @var array<int, string> */
    private array $compoundFields = [];

    private ?FieldMeasureFormat $fieldMeasureFormat = null;

    private function __construct(
        private readonly string $key,
        private readonly FieldType $type,
    ) {}

    public static function new(string $key, FieldType $type = FieldType::VarChar): self
    {
        return new self($key, $type);
    }

    /**
     * @param  array<string, string>  $permittedValues  key => label
     */
    public static function singleSelect(string $key, array $permittedValues): self
    {
        return self::new($key, FieldType::SingleSelect)->permittedValues($permittedValues);
    }

    /**
     * @param  array<string, string>  $permittedValues  key => label
     */
    public static function multiSelect(string $key, array $permittedValues): self
    {
        return self::new($key, FieldType::MultiSelect)->permittedValues($permittedValues);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function length(int $length): self
    {
        $this->length = $length;

        return $this;
    }

    public function default(string $default): self
    {
        $this->default = $default;

        return $this;
    }

    /**
     * @param  array<string, string>  $permittedValues  key => label
     */
    public function permittedValues(array $permittedValues): self
    {
        $this->permittedValues = $permittedValues;

        return $this;
    }

    /**
     * @param  array<string, array<string>>  $config  filter key => allowed values, e.g. ['immobilienart' => ['haus']]
     */
    public function filter(array $config, ?string $name = null): self
    {
        $this->filters[$name ?? $this->key] = $config;

        return $this;
    }

    /**
     * @param  array<string, string>  $dependencies  permitted value key => parent field value
     */
    public function dependencies(array $dependencies): self
    {
        $this->dependencies = $dependencies;

        return $this;
    }

    public function compoundFields(string ...$fieldKeys): self
    {
        $this->compoundFields = array_values($fieldKeys);

        return $this;
    }

    public function measureFormat(FieldMeasureFormat $fieldMeasureFormat): self
    {
        $this->fieldMeasureFormat = $fieldMeasureFormat;

        return $this;
    }

    public function make(): Field
    {
        return new Field(
            key: $this->key,
            label: $this->label ?? ucfirst($this->key),
            type: $this->type,
            length: $this->length,
            permittedValues: collect($this->permittedValues)
                ->mapWithKeys(fn (string $label, int|string $key): array => [(string) $key => new PermittedValue((string) $key, $label)]),
            default: $this->default,
            filters: collect($this->filters)
                ->mapWithKeys(fn (array $config, string $name): array => [$name => new FieldFilter($name, collect($config))]),
            dependencies: collect($this->dependencies)
                ->map(fn (string $parentFieldValue, int|string $permittedValueKey): FieldDependency => new FieldDependency((string) $permittedValueKey, $parentFieldValue))
                ->values(),
            compoundFields: collect($this->compoundFields),
            fieldMeasureFormat: $this->fieldMeasureFormat,
        );
    }
}
