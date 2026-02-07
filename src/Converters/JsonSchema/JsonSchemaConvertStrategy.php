<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\JsonSchema;

use Innobrain\Structure\Converters\Concerns\BaseConvertStrategy;

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
final readonly class JsonSchemaConvertStrategy extends BaseConvertStrategy
{
    use ConvertsFieldToJsonSchema;
    use ConvertsModuleToJsonSchema;

    /**
     * @param  bool  $includeNullable  true ➜ mark fields as nullable when they have no default
     * @param  bool  $includeDescriptions  true ➜ include field labels as descriptions
     */
    public function __construct(
        private bool $includeNullable = true,
        private bool $includeDescriptions = true,
    ) {}
}
