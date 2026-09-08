<?php

declare(strict_types=1);

use Innobrain\Structure\Enums\FieldType;
use Innobrain\Structure\Testing\FieldFactory;

beforeEach(function (): void {
    $this->objektart = FieldFactory::singleSelect('objektart', [
        'haus' => 'Haus',
        'wohnung' => 'Wohnung',
        '5' => 'Fünf',
    ])->make();
});

describe('permitted value labels', function (): void {
    it('maps keys to labels', function (): void {
        expect($this->objektart->permittedValueLabels())->toBe([
            'haus' => 'Haus',
            'wohnung' => 'Wohnung',
            5 => 'Fünf',
        ]);
    });

    it('finds the label for a key', function (): void {
        expect($this->objektart->labelFor('haus'))->toBe('Haus')
            ->and($this->objektart->labelFor('5'))->toBe('Fünf')
            ->and($this->objektart->labelFor('villa'))->toBeNull();
    });

    it('resolves a key from a key or label in any casing', function (): void {
        expect($this->objektart->resolvePermittedValueKey('haus'))->toBe('haus')
            ->and($this->objektart->resolvePermittedValueKey('HAUS'))->toBe('haus')
            ->and($this->objektart->resolvePermittedValueKey('Wohnung'))->toBe('wohnung')
            ->and($this->objektart->resolvePermittedValueKey(' fünf '))->toBe('5')
            ->and($this->objektart->resolvePermittedValueKey('villa'))->toBeNull();
    });

    it('prefers a key match over a label match', function (): void {
        $field = FieldFactory::singleSelect('status', ['a' => 'B', 'b' => 'A'])->make();

        expect($field->resolvePermittedValueKey('B'))->toBe('b');
    });
});

describe('permits', function (): void {
    it('accepts anything for a field without permitted values', function (): void {
        $field = FieldFactory::new('Vorname')->make();

        expect($field->permits('Max'))->toBeTrue()
            ->and($field->permits(['a', 'b']))->toBeTrue()
            ->and($field->permits(null))->toBeTrue();
    });

    it('checks scalar values against the permitted keys', function (): void {
        expect($this->objektart->permits('haus'))->toBeTrue()
            ->and($this->objektart->permits(5))->toBeTrue()
            ->and($this->objektart->permits('villa'))->toBeFalse()
            ->and($this->objektart->permits(null))->toBeFalse();
    });

    it('checks every item of an array', function (): void {
        $field = FieldFactory::multiSelect('Beziehung', ['1' => 'Kunde', '2' => 'Tippgeber'])->make();

        expect($field->permits(['1', '2']))->toBeTrue()
            ->and($field->permits([]))->toBeTrue()
            ->and($field->permits(['1', '9']))->toBeFalse();
    });
});

describe('field type helpers', function (): void {
    it('knows layout-only types', function (): void {
        expect(FieldType::RedHint->isLayoutOnly())->toBeTrue()
            ->and(FieldType::BlackHint->isLayoutOnly())->toBeTrue()
            ->and(FieldType::DividingLine->isLayoutOnly())->toBeTrue()
            ->and(FieldType::VarChar->isLayoutOnly())->toBeFalse();
    });

    it('knows select types', function (): void {
        expect(FieldType::SingleSelect->isSelect())->toBeTrue()
            ->and(FieldType::MultiSelect->isSelect())->toBeTrue()
            ->and(FieldType::Boolean->isSelect())->toBeFalse();
    });
});
