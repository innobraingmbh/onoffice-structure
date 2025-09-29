<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\StringType;
use Illuminate\JsonSchema\Types\Type;
use Innobrain\Structure\Contracts\ConvertStrategy;
use Innobrain\Structure\DTOs\Field;
use Innobrain\Structure\DTOs\FieldDependency;
use Innobrain\Structure\DTOs\FieldFilter;
use Innobrain\Structure\DTOs\Module;
use Innobrain\Structure\DTOs\PermittedValue;
use Innobrain\Structure\Enums\FieldType;

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
    /**
     * @param  bool  $includeNullable  true ➜ mark fields as nullable when they have no default
     * @param  bool  $includeDescriptions  true ➜ include field labels as descriptions
     */
    public function __construct(
        private bool $includeNullable = true,
        private bool $includeDescriptions = true,
    ) {}

    /* ---------------------------------------------------------------------
     * ConvertStrategy – leaf DTOs
     * ------------------------------------------------------------------- */

    public function convertPermittedValue(PermittedValue $pv): string
    {
        // Permitted values are handled at the Field level as enum options
        return $pv->key;
    }

    public function convertFieldDependency(FieldDependency $fd): array
    {
        // Dependencies could be used to determine required fields
        // For now, we'll return metadata that can be used later
        return [
            'field' => $fd->dependentFieldKey,
            'value' => $fd->dependentFieldValue,
        ];
    }

    public function convertFieldFilter(FieldFilter $ff): array
    {
        // Filters aren't directly represented in Prism schemas
        // Return metadata for potential future use
        return [
            'name' => $ff->name,
            'config' => $ff->config->toArray(),
        ];
    }

    /* ---------------------------------------------------------------------
     * ConvertStrategy – aggregates
     * ------------------------------------------------------------------- */

    /**
     * @return array<string, Type>
     */
    public function convertField(Field $field): array
    {
        return $this->createBaseSchema($field);
    }

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

    /* ---------------------------------------------------------------------
     * Internal helpers
     * ------------------------------------------------------------------- */

    /**
     * @return array<string, Type>
     */
    private function createBaseSchema(Field $field): array
    {
        $name = $field->key;
        $description = $this->includeDescriptions ? $field->label : null;
        $nullable = $this->includeNullable && $field->default === null;

        return match ($field->type) {
            FieldType::VarChar, FieldType::Text, FieldType::Blob => $this->createStringSchema($field, $name, $description, $nullable),
            FieldType::Integer => $this->createStandardSchema('integer', $name, $description, $nullable),
            FieldType::Float => $this->createStandardSchema('number', $name, $description, $nullable),
            FieldType::Boolean => $this->createStandardSchema('boolean', $name, $description, $nullable),
            FieldType::Date => $this->createStandardSchema('string', $name, $description ? $description.' (Date format: YYYY-MM-DD)' : 'Date format: YYYY-MM-DD', $nullable),
            FieldType::DateTime => $this->createStandardSchema('string', $name, $description ? $description.' (DateTime format: ISO 8601)' : 'DateTime format: ISO 8601', $nullable),
            FieldType::SingleSelect => $this->createEnumSchema($field, $name, $description, $nullable),
            FieldType::MultiSelect => $this->createMultiSelectSchema($field, $name, $description, $nullable),
        };
    }

    private function createStandardSchema(string $type, string $name, ?string $description, bool $nullable): array
    {
        $jsonSchema = JsonSchema::{$type}()
            ->title($name)
            ->description($description ?? '');

        if (! $nullable) {
            $jsonSchema->required();
        }

        return [$name => $jsonSchema];
    }

    /**
     * @return array<string, StringType>
     */
    private function createStringSchema(Field $field, string $name, ?string $description, bool $nullable): array
    {
        $jsonSchema = JsonSchema::string()
            ->title($name);

        // Add length constraint info to description if available
        $finalDescription = $description ?? '';
        if ($field->length && $this->includeDescriptions) {
            $lengthInfo = " (max length: $field->length)";
            $jsonSchema->description($finalDescription !== '' && $finalDescription !== '0' ? $finalDescription.$lengthInfo : $lengthInfo);
        }

        if ($field->length) {
            $jsonSchema->max($field->length);
        }

        if (! $nullable) {
            $jsonSchema->required();
        }

        return [$name => $jsonSchema];
    }

    /**
     * @return array<string, ArrayType|StringType>
     */
    private function createEnumSchema(Field $field, string $name, ?string $description, bool $nullable): array
    {
        if ($field->permittedValues->isEmpty()) {
            // If no permitted values, fall back to StringSchema
            $jsonSchema = JsonSchema::string()
                ->title($name)
                ->description($description ?? '');

            if (! $nullable) {
                $jsonSchema->required();
            }

            return [$name => $jsonSchema];
        }

        $options = $field->permittedValues
            ->map(fn (PermittedValue $pv) => $pv->key)
            ->values()
            ->toArray();

        $jsonSchema = JsonSchema::array()
            ->title($name)
            ->enum($options)
            ->description($description ?? '');

        if (! $nullable) {
            $jsonSchema->required();
        }

        return [$name => $jsonSchema];
    }

    /**
     * @return array<string, ArrayType>
     */
    private function createMultiSelectSchema(Field $field, string $name, ?string $description, bool $nullable): array
    {
        if ($field->permittedValues->isEmpty()) {
            // If no permitted values, create array of strings
            $jsonSchema = JsonSchema::array()
                ->title($name)
                ->items(
                    JsonSchema::string()
                        ->title($name.'_item')
                        ->description('Item value')
                        ->required()
                )
                ->description($description ?? '');

            if (! $nullable) {
                $jsonSchema->required();
            }

            return [$name => $jsonSchema];
        }

        // Create enum schema for array items
        $options = $field->permittedValues
            ->map(fn (PermittedValue $pv) => $pv->key)
            ->values()
            ->toArray();

        $jsonSchema = JsonSchema::array()
            ->title($name)
            ->enum($options)
            ->description($description ?? '');

        if (! $nullable) {
            $jsonSchema->required();
        }

        return [$name => $jsonSchema];
    }
}
