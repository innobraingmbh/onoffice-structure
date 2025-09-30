<?php

declare(strict_types=1);

use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\Converters\PrismSchema\PrismSchemaConvertStrategy;
use Innobrain\Structure\DTOs\Field;
use Innobrain\Structure\DTOs\FieldDependency;
use Innobrain\Structure\DTOs\Module;
use Innobrain\Structure\DTOs\PermittedValue;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\FieldType;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

describe('PrismSchemaConvertStrategy Feature Tests', function () {
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

        $strategy = new PrismSchemaConvertStrategy;
        $schema = $module->convert($strategy);

        // Verify the schema structure
        expect($schema)->toBeInstanceOf(ObjectSchema::class)
            ->and($schema->name)->toBe('estate')
            ->and($schema->description)->toBe('Real Estate Properties')
            ->and($schema->properties)->toHaveCount(8);

        // Check specific field conversions
        $properties = $schema->properties;

        // String field
        expect($properties[0])->toBeInstanceOf(StringSchema::class)
            ->and($properties[0]->name)->toBe('objekttitel')
            ->and($properties[0]->description)->toContain('Property Title')
            ->and($properties[0]->description)->toContain('max length: 200');

        // Enum field (single select)
        expect($properties[1])->toBeInstanceOf(EnumSchema::class)
            ->and($properties[1]->name)->toBe('objektart')
            ->and($properties[1]->options)->toBe(['haus', 'wohnung', 'grundstueck']);

        // Number field (float)
        expect($properties[2])->toBeInstanceOf(NumberSchema::class)
            ->and($properties[2]->name)->toBe('kaufpreis');

        // Integer field
        expect($properties[3])->toBeInstanceOf(NumberSchema::class)
            ->and($properties[3]->name)->toBe('zimmer');

        // Array field (multi select)
        expect($properties[4])->toBeInstanceOf(ArraySchema::class)
            ->and($properties[4]->name)->toBe('ausstattung')
            ->and($properties[4]->items)->toBeInstanceOf(EnumSchema::class)
            ->and($properties[4]->items->options)->toBe(['balkon', 'garten', 'garage', 'keller', 'aufzug']);

        // Boolean field
        expect($properties[5])->toBeInstanceOf(BooleanSchema::class)
            ->and($properties[5]->name)->toBe('verfuegbar');

        // Date field
        expect($properties[6])->toBeInstanceOf(StringSchema::class)
            ->and($properties[6]->name)->toBe('verfuegbar_ab')
            ->and($properties[6]->description)->toContain('YYYY-MM-DD');

        // Text field
        expect($properties[7])->toBeInstanceOf(StringSchema::class)
            ->and($properties[7]->name)->toBe('beschreibung');

        // Check required fields
        expect($schema->requiredFields)->toContain('zimmer')
            ->and($schema->requiredFields)->toContain('verfuegbar')
            ->and($schema->requiredFields)->not->toContain('objekttitel')
            ->and($schema->requiredFields)->not->toContain('verfuegbar_ab');
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

        $strategy = new PrismSchemaConvertStrategy;

        // Convert each module in the collection
        $schemas = $collection->map(fn ($module) => $module->convert($strategy));

        expect($schemas)->toHaveCount(1)
            ->and($schemas->first())->toBeInstanceOf(ObjectSchema::class)
            ->and($schemas->first()->properties)->toHaveCount(2);
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

        $strategy = new PrismSchemaConvertStrategy;
        $schema = $module->convert($strategy);

        expect($schema)->toBeInstanceOf(ObjectSchema::class)
            ->and($schema->properties)->toHaveCount(10);

        // Verify all field types are converted correctly
        $propertyTypes = array_map(fn ($prop) => $prop::class, $schema->properties);

        expect($propertyTypes)->toBe([
            StringSchema::class,  // varchar
            StringSchema::class,  // text
            StringSchema::class,  // blob
            NumberSchema::class,  // integer
            NumberSchema::class,  // float
            BooleanSchema::class, // boolean
            StringSchema::class,  // date
            StringSchema::class,  // datetime
            EnumSchema::class,    // single select
            ArraySchema::class,   // multi select
        ]);

        // Check required fields (those with defaults)
        expect($schema->requiredFields)->toContain('varchar_field')
            ->and($schema->requiredFields)->toContain('integer_field')
            ->and($schema->requiredFields)->toContain('float_field')
            ->and($schema->requiredFields)->toContain('boolean_field')
            ->and($schema->requiredFields)->toContain('single_select_field');
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
        $strategyNoDesc = new PrismSchemaConvertStrategy(
            includeNullable: true,
            includeDescriptions: false
        );

        $schemaNoDesc = $module->convert($strategyNoDesc);

        expect($schemaNoDesc->description)->toBe('')
            ->and($schemaNoDesc->properties[0]->description)->toBe('');

        // Test with nullable disabled
        $strategyNoNull = new PrismSchemaConvertStrategy(
            includeNullable: false,
            includeDescriptions: true
        );

        $schemaNoNull = $module->convert($strategyNoNull);

        expect($schemaNoNull->properties[0]->nullable ?? false)->toBeFalse()
            ->and($schemaNoNull->requiredFields)->toBe([]);
    });
});
