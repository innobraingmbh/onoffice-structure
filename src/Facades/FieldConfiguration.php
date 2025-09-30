<?php

declare(strict_types=1);

namespace Innobrain\Structure\Facades;

use Illuminate\Support\Facades\Facade;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\Services\FieldConfiguration as ServiceFieldConfiguration;

/**
 * @see ServiceFieldConfiguration
 *
 * @method static ModulesCollection retrieveForClient(OnOfficeApiCredentials $credentials, array $only = [])
 */
class FieldConfiguration extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ServiceFieldConfiguration::class;
    }
}
