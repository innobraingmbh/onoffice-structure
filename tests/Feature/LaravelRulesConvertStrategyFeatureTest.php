<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Converters\LaravelRulesConvertStrategy;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Facades\FieldConfiguration;

use function Pest\testDirectory;

/**
 * Feature-level test: ensure LaravelRulesConvertStrategy processes the real
 * onOffice field configuration correctly.
 */
it('generates correct Laravel validation rules from the field configuration (FieldsResponse2.json)', function () {
    // ------------------------------------------------------------------
    // 1. Fake onOffice API response with FieldsResponse2.json.
    // ------------------------------------------------------------------
    $file = file_get_contents(testDirectory('Stubs/FieldsResponse2.json'));
    $json = json_decode($file, true);

    Http::fake([
        'https://api.onoffice.de/api/stable/api.php' => Http::response($json),
    ]);

    // ------------------------------------------------------------------
    // 2. Retrieve modules & convert with the strategy under test.
    // ------------------------------------------------------------------
    $modules = FieldConfiguration::retrieveForClient(new OnOfficeApiCredentials('test', 'test'));
    $strategy = new LaravelRulesConvertStrategy;   // pipe syntax, include nullable
    $address = $modules->get(FieldConfigurationModule::Address->value);
    $estate = $modules->get(FieldConfigurationModule::Estate->value);

    $addressRules = $address->convert($strategy);
    $estateRules = $estate->convert($strategy);

    // ------------------------------------------------------------------
    // 3. Assertions – Address module (from FieldsResponse2.json)
    // ------------------------------------------------------------------
    // Single-select with non-null default ➜ no "nullable" (by default condition), in-rule present.
    // 'Status2Adr' has default "status2adr_active"
    expect($addressRules['Status2Adr'])
        ->toBe('string|in:status2adr_active,status2adr_archive'); // Default is not null, so 'nullable' is not auto-added.

    // Multi-select with null default ➜ array|distinct|nullable + item rules.
    // 'ArtDaten' has default null in FieldsResponse2.json
    expect($addressRules)
        ->toHaveKeys(['ArtDaten', 'ArtDaten.*'])
        ->and($addressRules['ArtDaten'])
        ->toBe('array|distinct|nullable') // Default is null, so nullable is added
        ->and($addressRules['ArtDaten.*'])
        ->toStartWith('in:'); // e.g. in:Eigentümer,Exposé-Sammler...

    // ------------------------------------------------------------------
    // 4. Assertions – Estate module (from FieldsResponse2.json)
    // ------------------------------------------------------------------
    // Field with dependencies should include at least one required_if rule.
    // 'objekttyp' has dependencies in FieldsResponse2.json like "required_if:stellplatz,parken"
    expect($estateRules['objekttyp'])
        ->toContain('required_if:stellplatz,parken');
});

it('generates correct Laravel validation rules from FieldsResponse_json field configuration', function () {
    // ------------------------------------------------------------------
    // 1. Fake onOffice API response with FieldsResponse.json.
    // ------------------------------------------------------------------
    $file = file_get_contents(testDirectory('Stubs/FieldsResponse.json'));
    $json = json_decode($file, true);

    Http::fake([
        'https://api.onoffice.de/api/stable/api.php' => Http::response($json),
    ]);

    // ------------------------------------------------------------------
    // 2. Retrieve modules & convert with the strategy under test.
    // ------------------------------------------------------------------
    $modules = FieldConfiguration::retrieveForClient(new OnOfficeApiCredentials('test', 'test'));
    $strategy = new LaravelRulesConvertStrategy(pipeSyntax: true, includeNullable: true);
    $addressModule = $modules->get(FieldConfigurationModule::Address->value);
    $estateModule = $modules->get(FieldConfigurationModule::Estate->value);

    $addressRules = $addressModule->convert($strategy);
    $estateRules = $estateModule->convert($strategy);

    // ------------------------------------------------------------------
    // 3. Assertions – Address module (from FieldsResponse.json)
    // ------------------------------------------------------------------

    // KdNr (integer, no default, nullable)
    expect($addressRules['KdNr'])->toBe('integer|nullable');

    // Email (varchar(100), no default, nullable)
    expect($addressRules['Email'])->toBe('string|max:100|nullable');

    // Beziehung (multiselect, permittedValues as list, no default, nullable)
    // Permitted values: "Kunde", "Verwandter", "Arbeitgeber", "Tippgeber"
    // Parsed keys for permittedValues will be "0", "1", "2", "3"
    expect($addressRules)
        ->toHaveKeys(['Beziehung', 'Beziehung.*'])
        ->and($addressRules['Beziehung'])->toBe('array|distinct|nullable')
        ->and($addressRules['Beziehung.*'])->toBe('in:0,1,2,3');

    // Status2Adr (singleselect, permittedValues as list, default: "status2adr_active")
    // Permitted values: "status2adr_active", "status2adr_archive"
    // Parsed keys for permittedValues will be "0", "1"
    // Default is not null, so 'nullable' is not added by the includeNullable logic.
    expect($addressRules['Status2Adr'])->toBe('string|in:0,1');

    // AGB_akzeptiert (boolean, no default, nullable)
    expect($addressRules['AGB_akzeptiert'])->toBe('boolean|nullable');

    // ------------------------------------------------------------------
    // 4. Assertions – Estate module (from FieldsResponse.json)
    // ------------------------------------------------------------------

    // wohnflaeche (float, no default, nullable)
    expect($estateRules['wohnflaeche'])->toBe('numeric|nullable');

    // befeuerung (multiselect, permittedValues as list, no default, nullable)
    // Permitted values: "alternativ", "elektro", "erdwaerme", "gas", "luftwp", "oel", "pellet", "solar"
    // Parsed keys: "0" through "7"
    expect($estateRules)
        ->toHaveKeys(['befeuerung', 'befeuerung.*'])
        ->and($estateRules['befeuerung'])->toBe('array|distinct|nullable')
        ->and($estateRules['befeuerung.*'])->toBe('in:0,1,2,3,4,5,6,7');

    // baujahr (integer, no default, nullable)
    expect($estateRules['baujahr'])->toBe('integer|nullable');

    // objektart (singleselect, permittedValues as list, no default, nullable)
    // Example permitted values: "zimmer", "haus", ...
    // Keys will be numeric.
    $objektArtPermittedValuesCount = count($json['response']['results'][0]['data']['records'][12]['elements']['objektart']['permittedvalues']);
    $expectedObjektArtInRule = 'in:'.implode(',', range(0, $objektArtPermittedValuesCount - 1));
    expect($estateRules['objektart'])->toBe('string|'.$expectedObjektArtInRule.'|nullable');

    // No dependencies are defined in FieldsResponse.json for 'objekttyp' in 'estate' module.
    // The 'objekttyp' field itself is present in FieldsResponse.json.
    // Let's check a field that actually has dependencies if any, or ensure a field without them doesn't get dependency rules.
    // 'objekttyp' in FieldsResponse.json has no "dependencies" array.
    // The parsing logic ensures dependencies will be an empty collection.
    // So, no 'required_if' rules should be generated for it from this stub.
    expect($estateRules['objekttyp'])->not->toContain('required_if');
});
