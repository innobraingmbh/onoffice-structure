<?php

declare(strict_types=1);

namespace Innobrain\Structure\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\OnOfficeAdapter\Exceptions\OnOfficeException;
use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\Collections\SearchCriteriaFieldCollection;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\FieldDependency;
use Innobrain\Structure\Dtos\FieldFilter;
use Innobrain\Structure\Dtos\Module;
use Innobrain\Structure\Dtos\PermittedValue;
use Innobrain\Structure\Dtos\SearchCriteriaField;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\FieldMeasureFormat;
use Innobrain\Structure\Enums\FieldType;
use Innobrain\Structure\Enums\Language;
use Innobrain\Structure\Enums\SearchCriteriaFieldType;
use LogicException;
use Throwable;

class Structure
{
    public function __construct(
        private readonly FieldConfiguration $fieldConfiguration,
        private readonly ?OnOfficeApiCredentials $onOfficeApiCredentials = null,
    ) {}

    /**
     * The classes a cache store needs to allow when unserializing a
     * ModulesCollection or a SearchCriteriaFieldCollection, for the
     * "cache.serializable_classes" config.
     *
     * @return array<int, class-string>
     */
    public static function serializableClasses(): array
    {
        return [
            ModulesCollection::class,
            Module::class,
            FieldCollection::class,
            Field::class,
            PermittedValue::class,
            FieldFilter::class,
            FieldDependency::class,
            Collection::class,
            FieldConfigurationModule::class,
            FieldType::class,
            FieldMeasureFormat::class,
            SearchCriteriaFieldCollection::class,
            SearchCriteriaField::class,
            SearchCriteriaFieldType::class,
        ];
    }

    public function forClient(OnOfficeApiCredentials $onOfficeApiCredentials): self
    {
        return new self($this->fieldConfiguration, $onOfficeApiCredentials);
    }

    /**
     * @param  FieldConfigurationModule|string|array<int, FieldConfigurationModule|string>  $only  Empty means all modules
     *
     * @throws OnOfficeException
     * @throws Throwable
     */
    public function getModules(FieldConfigurationModule|string|array $only = [], Language $language = Language::German): ModulesCollection
    {
        throw_unless($this->onOfficeApiCredentials instanceof OnOfficeApiCredentials, LogicException::class, 'No OnOfficeApiCredentials provided. Use the forClient method to provide credentials.');

        return $this->fieldConfiguration->retrieveForClient($this->onOfficeApiCredentials, Arr::wrap($only), $language);
    }

    /**
     * The fields the client has configured as search criteria, keyed by field
     * key. They are only read from onOffice when this is called.
     *
     * @throws OnOfficeException
     * @throws Throwable
     */
    public function getSearchCriteriaFields(Language $language = Language::German): SearchCriteriaFieldCollection
    {
        throw_unless($this->onOfficeApiCredentials instanceof OnOfficeApiCredentials, LogicException::class, 'No OnOfficeApiCredentials provided. Use the forClient method to provide credentials.');

        return resolve(SearchCriteria::class)->retrieveForClient($this->onOfficeApiCredentials, $language);
    }
}
