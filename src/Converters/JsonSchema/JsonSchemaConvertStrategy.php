<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\JsonSchema;

use Innobrain\Structure\Contracts\ConvertStrategy;

/**
 * Converts fields into JSON Schema types and modules into an object type
 * with one property per writable field.
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
