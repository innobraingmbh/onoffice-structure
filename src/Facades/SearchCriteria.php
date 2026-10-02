<?php

declare(strict_types=1);

namespace Innobrain\Structure\Facades;

use Illuminate\Support\Facades\Facade;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Collections\SearchCriteriaFieldCollection;
use Innobrain\Structure\Dtos\SearchCriteriaField;
use Innobrain\Structure\Enums\Language;
use Innobrain\Structure\Services\SearchCriteria as ServiceSearchCriteria;
use Innobrain\Structure\Testing\SearchCriteriaFake;

/**
 * @see ServiceSearchCriteria
 *
 * @method static SearchCriteriaFieldCollection retrieveForClient(OnOfficeApiCredentials $credentials, Language $language = Language::German)
 */
class SearchCriteria extends Facade
{
    /**
     * Replace the search criteria with canned fields for the rest of the test.
     *
     * @param  SearchCriteriaFieldCollection|array<int, SearchCriteriaField>  $fields
     */
    public static function fake(SearchCriteriaFieldCollection|array $fields = []): SearchCriteriaFake
    {
        $fake = new SearchCriteriaFake($fields);

        static::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return ServiceSearchCriteria::class;
    }
}
