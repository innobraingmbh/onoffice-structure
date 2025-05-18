<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Innobrain\Structure\Converters\LaravelRulesConvertStrategy;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Facades\FieldConfiguration;

/**
 * Feature-level test: ensure LaravelRulesConvertStrategy processes the real
 * onOffice field configuration correctly.
 */
it('generates correct Laravel validation rules from the field configuration', function () {
    // ------------------------------------------------------------------
    // 1. Fake onOffice API response with the bundled JSON stub.
    // ------------------------------------------------------------------
    $file = file_get_contents(Pest\testDirectory('Stubs/FieldsResponse2.json'));
    $json = json_decode($file, true);

    Http::fake([
        'https://api.onoffice.de/api/stable/api.php/' => Http::response($json),
    ]);

    // ------------------------------------------------------------------
    // 2. Retrieve modules & convert with the strategy under test.
    // ------------------------------------------------------------------
    $modules = FieldConfiguration::retrieveForClient('test-token', 'test-secret');
    $strategy = new LaravelRulesConvertStrategy;   // pipe syntax, include nullable
    $address = $modules->get(FieldConfigurationModule::Address->value);
    $estate = $modules->get(FieldConfigurationModule::Estate->value);

    $addressRules = $address->convert($strategy);
    $estateRules = $estate->convert($strategy);

    // ------------------------------------------------------------------
    // 3. Assertions – Address module
    // ------------------------------------------------------------------
    // Single-select with non-null default ➜ no "nullable", in-rule present.
    expect($addressRules['Status2Adr'])
        ->toBe('string|in:status2adr_active,status2adr_archive');

    // Multi-select with null default ➜ array|distinct|nullable + item rules.
    expect($addressRules)
        ->toHaveKeys(['ArtDaten', 'ArtDaten.*'])
        ->and($addressRules['ArtDaten'])
        ->toBe('array|distinct|nullable')
        ->and($addressRules['ArtDaten.*'])
        ->toStartWith('in:');

    // ------------------------------------------------------------------
    // 4. Assertions – Estate module
    // ------------------------------------------------------------------
    // Field with dependencies should include at least one required_if rule.
    expect($estateRules['objekttyp'])
        ->toContain('required_if:stellplatz,parken');
});
