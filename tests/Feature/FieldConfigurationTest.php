<?php

use Innobrain\Structure\Facades\FieldConfiguration;

it('should be able to retrieve the field configuration', function () {
    $fields = FieldConfiguration::retrieveForClient(env('ONOFFICE_TOKEN'), env('ONOFFICE_SECRET'));

    expect($fields)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($fields->first())->toBeInstanceOf(\Innobrain\Structure\DTOs\Module::class);

});
