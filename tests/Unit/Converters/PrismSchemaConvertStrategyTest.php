<?php

declare(strict_types=1);

use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Converters\PrismSchema\PrismSchemaConvertStrategy;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\FieldDependency;
use Innobrain\Structure\Dtos\FieldFilter;
use Innobrain\Structure\Dtos\Module;
use Innobrain\Structure\Dtos\PermittedValue;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\FieldType;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\BooleanSchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

describe('PrismSchemaConvertStrategy', function () {
    beforeEach(function () {
        $this->strategy = new PrismSchemaConvertStrategy;
    });

    describe('convertPermittedValue', function () {
        it('returns the key of the permitted value', function () {
            $pv = new PermittedValue('active', 'Active');

            expect($this->strategy->convertPermittedValue($pv))
                ->toBe('active');
        });
    });

    describe('convertFieldDependency', function () {
        it('returns dependency metadata', function () {
            $dependency = new FieldDependency('status', 'active');

            $result = $this->strategy->convertFieldDependency($dependency);

            expect($result)->toBe([
                'field' => 'status',
                'value' => 'active',
            ]);
        });
    });

    describe('convertFieldFilter', function () {
        it('returns filter metadata', function () {
            $filter = new FieldFilter(
                'range',
                collect(['min' => ['0'], 'max' => ['100']])
            );

            $result = $this->strategy->convertFieldFilter($filter);

            expect($result)->toBe([
                'name' => 'range',
                'config' => ['min' => ['0'], 'max' => ['100']],
            ]);
        });
    });

    describe('convertField', function () {
        it('converts varchar field to StringSchema', function () {
            $field = new Field(
                key: 'name',
                label: 'Full Name',
                type: FieldType::VarChar,
                length: 255,
                permittedValues: collect(),
                default: null,
                filters: collect(),
                dependencies: collect(),
                compoundFields: collect(),
                fieldMeasureFormat: null
            );

            $schema = $this->strategy->convertField($field);

            expect($schema)->toBeInstanceOf(StringSchema::class)
                ->and($schema->name)->toBe('name')
                ->and($schema->description)->toContain('Full Name')
                ->and($schema->description)->toContain('max length: 255')
                ->and($schema->nullable)->toBeTrue();
        });

        it('converts integer field to NumberSchema', function () {
            $field = new Field(
                key: 'age',
                label: 'Age',
                type: FieldType::Integer,
                length: null,
                permittedValues: collect(),
                default: null,
                filters: collect(),
                dependencies: collect(),
                compoundFields: collect(),
                fieldMeasureFormat: null
            );

            $schema = $this->strategy->convertField($field);

            expect($schema)->toBeInstanceOf(NumberSchema::class)
                ->and($schema->name)->toBe('age')
                ->and($schema->description)->toBe('Age');
        });

        it('converts boolean field to BooleanSchema', function () {
            $field = new Field(
                key: 'active',
                label: 'Is Active',
                type: FieldType::Boolean,
                length: null,
                permittedValues: collect(),
                default: 'false',
                filters: collect(),
                dependencies: collect(),
                compoundFields: collect(),
                fieldMeasureFormat: null
            );

            $schema = $this->strategy->convertField($field);

            expect($schema)->toBeInstanceOf(BooleanSchema::class)
                ->and($schema->name)->toBe('active')
                ->and($schema->nullable ?? false)->toBeFalsy();
        });

        it('converts date field to StringSchema with date format', function () {
            $field = new Field(
                key: 'birthdate',
                label: 'Birth Date',
                type: FieldType::Date,
                length: null,
                permittedValues: collect(),
                default: null,
                filters: collect(),
                dependencies: collect(),
                compoundFields: collect(),
                fieldMeasureFormat: null
            );

            $schema = $this->strategy->convertField($field);

            expect($schema)->toBeInstanceOf(StringSchema::class)
                ->and($schema->description)->toContain('Birth Date')
                ->and($schema->description)->toContain('YYYY-MM-DD');
        });

        it('converts single select field to EnumSchema', function () {
            $field = new Field(
                key: 'status',
                label: 'Status',
                type: FieldType::SingleSelect,
                length: null,
                permittedValues: collect([
                    'active' => new PermittedValue('active', 'Active'),
                    'inactive' => new PermittedValue('inactive', 'Inactive'),
                ]),
                default: null,
                filters: collect(),
                dependencies: collect(),
                compoundFields: collect(),
                fieldMeasureFormat: null
            );

            $schema = $this->strategy->convertField($field);

            expect($schema)->toBeInstanceOf(EnumSchema::class)
                ->and($schema->name)->toBe('status')
                ->and($schema->options)->toBe(['active', 'inactive']);
        });

        it('converts multi select field to ArraySchema with EnumSchema items', function () {
            $field = new Field(
                key: 'tags',
                label: 'Tags',
                type: FieldType::MultiSelect,
                length: null,
                permittedValues: collect([
                    'php' => new PermittedValue('php', 'PHP'),
                    'js' => new PermittedValue('js', 'JavaScript'),
                    'python' => new PermittedValue('python', 'Python'),
                ]),
                default: null,
                filters: collect(),
                dependencies: collect(),
                compoundFields: collect(),
                fieldMeasureFormat: null
            );

            $schema = $this->strategy->convertField($field);

            expect($schema)->toBeInstanceOf(ArraySchema::class)
                ->and($schema->name)->toBe('tags')
                ->and($schema->items)->toBeInstanceOf(EnumSchema::class)
                ->and($schema->items->options)->toBe(['php', 'js', 'python']);
        });

        it('handles empty permitted values for select fields', function () {
            $field = new Field(
                key: 'category',
                label: 'Category',
                type: FieldType::SingleSelect,
                length: null,
                permittedValues: collect(),
                default: null,
                filters: collect(),
                dependencies: collect(),
                compoundFields: collect(),
                fieldMeasureFormat: null
            );

            $schema = $this->strategy->convertField($field);

            expect($schema)->toBeInstanceOf(StringSchema::class);
        });
    });

    describe('convertModule', function () {
        it('converts module to ObjectSchema with field properties', function () {
            $module = new Module(
                key: FieldConfigurationModule::Address,
                label: 'Address Information',
                fields: new FieldCollection([
                    'street' => new Field(
                        key: 'street',
                        label: 'Street',
                        type: FieldType::VarChar,
                        length: 200,
                        permittedValues: collect(),
                        default: null,
                        filters: collect(),
                        dependencies: collect(),
                        compoundFields: collect(),
                        fieldMeasureFormat: null
                    ),
                    'city' => new Field(
                        key: 'city',
                        label: 'City',
                        type: FieldType::VarChar,
                        length: 100,
                        permittedValues: collect(),
                        default: null,
                        filters: collect(),
                        dependencies: collect(),
                        compoundFields: collect(),
                        fieldMeasureFormat: null
                    ),
                    'verified' => new Field(
                        key: 'verified',
                        label: 'Verified',
                        type: FieldType::Boolean,
                        length: null,
                        permittedValues: collect(),
                        default: 'false',
                        filters: collect(),
                        dependencies: collect(),
                        compoundFields: collect(),
                        fieldMeasureFormat: null
                    ),
                ])
            );

            $schema = $this->strategy->convertModule($module);

            expect($schema)->toBeInstanceOf(ObjectSchema::class)
                ->and($schema->name)->toBe('address')
                ->and($schema->description)->toBe('Address Information')
                ->and($schema->properties)->toHaveCount(3)
                ->and($schema->requiredFields)->toBe(['verified']);
        });
    });

    describe('configuration options', function () {
        it('excludes descriptions when configured', function () {
            $strategy = new PrismSchemaConvertStrategy(
                includeNullable: true,
                includeDescriptions: false
            );

            $field = new Field(
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
            );

            $schema = $strategy->convertField($field);

            expect($schema->description)->toBe('');
        });

        it('handles nullable configuration', function () {
            $strategyNullable = new PrismSchemaConvertStrategy(
                includeNullable: true,
                includeDescriptions: true
            );

            $strategyNotNullable = new PrismSchemaConvertStrategy(
                includeNullable: false,
                includeDescriptions: true
            );

            $field = new Field(
                key: 'optional',
                label: 'Optional Field',
                type: FieldType::VarChar,
                length: null,
                permittedValues: collect(),
                default: null,
                filters: collect(),
                dependencies: collect(),
                compoundFields: collect(),
                fieldMeasureFormat: null
            );

            $schemaNullable = $strategyNullable->convertField($field);
            $schemaNotNullable = $strategyNotNullable->convertField($field);

            expect($schemaNullable->nullable)->toBeTrue()
                ->and($schemaNotNullable->nullable ?? false)->toBeFalse();
        });
    });
});
