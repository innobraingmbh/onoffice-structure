<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\JsonSchema;

use Innobrain\Structure\Contracts\ConvertStrategy;

/**
 * Convert the package's DTOs into JSON Schema format.
 *
 * Typical usage:
 *   $schema = $module->convert(new JsonSchemaConvertStrategy());
 *   $fieldSchema = $field->convert(new JsonSchemaConvertStrategy());
 *
 * The strategy returns:
 *   • Module   ⇒ ObjectType with properties for each field
 *   • Field    ⇒ Type (type depends on field type)
 */
final readonly class JsonSchemaConvertStrategy implements ConvertStrategy
{
    use ConvertsFieldToJsonSchema;
    use ConvertsModuleToJsonSchema;

    /**
     * Fields without a default value are marked as required; fields with a
     * default are optional and carry it as the schema's "default".
     *
     * @param  bool  $includeNullable  true ➜ allow null for fields without a default
     * @param  bool  $includeDescriptions  true ➜ include field labels as descriptions
     */
    public function __construct(
        private bool $includeNullable = true,
        private bool $includeDescriptions = true,
    ) {}
}
