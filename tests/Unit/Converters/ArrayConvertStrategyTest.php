<?php

declare(strict_types=1);

namespace Innobrain\Structure\Tests\Unit\Converters;

use Illuminate\Support\Collection;
use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\Converters\ArrayConvertStrategy;
use Innobrain\Structure\DTOs\Field;
use Innobrain\Structure\DTOs\FieldDependency;
use Innobrain\Structure\DTOs\FieldFilter;
use Innobrain\Structure\DTOs\Module;
use Innobrain\Structure\DTOs\PermittedValue;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\FieldType;

describe('ArrayConvertStrategy', function () {
    describe('PermittedValue Conversion', function () {
        it('converts PermittedValue to array', function () {
            $pv = new PermittedValue(key: 'pv_key', label: 'PV Label');
            $strategy = new ArrayConvertStrategy(dropEmpty: false);
            $result = $pv->convert($strategy);

            expect($result)->toBe([
                'key' => 'pv_key',
                'label' => 'PV Label',
            ]);
        });

        it('converts PermittedValue to array and dropEmpty has no effect', function () {
            $pv = new PermittedValue(key: 'pv_key', label: 'PV Label');
            $strategy = new ArrayConvertStrategy(dropEmpty: true);
            $result = $pv->convert($strategy);

            expect($result)->toBe([
                'key' => 'pv_key',
                'label' => 'PV Label',
            ]);
        });
    });

    describe('FieldDependency Conversion', function () {
        it('converts FieldDependency to array', function () {
            $fd = new FieldDependency(dependentFieldKey: 'dep_key', dependentFieldValue: 'dep_value');
            $strategy = new ArrayConvertStrategy(dropEmpty: false);
            $result = $fd->convert($strategy);

            expect($result)->toBe([
                'dependentFieldKey' => 'dep_key',
                'dependentFieldValue' => 'dep_value',
            ]);
        });

        it('converts FieldDependency to array and dropEmpty has no effect', function () {
            $fd = new FieldDependency(dependentFieldKey: 'dep_key', dependentFieldValue: 'dep_value');
            $strategy = new ArrayConvertStrategy(dropEmpty: true);
            $result = $fd->convert($strategy);

            expect($result)->toBe([
                'dependentFieldKey' => 'dep_key',
                'dependentFieldValue' => 'dep_value',
            ]);
        });
    });

    describe('FieldFilter Conversion', function () {
        it('converts FieldFilter to array with config', function () {
            $ff = new FieldFilter(name: 'filter_name', config: new Collection(['cfg_key' => ['cfg_value']]));
            $strategy = new ArrayConvertStrategy(dropEmpty: false);
            $result = $ff->convert($strategy);

            expect($result)->toBe([
                'name' => 'filter_name',
                'config' => ['cfg_key' => ['cfg_value']],
            ]);
        });

        it('converts FieldFilter to array and drops empty config when dropEmpty is true', function () {
            $ff = new FieldFilter(name: 'filter_name', config: new Collection);
            $strategy = new ArrayConvertStrategy(dropEmpty: true);
            $result = $ff->convert($strategy);

            expect($result)->toBe(['name' => 'filter_name']);
        });

        it('converts FieldFilter to array and keeps empty config when dropEmpty is false', function () {
            $ff = new FieldFilter(name: 'filter_name', config: new Collection);
            $strategy = new ArrayConvertStrategy(dropEmpty: false);
            $result = $ff->convert($strategy);

            expect($result)->toBe([
                'name' => 'filter_name',
                'config' => [],
            ]);
        });
    });

    describe('Field Conversion', function () {
        $baseFieldData = [
            'key' => 'field_key',
            'label' => 'Field Label',
            'type' => FieldType::VarChar,
            'length' => 100,
            'permittedValues' => new Collection(['pv1' => new PermittedValue('pv1', 'Permitted Value 1')]),
            'default' => 'default_value',
            'filters' => new Collection(['ff1' => new FieldFilter('ff1', new Collection(['cfg' => ['val']]))]),
            'dependencies' => new Collection([new FieldDependency('dep_key', 'dep_val')]),
            'compoundFields' => new Collection(['cf1', 'cf2']),
            'fieldMeasureFormat' => 'DATA_TYPE_TEXT',
        ];

        it('converts Field to array with all values', function () use ($baseFieldData) {
            $field = new Field(...$baseFieldData);
            $strategy = new ArrayConvertStrategy(dropEmpty: false);
            $result = $field->convert($strategy);

            expect($result)->toBe([
                'key' => 'field_key',
                'label' => 'Field Label',
                'type' => 'varchar',
                'length' => 100,
                'permittedValues' => ['pv1' => ['key' => 'pv1', 'label' => 'Permitted Value 1']],
                'default' => 'default_value',
                'filters' => ['ff1' => ['name' => 'ff1', 'config' => ['cfg' => ['val']]]],
                'dependencies' => [['dependentFieldKey' => 'dep_key', 'dependentFieldValue' => 'dep_val']],
                'compoundFields' => ['cf1', 'cf2'],
                'fieldMeasureFormat' => 'DATA_TYPE_TEXT',
            ]);
        });

        it('converts Field to array and drops null/empty values when dropEmpty is true', function () {
            $fieldData = [
                'key' => 'field_key',
                'label' => 'Field Label',
                'type' => FieldType::Integer,
                'length' => null, // should be dropped
                'permittedValues' => new Collection, // should be dropped
                'default' => null, // should be dropped
                'filters' => new Collection, // should be dropped
                'dependencies' => new Collection, // should be dropped
                'compoundFields' => new Collection, // should be dropped
                'fieldMeasureFormat' => '', // should be dropped
            ];
            $field = new Field(...$fieldData);
            $strategy = new ArrayConvertStrategy(dropEmpty: true);
            $result = $field->convert($strategy);

            expect($result)->toBe([
                'key' => 'field_key',
                'label' => 'Field Label',
                'type' => 'integer',
            ]);
        });

        it('converts Field to array and keeps null/empty values when dropEmpty is false', function () {
            $fieldData = [
                'key' => 'field_key',
                'label' => 'Field Label',
                'type' => FieldType::Integer,
                'length' => null,
                'permittedValues' => new Collection,
                'default' => null,
                'filters' => new Collection,
                'dependencies' => new Collection,
                'compoundFields' => new Collection,
                'fieldMeasureFormat' => '',
            ];
            $field = new Field(...$fieldData);
            $strategy = new ArrayConvertStrategy(dropEmpty: false);
            $result = $field->convert($strategy);

            expect($result)->toBe([
                'key' => 'field_key',
                'label' => 'Field Label',
                'type' => 'integer',
                'length' => null,
                'permittedValues' => [],
                'default' => null,
                'filters' => [],
                'dependencies' => [],
                'compoundFields' => [],
                'fieldMeasureFormat' => '',
            ]);
        });
    });

    describe('Module Conversion', function () {
        it('converts Module to array with fields', function () {
            $field = new Field(
                key: 'field1',
                label: 'Field 1',
                type: FieldType::Text,
                length: null,
                permittedValues: new Collection,
                default: null,
                filters: new Collection,
                dependencies: new Collection,
                compoundFields: new Collection,
                fieldMeasureFormat: null
            );
            $module = new Module(
                key: FieldConfigurationModule::Address,
                label: 'Address Module',
                fields: new FieldCollection(['field1' => $field])
            );
            $strategy = new ArrayConvertStrategy(dropEmpty: false);
            $result = $module->convert($strategy);

            expect($result)->toBe([
                'key' => 'address',
                'label' => 'Address Module',
                'fields' => [
                    'field1' => [
                        'key' => 'field1',
                        'label' => 'Field 1',
                        'type' => 'text',
                        'length' => null,
                        'permittedValues' => [],
                        'default' => null,
                        'filters' => [],
                        'dependencies' => [],
                        'compoundFields' => [],
                        'fieldMeasureFormat' => null,
                    ],
                ],
            ]);
        });

        it('converts Module to array and drops empty fields collection when dropEmpty is true', function () {
            $module = new Module(
                key: FieldConfigurationModule::Address,
                label: 'Address Module',
                fields: new FieldCollection // empty fields
            );
            $strategy = new ArrayConvertStrategy(dropEmpty: true);
            $result = $module->convert($strategy);

            expect($result)->toBe([
                'key' => 'address',
                'label' => 'Address Module',
                // 'fields' key should be absent
            ]);
            expect($result)->not->toHaveKey('fields');
        });

        it('converts Module to array and keeps empty fields collection when dropEmpty is false', function () {
            $module = new Module(
                key: FieldConfigurationModule::Address,
                label: 'Address Module',
                fields: new FieldCollection // empty fields
            );
            $strategy = new ArrayConvertStrategy(dropEmpty: false);
            $result = $module->convert($strategy);

            expect($result)->toBe([
                'key' => 'address',
                'label' => 'Address Module',
                'fields' => [],
            ]);
        });
    });

    describe('ModulesCollection Conversion', function () {
        it('converts ModulesCollection to an array of module arrays', function () {
            $module1Field = new Field(
                key: 'field1', label: 'Field 1', type: FieldType::Text, length: null,
                permittedValues: new Collection, default: null, filters: new Collection,
                dependencies: new Collection, compoundFields: new Collection, fieldMeasureFormat: null
            );
            $module1 = new Module(
                key: FieldConfigurationModule::Address,
                label: 'Address Module',
                fields: new FieldCollection(['field1' => $module1Field])
            );

            $module2Field = new Field(
                key: 'field2', label: 'Field 2', type: FieldType::Integer, length: 10,
                permittedValues: new Collection, default: '0', filters: new Collection,
                dependencies: new Collection, compoundFields: new Collection, fieldMeasureFormat: null
            );
            $module2 = new Module(
                key: FieldConfigurationModule::Estate,
                label: 'Estate Module',
                fields: new FieldCollection(['field2' => $module2Field])
            );

            $modulesCollection = new ModulesCollection([$module1, $module2]);
            $strategy = new ArrayConvertStrategy(dropEmpty: false);
            $result = $modulesCollection->convert($strategy);

            expect($result)->toBeArray()->toHaveCount(2);
            expect($result[0])->toBe([
                'key' => 'address',
                'label' => 'Address Module',
                'fields' => [
                    'field1' => [
                        'key' => 'field1', 'label' => 'Field 1', 'type' => 'text', 'length' => null,
                        'permittedValues' => [], 'default' => null, 'filters' => [],
                        'dependencies' => [], 'compoundFields' => [], 'fieldMeasureFormat' => null,
                    ],
                ],
            ]);
            expect($result[1])->toBe([
                'key' => 'estate',
                'label' => 'Estate Module',
                'fields' => [
                    'field2' => [
                        'key' => 'field2', 'label' => 'Field 2', 'type' => 'integer', 'length' => 10,
                        'permittedValues' => [], 'default' => '0', 'filters' => [],
                        'dependencies' => [], 'compoundFields' => [], 'fieldMeasureFormat' => null,
                    ],
                ],
            ]);
        });

        it('converts ModulesCollection to array and drops empty values when dropEmpty is true', function () {
            $module1Field = new Field(
                key: 'field1', label: 'Field 1', type: FieldType::Text, length: null,
                permittedValues: new Collection, default: null, filters: new Collection, // all empty/null
                dependencies: new Collection, compoundFields: new Collection, fieldMeasureFormat: null
            );
            $module1 = new Module(
                key: FieldConfigurationModule::Address,
                label: 'Address Module',
                fields: new FieldCollection(['field1' => $module1Field])
            );

            $module2 = new Module( // Module with empty fields
                key: FieldConfigurationModule::Estate,
                label: 'Estate Module',
                fields: new FieldCollection
            );

            $modulesCollection = new ModulesCollection([$module1, $module2]);
            $strategy = new ArrayConvertStrategy(dropEmpty: true);
            $result = $modulesCollection->convert($strategy);

            expect($result)->toBeArray()->toHaveCount(2);
            // Module 1: field1 should only have key, label, type
            expect($result[0])->toBe([
                'key' => 'address',
                'label' => 'Address Module',
                'fields' => [
                    'field1' => [
                        'key' => 'field1', 'label' => 'Field 1', 'type' => 'text',
                    ],
                ],
            ]);
            // Module 2: fields collection itself should be dropped
            expect($result[1])->toBe([
                'key' => 'estate',
                'label' => 'Estate Module',
            ]);
            expect($result[1])->not->toHaveKey('fields');
        });

        it('converts an empty ModulesCollection to an empty array', function () {
            $modulesCollection = new ModulesCollection;
            $strategy = new ArrayConvertStrategy(dropEmpty: false);
            $result = $modulesCollection->convert($strategy);
            expect($result)->toBe([]);

            $strategyDropEmpty = new ArrayConvertStrategy(dropEmpty: true);
            $resultDropEmpty = $modulesCollection->convert($strategyDropEmpty);
            expect($resultDropEmpty)->toBe([]);
        });
    });
});
