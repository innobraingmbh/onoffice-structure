<?php

declare(strict_types=1);

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Collections\SearchCriteriaFieldCollection;
use Innobrain\Structure\Enums\Language;
use Innobrain\Structure\Enums\SearchCriteriaFieldType;
use Innobrain\Structure\Facades\Structure;
use Innobrain\Structure\Services\Structure as StructureService;

use function Pest\testDirectory;

beforeEach(function (): void {
    Http::fake([
        'https://api.onoffice.de/api/stable/api.php' => Http::response(json_decode(file_get_contents(testDirectory('Stubs/SearchCriteriaFieldsResponse.json')), true)),
    ]);

    $this->fields = Structure::forClient(new OnOfficeApiCredentials('test', 'test'))->getSearchCriteriaFields(Language::English);
});

it('keys the fields of every category by field key in API order', function (): void {
    expect($this->fields)->toBeInstanceOf(SearchCriteriaFieldCollection::class)
        ->and($this->fields->keys()->all())->toBe([
            'vermarktungsart', 'objektart', 'objekttyp',
            'wohnflaeche', 'anzahl_zimmer',
            'kaufpreis', 'kaltmiete', 'netRentPerSquareMeterPerYear',
            'regionaler_zusatz',
        ]);
});

it('requests the language and the range translations', function (): void {
    Http::assertSent(function (Request $request): bool {
        $parameters = $request->data()['request']['actions'][0]['parameters'];

        return $parameters['language'] === 'ENG' && $parameters['additionalTranslations'] === true;
    });
});

it('parses a select field', function (): void {
    $field = $this->fields->get('vermarktungsart');

    expect($field->label)->toBe('Type of commercialization')
        ->and($field->category)->toBe('Kategorie')
        ->and($field->type)->toBe(SearchCriteriaFieldType::SingleSelect)
        ->and($field->rawType)->toBe('singleselect')
        ->and($field->mustMatchByDefault)->toBeTrue()
        ->and($field->isMandatory)->toBeFalse()
        ->and($field->isRange)->toBeFalse()
        ->and($field->permittedValueLabels())->toBe(['kauf' => 'Purchase', 'miete' => 'Rent', 'pacht' => 'Lease', 'erbpacht' => 'Hereditary lease'])
        ->and($field->isClassification())->toBeTrue();
});

it('parses a range field with the labels of its bounds', function (): void {
    $field = $this->fields->get('kaufpreis');

    expect($field->isRange)->toBeTrue()
        ->and($field->type)->toBe(SearchCriteriaFieldType::Decimal)
        ->and($field->rawType)->toBe('urn:onoffice-de-ns:smart:2.5:dbAccess:dataType:decimal')
        ->and($field->rangeFromKey())->toBe('kaufpreis__von')
        ->and($field->rangeToKey())->toBe('kaufpreis__bis')
        ->and($field->storedRangeKey())->toBe('range_kaufpreis')
        ->and($field->rangeFromLabel)->toBe('min. Sales price')
        ->and($field->rangeToLabel)->toBe('max. Sales price')
        ->and($field->mustMatchByDefault)->toBeFalse()
        ->and($field->hasPermittedValues())->toBeFalse();
});

it('parses the classification values a field applies to', function (): void {
    $field = $this->fields->get('netRentPerSquareMeterPerYear');

    expect($field->appliesTo->all())->toBe(['vermarktungsart' => ['miete'], 'nutzungsart' => ['gewerbe']])
        ->and($this->fields->get('objektart')->appliesTo)->toBeEmpty();
});

it('parses the regions field', function (): void {
    $field = $this->fields->get('regionaler_zusatz');

    expect($field->isRegions())->toBeTrue()
        ->and($field->type)->toBe(SearchCriteriaFieldType::RegionsDisplayLive)
        ->and($this->fields->get('kaufpreis')->isRegions())->toBeFalse();
});

it('keeps a field whose type is unknown', function (): void {
    $response = json_decode(file_get_contents(testDirectory('Stubs/SearchCriteriaFieldsResponse.json')), true);
    $response['response']['results'][0]['data']['records'] = [[
        'id' => 0,
        'type' => '',
        'elements' => ['name' => 'Sonstiges', 'fields' => [
            ['id' => 'neu', 'name' => 'Neu', 'position' => 1, 'type' => 'urn:onoffice-de-ns:smart:2.5:dbAccess:dataType:hologram', 'mandatory' => 'true'],
            ['name' => 'Ohne Schlüssel', 'type' => 'singleselect'],
        ]],
    ]];

    Http::swap(new Illuminate\Http\Client\Factory);
    Http::fake(['https://api.onoffice.de/api/stable/api.php' => Http::response($response)]);

    $fields = Structure::forClient(new OnOfficeApiCredentials('test', 'test'))->getSearchCriteriaFields();

    expect($fields->keys()->all())->toBe(['neu'])
        ->and($fields->get('neu')->type)->toBeNull()
        ->and($fields->get('neu')->rawType)->toBe('urn:onoffice-de-ns:smart:2.5:dbAccess:dataType:hologram')
        ->and($fields->get('neu')->isMandatory)->toBeTrue();
});

it('requires credentials', function (): void {
    Structure::getSearchCriteriaFields();
})->throws(LogicException::class);

it('survives serialization with the serializable classes', function (): void {
    $restored = unserialize(serialize($this->fields), ['allowed_classes' => StructureService::serializableClasses()]);

    expect($restored)->toEqual($this->fields)
        ->and(serialize($restored))->not->toContain('__PHP_Incomplete_Class');
});
