<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\PrismSchema;

use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\Module;
use Prism\Prism\Schema\ObjectSchema;

trait ConvertsModuleToPrismSchema
{
    public function convertModule(Module $module): ObjectSchema
    {
        $properties = [];
        $requiredFields = [];

        foreach ($module->fields as $fieldKey => $field) {
            /** @var Field $field */
            $properties[] = $this->convertField($field);

            // Mark field as required if it has a default value
            if ($field->default !== null) {
                $requiredFields[] = $fieldKey;
            }
        }

        $description = $this->includeDescriptions ? $module->label : '';

        return new ObjectSchema(
            name: $module->key->value,
            description: $description,
            properties: $properties,
            requiredFields: $requiredFields
        );
    }
}
