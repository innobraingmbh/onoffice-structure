<?php

declare(strict_types=1);

namespace Innobrain\Structure\Testing;

use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Collections\SearchCriteriaFieldCollection;
use Innobrain\Structure\Dtos\SearchCriteriaField;
use Innobrain\Structure\Enums\Language;
use Innobrain\Structure\Services\SearchCriteria;
use Override;
use PHPUnit\Framework\Assert as PHPUnit;

class SearchCriteriaFake extends SearchCriteria
{
    private readonly SearchCriteriaFieldCollection $fields;

    /**
     * @var array<int, Language>
     */
    private array $retrievals = [];

    /**
     * @param  SearchCriteriaFieldCollection|array<int, SearchCriteriaField>  $fields
     */
    public function __construct(SearchCriteriaFieldCollection|array $fields = [])
    {
        $this->fields = SearchCriteriaFieldCollection::fromFields($fields);
    }

    /**
     * Returns the canned fields. The same fields are returned for every language.
     */
    #[Override]
    public function retrieveForClient(OnOfficeApiCredentials $credentials, Language $language = Language::German): SearchCriteriaFieldCollection
    {
        $this->retrievals[] = $language;

        return $this->fields;
    }

    public function assertRetrieved(?Language $language = null): void
    {
        $retrieved = collect($this->retrievals)->contains(
            fn (Language $retrieval): bool => ! $language instanceof Language || $retrieval === $language,
        );

        PHPUnit::assertTrue($retrieved, $language instanceof Language
            ? "The search criteria fields were not retrieved in language [{$language->value}]."
            : 'The search criteria fields were not retrieved.');
    }

    public function assertRetrievedTimes(int $times): void
    {
        $actual = count($this->retrievals);

        PHPUnit::assertSame($times, $actual, "The search criteria fields were retrieved {$actual} times instead of {$times}.");
    }

    public function assertNotRetrieved(): void
    {
        PHPUnit::assertEmpty($this->retrievals, 'The search criteria fields were retrieved unexpectedly.');
    }
}
