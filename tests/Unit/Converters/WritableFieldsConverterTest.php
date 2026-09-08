<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Converters\JsonSchema\JsonSchemaConvertStrategy;
use Innobrain\Structure\Converters\LaravelRules\LaravelRulesConvertStrategy;
use Innobrain\Structure\Converters\PrismSchema\PrismSchemaConvertStrategy;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\Module;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\FieldType;
use Prism\Prism\Contracts\Schema;

/**
 * Module conversions only include fields that can be written to the API:
 * hints and dividing lines carry no data, and compound fields are set
 * through their individual fields.
 */
describe('writable fields in module converters', function () {
    function writableTestField(string $key, FieldType $type, array $compoundFields = []): Field
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
            compoundFields: new Collection($compoundFields),
            fieldMeasureFormat: null,
        );
    }

    function writableTestModule(): Module
    {
        return new Module(
            key: FieldConfigurationModule::Address,
            label: 'Address',
            fields: new FieldCollection([
                'Anrede' => writableTestField('Anrede', FieldType::VarChar),
                'Titel' => writableTestField('Titel', FieldType::VarChar),
                'Anrede-Titel' => writableTestField('Anrede-Titel', FieldType::VarChar, ['Anrede', 'Titel']),
                'Hinweis' => writableTestField('Hinweis', FieldType::RedHint),
                'Warnung' => writableTestField('Warnung', FieldType::BlackHint),
                'Trennlinie' => writableTestField('Trennlinie', FieldType::DividingLine),
            ]),
        );
    }

    it('reports hints, dividing lines and compound fields as not writable', function () {
        $fields = writableTestModule()->fields;

        expect($fields->get('Anrede')->isWritable())->toBeTrue()
            ->and($fields->get('Anrede-Titel')->isWritable())->toBeFalse()
            ->and($fields->get('Hinweis')->isWritable())->toBeFalse()
            ->and($fields->get('Warnung')->isWritable())->toBeFalse()
            ->and($fields->get('Trennlinie')->isWritable())->toBeFalse();
    });

    it('only emits Laravel rules for writable fields', function () {
        $rules = writableTestModule()->convert(new LaravelRulesConvertStrategy);

        expect(array_keys($rules))->toBe(['Anrede', 'Titel']);
    });

    it('only emits JSON Schema properties for writable fields', function () {
        $schema = writableTestModule()->convert(new JsonSchemaConvertStrategy)->toArray();

        expect(array_keys($schema['properties']))->toBe(['Anrede', 'Titel'])
            ->and($schema['required'])->toBe(['Anrede', 'Titel']);
    });

    it('only emits Prism properties for writable fields', function () {
        $schema = writableTestModule()->convert(new PrismSchemaConvertStrategy);

        expect(array_map(fn (Schema $property): string => $property->name(), $schema->properties))->toBe(['Anrede', 'Titel'])
            ->and($schema->requiredFields)->toBe(['Anrede', 'Titel']);
    });

    it('still converts a single non-writable field when asked directly', function () {
        $field = writableTestField('Trennlinie', FieldType::DividingLine);

        expect($field->convert(new LaravelRulesConvertStrategy))->toBe('string|nullable');
    });
});
