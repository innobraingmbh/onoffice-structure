<?php

declare(strict_types=1);

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Converters\Array\ArrayConvertStrategy;
use Innobrain\Structure\Converters\JsonSchema\JsonSchemaConvertStrategy;
use Innobrain\Structure\Converters\LaravelRules\LaravelRulesConvertStrategy;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Enums\Language;
use Innobrain\Structure\Facades\Structure;
use Throwable;

use function json_encode;

class ProbeAllCommand extends Command
{
    protected $signature = 'probe:all {--language=DEU}';

    protected $description = 'Convert every live module with every strategy and report anomalies.';

    public function handle(): int
    {
        $language = Language::from((string) $this->option('language'));
        $modules = Structure::forClient(new OnOfficeApiCredentials(
            token: (string) config('onoffice.token'),
            secret: (string) config('onoffice.secret'),
        ))->getModules([], $language);

        $strategies = [
            'array' => new ArrayConvertStrategy,
            'array-dropEmpty' => new ArrayConvertStrategy(dropEmpty: true),
            'rules-pipe' => new LaravelRulesConvertStrategy,
            'rules-array' => new LaravelRulesConvertStrategy(pipeSyntax: false, includeNullable: false),
            'json' => new JsonSchemaConvertStrategy,
            'json-strict' => new JsonSchemaConvertStrategy(includeNullable: false, includeDescriptions: false),
        ];

        $rows = [];
        $problems = [];

        foreach ($modules as $module) {
            foreach ($strategies as $name => $strategy) {
                try {
                    $result = $module->convert($strategy);
                    if ($strategy instanceof JsonSchemaConvertStrategy) {
                        $result = $result->toArray();
                        $encoded = json_encode($result, JSON_THROW_ON_ERROR);
                        $props = count($result['properties'] ?? []);
                        $required = count($result['required'] ?? []);
                        $rows[] = [$module->key->value, $name, "props={$props} required={$required} bytes=".strlen($encoded)];
                    } elseif ($strategy instanceof LaravelRulesConvertStrategy) {
                        // Make sure Laravel accepts every generated rule string.
                        Validator::make([], $result)->passes();
                        $rows[] = [$module->key->value, $name, 'rules='.count($result)];
                    } else {
                        json_encode($result, JSON_THROW_ON_ERROR);
                        $rows[] = [$module->key->value, $name, 'fields='.count($result['fields'] ?? [])];
                    }
                } catch (Throwable $e) {
                    $problems[] = [$module->key->value, $name, $e::class.': '.$e->getMessage()];
                }

                foreach ($module->fields as $field) {
                    try {
                        $field->convert($strategy);
                    } catch (Throwable $e) {
                        $problems[] = [$module->key->value, "{$name}/{$field->key}", $e::class.': '.$e->getMessage()];
                    }
                }
            }

            // Per-field sanity checks independent of strategies.
            foreach ($module->fields as $field) {
                $this->checkField($module->key->value, $field, $problems);
            }
        }

        $this->table(['module', 'strategy', 'result'], $rows);

        if ($problems !== []) {
            $this->components->error('problems: '.count($problems));
            $this->table(['module', 'where', 'problem'], $problems);

            return self::FAILURE;
        }

        $this->components->info('no problems');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<int, string>>  $problems
     */
    private function checkField(string $module, Field $field, array &$problems): void
    {

        if ($field->type->isSelect() && ! $field->hasPermittedValues()) {
            $problems[] = [$module, $field->key, 'select field without permitted values'];
        }

        if (! $field->type->isSelect() && $field->hasPermittedValues()) {
            $problems[] = [$module, $field->key, "{$field->type->value} field with ".$field->permittedValues->count().' permitted values'];
        }

        if ($field->hasDefault() && $field->hasPermittedValues() && $field->doesntContainPermittedValue($field->default)) {
            $problems[] = [$module, $field->key, "default [{$field->default}] not in permitted values"];
        }

        foreach ($field->dependencies as $dependency) {
            if ($field->doesntContainPermittedValue($dependency->permittedValueKey)) {
                $problems[] = [$module, $field->key, "dependency key [{$dependency->permittedValueKey}] not in permitted values"];
            }
        }

        if ($field->compoundFields->isNotEmpty() && $field->isWritable()) {
            $problems[] = [$module, $field->key, 'compound field reported writable'];
        }

        if ($field->label === '') {
            $problems[] = [$module, $field->key, 'empty label'];
        }

        foreach ($field->permittedValues as $permittedValue) {
            if ($permittedValue->key !== (string) $permittedValue->key || $permittedValue->key === '') {
                $problems[] = [$module, $field->key, 'odd permitted value key ['.$permittedValue->key.']'];
            }
        }
    }
}
