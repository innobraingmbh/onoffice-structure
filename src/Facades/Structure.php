<?php

declare(strict_types=1);

namespace Innobrain\Structure\Facades;

use Illuminate\Support\Facades\Facade;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\Dtos\Module;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\Language;
use Innobrain\Structure\Services\Structure as ServiceStructure;
use Innobrain\Structure\Testing\FieldConfigurationFake;

/**
 * @see ServiceStructure
 *
 * @method static ServiceStructure forClient(OnOfficeApiCredentials $onOfficeApiCredentials)
 * @method static ModulesCollection getModules(FieldConfigurationModule|string|array<int, FieldConfigurationModule|string> $only = [], Language $language = Language::German)
 * @method static array<int, class-string> serializableClasses()
 */
class Structure extends Facade
{
    /**
     * Replace the field configuration with canned modules for the rest of the test.
     *
     * @param  ModulesCollection|array<int, Module>  $modules
     */
    public static function fake(ModulesCollection|array $modules = []): FieldConfigurationFake
    {
        return FieldConfiguration::fake($modules);
    }

    protected static function getFacadeAccessor(): string
    {
        return ServiceStructure::class;
    }
}
