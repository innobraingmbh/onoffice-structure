<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\JsonSchema;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ObjectType;
use Innobrain\Structure\DTOs\Field;
use Innobrain\Structure\DTOs\Module;

trait JsonSchemaModule
{
    public function convertModule(Module $module): ObjectType
    {
        $properties = [];

        foreach ($module->fields as $field) {
            /** @var Field $field */
            $schema = $this->convertField($field);

            $properties = array_merge($properties, $schema);
        }

        $description = $this->includeDescriptions ? $module->label : '';

        return JsonSchema::object($properties)
            ->title($module->key->value)
            ->description($description);
    }
}
