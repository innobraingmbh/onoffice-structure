<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\JsonSchema;

use Innobrain\Structure\Converters\Concerns\ConvertStrategy;

/**
 * Convert the package's DTOs into Prism PHP schemas.
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
    use ConvertsFieldDependencyToJsonSchema;
    use ConvertsFieldFilterToJsonSchema;
    use ConvertsFieldToJsonSchema;
    use ConvertsModuleToJsonSchema;
    use ConvertsPermittedValueToJsonSchema;

    /**
     * @param  bool  $includeNullable  true ➜ mark fields as nullable when they have no default
     * @param  bool  $includeDescriptions  true ➜ include field labels as descriptions
     */
    public function __construct(
        private bool $includeNullable = true,
        private bool $includeDescriptions = true,
    ) {}
}
