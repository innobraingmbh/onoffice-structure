<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\Dtos\FieldViolation;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\ViolationReason;
use Innobrain\Structure\Testing\FieldFactory;
use Innobrain\Structure\Testing\ModuleFactory;

beforeEach(function (): void {
    $this->module = ModuleFactory::new(FieldConfigurationModule::Estate)->fields(
        FieldFactory::new('Ort'),
        FieldFactory::singleSelect('objektart', ['haus' => 'Haus', 'wohnung' => 'Wohnung']),
        FieldFactory::multiSelect('ausstattung', ['balkon' => 'Balkon', 'garten' => 'Garten']),
    )->make();
});

describe('find', function (): void {
    it('finds by exact key first', function (): void {
        expect($this->module->fields->find('Ort')?->key)->toBe('Ort');
    });

    it('falls back to a case-insensitive match', function (): void {
        expect($this->module->fields->find('ort')?->key)->toBe('Ort')
            ->and($this->module->fields->find('OBJEKTART')?->key)->toBe('objektart');
    });

    it('returns null for unknown keys', function (): void {
        expect($this->module->fields->find('missing'))->toBeNull();
    });

    it('is available on the module and the modules collection', function (): void {
        $collection = new ModulesCollection(['estate' => $this->module]);

        expect($this->module->field('ort')?->key)->toBe('Ort')
            ->and($collection->module(FieldConfigurationModule::Estate))->toBe($this->module)
            ->and($collection->module('estate'))->toBe($this->module)
            ->and($collection->module('address'))->toBeNull();
    });
});

describe('sanitize', function (): void {
    it('drops unknown fields and values that are not permitted', function (): void {
        $sanitized = $this->module->fields->sanitize(new Collection([
            'Ort' => 'Aachen',
            'objektart' => 'villa',
            'unknown' => 'x',
        ]));

        expect($sanitized->all())->toBe(['Ort' => 'Aachen']);
    });

    it('drops single items of a multi-select array', function (): void {
        $sanitized = $this->module->fields->sanitize(new Collection([
            'ausstattung' => ['balkon', 'pool', 'garten'],
        ]));

        expect($sanitized->get('ausstattung'))->toBe(['balkon', 'garten']);
    });
});

describe('violations', function (): void {
    it('reports unknown fields and rejected values', function (): void {
        $violations = $this->module->fields->violations(new Collection([
            'Ort' => 'Aachen',
            'objektart' => 'villa',
            'ausstattung' => ['balkon', 'pool'],
            'unknown' => 'x',
        ]));

        expect($violations)->toHaveCount(3)
            ->and($violations->every(fn ($violation): bool => $violation instanceof FieldViolation))->toBeTrue()
            ->and($violations->map(fn (FieldViolation $violation): array => [$violation->fieldKey, $violation->value, $violation->reason])->all())->toBe([
                ['objektart', 'villa', ViolationReason::ValueNotPermitted],
                ['ausstattung', 'pool', ViolationReason::ValueNotPermitted],
                ['unknown', 'x', ViolationReason::UnknownField],
            ]);
    });

    it('is empty for valid data', function (): void {
        expect($this->module->fields->violations(new Collection(['Ort' => 'Aachen', 'objektart' => 'haus'])))->toBeEmpty();
    });
});
