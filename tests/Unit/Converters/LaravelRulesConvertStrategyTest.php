<?php

declare(strict_types=1);

namespace Innobrain\Structure\Tests\Unit\Converters;

use Illuminate\Support\Collection;
use Innobrain\Structure\Converters\LaravelRulesConvertStrategy;
use Innobrain\Structure\DTOs\Field;
use Innobrain\Structure\DTOs\FieldDependency;
use Innobrain\Structure\DTOs\Module;
use Innobrain\Structure\DTOs\PermittedValue;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\FieldType;

describe('LaravelRulesConvertStrategy', function () {
    describe('convertField – pipe syntax', function () {
        it('builds rules with length constraint and nullable', function () {
            $field = new Field(
                key: 'title',
                label: 'Title',
                type: FieldType::VarChar,
                length: 80,
                permittedValues: new Collection,
                default: null,
                filters: new Collection,
                dependencies: new Collection,
                compoundFields: new Collection,
                fieldMeasureFormat: null,
            );

            $strategy = new LaravelRulesConvertStrategy(pipeSyntax: true, includeNullable: true);

            $result = $field->convert($strategy);

            expect($result)->toBe('string|max:80|nullable');
        });
    });

    describe('convertField – array syntax', function () {
        it('adds an in: rule for single-select fields with permitted values', function () {
            $permitted = new Collection([
                'val1' => new PermittedValue('val1', 'Value 1'),
                'val2' => new PermittedValue('val2', 'Value 2'),
            ]);

            $field = new Field(
                key: 'status',
                label: 'Status',
                type: FieldType::SingleSelect,
                length: null,
                permittedValues: $permitted,
                default: 'val1',   // has default ➜ no nullable
                filters: new Collection,
                dependencies: new Collection,
                compoundFields: new Collection,
                fieldMeasureFormat: null,
            );

            $strategy = new LaravelRulesConvertStrategy(pipeSyntax: false, includeNullable: false);

            $result = $field->convert($strategy);

            expect($result)->toBe([
                'string',
                'in:val1,val2',
            ]);
        });
    });

    describe('convertModule – multi-select handling', function () {
        it('adds a field.* rule and correct base rules', function () {
            $permitted = new Collection([
                'opt1' => new PermittedValue('opt1', 'Option 1'),
                'opt2' => new PermittedValue('opt2', 'Option 2'),
            ]);

            $multiselect = new Field(
                key: 'tags',
                label: 'Tags',
                type: FieldType::MultiSelect,
                length: null,
                permittedValues: $permitted,
                default: null,
                filters: new Collection,
                dependencies: new Collection,
                compoundFields: new Collection,
                fieldMeasureFormat: null,
            );

            $module = new Module(
                key: FieldConfigurationModule::Address,
                label: 'Address',
                fields: new Collection(['tags' => $multiselect]),
            );

            $strategy = new LaravelRulesConvertStrategy(pipeSyntax: true, includeNullable: true);

            $result = $module->convert($strategy);

            expect($result)->toBe([
                'tags' => 'array|distinct|nullable',
                'tags.*' => 'in:opt1,opt2',
            ]);
        });
    });

    describe('convertField – dependency rules', function () {
        it('appends required_if when dependencies exist', function () {
            $dependency = new FieldDependency('building', 'tower');

            $field = new Field(
                key: 'floor',
                label: 'Floor',
                type: FieldType::Integer,
                length: null,
                permittedValues: new Collection,
                default: null,
                filters: new Collection,
                dependencies: new Collection([$dependency]),
                compoundFields: new Collection,
                fieldMeasureFormat: null,
            );

            $strategy = new LaravelRulesConvertStrategy(pipeSyntax: true, includeNullable: false);

            $result = $field->convert($strategy);

            expect($result)->toBe('integer|required_if:building,tower');
        });
    });
});
