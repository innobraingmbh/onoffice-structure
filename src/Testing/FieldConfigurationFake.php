<?php

declare(strict_types=1);

namespace Innobrain\Structure\Testing;

use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\Dtos\Module;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\Language;
use Innobrain\Structure\Services\FieldConfiguration;
use Override;
use PHPUnit\Framework\Assert as PHPUnit;

use function Illuminate\Support\enum_value;

class FieldConfigurationFake extends FieldConfiguration
{
    private readonly ModulesCollection $modules;

    /**
     * @var array<int, array{only: array<int, string>, language: Language}>
     */
    private array $retrievals = [];

    /**
     * @param  ModulesCollection|array<int, Module>  $modules
     */
    public function __construct(ModulesCollection|array $modules = [])
    {
        if ($modules instanceof ModulesCollection) {
            $this->modules = $modules;

            return;
        }

        $this->modules = new ModulesCollection;

        foreach ($modules as $module) {
            $this->modules->put($module->key->value, $module);
        }
    }

    /**
     * Returns the canned modules, narrowed to the requested ones. The same
     * modules are returned for every language.
     */
    #[Override]
    public function retrieveForClient(OnOfficeApiCredentials $credentials, array $only = [], Language $language = Language::German): ModulesCollection
    {
        $moduleValues = FieldConfigurationModule::values($only);

        $this->retrievals[] = ['only' => $moduleValues, 'language' => $language];

        return $this->modules->only($moduleValues);
    }

    public function assertRetrieved(FieldConfigurationModule|string|null $module = null, ?Language $language = null): void
    {
        $moduleValue = enum_value($module);

        $retrieved = collect($this->retrievals)->contains(
            fn (array $retrieval): bool => ($moduleValue === null || in_array($moduleValue, $retrieval['only'], true))
                && (! $language instanceof Language || $retrieval['language'] === $language),
        );

        PHPUnit::assertTrue($retrieved, $moduleValue === null
            ? 'The field configuration was not retrieved.'
            : "The field configuration was not retrieved for module [{$moduleValue}].");
    }

    public function assertRetrievedTimes(int $times): void
    {
        $actual = count($this->retrievals);

        PHPUnit::assertSame($times, $actual, "The field configuration was retrieved {$actual} times instead of {$times}.");
    }

    public function assertNotRetrieved(): void
    {
        PHPUnit::assertEmpty($this->retrievals, 'The field configuration was retrieved unexpectedly.');
    }
}
