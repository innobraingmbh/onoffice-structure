<?php

declare(strict_types=1);

namespace Innobrain\Structure\Converters\JsonSchema;

use Illuminate\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\BooleanType;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\NumberType;
use Illuminate\JsonSchema\Types\StringType;
use Illuminate\JsonSchema\Types\Type;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Enums\FieldType;

trait ConvertsFieldToJsonSchema
{
    /**
     * @return array<string, Type>
     */
    public function convertField(Field $field): array
    {
        $schema = $this->createSchema($field);

        $schema->title($field->key)
            ->required(! $field->hasDefault())
            ->nullable($this->isNullable($field));

        return [$field->key => $schema];
    }

    private function createSchema(Field $field): Type
    {
        return match ($field->type) {
            FieldType::VarChar,
            FieldType::Text,
            FieldType::Blob,
            FieldType::User,
            FieldType::File,
            FieldType::RedHint,
            FieldType::BlackHint,
            FieldType::DividingLine => $this->createStringSchema($field),
            FieldType::Integer => $this->createIntegerSchema($field),
            FieldType::Float => $this->createNumberSchema($field),
            FieldType::Boolean => $this->createBooleanSchema($field),
            FieldType::Date => $this->createDateSchema($field, 'date'),
            FieldType::DateTime => $this->createDateSchema($field, 'date-time'),
            FieldType::SingleSelect => $this->createSingleSelectSchema($field),
            FieldType::MultiSelect => $this->createMultiSelectSchema($field),
        };
    }

    private function createStringSchema(Field $field): StringType
    {
        $schema = JsonSchema::string();

        if ($field->length !== null) {
            $schema->max($field->length);
        }

        if ($field->hasDefault()) {
            $schema->default($field->default);
        }

        $this->applyDescription($schema, $field);

        return $schema;
    }

    private function createIntegerSchema(Field $field): IntegerType
    {
        $schema = JsonSchema::integer();

        if ($field->hasDefault()) {
            $schema->default((int) $field->default);
        }

        $this->applyDescription($schema, $field);

        return $schema;
    }

    private function createNumberSchema(Field $field): NumberType
    {
        $schema = JsonSchema::number();

        if ($field->hasDefault()) {
            $schema->default((float) $field->default);
        }

        $this->applyDescription($schema, $field);

        return $schema;
    }

    private function createBooleanSchema(Field $field): BooleanType
    {
        $schema = JsonSchema::boolean();

        if ($field->hasDefault()) {
            $schema->default(filter_var($field->default, FILTER_VALIDATE_BOOLEAN));
        }

        $this->applyDescription($schema, $field);

        return $schema;
    }

    private function createDateSchema(Field $field, string $format): StringType
    {
        $schema = JsonSchema::string()->format($format);

        if ($field->hasDefault()) {
            $schema->default($field->default);
        }

        $this->applyDescription($schema, $field);

        return $schema;
    }

    private function createSingleSelectSchema(Field $field): StringType
    {
        $schema = JsonSchema::string();

        if ($field->hasPermittedValues()) {
            $options = $field->permittedValueKeys();

            if ($this->isNullable($field)) {
                $options[] = null;
            }

            $schema->enum($options);
        }

        if ($field->hasDefault()) {
            $schema->default($field->default);
        }

        $this->applyDescription($schema, $field);

        return $schema;
    }

    /**
     * The items are not marked unique: structured-output grammars (OpenAI
     * strict mode, vLLM/xgrammar) reject "uniqueItems", and consumers can
     * dedupe trivially.
     */
    private function createMultiSelectSchema(Field $field): ArrayType
    {
        $items = JsonSchema::string();

        if ($field->hasPermittedValues()) {
            $items->enum($field->permittedValueKeys());
        }

        $schema = JsonSchema::array()->items($items);

        $this->applyDescription($schema, $field);

        return $schema;
    }

    private function applyDescription(Type $schema, Field $field): void
    {
        if ($this->includeDescriptions && $field->label !== '') {
            $schema->description($field->label);
        }
    }

    private function isNullable(Field $field): bool
    {
        return $this->includeNullable && ! $field->hasDefault();
    }
}
