<?php

declare(strict_types=1);

use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Dtos\SearchCriteriaField;
use Innobrain\Structure\Enums\EstateClassification;
use Innobrain\Structure\Enums\Language;
use Innobrain\Structure\Enums\SearchCriteriaFieldType;
use Innobrain\Structure\Facades\SearchCriteria;
use Innobrain\Structure\Facades\Structure;
use Innobrain\Structure\Testing\SearchCriteriaFieldFactory;
use PHPUnit\Framework\AssertionFailedError;

beforeEach(function (): void {
    $this->credentials = new OnOfficeApiCredentials('token', 'secret');
});

it('builds a minimal field', function (): void {
    $field = SearchCriteriaFieldFactory::new('ort')->make();

    expect($field->key)->toBe('ort')
        ->and($field->label)->toBe('Ort')
        ->and($field->type)->toBeNull()
        ->and($field->rawType)->toBe('')
        ->and($field->isRange)->toBeFalse()
        ->and($field->isClassification())->toBeFalse();
});

it('builds a fully configured field', function (): void {
    $field = SearchCriteriaFieldFactory::range('kaufpreis', SearchCriteriaFieldType::Decimal)
        ->label('Kaufpreis')
        ->category('Preise')
        ->rangeLabels('Kaufpreis von', 'Kaufpreis bis')
        ->mustMatchByDefault()
        ->mandatory()
        ->appliesTo(EstateClassification::MarketingType, 'kauf')
        ->make();

    expect($field)->toEqual(new SearchCriteriaField(
        key: 'kaufpreis',
        label: 'Kaufpreis',
        category: 'Preise',
        rawType: 'decimal',
        type: SearchCriteriaFieldType::Decimal,
        isRange: true,
        rangeFromLabel: 'Kaufpreis von',
        rangeToLabel: 'Kaufpreis bis',
        mustMatchByDefault: true,
        isMandatory: true,
        appliesTo: collect(['vermarktungsart' => ['kauf']]),
    ));
});

it('builds selects and the regions field', function (): void {
    $select = SearchCriteriaFieldFactory::multiSelect('ausstattung', ['1' => 'Eins', 'pool' => 'Pool'])->make();

    expect($select->type)->toBe(SearchCriteriaFieldType::MultiSelect)
        ->and($select->permittedValueKeys())->toBe(['1', 'pool'])
        ->and(SearchCriteriaFieldFactory::regions()->make()->isRegions())->toBeTrue();
});

it('serves canned fields through the Structure facade', function (): void {
    $fake = Structure::fakeSearchCriteria([SearchCriteriaFieldFactory::range('kaufpreis')->make()]);

    $fields = Structure::forClient($this->credentials)->getSearchCriteriaFields(Language::English);

    expect($fields->keys()->all())->toBe(['kaufpreis']);

    $fake->assertRetrieved();
    $fake->assertRetrieved(Language::English);
    $fake->assertRetrievedTimes(1);
});

it('replaces a Structure instance that was resolved before faking', function (): void {
    Structure::forClient($this->credentials);

    Structure::fakeSearchCriteria([SearchCriteriaFieldFactory::new('ort')->make()]);

    expect(Structure::forClient($this->credentials)->getSearchCriteriaFields()->keys()->all())->toBe(['ort']);
});

it('fails assertions when nothing was retrieved', function (): void {
    $fake = SearchCriteria::fake();

    $fake->assertNotRetrieved();

    expect(fn () => $fake->assertRetrieved())->toThrow(AssertionFailedError::class)
        ->and(fn () => $fake->assertRetrievedTimes(1))->toThrow(AssertionFailedError::class);
});

it('fails the language assertion for another language', function (): void {
    $fake = SearchCriteria::fake();

    SearchCriteria::retrieveForClient($this->credentials);

    expect(fn () => $fake->assertRetrieved(Language::English))->toThrow(AssertionFailedError::class)
        ->and(fn () => $fake->assertNotRetrieved())->toThrow(AssertionFailedError::class);
});
