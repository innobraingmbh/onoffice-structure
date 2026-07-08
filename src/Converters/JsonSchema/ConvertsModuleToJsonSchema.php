<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\JsonSchema;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ObjectType;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\Module;

trait ConvertsModuleToJsonSchema
{
    public function convertModule(Module $module): ObjectType
    {
        $properties = $module->fields
            ->toBase()
            ->mapWithKeys(fn (Field $field) => $this->convertField($field))
            ->all();

        $schema = JsonSchema::object($properties)
            ->title($module->key->value);

        if ($this->includeDescriptions && $module->label !== '') {
            $schema->description($module->label);
        }

        return $schema;
    }
}
