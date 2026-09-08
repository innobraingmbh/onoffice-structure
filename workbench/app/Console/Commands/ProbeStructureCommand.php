<?php

declare(strict_types=1);

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\Module;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\Language;
use Innobrain\Structure\Facades\Structure;

class ProbeStructureCommand extends Command
{
    protected $signature = 'probe:structure
        {--only=* : Module keys to fetch (e.g. estate, address). Defaults to all modules.}
        {--language=DEU : Language code for labels.}
        {--fields : Print every field of each fetched module.}';

    protected $description = 'Probe the field configuration endpoint against the live onOffice API.';

    public function handle(): int
    {
        $language = Language::tryFrom((string) $this->option('language'));

        if (! $language instanceof Language) {
            $this->components->error('unknown language: '.$this->option('language'));

            return self::FAILURE;
        }

        /** @var array<int, string> $only */
        $only = $this->option('only');

        $unknownModules = array_diff($only, FieldConfigurationModule::values());

        if ($unknownModules !== []) {
            $this->components->error('unknown modules: '.implode(', ', $unknownModules));

            return self::FAILURE;
        }

        $modules = new ModulesCollection;

        $this->components->task('getModules('.($only === [] ? 'all' : implode(', ', $only)).", {$language->value})", function () use ($only, $language, &$modules): void {
            $modules = Structure::forClient($this->credentials())->getModules($only, $language);
        });

        $this->components->info("modules: {$modules->count()}");

        if ($modules->isEmpty()) {
            $this->components->warn('no modules returned');

            return self::SUCCESS;
        }

        $this->table(
            ['module', 'label', 'fields'],
            $modules->map(fn (Module $module): array => [
                $module->key->value,
                $module->label,
                $module->fields->count(),
            ])->values()->all(),
        );

        if (! $this->option('fields')) {
            return self::SUCCESS;
        }

        foreach ($modules as $module) {
            $this->components->info("{$module->key->value} ({$module->label})");

            $this->table(
                ['key', 'label', 'type', 'length', 'default', 'permitted', 'filters', 'dependencies', 'compound', 'measure'],
                $module->fields->map(fn (Field $field): array => [
                    $field->key,
                    $field->label,
                    $field->type->value,
                    $field->length ?? '',
                    $field->default ?? '',
                    $field->permittedValues->count(),
                    $field->filters->count(),
                    $field->dependencies->count(),
                    $field->compoundFields->implode(', '),
                    $field->fieldMeasureFormat ?? '',
                ])->values()->all(),
            );
        }

        return self::SUCCESS;
    }

    private function credentials(): OnOfficeApiCredentials
    {
        return new OnOfficeApiCredentials(
            token: (string) config('onoffice.token'),
            secret: (string) config('onoffice.secret'),
        );
    }
}
