<?php

declare(strict_types=1);

use Illuminate\JsonSchema\Types\ArrayType;
use Illuminate\JsonSchema\Types\BooleanType;
use Illuminate\JsonSchema\Types\IntegerType;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\StringType;
use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Converters\JsonSchema\JsonSchemaConvertStrategy;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\FieldDependency;
use Innobrain\Structure\Dtos\FieldFilter;
use Innobrain\Structure\Dtos\Module;
use Innobrain\Structure\Dtos\PermittedValue;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\FieldType;

describe('JsonSchemaConvertStrategy', function () {
    beforeEach(function () {
        $this->strategy = new JsonSchemaConvertStrategy;
    });

    describe('convertPermittedValue', function () {
        it('returns empty array by default', function () {
            $pv = new PermittedValue('active', 'Active');

            expect($this->strategy->convertPermittedValue($pv))
                ->toBe([]);
        });
    });

    describe('convertFieldDependency', function () {
        it('returns empty array by default', function () {
            $dependency = new FieldDependency('status', 'active');

            $result = $this->strategy->convertFieldDependency($dependency);

            expect($result)->toBe([]);
        });
    });

    describe('convertFieldFilter', function () {
        it('returns empty array by default', function () {
            $filter = new FieldFilter(
                'range',
                collect(['min' => ['0'], 'max' => ['100']])
            );

            $result = $this->strategy->convertFieldFilter($filter);

            expect($result)->toBe([]);
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

            expect($schema)->toBeArray()
                ->and($schema['name'])->toBeInstanceOf(StringType::class)
                ->and($schema['name']->toArray()['title'])->toBe('name')
                ->and($schema['name']->toArray()['description'])->toContain('Full Name')
                ->and($schema['name']->toArray()['description'])->toContain('max length: 255')
                ->and($schema['name']->toArray()['maxLength'])->toBe(255);
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

            expect($schema)->toBeArray()
                ->and($schema['age'])->toBeInstanceOf(IntegerType::class)
                ->and($schema['age']->toArray()['title'])->toBe('age')
                ->and($schema['age']->toArray()['description'])->toBe('Age');
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

            expect($schema)->toBeArray()
                ->and($schema['active'])->toBeInstanceOf(BooleanType::class)
                ->and($schema['active']->toArray()['title'])->toBe('active');
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

            expect($schema)->toBeArray()
                ->and($schema['birthdate'])->toBeInstanceOf(StringType::class)
                ->and($schema['birthdate']->toArray()['description'])->toContain('Birth Date')
                ->and($schema['birthdate']->toArray()['description'])->toContain('YYYY-MM-DD');
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

            expect($schema)->toBeArray()
                ->and($schema['status'])->toBeInstanceOf(ArrayType::class)
                ->and($schema['status']->toArray()['enum'])->toBe(['active', 'inactive']);
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

            expect($schema)->toBeArray()
                ->and($schema['tags'])->toBeInstanceOf(ArrayType::class)
                ->and($schema['tags']->toArray()['enum'])->toBe(['php', 'js', 'python']);
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

            expect($schema)->toBeArray()
                ->and($schema['category'])->toBeInstanceOf(StringType::class);
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

            expect($schema)->toBeInstanceOf(ObjectType::class)
                ->and($schema->toArray()['title'])->toBe('address')
                ->and($schema->toArray()['description'])->toBe('Address Information')
                ->and($schema->toArray()['properties'])->toHaveCount(3)
                ->and($schema->toArray()['required'])->toBe(['verified']);
        });
    });

    describe('configuration options', function () {
        it('excludes descriptions when configured', function () {
            $strategy = new JsonSchemaConvertStrategy(
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

            expect($schema['email']->toArray())->not->toHaveKey('description');
        });

        it('handles nullable configuration', function () {
            $strategyNullable = new JsonSchemaConvertStrategy(
                includeNullable: true,
                includeDescriptions: true
            );

            $strategyNotNullable = new JsonSchemaConvertStrategy(
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

            expect($schemaNullable['optional']->toArray()['type'])->toBe(['string', 'null'])
                ->and($schemaNotNullable['optional']->toArray()['type'])->toBe('string');
        });
    });
});
