<?php

declare(strict_types=1);

namespace Innobrain\Structure\Tests\Unit\Collections;

use Illuminate\Support\Collection;
use Innobrain\Structure\Builders\FieldFilterBuilder;
use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\FieldFilter;
use Innobrain\Structure\Enums\FieldType;

describe('FieldCollection', function () {
    beforeEach(function () {
        // Create test fields
        $this->field1 = new Field(
            key: 'field1',
            label: 'Field 1',
            type: FieldType::VarChar,
            length: 255,
            permittedValues: new Collection,
            default: null,
            filters: new Collection([
                'filter1' => new FieldFilter(
                    name: 'filter1',
                    config: new Collection([
                        'type' => ['estate'],
                    ])
                ),
            ]),
            dependencies: new Collection,
            compoundFields: new Collection,
            fieldMeasureFormat: null
        );

        $this->field2 = new Field(
            key: 'field2',
            label: 'Field 2',
            type: FieldType::Boolean,
            length: null,
            permittedValues: new Collection,
            default: null,
            filters: new Collection,
            dependencies: new Collection,
            compoundFields: new Collection,
            fieldMeasureFormat: null
        );

        $this->fields = new FieldCollection([
            'field1' => $this->field1,
            'field2' => $this->field2,
        ]);
    });

    describe('inheritance', function () {
        it('extends Laravel Collection', function () {
            expect($this->fields)->toBeInstanceOf(Collection::class)
                ->and($this->fields)->toBeInstanceOf(FieldCollection::class);
        });

        it('retains all Collection methods', function () {
            expect($this->fields->count())->toBe(2)
                ->and($this->fields->first())->toBe($this->field1)
                ->and($this->fields->get('field2'))->toBe($this->field2)
                ->and($this->fields->has('field1'))->toBeTrue();
        });
    });

    describe('->whereMatchesFilters()', function () {
        it('returns FieldFilterBuilder instance', function () {
            $builder = $this->fields->whereMatchesFilters();

            expect($builder)->toBeInstanceOf(FieldFilterBuilder::class);
        });

        it('builder operates on the collection', function () {
            $result = $this->fields->whereMatchesFilters()
                ->where('type', 'estate')
                ->get();

            // field1 matches, field2 has no filters
            expect($result)->toBeInstanceOf(FieldCollection::class)
                ->and($result->count())->toBe(2);
        });

        it('can chain filter operations', function () {
            $result = $this->fields->whereMatchesFilters()
                ->where('type', 'estate')
                ->where('category', 'house')
                ->get();

            expect($result)->toBeInstanceOf(FieldCollection::class);
        });

        it('preserves original collection', function () {
            $originalCount = $this->fields->count();

            $filtered = $this->fields->whereMatchesFilters()
                ->where('type', 'nonexistent')
                ->get();

            expect($this->fields->count())->toBe($originalCount)
                ->and($filtered)->not->toBe($this->fields);
        });
    });

    describe('type safety', function () {
        it('maintains Field type in collection', function () {
            $firstField = $this->fields->first();
            expect($firstField)->toBeInstanceOf(Field::class);

            $this->fields->each(function ($field) {
                expect($field)->toBeInstanceOf(Field::class);
            });
        });

        it('works with string keys', function () {
            expect($this->fields->get('field1'))->toBe($this->field1)
                ->and($this->fields->get('field2'))->toBe($this->field2)
                ->and($this->fields->get('nonexistent'))->toBeNull();
        });
    });
});
