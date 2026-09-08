<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Collections\ModulesCollection;
use Innobrain\Structure\Facades\FieldConfiguration;
use Innobrain\Structure\Services\Structure;

use function Pest\testDirectory;

it('lists every class needed to unserialize a modules collection', function (): void {
    Http::fake([
        'https://api.onoffice.de/api/stable/api.php' => Http::response(json_decode(file_get_contents(testDirectory('Stubs/FieldsResponseWithRealDataTypes.json')), true)),
    ]);

    $modules = FieldConfiguration::retrieveForClient(new OnOfficeApiCredentials('test', 'test'));

    $restored = unserialize(serialize($modules), ['allowed_classes' => Structure::serializableClasses()]);

    expect($restored)->toBeInstanceOf(ModulesCollection::class)
        ->and($restored)->toEqual($modules)
        ->and(serialize($restored))->not->toContain('__PHP_Incomplete_Class');
});
