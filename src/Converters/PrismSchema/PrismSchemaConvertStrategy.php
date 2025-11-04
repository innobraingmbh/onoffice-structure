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
    use ConvertsFieldDependencyToPrismSchema;
    use ConvertsFieldFilterToPrismSchema;
    use ConvertsFieldToPrismSchema;
    use ConvertsModuleToPrismSchema;
    use ConvertsPermittedValueToPrismSchema;

    /**
     * @param  bool  $includeNullable  true ➜ mark fields as nullable when they have no default
     * @param  bool  $includeDescriptions  true ➜ include field labels as descriptions
     * @param  string[]  $requiredFieldKeys  list of field keys to mark as required, overrides internal logic
     */
    public function __construct(
        private bool $includeNullable = true,
        private bool $includeDescriptions = true,
        private array $requiredFieldKeys = [],
    ) {}
}
