<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\Converters\ArrayConvertStrategy;
use Innobrain\Structure\DTOs\Field;
use Innobrain\Structure\DTOs\FieldFilter;
use Innobrain\Structure\DTOs\Module;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Facades\FieldConfiguration;

it('should be able to retrieve the field configuration', function () {
    $file = file_get_contents(Pest\testDirectory('Stubs/FieldsResponse.json'));
    $json = json_decode($file, true);

    Illuminate\Support\Facades\Http::fake([
        'https://api.onoffice.de/api/stable/api.php/' => Illuminate\Support\Facades\Http::response($json),
    ]);

    $modules = FieldConfiguration::retrieveForClient('test', 'test');

    expect($modules)->toBeInstanceOf(ModulesCollection::class) // Changed from Collection::class to ModulesCollection::class
        ->and($modules->first())->toBeInstanceOf(Module::class)
        ->and($modules->first()->fields)->toBeInstanceOf(Collection::class)
        ->and($modules->first()->fields->first())->toBeInstanceOf(Field::class);

});

it('should be able to retrieve the field configuration for client 2', function () {
    $file = file_get_contents(Pest\testDirectory('Stubs/FieldsResponse2.json'));
    $json = json_decode($file, true);

    Illuminate\Support\Facades\Http::fake([
        'https://api.onoffice.de/api/stable/api.php/' => Illuminate\Support\Facades\Http::response($json),
    ]);

    $modules = FieldConfiguration::retrieveForClient('test', 'test');

    expect($modules)->toBeInstanceOf(ModulesCollection::class) // Changed from Collection::class to ModulesCollection::class
        ->and($modules->first())->toBeInstanceOf(Module::class)
        ->and($modules->first()->fields)->toBeInstanceOf(Collection::class)
        ->and($modules->first()->fields->first())->toBeInstanceOf(Field::class)
        ->and($modules->first()->fields->get('Anrede-Titel')->filters->first())->toBeInstanceOf(FieldFilter::class);
});

it('should correctly convert retrieved field configuration to array using ArrayConvertStrategy', function () {
    $file = file_get_contents(Pest\testDirectory('Stubs/FieldsResponse2.json'));
    $jsonResponse = json_decode($file, true);

    Illuminate\Support\Facades\Http::fake([
        'https://api.onoffice.de/api/stable/api.php/' => Illuminate\Support\Facades\Http::response($jsonResponse),
    ]);

    $modulesCollection = FieldConfiguration::retrieveForClient('test-token', 'test-secret');

    // Test with dropEmpty: false
    $strategyKeepEmpty = new ArrayConvertStrategy(dropEmpty: false);
    $convertedArrayKeepEmpty = $modulesCollection->convert($strategyKeepEmpty);

    expect($convertedArrayKeepEmpty)->toBeArray();

    // Find the 'address' module in the converted array
    $addressModuleKeepEmpty = collect($convertedArrayKeepEmpty)->firstWhere('key', FieldConfigurationModule::Address->value);
    expect($addressModuleKeepEmpty)->not->toBeNull()
        ->and($addressModuleKeepEmpty['label'])->toBe('Address');

    // Check a simple field: KdNr
    $kdNrFieldKeepEmpty = $addressModuleKeepEmpty['fields']['KdNr'];
    expect($kdNrFieldKeepEmpty)->toBe([
        'key' => 'KdNr',
        'label' => 'KdNr',
        'type' => 'integer',
        'length' => null,
        'permittedValues' => [],
        'default' => null,
        'filters' => [],
        'dependencies' => [],
        'compoundFields' => [],
        'fieldMeasureFormat' => null,
    ]);

    // Check a field with permitted values and default: Status2Adr
    $status2AdrFieldKeepEmpty = $addressModuleKeepEmpty['fields']['Status2Adr'];
    expect($status2AdrFieldKeepEmpty['key'])->toBe('Status2Adr')
        ->and($status2AdrFieldKeepEmpty['type'])->toBe('singleselect')
        ->and($status2AdrFieldKeepEmpty['default'])->toBe('status2adr_active')
        ->and($status2AdrFieldKeepEmpty['permittedValues'])->toBe([
            'status2adr_active' => ['key' => 'status2adr_active', 'label' => 'Aktiv'],
            'status2adr_archive' => ['key' => 'status2adr_archive', 'label' => 'Archiviert'],
        ]);

    // Check a field with filters and compound fields: Anrede-Titel
    $anredeTitelFieldKeepEmpty = $addressModuleKeepEmpty['fields']['Anrede-Titel'];
    expect($anredeTitelFieldKeepEmpty['key'])->toBe('Anrede-Titel')
        ->and($anredeTitelFieldKeepEmpty['type'])->toBe('varchar')
        ->and($anredeTitelFieldKeepEmpty['compoundFields'])->toBe(['Anrede', 'Titel'])
        ->and($anredeTitelFieldKeepEmpty['filters']['Anrede-Titel'])->toBe([
            'name' => 'Anrede-Titel',
            'config' => [
                'kontaktkategorien' => ['', 'businessCustomer', 'privateCustomer'],
            ],
        ]);

    // Check a field with dependencies from 'estate' module: objekttyp
    $estateModuleKeepEmpty = collect($convertedArrayKeepEmpty)->firstWhere('key', FieldConfigurationModule::Estate->value);
    $objekttypFieldKeepEmpty = $estateModuleKeepEmpty['fields']['objekttyp'];
    expect($objekttypFieldKeepEmpty['dependencies'])->toContain([
        'dependentFieldKey' => 'stellplatz',
        'dependentFieldValue' => 'parken',
    ]);

    // Test with dropEmpty: true
    $strategyDropEmpty = new ArrayConvertStrategy(dropEmpty: true);
    $convertedArrayDropEmpty = $modulesCollection->convert($strategyDropEmpty);

    expect($convertedArrayDropEmpty)->toBeArray();

    // Find the 'address' module in the converted array
    $addressModuleDropEmpty = collect($convertedArrayDropEmpty)->firstWhere('key', FieldConfigurationModule::Address->value);
    expect($addressModuleDropEmpty)->not->toBeNull()
        ->and($addressModuleDropEmpty['label'])->toBe('Address');

    // Check a simple field where nulls/empties should be dropped: KdNr
    // Original: "KdNr":{"type":"integer","length":null,"permittedvalues":null,"default":null,"filters":[],"dependencies":[],"compoundFields":[],"label":"KdNr","fieldMeasureFormat":null}
    $kdNrFieldDropEmpty = $addressModuleDropEmpty['fields']['KdNr'];
    expect($kdNrFieldDropEmpty)->toBe([
        'key' => 'KdNr',
        'label' => 'KdNr',
        'type' => 'integer',
        // All other null/empty fields are dropped
    ])
        ->and($kdNrFieldDropEmpty)->not->toHaveKeys(['length', 'permittedValues', 'default', 'filters', 'dependencies', 'compoundFields', 'fieldMeasureFormat']);

    // Check Status2Adr field again with dropEmpty: true
    $status2AdrFieldDropEmpty = $addressModuleDropEmpty['fields']['Status2Adr'];
    expect($status2AdrFieldDropEmpty['key'])->toBe('Status2Adr')
        ->and($status2AdrFieldDropEmpty['type'])->toBe('singleselect')
        ->and($status2AdrFieldDropEmpty['default'])->toBe('status2adr_active') // default is not empty
        ->and($status2AdrFieldDropEmpty['permittedValues'])->toBe([ // permittedValues is not empty
            'status2adr_active' => ['key' => 'status2adr_active', 'label' => 'Aktiv'],
            'status2adr_archive' => ['key' => 'status2adr_archive', 'label' => 'Archiviert'],
        ])
        ->and($status2AdrFieldDropEmpty)->not->toHaveKeys(['length', 'filters', 'dependencies', 'compoundFields', 'fieldMeasureFormat']);
    // These were null or empty

    // Check Anrede-Titel field again with dropEmpty: true
    $anredeTitelFieldDropEmpty = $addressModuleDropEmpty['fields']['Anrede-Titel'];
    expect($anredeTitelFieldDropEmpty['key'])->toBe('Anrede-Titel')
        ->and($anredeTitelFieldDropEmpty['type'])->toBe('varchar')
        ->and($anredeTitelFieldDropEmpty['length'])->toBe(80) // Length is not null
        ->and($anredeTitelFieldDropEmpty['compoundFields'])->toBe(['Anrede', 'Titel']) // compoundFields is not empty
        ->and($anredeTitelFieldDropEmpty['filters']['Anrede-Titel'])->toBe([ // filters is not empty
            'name' => 'Anrede-Titel',
            'config' => [
                'kontaktkategorien' => [1 => 'businessCustomer', 2 => 'privateCustomer'],
            ],
        ])
        ->and($anredeTitelFieldDropEmpty)->not->toHaveKeys(['permittedValues', 'default', 'dependencies', 'fieldMeasureFormat']);

    // Check a field that should have many things dropped: gwgGeburtsdatum
    // "gwgGeburtsdatum":{"type":"date","length":null,"permittedvalues":null,"default":null,"filters":[],"dependencies":[],"compoundFields":[],"label":"Gwg-Geburtsdatum","fieldMeasureFormat":null}
    $gwgGeburtsdatumFieldDropEmpty = $addressModuleDropEmpty['fields']['gwgGeburtsdatum'];
    expect($gwgGeburtsdatumFieldDropEmpty)->toBe([
        'key' => 'gwgGeburtsdatum',
        'label' => 'Gwg-Geburtsdatum',
        'type' => 'date',
    ])
        ->and($gwgGeburtsdatumFieldDropEmpty)->not->toHaveKeys(['length', 'permittedValues', 'default', 'filters', 'dependencies', 'compoundFields', 'fieldMeasureFormat']);

});

it('should correctly convert retrieved field configuration from FieldsResponse_json to array using ArrayConvertStrategy', function () {
    $file = file_get_contents(Pest\testDirectory('Stubs/FieldsResponse.json'));
    $jsonResponse = json_decode($file, true);

    Illuminate\Support\Facades\Http::fake([
        'https://api.onoffice.de/api/stable/api.php/' => Illuminate\Support\Facades\Http::response($jsonResponse),
    ]);

    $modulesCollection = FieldConfiguration::retrieveForClient('test-token', 'test-secret');

    // Test with dropEmpty: false
    $strategyKeepEmpty = new ArrayConvertStrategy(dropEmpty: false);
    $convertedArrayKeepEmpty = $modulesCollection->convert($strategyKeepEmpty);

    expect($convertedArrayKeepEmpty)->toBeArray();

    // Find the 'address' module in the converted array
    $addressModuleKeepEmpty = collect($convertedArrayKeepEmpty)->firstWhere('key', FieldConfigurationModule::Address->value);
    expect($addressModuleKeepEmpty)->not->toBeNull()
        ->and($addressModuleKeepEmpty['key'])->toBe(FieldConfigurationModule::Address->value)
        ->and($addressModuleKeepEmpty['label'])->toBe('Address'); // No explicit label in stub, so ucfirst(key)

    // Check a simple field: KdNr (label will be KdNr as no explicit label in stub)
    $kdNrFieldKeepEmpty = $addressModuleKeepEmpty['fields']['KdNr'];
    expect($kdNrFieldKeepEmpty)->toBe([
        'key' => 'KdNr',
        'label' => 'KdNr', // Derived from key
        'type' => 'integer',
        'length' => null,
        'permittedValues' => [],
        'default' => null,
        'filters' => [],
        'dependencies' => [],
        'compoundFields' => [],
        'fieldMeasureFormat' => null,
    ]);

    // Check a multiselect field with permitted values (array of strings): Beziehung
    $beziehungFieldKeepEmpty = $addressModuleKeepEmpty['fields']['Beziehung'];
    expect($beziehungFieldKeepEmpty['key'])->toBe('Beziehung')
        ->and($beziehungFieldKeepEmpty['label'])->toBe('Beziehung') // Derived from key
        ->and($beziehungFieldKeepEmpty['type'])->toBe('multiselect')
        ->and($beziehungFieldKeepEmpty['permittedValues'])->toBe([
            '0' => ['key' => '0', 'label' => 'Kunde'],
            '1' => ['key' => '1', 'label' => 'Verwandter'],
            '2' => ['key' => '2', 'label' => 'Arbeitgeber'],
            '3' => ['key' => '3', 'label' => 'Tippgeber'],
        ]);

    // Check a singleselect field with permitted values (array of strings) and default: Status2Adr
    $status2AdrFieldKeepEmpty = $addressModuleKeepEmpty['fields']['Status2Adr'];
    expect($status2AdrFieldKeepEmpty['key'])->toBe('Status2Adr')
        ->and($status2AdrFieldKeepEmpty['label'])->toBe('Status2Adr') // Derived from key
        ->and($status2AdrFieldKeepEmpty['type'])->toBe('singleselect')
        ->and($status2AdrFieldKeepEmpty['default'])->toBe('status2adr_active')
        ->and($status2AdrFieldKeepEmpty['permittedValues'])->toBe([
            '0' => ['key' => '0', 'label' => 'status2adr_active'],
            '1' => ['key' => '1', 'label' => 'status2adr_archive'],
        ]);

    // Check a field with compound fields: Anrede-Titel
    $anredeTitelFieldKeepEmpty = $addressModuleKeepEmpty['fields']['Anrede-Titel'];
    expect($anredeTitelFieldKeepEmpty['key'])->toBe('Anrede-Titel')
        ->and($anredeTitelFieldKeepEmpty['label'])->toBe('Anrede-Titel') // Derived from key
        ->and($anredeTitelFieldKeepEmpty['type'])->toBe('varchar')
        ->and($anredeTitelFieldKeepEmpty['compoundFields'])->toBe(['Anrede', 'Titel']);

    // Test with dropEmpty: true
    $strategyDropEmpty = new ArrayConvertStrategy(dropEmpty: true);
    $convertedArrayDropEmpty = $modulesCollection->convert($strategyDropEmpty);

    expect($convertedArrayDropEmpty)->toBeArray();
    $addressModuleDropEmpty = collect($convertedArrayDropEmpty)->firstWhere('key', FieldConfigurationModule::Address->value);

    // Check KdNr field with dropEmpty: true
    $kdNrFieldDropEmpty = $addressModuleDropEmpty['fields']['KdNr'];
    expect($kdNrFieldDropEmpty)->toBe([
        'key' => 'KdNr',
        'label' => 'KdNr',
        'type' => 'integer',
    ])->and($kdNrFieldDropEmpty)->not->toHaveKeys(['length', 'permittedValues', 'default', 'filters', 'dependencies', 'compoundFields', 'fieldMeasureFormat']);

    // Check Beziehung field with dropEmpty: true (permittedValues should remain as it's not empty)
    $beziehungFieldDropEmpty = $addressModuleDropEmpty['fields']['Beziehung'];
    expect($beziehungFieldDropEmpty)->toEqual([
        'key' => 'Beziehung',
        'label' => 'Beziehung',
        'type' => 'multiselect',
        'permittedValues' => [
            '0' => ['key' => '0', 'label' => 'Kunde'],
            '1' => ['key' => '1', 'label' => 'Verwandter'],
            '2' => ['key' => '2', 'label' => 'Arbeitgeber'],
            '3' => ['key' => '3', 'label' => 'Tippgeber'],
        ],
    ])
        ->and($beziehungFieldDropEmpty)->not->toHaveKeys(['length', 'default', 'filters', 'dependencies', 'compoundFields', 'fieldMeasureFormat']);

    // Check Anrede-Titel field with dropEmpty: true (compoundFields should remain)
    $anredeTitelFieldDropEmpty = $addressModuleDropEmpty['fields']['Anrede-Titel'];
    expect($anredeTitelFieldDropEmpty)->toEqual([
        'key' => 'Anrede-Titel',
        'label' => 'Anrede-Titel',
        'type' => 'varchar',
        'length' => 80, // Length is present in stub
        'compoundFields' => ['Anrede', 'Titel'],
    ])
        ->and($anredeTitelFieldDropEmpty)->not->toHaveKeys(['permittedValues', 'default', 'filters', 'dependencies', 'fieldMeasureFormat']);

});
