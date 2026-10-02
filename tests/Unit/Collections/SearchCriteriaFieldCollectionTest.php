<?php

declare(strict_types=1);

use Innobrain\Structure\Collections\SearchCriteriaFieldCollection;
use Innobrain\Structure\Testing\SearchCriteriaFieldFactory;

beforeEach(function (): void {
    $this->fields = SearchCriteriaFieldCollection::fromFields([
        SearchCriteriaFieldFactory::singleSelect('objektart', ['haus' => 'Haus'])->category('Kategorie')->make(),
        SearchCriteriaFieldFactory::range('kaufpreis')->category('Preise')->make(),
        SearchCriteriaFieldFactory::range('kaltmiete')->category('Preise')->make(),
    ]);
});

it('keys the fields by their key', function (): void {
    expect($this->fields->keys()->all())->toBe(['objektart', 'kaufpreis', 'kaltmiete'])
        ->and(SearchCriteriaFieldCollection::fromFields($this->fields))->toEqual($this->fields);
});

it('groups the fields by category', function (): void {
    $categories = $this->fields->byCategory();

    expect($categories->keys()->all())->toBe(['Kategorie', 'Preise'])
        ->and($categories->get('Preise'))->toBeInstanceOf(SearchCriteriaFieldCollection::class)
        ->and($categories->get('Preise')->keys()->all())->toBe(['kaufpreis', 'kaltmiete']);
});
