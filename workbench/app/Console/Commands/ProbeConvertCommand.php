<?php

declare(strict_types=1);

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Converters\Array\ArrayConvertStrategy;
use Innobrain\Structure\Converters\JsonSchema\JsonSchemaConvertStrategy;
use Innobrain\Structure\Converters\LaravelRules\LaravelRulesConvertStrategy;
use Innobrain\Structure\Dtos\Module;
use Innobrain\Structure\Enums\Language;
use Innobrain\Structure\Facades\Structure;

use function json_encode;

class ProbeConvertCommand extends Command
{
    protected $signature = 'probe:convert
        {module : Module key to fetch (e.g. estate).}
        {--format=rules : One of rules, json, array.}
        {--field=* : Restrict output to these field keys.}
        {--language=DEU : Language code for labels.}';

    protected $description = 'Run a converter strategy against a live onOffice module and print the result.';

    public function handle(): int
    {
        $language = Language::from((string) $this->option('language'));
        $moduleKey = (string) $this->argument('module');

        $module = Structure::forClient(new OnOfficeApiCredentials(
            token: (string) config('onoffice.token'),
            secret: (string) config('onoffice.secret'),
        ))->getModules($moduleKey, $language)->get($moduleKey);

        if (! $module instanceof Module) {
            $this->components->error("module {$moduleKey} not returned");

            return self::FAILURE;
        }

        /** @var array<int, string> $fieldKeys */
        $fieldKeys = $this->option('field');

        if ($fieldKeys !== []) {
            $module = new Module(
                key: $module->key,
                label: $module->label,
                fields: $module->fields->only($fieldKeys),
            );
        }

        $strategy = match ((string) $this->option('format')) {
            'rules' => new LaravelRulesConvertStrategy,
            'json' => new JsonSchemaConvertStrategy,
            'array' => new ArrayConvertStrategy,
            default => null,
        };

        if ($strategy === null) {
            $this->components->error('unknown format: '.$this->option('format'));

            return self::FAILURE;
        }

        $result = $module->convert($strategy);

        if ($strategy instanceof JsonSchemaConvertStrategy) {
            $result = $result->toArray();
        }

        $this->line((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
