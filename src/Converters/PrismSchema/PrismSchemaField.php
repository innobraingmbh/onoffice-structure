<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\PrismSchema;

use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\PermittedValue;
use Innobrain\Structure\Enums\FieldType;
use Prism\Prism\Contracts\Schema;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\StringSchema;

trait PrismSchemaField
{
    public function convertField(Field $field): Schema
    {
        return $this->createBaseSchema($field);
    }

    private function createBaseSchema(Field $field): Schema
    {
        $name = $field->key;
        $description = $this->includeDescriptions ? $field->label : null;
        $nullable = $this->includeNullable && $field->default === null;

        return match ($field->type) {
            FieldType::VarChar, FieldType::Text, FieldType::Blob => $this->createStringSchema($field, $name, $description, $nullable),
            FieldType::Integer, FieldType::Float => new NumberSchema(
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
