<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\PrismSchema;

use Innobrain\Structure\Converters\Concerns\ConvertStrategy;

/**
 * Convert the package's DTOs into Prism PHP schemas.
 *
 * Typical usage:
 *   $schema = $module->convert(new PrismSchemaConvertStrategy());
 *   $fieldSchema = $field->convert(new PrismSchemaConvertStrategy());
 *
 * The strategy returns:
 *   • Module   ⇒ ObjectSchema with properties for each field
 *   • Field    ⇒ Schema (type depends on field type)
 */
final readonly class PrismSchemaConvertStrategy implements ConvertStrategy
{
    use PrismSchemaField;
    use PrismSchemaFieldDependency;
    use PrismSchemaFieldFilter;
    use PrismSchemaModule;
    use PrismSchemaPermittedValue;

    /**
     * @param  bool  $includeNullable  true ➜ mark fields as nullable when they have no default
     * @param  bool  $includeDescriptions  true ➜ include field labels as descriptions
     */
    public function __construct(
        private bool $includeNullable = true,
        private bool $includeDescriptions = true,
    ) {}
}
