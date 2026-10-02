<?php

declare(strict_types=1);

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\OnOfficeAdapter\Facades\SearchCriteriaRepository;
use Innobrain\Structure\Dtos\SearchCriteriaField;
use Innobrain\Structure\Enums\Language;
use Innobrain\Structure\Facades\Structure;

use function json_encode;

class ProbeSearchCriteriaCommand extends Command
{
    protected $signature = 'probe:search-criteria
        {--language=DEU : Language code for labels.}
        {--raw= : File to write the raw JSON response to.}';

    protected $description = 'Show the search criteria fields of the live onOffice account as parsed by the package.';

    public function handle(): int
    {
        $credentials = new OnOfficeApiCredentials(
            token: (string) config('onoffice.token'),
            secret: (string) config('onoffice.secret'),
        );

        $language = Language::from((string) $this->option('language'));

        if (is_string($this->option('raw'))) {
            $raw = SearchCriteriaRepository::fields()
                ->withCredentials($credentials)
                ->parameters(['language' => $language->value, 'additionalTranslations' => true])
                ->get();

            file_put_contents($this->option('raw'), json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            $this->components->info('wrote '.$this->option('raw'));
        }

        $fields = Structure::forClient($credentials)->getSearchCriteriaFields($language);

        $this->table(
            ['category', 'key', 'label', 'type', 'raw type', 'range', 'must match', 'mandatory', 'values', 'applies to'],
            $fields->map(fn (SearchCriteriaField $field): array => [
                $field->category,
                $field->key,
                $field->label,
                $field->type->value ?? 'UNKNOWN',
                $field->rawType,
                $field->isRange ? "{$field->rangeFromKey()} ({$field->rangeFromLabel}) / {$field->rangeToKey()} ({$field->rangeToLabel})" : '',
                $field->mustMatchByDefault ? 'yes' : '',
                $field->isMandatory ? 'yes' : '',
                $field->hasPermittedValues() ? (string) $field->permittedValues->count() : '',
                (string) json_encode($field->appliesTo->all()),
            ])->values()->all(),
        );

        $unknown = $fields->filter(fn (SearchCriteriaField $field): bool => $field->type === null);

        if ($unknown->isNotEmpty()) {
            $this->components->warn('Unknown types: '.$unknown->map(fn (SearchCriteriaField $field): string => "{$field->key} ({$field->rawType})")->implode(', '));
        }

        return self::SUCCESS;
    }
}
