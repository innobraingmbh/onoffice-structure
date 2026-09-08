<?php

declare(strict_types=1);

use Innobrain\Structure\Dtos\FieldDependency;
use Innobrain\Structure\Dtos\FieldFilter;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\FieldMeasureFormat;
use Innobrain\Structure\Enums\FieldType;
use Innobrain\Structure\Testing\FieldFactory;
use Innobrain\Structure\Testing\ModuleFactory;

it('builds a minimal field', function (): void {
    $field = FieldFactory::new('Ort')->make();

    expect($field->key)->toBe('Ort')
        ->and($field->label)->toBe('Ort')
        ->and($field->type)->toBe(FieldType::VarChar)
        ->and($field->permittedValues)->toBeEmpty();
});

it('builds a fully configured field', function (): void {
    $field = FieldFactory::singleSelect('objekttyp', ['einfamilienhaus' => 'Einfamilienhaus', '1' => 'Eins'])
        ->label('Objekttyp')
        ->length(10)
        ->default('einfamilienhaus')
        ->dependencies(['einfamilienhaus' => 'haus', '1' => 'wohnung'])
        ->filter(['immobilienart' => ['haus']])
        ->compoundFields('a', 'b')
        ->measureFormat(FieldMeasureFormat::Area)
        ->make();

    expect($field->label)->toBe('Objekttyp')
        ->and($field->length)->toBe(10)
        ->and($field->default)->toBe('einfamilienhaus')
        ->and($field->permittedValueKeys())->toBe(['einfamilienhaus', '1'])
        ->and($field->permittedValues->get('1')?->label)->toBe('Eins')
        ->and($field->dependencies->all())->toEqual([
            new FieldDependency('einfamilienhaus', 'haus'),
            new FieldDependency('1', 'wohnung'),
        ])
        ->and($field->filters->get('objekttyp'))->toEqual(new FieldFilter('objekttyp', collect(['immobilienart' => ['haus']])))
        ->and($field->compoundFields->all())->toBe(['a', 'b'])
        ->and($field->fieldMeasureFormat)->toBe(FieldMeasureFormat::Area)
        ->and($field->withPermittedValuesFor('wohnung')->permittedValueKeys())->toBe(['1']);
});

it('builds a module with fields keyed by their key', function (): void {
    $module = ModuleFactory::new(FieldConfigurationModule::Address)
        ->fields(FieldFactory::new('Vorname'), FieldFactory::new('Name')->make())
        ->make();

    expect($module->key)->toBe(FieldConfigurationModule::Address)
        ->and($module->label)->toBe('Address')
        ->and($module->fields->keys()->all())->toBe(['Vorname', 'Name']);
});
