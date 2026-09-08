<?php

declare(strict_types=1);

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\OnOfficeAdapter\Facades\FieldRepository;
use Innobrain\Structure\Enums\FieldConfigurationModule;

use function json_encode;

class ProbeRawFieldsCommand extends Command
{
    protected $signature = 'probe:raw-fields
        {path : File to write the raw JSON response to.}
        {--only=* : Module keys to fetch. Defaults to all modules.}
        {--language=DEU : Language code for labels.}
        {--no-real-data-types : Omit the realDataTypes parameter.}';

    protected $description = 'Dump the raw field configuration response from the live onOffice API to a file.';

    public function handle(): int
    {
        /** @var array<int, string> $only */
        $only = $this->option('only');

        $raw = FieldRepository::query()
            ->withCredentials(new OnOfficeApiCredentials(
                token: (string) config('onoffice.token'),
                secret: (string) config('onoffice.secret'),
            ))
            ->withModules(FieldConfigurationModule::values($only))
            ->parameters([
                'labels' => true,
                'language' => (string) $this->option('language'),
                'showfieldfilters' => true,
                'showfielddependencies' => true,
                'showFieldMeasureFormat' => true,
                'realDataTypes' => ! $this->option('no-real-data-types'),
            ])
            ->get();

        file_put_contents((string) $this->argument('path'), json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->components->info('wrote '.$this->argument('path'));

        return self::SUCCESS;
    }
}
