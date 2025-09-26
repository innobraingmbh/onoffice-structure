<?php

declare(strict_types=1);

use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\Support\Collection;
use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\Converters\JsonSchemaConvertStrategy;
use Innobrain\Structure\DTOs\Field;
use Innobrain\Structure\DTOs\FieldDependency;
use Innobrain\Structure\DTOs\Module;
use Innobrain\Structure\DTOs\PermittedValue;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\FieldType;

describe('JsonSchemaConvertStrategy Feature Tests', function () {
    it('converts a complete real-world module structure', function () {
        // Create a realistic estate module
        $module = new Module(
            key: FieldConfigurationModule::Estate,
            label: 'Real Estate Properties',
            fields: new FieldCollection([
                'objekttitel' => new Field(
                    key: 'objekttitel',
                    label: 'Property Title',
                    type: FieldType::VarChar,
                    length: 200,
                    permittedValues: collect(),
                    default: null,
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
                'objektart' => new Field(
                    key: 'objektart',
                    label: 'Property Type',
                    type: FieldType::SingleSelect,
                    length: null,
                    permittedValues: collect([
                        'haus' => new PermittedValue('haus', 'House'),
                        'wohnung' => new PermittedValue('wohnung', 'Apartment'),
                        'grundstueck' => new PermittedValue('grundstueck', 'Land'),
                    ]),
                    default: null,
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
                'kaufpreis' => new Field(
                    key: 'kaufpreis',
                    label: 'Purchase Price',
                    type: FieldType::Float,
                    length: null,
                    permittedValues: collect(),
                    default: null,
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: 'EUR'
                ),
                'zimmer' => new Field(
                    key: 'zimmer',
                    label: 'Number of Rooms',
                    type: FieldType::Integer,
                    length: null,
                    permittedValues: collect(),
                    default: '0',
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
                'ausstattung' => new Field(
                    key: 'ausstattung',
                    label: 'Features',
                    type: FieldType::MultiSelect,
                    length: null,
                    permittedValues: collect([
                        'balkon' => new PermittedValue('balkon', 'Balcony'),
                        'garten' => new PermittedValue('garten', 'Garden'),
                        'garage' => new PermittedValue('garage', 'Garage'),
                        'keller' => new PermittedValue('keller', 'Basement'),
                        'aufzug' => new PermittedValue('aufzug', 'Elevator'),
                    ]),
                    default: null,
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
                'verfuegbar' => new Field(
                    key: 'verfuegbar',
                    label: 'Available',
                    type: FieldType::Boolean,
                    length: null,
                    permittedValues: collect(),
                    default: 'true',
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
                'verfuegbar_ab' => new Field(
                    key: 'verfuegbar_ab',
                    label: 'Available From',
                    type: FieldType::Date,
                    length: null,
                    permittedValues: collect(),
                    default: null,
                    filters: collect(),
                    dependencies: collect([
                        new FieldDependency('verfuegbar', 'true'),
                    ]),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
                'beschreibung' => new Field(
                    key: 'beschreibung',
                    label: 'Description',
                    type: FieldType::Text,
                    length: null,
                    permittedValues: collect(),
                    default: null,
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
            ])
        );

        $strategy = new JsonSchemaConvertStrategy;
        /** @var ObjectType $schema */
        $schema = $module->convert($strategy)->toArray();
        $properties = $schema['properties'];

        // Verify the schema structure
        expect($schema)->toBeArray()
            ->and($properties)->toHaveCount(8);

        // Check specific field conversions
        // String field
        expect($properties['objekttitel'])->toBeArray()
            ->and($properties['objekttitel']['title'])->toBe('objekttitel')
            ->and($properties['objekttitel']['description'])->toContain('Property Title')
            ->and($properties['objekttitel']['description'])->toContain('max length: 200')
            ->and($properties['objekttitel']['type'])->toBe('string')
            ->and($properties['objekttitel']['maxLength'])->toBe(200)
            // Enum field (single select)
            ->and($properties['objektart'])->toBeArray()
            ->and($properties['objektart']['title'])->toBe('objektart')
            ->and($properties['objektart']['description'])->toContain('Property Type')
            ->and($properties['objektart']['type'])->toBe('array')
            ->and($properties['objektart']['enum'])->toBe(['haus', 'wohnung', 'grundstueck'])
            // Number field (float)
            ->and($properties['kaufpreis'])->toBeArray()
            ->and($properties['kaufpreis']['title'])->toBe('kaufpreis')
            ->and($properties['kaufpreis']['description'])->toContain('Purchase Price')
            ->and($properties['kaufpreis']['type'])->toBe('number')
            // Integer field
            ->and($properties['zimmer'])->toBeArray()
            ->and($properties['zimmer']['title'])->toBe('zimmer')
            ->and($properties['zimmer']['description'])->toContain('Number of Rooms')
            ->and($properties['zimmer']['type'])->toBe('integer')
            // Array field (multi select)
            ->and($properties['ausstattung'])->toBeArray()
            ->and($properties['ausstattung']['title'])->toBe('ausstattung')
            ->and($properties['ausstattung']['description'])->toContain('Features')
            ->and($properties['ausstattung']['type'])->toBe('array')
            ->and($properties['ausstattung']['enum'])->toBe(['balkon', 'garten', 'garage', 'keller', 'aufzug'])
            // Boolean field
            ->and($properties['verfuegbar'])->toBeArray()
            ->and($properties['verfuegbar']['title'])->toBe('verfuegbar')
            ->and($properties['verfuegbar']['description'])->toContain('Available')
            ->and($properties['verfuegbar']['type'])->toBe('boolean')
            // Date field
            ->and($properties['verfuegbar_ab'])->toBeArray()
            ->and($properties['verfuegbar_ab']['title'])->toBe('verfuegbar_ab')
            ->and($properties['verfuegbar_ab']['description'])->toContain('Available From')
            ->and($properties['verfuegbar_ab']['description'])->toContain('YYYY-MM-DD')
            ->and($properties['verfuegbar_ab']['type'])->toBe('string')
            // Text field
            ->and($properties['beschreibung'])->toBeArray()
            ->and($properties['beschreibung']['title'])->toBe('beschreibung')
            ->and($properties['beschreibung']['type'])->toBe('string')
            // Check required fields
            ->and($schema['required'])->toBeArray()
            ->toContain('zimmer')
            ->toContain('verfuegbar')
            ->not->toContain('objekttitel')
            ->not->toContain('verfuegbar_ab');
    });

    it('handles ModulesCollection conversion', function () {
        $collection = new ModulesCollection;

        // Add a simple module
        $collection->put('contact', new Module(
            key: FieldConfigurationModule::Address,
            label: 'Contact Information',
            fields: new FieldCollection([
                'email' => new Field(
                    key: 'email',
                    label: 'Email Address',
                    type: FieldType::VarChar,
                    length: 255,
                    permittedValues: collect(),
                    default: null,
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
                'phone' => new Field(
                    key: 'phone',
                    label: 'Phone Number',
                    type: FieldType::VarChar,
                    length: 20,
                    permittedValues: collect(),
                    default: null,
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
            ])
        ));

        $strategy = new JsonSchemaConvertStrategy;

        // Convert each module in the collection
        $schemas = $collection->map(fn ($module) => $module->convert($strategy));

        expect($schemas)->toHaveCount(1)
            ->and($schemas->first())->toBeInstanceOf(ObjectType::class)
            ->and($schemas->first()->toArray()['properties'])->toHaveCount(2);
    });

    it('converts complex nested structure with all field types', function () {
        $module = new Module(
            key: FieldConfigurationModule::Estate,
            label: 'Complex Module',
            fields: new FieldCollection([
                'varchar_field' => new Field(
                    key: 'varchar_field',
                    label: 'VarChar Field',
                    type: FieldType::VarChar,
                    length: 100,
                    permittedValues: collect(),
                    default: 'default_value',
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
                'text_field' => new Field(
                    key: 'text_field',
                    label: 'Text Field',
                    type: FieldType::Text,
                    length: null,
                    permittedValues: collect(),
                    default: null,
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
                'blob_field' => new Field(
                    key: 'blob_field',
                    label: 'Blob Field',
                    type: FieldType::Blob,
                    length: null,
                    permittedValues: collect(),
                    default: null,
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
                'integer_field' => new Field(
                    key: 'integer_field',
                    label: 'Integer Field',
                    type: FieldType::Integer,
                    length: null,
                    permittedValues: collect(),
                    default: '42',
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
                'float_field' => new Field(
                    key: 'float_field',
                    label: 'Float Field',
                    type: FieldType::Float,
                    length: null,
                    permittedValues: collect(),
                    default: '3.14',
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
                'boolean_field' => new Field(
                    key: 'boolean_field',
                    label: 'Boolean Field',
                    type: FieldType::Boolean,
                    length: null,
                    permittedValues: collect(),
                    default: 'false',
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
                'date_field' => new Field(
                    key: 'date_field',
                    label: 'Date Field',
                    type: FieldType::Date,
                    length: null,
                    permittedValues: collect(),
                    default: null,
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
                'datetime_field' => new Field(
                    key: 'datetime_field',
                    label: 'DateTime Field',
                    type: FieldType::DateTime,
                    length: null,
                    permittedValues: collect(),
                    default: null,
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
                'single_select_field' => new Field(
                    key: 'single_select_field',
                    label: 'Single Select Field',
                    type: FieldType::SingleSelect,
                    length: null,
                    permittedValues: collect([
                        'option1' => new PermittedValue('option1', 'Option 1'),
                        'option2' => new PermittedValue('option2', 'Option 2'),
                    ]),
                    default: 'option1',
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
                'multi_select_field' => new Field(
                    key: 'multi_select_field',
                    label: 'Multi Select Field',
                    type: FieldType::MultiSelect,
                    length: null,
                    permittedValues: collect([
                        'tag1' => new PermittedValue('tag1', 'Tag 1'),
                        'tag2' => new PermittedValue('tag2', 'Tag 2'),
                        'tag3' => new PermittedValue('tag3', 'Tag 3'),
                    ]),
                    default: null,
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
            ])
        );

        $strategy = new JsonSchemaConvertStrategy;
        $schema = $module->convert($strategy);

        expect($schema)->toBeInstanceOf(ObjectType::class)
            ->and($schema->toArray()['properties'])->toHaveCount(10);

        // Verify all field types are converted correctly
        $propertyTypes = array_map(fn ($prop) => $prop['type'], $schema->toArray()['properties']);

        expect($propertyTypes)->toBe([
            'varchar_field' => 'string',
            'text_field' => 'string',
            'blob_field' => 'string',
            'integer_field' => 'integer',
            'float_field' => 'number',
            'boolean_field' => 'boolean',
            'date_field' => 'string',
            'datetime_field' => 'string',
            'single_select_field' => 'array',
            'multi_select_field' => 'array',
        ]);

        $schema = $schema->toArray();

        // Check required fields (those with defaults)
        expect($schema['required'])->toContain('varchar_field')
            ->toContain('integer_field')
            ->toContain('float_field')
            ->toContain('boolean_field')
            ->toContain('single_select_field');
    });

    it('respects configuration options', function () {
        $module = new Module(
            key: FieldConfigurationModule::Estate,
            label: 'Test Module',
            fields: new FieldCollection([
                'field1' => new Field(
                    key: 'field1',
                    label: 'Field 1',
                    type: FieldType::VarChar,
                    length: 50,
                    permittedValues: collect(),
                    default: null,
                    filters: collect(),
                    dependencies: collect(),
                    compoundFields: collect(),
                    fieldMeasureFormat: null
                ),
            ])
        );

        // Test with descriptions disabled
        $strategyNoDesc = new JsonSchemaConvertStrategy(
            includeNullable: true,
            includeDescriptions: false
        );

        $schemaNoDesc = $module->convert($strategyNoDesc)->toArray();

        expect($schemaNoDesc['description'])->toBe('')
            ->and($schemaNoDesc['properties']['field1'])->not->toHaveKey('description');

        // Test with nullable disabled
        $strategyNoNull = new JsonSchemaConvertStrategy(
            includeNullable: false,
            includeDescriptions: true
        );

        $schemaNoNull = $module->convert($strategyNoNull)->toArray();

        expect($schemaNoNull['properties']['field1']['nullable'] ?? false)->toBeFalse()
            ->and($schemaNoNull['required'])->toBe(['field1']);
    });
});
