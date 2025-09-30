<?php

declare(strict_types=1);

namespace Innobrain\Structure\Services;

use Illuminate\Support\Arr;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\OnOfficeAdapter\Exceptions\OnOfficeException;
use Innobrain\Structure\Collections\ModulesCollection;
use LogicException;
use Throwable;

class Structure
{
    public function __construct(
        private readonly FieldConfiguration $fieldConfiguration,
        private ?OnOfficeApiCredentials $onOfficeApiCredentials = null,
    ) {}

    public function forClient(OnOfficeApiCredentials $onOfficeApiCredentials): self
    {
        $this->onOfficeApiCredentials = $onOfficeApiCredentials;

        return $this;
    }

    /**
     * @param  string|array<int, string>  $only
     *
     * @throws OnOfficeException
     * @throws Throwable
     */
    public function getModules(string|array $only = []): ModulesCollection
    {
        throw_unless($this->onOfficeApiCredentials instanceof OnOfficeApiCredentials, LogicException::class, 'No OnOfficeApiCredentials provided. Use the forClient method to provide credentials.');

        return $this->fieldConfiguration->retrieveForClient($this->onOfficeApiCredentials, Arr::wrap($only));
    }
}
