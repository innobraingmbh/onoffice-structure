<?php

declare(strict_types=1);

namespace Innobrain\Structure\Tests\Unit\Converters;

use Illuminate\Support\Collection;
use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\Converters\Array\ArrayConvertStrategy;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\FieldDependency;
use Innobrain\Structure\Dtos\FieldFilter;
use Innobrain\Structure\Dtos\Module;
use Innobrain\Structure\Dtos\PermittedValue;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\FieldType;

describe('ArrayHydrator', function () {
    it('round-trips a full ModulesCollection', function () {
        $original = new ModulesCollection([
            'address' => new Module(
                key: FieldConfigurationModule::Address,
                label: 'Adresse',
                fields: new FieldCollection([
                    'Vorname' => new Field(
                        key: 'Vorname',
                        label: 'Vorname',
                        type: FieldType::VarChar,
                        length: 80,
                        permittedValues: new Collection([
                            'pv1' => new PermittedValue(key: 'pv1', label: 'PV One'),
                            'pv2' => new PermittedValue(key: 'pv2', label: 'PV Two'),
                        ]),
                        default: 'Hans',
                        filters: new Collection([
                            'f1' => new FieldFilter(
                                name: 'f1',
                                config: collect(['objekt_art' => ['haus', 'wohnung']]),
                            ),
                        ]),
                        dependencies: new Collection([
                            new FieldDependency(dependentFieldKey: 'dep_field', dependentFieldValue: 'dep_val'),
                        ]),
                        compoundFields: collect(['cf1', 'cf2']),
                        fieldMeasureFormat: 'DATA_TYPE_TEXT',
                    ),
                ]),
            ),
            'estate' => new Module(
                key: FieldConfigurationModule::Estate,
                label: 'Immobilie',
                fields: new FieldCollection([
                    'Kaufpreis' => new Field(
                        key: 'Kaufpreis',
                        label: 'Kaufpreis',
                        type: FieldType::Float,
                        length: null,
                        permittedValues: new Collection,
                        default: null,
                        filters: new Collection,
                        dependencies: new Collection,
                        compoundFields: collect(),
                        fieldMeasureFormat: null,
                    ),
                ]),
            ),
        ]);

        $array = $original->convert(new ArrayConvertStrategy);
        $hydrated = ModulesCollection::fromArray($array);

        // Collection type is preserved
        expect($hydrated)->toBeInstanceOf(ModulesCollection::class);
        expect($hydrated)->toHaveCount(2);

        // Address module
        $addressModule = $hydrated->first();
        expect($addressModule->key)->toBe(FieldConfigurationModule::Address);
        expect($addressModule->label)->toBe('Adresse');
        expect($addressModule->fields)->toBeInstanceOf(FieldCollection::class);

        // Field
        $vorname = $addressModule->fields->get('Vorname');
        expect($vorname->key)->toBe('Vorname');
        expect($vorname->label)->toBe('Vorname');
        expect($vorname->type)->toBe(FieldType::VarChar);
        expect($vorname->length)->toBe(80);
        expect($vorname->default)->toBe('Hans');
        expect($vorname->fieldMeasureFormat)->toBe('DATA_TYPE_TEXT');

        // permittedValues keys are preserved
        expect($vorname->permittedValues->keys()->all())->toBe(['pv1', 'pv2']);
        expect($vorname->permittedValues->get('pv1'))->toBeInstanceOf(PermittedValue::class);
        expect($vorname->permittedValues->get('pv1')->key)->toBe('pv1');
        expect($vorname->permittedValues->get('pv1')->label)->toBe('PV One');

        // filters
        expect($vorname->filters->get('f1'))->toBeInstanceOf(FieldFilter::class);
        expect($vorname->filters->get('f1')->name)->toBe('f1');
        expect($vorname->filters->get('f1')->config->get('objekt_art'))->toBe(['haus', 'wohnung']);

        // dependencies
        expect($vorname->dependencies)->toHaveCount(1);
        expect($vorname->dependencies->first())->toBeInstanceOf(FieldDependency::class);
        expect($vorname->dependencies->first()->dependentFieldKey)->toBe('dep_field');
        expect($vorname->dependencies->first()->dependentFieldValue)->toBe('dep_val');

        // compoundFields
        expect($vorname->compoundFields->values()->all())->toBe(['cf1', 'cf2']);

        // Estate module with nulls
        $estateModule = $hydrated->last();
        expect($estateModule->key)->toBe(FieldConfigurationModule::Estate);
        $kaufpreis = $estateModule->fields->get('Kaufpreis');
        expect($kaufpreis->length)->toBeNull();
        expect($kaufpreis->default)->toBeNull();
        expect($kaufpreis->fieldMeasureFormat)->toBeNull();
        expect($kaufpreis->permittedValues)->toBeEmpty();
        expect($kaufpreis->filters)->toBeEmpty();
        expect($kaufpreis->dependencies)->toBeEmpty();
        expect($kaufpreis->compoundFields)->toBeEmpty();
    });

    it('round-trips a module with an empty FieldCollection', function () {
        $original = new ModulesCollection([
            'address' => new Module(
                key: FieldConfigurationModule::Address,
                label: 'Adresse',
                fields: new FieldCollection,
            ),
        ]);

        $hydrated = ModulesCollection::fromArray($original->convert(new ArrayConvertStrategy));

        expect($hydrated)->toHaveCount(1);
        expect($hydrated->first()->fields)->toBeInstanceOf(FieldCollection::class);
        expect($hydrated->first()->fields)->toBeEmpty();
    });

    it('round-trips dropEmpty: true output', function () {
        $original = new ModulesCollection([
            'address' => new Module(
                key: FieldConfigurationModule::Address,
                label: 'Adresse',
                fields: new FieldCollection([
                    'Vorname' => new Field(
                        key: 'Vorname',
                        label: 'Vorname',
                        type: FieldType::VarChar,
                        length: null,
                        permittedValues: new Collection,
                        default: null,
                        filters: new Collection,
                        dependencies: new Collection,
                        compoundFields: collect(),
                        fieldMeasureFormat: null,
                    ),
                ]),
            ),
        ]);

        $array = $original->convert(new ArrayConvertStrategy(dropEmpty: true));
        $hydrated = ModulesCollection::fromArray($array);

        expect($hydrated)->toHaveCount(1);
        $vorname = $hydrated->first()->fields->get('Vorname');
        expect($vorname->key)->toBe('Vorname');
        expect($vorname->type)->toBe(FieldType::VarChar);
        expect($vorname->length)->toBeNull();
        expect($vorname->default)->toBeNull();
        expect($vorname->permittedValues)->toBeEmpty();
        expect($vorname->filters)->toBeEmpty();
        expect($vorname->dependencies)->toBeEmpty();
        expect($vorname->compoundFields)->toBeEmpty();
    });

    it('round-trips an empty ModulesCollection', function () {
        $hydrated = ModulesCollection::fromArray([]);
        expect($hydrated)->toBeInstanceOf(ModulesCollection::class);
        expect($hydrated)->toBeEmpty();
    });
});
