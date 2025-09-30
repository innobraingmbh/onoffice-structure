<?php

declare(strict_types=1);

namespace Innobrain\Structure\Tests\Unit\Builders;

use Illuminate\Support\Collection;
use Innobrain\Structure\Builders\FieldFilterBuilder;
use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\FieldFilter;
use Innobrain\Structure\Enums\FieldType;

describe('FieldFilterBuilder', function () {
    beforeEach(function () {
        // Create test fields with different filter configurations
        $this->field1 = new Field(
            key: 'field1',
            label: 'Field 1',
            type: FieldType::VarChar,
            length: null,
            permittedValues: new Collection,
            default: null,
            filters: new Collection([
                'filter1' => new FieldFilter(
                    name: 'filter1',
                    config: new Collection([
                        'type' => ['estate'],
                        'category' => ['house', 'apartment'],
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
            filters: new Collection([
                'filter2' => new FieldFilter(
                    name: 'filter2',
                    config: new Collection([
                        'type' => ['land'],
                        'category' => ['commercial'],
                    ])
                ),
            ]),
            dependencies: new Collection,
            compoundFields: new Collection,
            fieldMeasureFormat: null
        );

        $this->field3 = new Field(
            key: 'field3',
            label: 'Field 3',
            type: FieldType::Integer,
            length: null,
            permittedValues: new Collection,
            default: null,
            filters: new Collection, // No filters
            dependencies: new Collection,
            compoundFields: new Collection,
            fieldMeasureFormat: null
        );

        $this->fields = new FieldCollection([
            'field1' => $this->field1,
            'field2' => $this->field2,
            'field3' => $this->field3,
        ]);
    });

    describe('->where()', function () {
        it('filters fields by a single filter criterion', function () {
            $builder = new FieldFilterBuilder($this->fields);
            $result = $builder->where('type', 'estate')->get();

            // field1 and field3 (field3 has no filters so matches all)
            expect($result)->toBeInstanceOf(FieldCollection::class)
                ->and($result->count())->toBe(2)
                ->and($result->has('field1'))->toBeTrue()
                ->and($result->has('field3'))->toBeTrue();
        });

        it('chains multiple where clauses', function () {
            $builder = new FieldFilterBuilder($this->fields);
            $result = $builder
                ->where('type', 'estate')
                ->where('category', 'house')
                ->get();

            expect($result->count())->toBe(2)
                ->and($result->has('field1'))->toBeTrue()
                ->and($result->has('field3'))->toBeTrue();
        });

        it('returns empty collection when no fields match', function () {
            $builder = new FieldFilterBuilder($this->fields);
            $result = $builder
                ->where('type', 'nonexistent')
                ->get();

            // Only field3 which has no filters
            expect($result)->toBeInstanceOf(FieldCollection::class)
                ->and($result->count())->toBe(1)
                ->and($result->has('field3'))->toBeTrue();
        });

        it('overwrites previous filter value when same key is used', function () {
            $builder = new FieldFilterBuilder($this->fields);
            $result = $builder
                ->where('type', 'estate')
                ->where('type', 'land') // Overwrites previous
                ->get();

            expect($result->count())->toBe(2)
                ->and($result->has('field2'))->toBeTrue()
                ->and($result->has('field3'))->toBeTrue();
        });
    });

    describe('->get()', function () {
        it('returns all fields when no filters applied', function () {
            $builder = new FieldFilterBuilder($this->fields);
            $result = $builder->get();

            expect($result)->toBeInstanceOf(FieldCollection::class)
                ->and($result->count())->toBe(3);
        });

        it('returns filtered FieldCollection instance', function () {
            $builder = new FieldFilterBuilder($this->fields);
            $result = $builder->where('type', 'land')->get();

            expect($result)->toBeInstanceOf(FieldCollection::class)
                ->and($result->count())->toBe(2);
        });

        it('returns new FieldCollection instance', function () {
            $builder = new FieldFilterBuilder($this->fields);
            $result = $builder->where('type', 'estate')->get();

            expect($result)->not->toBe($this->fields)
                ->and($result)->toBeInstanceOf(FieldCollection::class);
        });
    });

    describe('->first()', function () {
        it('returns first matching field', function () {
            $builder = new FieldFilterBuilder($this->fields);
            $result = $builder->where('type', 'estate')->first();

            expect($result)->toBeInstanceOf(Field::class)
                ->and($result->key)->toBe('field1');
        });

        it('returns null when no fields match', function () {
            // Create fields that will not match
            $strictFields = new FieldCollection([
                'field1' => $this->field1,
                'field2' => $this->field2,
            ]);

            $builder = new FieldFilterBuilder($strictFields);
            $result = $builder->where('type', 'nonexistent')->first();

            expect($result)->toBeNull();
        });
    });

    describe('->getFilters()', function () {
        it('returns empty array when no filters set', function () {
            $builder = new FieldFilterBuilder($this->fields);
            $filters = $builder->getFilters();

            expect($filters)->toBe([]);
        });

        it('returns all applied filters', function () {
            $builder = new FieldFilterBuilder($this->fields);
            $builder
                ->where('type', 'estate')
                ->where('category', 'house');

            $filters = $builder->getFilters();

            expect($filters)->toBe([
                'type' => 'estate',
                'category' => 'house',
            ]);
        });
    });
});
