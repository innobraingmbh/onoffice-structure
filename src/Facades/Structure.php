<?php

declare(strict_types=1);

namespace Innobrain\Structure\Facades;

use Illuminate\Support\Facades\Facade;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\Enums\Language;
use Innobrain\Structure\Services\Structure as ServiceStructure;

/**
 * @see ServiceStructure
 *
 * @method static ServiceStructure forClient(OnOfficeApiCredentials $onOfficeApiCredentials)
 * @method static ModulesCollection getModules(string|array<int, string> $only = [], Language $language = Language::German)
 */
class Structure extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ServiceStructure::class;
    }
}
