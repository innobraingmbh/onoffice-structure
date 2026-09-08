<?php

declare(strict_types=1);

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Dtos\FieldViolation;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Facades\Structure;

class ProbeLookupCommand extends Command
{
    protected $signature = 'probe:lookup';

    protected $description = 'Exercise find(), label helpers, violations() and the serializable class list against live data.';

    public function handle(): int
    {
        $modules = Structure::forClient(new OnOfficeApiCredentials(
            token: (string) config('onoffice.token'),
            secret: (string) config('onoffice.secret'),
        ))->getModules([FieldConfigurationModule::Address, FieldConfigurationModule::Estate]);

        $estate = $modules->module(FieldConfigurationModule::Estate);
        $address = $modules->module('address');

        $this->table(['lookup', 'result'], [
            ['module(Estate)->field("ort")', $estate?->field('ort')?->key ?? 'null'],
            ['module("address")->field("ANREDE")', $address?->field('ANREDE')?->key ?? 'null'],
            ['module("address")->field("nope")', $address?->field('nope')?->key ?? 'null'],
            ['module("nope")', $modules->module('nope') === null ? 'null' : 'module'],
        ]);

        $objektart = $estate->field('objektart');

        $this->table(['permitted value helper', 'result'], [
            ['labelFor("haus")', $objektart->labelFor('haus') ?? 'null'],
            ['labelFor("nope")', $objektart->labelFor('nope') ?? 'null'],
            ['permittedValueKeyFor("Wohnung")', $objektart->permittedValueKeyFor('Wohnung') ?? 'null'],
            ['permittedValueKeyFor(" HAUS ")', $objektart->permittedValueKeyFor(' HAUS ') ?? 'null'],
            ['permittedValueKeyFor("Büro/Praxen")', $objektart->permittedValueKeyFor('Büro/Praxen') ?? 'null'],
            ['permittedValueKeyFor("villa")', $objektart->permittedValueKeyFor('villa') ?? 'null'],
            ['permits(null)', var_export($objektart->permits(null), true)],
            ['permits(["haus", "villa"])', var_export($objektart->permits(['haus', 'villa']), true)],
            ['permittedValueLabels() count', (string) count($objektart->permittedValueLabels())],
        ]);

        $input = collect([
            'OBJEKTART' => 'haus',
            'objekttyp' => 'villa',
            'zustand' => 'not_a_value',
            'Ausstatt_kategorie' => null,
            'nope' => 'x',
        ]);

        $this->components->info('sanitize keeps: '.$estate->fields->sanitize($input)->map(fn ($v, $k) => "$k=".var_export($v, true))->implode(', '));

        $this->table(['field', 'value', 'reason'], $estate->fields->violations($input)
            ->map(fn (FieldViolation $violation): array => [$violation->fieldKey, var_export($violation->value, true), $violation->reason->value])
            ->all());

        $restored = unserialize(serialize($modules), ['allowed_classes' => Structure::serializableClasses()]);
        $this->components->info('serialize round trip: '.($restored === $modules ? 'equal' : 'DIFFERENT'));

        return self::SUCCESS;
    }
}
