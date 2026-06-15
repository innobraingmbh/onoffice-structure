<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Dtos\Field;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\FieldType;
use Innobrain\Structure\Facades\FieldConfiguration;

use function Pest\testDirectory;

/**
 * Feature-level tests verifying that the five real data types introduced by the
 * onOffice API parameter `realDataTypes=true` are parsed correctly and are NOT
 * silently dropped by FieldConfiguration::retrieveForClient().
 */
describe('realDataTypes parsing', function () {
    beforeEach(function () {
        $file = file_get_contents(testDirectory('Stubs/FieldsResponseWithRealDataTypes.json'));
        $json = json_decode($file, true);

        Http::fake([
            'https://api.onoffice.de/api/stable/api.php' => Http::response($json),
        ]);

        $this->modules = FieldConfiguration::retrieveForClient(new OnOfficeApiCredentials('test', 'test'));
        $this->addressFields = $this->modules->get(FieldConfigurationModule::Address->value)->fields;
    });

    it('parses a user-type field and does not drop it', function () {
        expect($this->addressFields->has('Benutzer'))->toBeTrue()
            ->and($this->addressFields->get('Benutzer'))->toBeInstanceOf(Field::class)
            ->and($this->addressFields->get('Benutzer')->type)->toBe(FieldType::User)
            ->and($this->addressFields->get('Benutzer')->label)->toBe('Betreuer');
    });

    it('parses a datei-type field and does not drop it', function () {
        expect($this->addressFields->has('Profilbild'))->toBeTrue()
            ->and($this->addressFields->get('Profilbild')->type)->toBe(FieldType::File);
    });

    it('parses a redhint-type field and does not drop it', function () {
        expect($this->addressFields->has('Hinweis1'))->toBeTrue()
            ->and($this->addressFields->get('Hinweis1')->type)->toBe(FieldType::RedHint);
    });

    it('parses a blackhint-type field and does not drop it', function () {
        expect($this->addressFields->has('Hinweis2'))->toBeTrue()
            ->and($this->addressFields->get('Hinweis2')->type)->toBe(FieldType::BlackHint);
    });

    it('parses a dividingline-type field and does not drop it', function () {
        expect($this->addressFields->has('Trennlinie'))->toBeTrue()
            ->and($this->addressFields->get('Trennlinie')->type)->toBe(FieldType::DividingLine);
    });

    it('the address module contains all expected fields including real data types', function () {
        expect($this->addressFields->keys()->all())
            ->toContain('KdNr')
            ->toContain('Benutzer')
            ->toContain('Profilbild')
            ->toContain('Hinweis1')
            ->toContain('Hinweis2')
            ->toContain('Trennlinie');
    });

    it('sends realDataTypes=true in the API request', function () {
        Http::fake([
            'https://api.onoffice.de/api/stable/api.php' => function (Illuminate\Http\Client\Request $request) {
                $body = json_decode($request->body(), true);
                $parameters = data_get($body, 'request.actions.0.parameters', []);

                expect($parameters)->toHaveKey('realDataTypes')
                    ->and($parameters['realDataTypes'])->toBeTrue();

                return Http::response(['status' => ['code' => 200], 'response' => ['results' => []]]);
            },
        ]);

        FieldConfiguration::retrieveForClient(new OnOfficeApiCredentials('test', 'test'));
    });
});
