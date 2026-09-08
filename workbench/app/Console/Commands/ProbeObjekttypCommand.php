<?php

declare(strict_types=1);

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Dtos\PermittedValue;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Facades\Structure;

class ProbeObjekttypCommand extends Command
{
    protected $signature = 'probe:objekttyp {objektart : An objektart key such as haus or wohnung}';

    protected $description = 'List the objekttyp values available for an objektart, resolved from field dependencies.';

    public function handle(): int
    {
        $credentials = new OnOfficeApiCredentials(config('onoffice.token'), config('onoffice.secret'));

        $estate = Structure::forClient($credentials)
            ->getModules(FieldConfigurationModule::Estate)
            ->get(FieldConfigurationModule::Estate->value);

        $objekttyp = $estate?->fields->get('objekttyp');

        if (! $objekttyp instanceof Field) {
            $this->components->error('objekttyp field not found');

            return self::FAILURE;
        }

        $objektart = (string) $this->argument('objektart');
        $narrowed = $objekttyp->withPermittedValuesFor($objektart);

        $this->components->info(sprintf('%d of %d objekttyp values apply to objektart "%s"', $narrowed->permittedValues->count(), $objekttyp->permittedValues->count(), $objektart));

        $this->table(['key', 'label'], $narrowed->permittedValues->map(fn (PermittedValue $value): array => [$value->key, $value->label])->values()->all());

        return self::SUCCESS;
    }
}
