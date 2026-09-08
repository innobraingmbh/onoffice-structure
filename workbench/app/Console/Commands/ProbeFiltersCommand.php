<?php

declare(strict_types=1);

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Facades\Structure;

class ProbeFiltersCommand extends Command
{
    protected $signature = 'probe:filters';

    protected $description = 'Exercise whereMatchesFilters() and sanitize() against live address and estate modules.';

    public function handle(): int
    {
        $modules = Structure::forClient(new OnOfficeApiCredentials(
            token: (string) config('onoffice.token'),
            secret: (string) config('onoffice.secret'),
        ))->getModules([FieldConfigurationModule::Address, FieldConfigurationModule::Estate]);

        $address = $modules->get('address');
        $estate = $modules->get('estate');

        $rows = [];
        $rows[] = ['address all', $address->fields->count()];
        $rows[] = ['address kontaktkategorien=privateCustomer', $address->fields->whereMatchesFilters()->where('kontaktkategorien', 'privateCustomer')->get()->count()];
        $rows[] = ['address kontaktkategorien=company', $address->fields->whereMatchesFilters()->where('kontaktkategorien', 'company')->get()->count()];
        $rows[] = ['address kontaktkategorien=institution', $address->fields->whereMatchesFilters()->where('kontaktkategorien', 'institution')->get()->count()];
        $rows[] = ['address unknownFilter=x', $address->fields->whereMatchesFilters()->where('unknownFilter', 'x')->get()->count()];
        $rows[] = ['estate all', $estate->fields->count()];
        $rows[] = ['estate immobilienart=wohnung', $estate->fields->whereMatchesFilters()->where('immobilienart', 'wohnung')->get()->count()];
        $rows[] = ['estate immobilienart=grundstueck', $estate->fields->whereMatchesFilters()->where('immobilienart', 'grundstueck')->get()->count()];
        $rows[] = ['estate wohnung + miete + wohnen', $estate->fields->whereMatchesFilters()->where('immobilienart', 'wohnung')->where('vermarktungsarten', 'miete')->where('nutzungsarten', 'wohnen')->get()->count()];
        $rows[] = ['estate grundstueck + kauf', $estate->fields->whereMatchesFilters()->where('immobilienart', 'grundstueck')->where('vermarktungsarten', 'kauf')->get()->count()];
        $this->table(['query', 'fields'], $rows);

        $grundstueck = $estate->fields->whereMatchesFilters()->where('immobilienart', 'grundstueck')->get();
        $this->components->info('grundstueck keeps: '.$grundstueck->filter(fn ($f) => $f->filters->isNotEmpty())->keys()->implode(', '));

        $sanitized = $estate->fields->sanitize(collect([
            'objektart' => 'haus',
            'objekttyp' => 'einfamilienhaus',
            'kaufpreis' => '250000',
            'anzahl_zimmer' => '4',
            'nope' => 'x',
            'objektart_bad' => 'villa',
            'zustand' => 'not_a_value',
            'Ausstatt_kategorie' => 'not_a_value',
        ]));
        $this->components->info('sanitize keeps: '.$sanitized->keys()->implode(', '));

        $addressSanitized = $address->fields->sanitize(collect([
            'Anrede' => 'Herr',
            'Vorname' => 'Max',
            'Email' => 'max@example.com',
            'Land' => 'DEU',
            'Anrede_bad' => 'x',
            'Beziehung' => '999999',
        ]));
        $this->components->info('address sanitize keeps: '.$addressSanitized->keys()->implode(', '));

        return self::SUCCESS;
    }
}
