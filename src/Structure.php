<?php

declare(strict_types=1);

namespace Innobrain\Structure;

use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Collections\ModulesCollection;

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

    public function get(): ModulesCollection
    {
        return $this->fieldConfiguration->retrieveForClient($this->onOfficeApiCredentials);
    }
}
