<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\FieldDependency;
use Innobrain\Structure\Dtos\PermittedValue;
use Innobrain\Structure\Enums\FieldType;

describe('Field', function () {
    it('only needs key, label and type', function () {
        $field = new Field(key: 'name', label: 'Name', type: FieldType::VarChar);

        expect($field->length)->toBeNull()
            ->and($field->default)->toBeNull()
            ->and($field->fieldMeasureFormat)->toBeNull()
            ->and($field->permittedValues)->toBeEmpty()
            ->and($field->filters)->toBeEmpty()
            ->and($field->dependencies)->toBeEmpty()
            ->and($field->compoundFields)->toBeEmpty();
    });

    describe('withPermittedValuesFor', function () {
        $objekttyp = fn (): Field => new Field(
            key: 'objekttyp',
            label: 'Objekttyp',
            type: FieldType::SingleSelect,
            permittedValues: new Collection([
                'einfamilienhaus' => new PermittedValue('einfamilienhaus', 'Einfamilienhaus'),
                'reihenhaus' => new PermittedValue('reihenhaus', 'Reihenhaus'),
                'etage' => new PermittedValue('etage', 'Etagenwohnung'),
            ]),
            dependencies: new Collection([
                new FieldDependency('einfamilienhaus', 'haus'),
                new FieldDependency('reihenhaus', 'haus'),
                new FieldDependency('etage', 'wohnung'),
            ]),
        );

        it('keeps the permitted values whose dependency matches the parent value', function () use ($objekttyp) {
            $narrowed = $objekttyp()->withPermittedValuesFor('haus');

            expect($narrowed->permittedValues->keys()->all())->toBe(['einfamilienhaus', 'reihenhaus'])
                ->and($narrowed->key)->toBe('objekttyp')
                ->and($narrowed->dependencies)->toHaveCount(3);
        });

        it('returns no permitted values for an unknown parent value', function () use ($objekttyp) {
            expect($objekttyp()->withPermittedValuesFor('grundstueck')->permittedValues)->toBeEmpty();
        });

        it('returns the field unchanged when it has no dependencies', function () {
            $field = new Field(
                key: 'status',
                label: 'Status',
                type: FieldType::SingleSelect,
                permittedValues: new Collection(['active' => new PermittedValue('active', 'Active')]),
            );

            expect($field->withPermittedValuesFor('anything'))->toBe($field);
        });
    });
});
