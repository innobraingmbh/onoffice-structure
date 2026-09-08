<?php

declare(strict_types=1);

namespace Innobrain\Structure\Testing;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\Module;
use Innobrain\Structure\Enums\FieldConfigurationModule;

/**
 * Builds Module DTOs for tests with the fields keyed the way the parser keys them.
 */
final class ModuleFactory
{
    use Conditionable;

    private ?string $label = null;

    /** @var array<int, Field> */
    private array $fields = [];

    private function __construct(
        private readonly FieldConfigurationModule $module,
    ) {}

    public static function new(FieldConfigurationModule $module): self
    {
        return new self($module);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function fields(Field|FieldFactory ...$fields): self
    {
        foreach ($fields as $field) {
            $this->fields[] = $field instanceof FieldFactory ? $field->make() : $field;
        }

        return $this;
    }

    public function make(): Module
    {
        $fields = new FieldCollection;

        foreach ($this->fields as $field) {
            $fields->put($field->key, $field);
        }

        return new Module(
            key: $this->module,
            label: $this->label ?? Str::ucfirst($this->module->value),
            fields: $fields,
        );
    }
}
