<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Innobrain\Structure\DTOs\Field;
use Innobrain\Structure\DTOs\FieldDependency;
use Innobrain\Structure\DTOs\FieldFilter;
use Innobrain\Structure\DTOs\Module;
use Innobrain\Structure\DTOs\PermittedValue;
use Innobrain\Structure\Facades\FieldConfiguration;

it('should be able to retrieve the field configuration', function () {
    $file = file_get_contents(Pest\testDirectory('Stubs/FieldsResponse.json'));
    $json = json_decode($file, true);

    Illuminate\Support\Facades\Http::fake([
        'https://api.onoffice.de/api/stable/api.php/' => Illuminate\Support\Facades\Http::response($json),
    ]);

    $modules = FieldConfiguration::retrieveForClient('test', 'test');

    expect($modules)->toBeInstanceOf(Collection::class)
        ->and($modules->first())->toBeInstanceOf(Module::class)
        ->and($modules->first()->fields)->toBeInstanceOf(Collection::class)
        ->and($modules->first()->fields->first())->toBeInstanceOf(Field::class);

});

it('should be able to retrieve the field configuration for client 2', function () {
    $file = file_get_contents(Pest\testDirectory('Stubs/FieldsResponse2.json'));
    $json = json_decode($file, true);

    Illuminate\Support\Facades\Http::fake([
        'https://api.onoffice.de/api/stable/api.php/' => Illuminate\Support\Facades\Http::response($json),
    ]);

    $modules = FieldConfiguration::retrieveForClient('test', 'test');

    expect($modules)->toBeInstanceOf(Collection::class)
        ->and($modules->first())->toBeInstanceOf(Module::class)
        ->and($modules->first()->fields)->toBeInstanceOf(Collection::class)
        ->and($modules->first()->fields->first())->toBeInstanceOf(Field::class)
        ->and($modules->first()->fields->get('Anrede-Titel')->filters->first())->toBeInstanceOf(FieldFilter::class);
});
