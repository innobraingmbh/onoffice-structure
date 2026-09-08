<?php

declare(strict_types=1);

namespace Innobrain\Structure\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\OnOfficeAdapter\Exceptions\OnOfficeException;
use Innobrain\Structure\Collections\FieldCollection;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\FieldDependency;
use Innobrain\Structure\Dtos\FieldFilter;
use Innobrain\Structure\Dtos\Module;
use Innobrain\Structure\Dtos\PermittedValue;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\FieldMeasureFormat;
use Innobrain\Structure\Enums\FieldType;
use Innobrain\Structure\Enums\Language;
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
     * ModulesCollection, for the "cache.serializable_classes" config.
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
}
