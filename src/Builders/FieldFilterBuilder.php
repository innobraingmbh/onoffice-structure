<?php

declare(strict_types=1);

namespace Innobrain\Structure\Builders;

use Illuminate\Support\Traits\Conditionable;
use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Dtos\Field;

class FieldFilterBuilder
{
    use Conditionable;

    /**
     * @var array<string, string>
     */
    private array $filters = [];

    public function __construct(private readonly FieldCollection $fields) {}

    public function where(string $key, string $value): self
    {
        $this->filters[$key] = $value;

        return $this;
    }

    public function get(): FieldCollection
    {
        if ($this->filters === []) {
            return $this->fields;
        }

        return $this->fields->filter(fn (Field $field): bool => $field->matchesFilters($this->filters));
    }

    public function first(): ?Field
    {
        return $this->get()->first();
    }

    /**
     * @return array<string, string>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}
