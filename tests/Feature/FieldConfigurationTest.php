<?php

declare(strict_types=1);

use Innobrain\Structure\Facades\FieldConfiguration;

it('should be able to retrieve the field configuration', function () {
    $file = file_get_contents(Pest\testDirectory('Stubs/FieldsResponse.json'));
    $json = json_decode($file, true);

    Http::fake([
        'https://api.onoffice.de/api/stable/api.php/' => Http::response($json),
    ]);

    $fields = FieldConfiguration::retrieveForClient('test', 'test');

    expect($fields)->toBeInstanceOf(Illuminate\Support\Collection::class)
        ->and($fields->first())->toBeInstanceOf(Innobrain\Structure\DTOs\Module::class);

});

it('should be able to retrieve the field configuration for client 2', function () {
    $file = file_get_contents(Pest\testDirectory('Stubs/FieldsResponse2.json'));
    $json = json_decode($file, true);

    Http::fake([
        'https://api.onoffice.de/api/stable/api.php/' => Http::response($json),
    ]);

    $fields = FieldConfiguration::retrieveForClient('test', 'test');

    expect($fields)->toBeInstanceOf(Illuminate\Support\Collection::class)
        ->and($fields->first())->toBeInstanceOf(Innobrain\Structure\DTOs\Module::class);

});
