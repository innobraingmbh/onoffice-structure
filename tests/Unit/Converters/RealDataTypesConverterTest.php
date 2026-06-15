<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Innobrain\Structure\Converters\JsonSchema\JsonSchemaConvertStrategy;
use Innobrain\Structure\Converters\LaravelRules\LaravelRulesConvertStrategy;
use Innobrain\Structure\Converters\PrismSchema\PrismSchemaConvertStrategy;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Enums\FieldType;
use Prism\Prism\Schema\StringSchema;

/**
 * Unit tests ensuring every converter handles each real data type case without
 * throwing an UnhandledMatchError and produces the expected string-like output.
 */
describe('real data types in converters', function () {
    /**
     * Build a minimal Field DTO for a given FieldType.
     */
    function makeField(FieldType $type, string $key = 'field'): Field
    {
        return new Field(
            key: $key,
            label: ucfirst($key),
            type: $type,
            length: null,
            permittedValues: new Collection,
            default: null,
            filters: new Collection,
            dependencies: new Collection,
            compoundFields: new Collection,
            fieldMeasureFormat: null,
        );
    }

    $realDataTypes = [
        'User' => FieldType::User,
        'File' => FieldType::File,
        'RedHint' => FieldType::RedHint,
        'BlackHint' => FieldType::BlackHint,
        'DividingLine' => FieldType::DividingLine,
    ];

    describe('LaravelRulesConvertStrategy', function () use ($realDataTypes) {
        it('produces string validation rule for each real data type', function (string $name, FieldType $type) {
            $field = makeField($type, strtolower($name));
            $strategy = new LaravelRulesConvertStrategy(pipeSyntax: true, includeNullable: true);

            $result = $field->convert($strategy);

            expect($result)->toBeString()
                ->toStartWith('string');
        })->with(
            collect($realDataTypes)
                ->map(fn (FieldType $type, string $name) => [$name, $type])
                ->values()
                ->all()
        );

        it('produces array validation rule for each real data type (array syntax)', function (string $name, FieldType $type) {
            $field = makeField($type, strtolower($name));
            $strategy = new LaravelRulesConvertStrategy(pipeSyntax: false, includeNullable: false);

            $result = $field->convert($strategy);

            expect($result)->toBeArray()
                ->and($result[0])->toBe('string');
        })->with(
            collect($realDataTypes)
                ->map(fn (FieldType $type, string $name) => [$name, $type])
                ->values()
                ->all()
        );

        it('appends nullable when field has no default', function () {
            $field = makeField(FieldType::User, 'Benutzer');
            $strategy = new LaravelRulesConvertStrategy(pipeSyntax: true, includeNullable: true);

            expect($field->convert($strategy))->toBe('string|nullable');
        });

        it('omits nullable when field has a default', function () {
            $field = new Field(
                key: 'Benutzer',
                label: 'Betreuer',
                type: FieldType::User,
                length: null,
                permittedValues: new Collection,
                default: 'mschmidt',
                filters: new Collection,
                dependencies: new Collection,
                compoundFields: new Collection,
                fieldMeasureFormat: null,
            );
            $strategy = new LaravelRulesConvertStrategy(pipeSyntax: true, includeNullable: true);

            expect($field->convert($strategy))->toBe('string');
        });

        it('respects length constraint on User field', function () {
            $field = new Field(
                key: 'Benutzer',
                label: 'Betreuer',
                type: FieldType::User,
                length: 40,
                permittedValues: new Collection,
                default: null,
                filters: new Collection,
                dependencies: new Collection,
                compoundFields: new Collection,
                fieldMeasureFormat: null,
            );
            $strategy = new LaravelRulesConvertStrategy(pipeSyntax: true, includeNullable: true);

            // length rule only applies to VarChar, not User — confirm no max: rule
            expect($field->convert($strategy))->toBe('string|nullable')
                ->not->toContain('max:');
        });
    });

    describe('JsonSchemaConvertStrategy', function () use ($realDataTypes) {
        it('produces a StringType schema for each real data type', function (string $name, FieldType $type) {
            $field = makeField($type, strtolower($name));
            $strategy = new JsonSchemaConvertStrategy(includeNullable: true, includeDescriptions: true);

            $result = $strategy->convertField($field);

            expect($result)->toBeArray()
                ->toHaveKey(strtolower($name));

            $schemaArray = $result[strtolower($name)]->toArray();
            // StringType produces type=["string","null"] when nullable
            expect($schemaArray['type'])->toContain('string');
        })->with(
            collect($realDataTypes)
                ->map(fn (FieldType $type, string $name) => [$name, $type])
                ->values()
                ->all()
        );
    });

    describe('PrismSchemaConvertStrategy', function () use ($realDataTypes) {
        it('produces a StringSchema for each real data type', function (string $name, FieldType $type) {
            $field = makeField($type, strtolower($name));
            $strategy = new PrismSchemaConvertStrategy(includeNullable: true, includeDescriptions: true);

            $result = $strategy->convertField($field);

            expect($result)->toBeInstanceOf(StringSchema::class)
                ->and($result->name)->toBe(strtolower($name));
        })->with(
            collect($realDataTypes)
                ->map(fn (FieldType $type, string $name) => [$name, $type])
                ->values()
                ->all()
        );

        it('marks User field as nullable when it has no default', function () {
            $field = makeField(FieldType::User, 'Benutzer');
            $strategy = new PrismSchemaConvertStrategy(includeNullable: true);

            $result = $strategy->convertField($field);

            expect($result)->toBeInstanceOf(StringSchema::class)
                ->and($result->nullable)->toBeTrue();
        });
    });
});
