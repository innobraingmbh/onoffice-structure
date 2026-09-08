<?php

declare(strict_types=1);

namespace Innobrain\Structure\Facades;

use Illuminate\Support\Facades\Facade;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\Dtos\Module;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\Language;
use Innobrain\Structure\Services\FieldConfiguration as ServiceFieldConfiguration;
use Innobrain\Structure\Testing\FieldConfigurationFake;

/**
 * @see ServiceFieldConfiguration
 *
 * @method static ModulesCollection retrieveForClient(OnOfficeApiCredentials $credentials, array<int, FieldConfigurationModule|string> $only = [], Language $language = Language::German)
 */
class FieldConfiguration extends Facade
{
    /**
     * Replace the field configuration with canned modules for the rest of the test.
     *
     * @param  ModulesCollection|array<int, Module>  $modules
     */
    public static function fake(ModulesCollection|array $modules = []): FieldConfigurationFake
    {
        $fake = new FieldConfigurationFake($modules);

        static::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return ServiceFieldConfiguration::class;
    }
}
