<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters;

use Innobrain\Structure\Contracts\ConvertStrategy;
use Innobrain\Structure\DTOs\Field;
use Innobrain\Structure\DTOs\FieldDependency;
use Innobrain\Structure\DTOs\FieldFilter;
use Innobrain\Structure\DTOs\Module;
use Innobrain\Structure\DTOs\PermittedValue;
use Innobrain\Structure\Enums\FieldType;
use Prism\Prism\Contracts\Schema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

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

    public function convertPermittedValue(PermittedValue $pv): mixed
    {
        // Permitted values are handled at the Field level as enum options
        return $pv->key;
    }

    public function convertFieldDependency(FieldDependency $fd): mixed
    {
        // Dependencies could be used to determine required fields
        // For now, we'll return metadata that can be used later
        return [
            'field' => $fd->dependentFieldKey,
            'value' => $fd->dependentFieldValue,
        ];
    }

    public function convertFieldFilter(FieldFilter $ff): mixed
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

    public function convertField(Field $field): mixed
    {
        return $this->createBaseSchema($field);
    }

    /**
     * @return ObjectSchema
     */
    public function convertModule(Module $module): mixed
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

    /* ---------------------------------------------------------------------
     * Internal helpers
     * ------------------------------------------------------------------- */

    private function createBaseSchema(Field $field): Schema
    {
        $name = $field->key;
        $description = $this->includeDescriptions ? $field->label : null;
        $nullable = $this->includeNullable && $field->default === null;

        return match ($field->type) {
            FieldType::VarChar, FieldType::Text, FieldType::Blob => $this->createStringSchema($field, $name, $description, $nullable),
            FieldType::Integer => new NumberSchema(
                name: $name,
                description: $description ?? '',
                nullable: $nullable
            ),
            FieldType::Float => new NumberSchema(
                name: $name,
                description: $description ?? '',
                nullable: $nullable
            ),
            FieldType::Boolean => new BooleanSchema(
                name: $name,
                description: $description ?? '',
                nullable: $nullable
            ),
            FieldType::Date => new StringSchema(
                name: $name,
                description: $description ? $description.' (Date format: YYYY-MM-DD)' : 'Date format: YYYY-MM-DD',
                nullable: $nullable
            ),
            FieldType::DateTime => new StringSchema(
                name: $name,
                description: $description ? $description.' (DateTime format: ISO 8601)' : 'DateTime format: ISO 8601',
                nullable: $nullable
            ),
            FieldType::SingleSelect => $this->createEnumSchema($field, $name, $description, $nullable),
            FieldType::MultiSelect => $this->createMultiSelectSchema($field, $name, $description, $nullable),
        };
    }

    private function createStringSchema(Field $field, string $name, ?string $description, bool $nullable): StringSchema
    {
        // Add length constraint info to description if available
        $finalDescription = $description ?? '';
        if ($field->length && $this->includeDescriptions) {
            $lengthInfo = " (max length: {$field->length})";
            $finalDescription = $finalDescription !== '' && $finalDescription !== '0' ? $finalDescription.$lengthInfo : $lengthInfo;
        }

        return new StringSchema(
            name: $name,
            description: $finalDescription,
            nullable: $nullable
        );
    }

    private function createEnumSchema(Field $field, string $name, ?string $description, bool $nullable): Schema
    {
        if ($field->permittedValues->isEmpty()) {
            // If no permitted values, fall back to StringSchema
            return new StringSchema(
                name: $name,
                description: $description ?? '',
                nullable: $nullable
            );
        }

        $options = $field->permittedValues
            ->map(fn (PermittedValue $pv) => $pv->key)
            ->values()
            ->toArray();

        return new EnumSchema(
            name: $name,
            description: $description ?? '',
            options: $options,
            nullable: $nullable
        );
    }

    private function createMultiSelectSchema(Field $field, string $name, ?string $description, bool $nullable): ArraySchema
    {
        if ($field->permittedValues->isEmpty()) {
            // If no permitted values, create array of strings
            $itemSchema = new StringSchema(
                name: $name.'_item',
                description: 'Item value',
                nullable: false
            );
        } else {
            // Create enum schema for array items
            $options = $field->permittedValues
                ->map(fn (PermittedValue $pv) => $pv->key)
                ->values()
                ->toArray();

            $itemSchema = new EnumSchema(
                name: $name.'_item',
                description: 'Allowed value',
                options: $options,
                nullable: false
            );
        }

        return new ArraySchema(
            name: $name,
            description: $description ?? '',
            items: $itemSchema,
            nullable: $nullable
        );
    }
}
