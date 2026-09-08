<?php

declare(strict_types=1);

use Innobrain\OnOfficeAdapter\Dtos\OnOfficeApiCredentials;
use Innobrain\Structure\Enums\FieldConfigurationModule;
use Innobrain\Structure\Enums\Language;
use Innobrain\Structure\Facades\FieldConfiguration;
use Innobrain\Structure\Facades\Structure;
use Innobrain\Structure\Testing\FieldFactory;
use Innobrain\Structure\Testing\ModuleFactory;
use PHPUnit\Framework\AssertionFailedError;

beforeEach(function (): void {
    $this->estate = ModuleFactory::new(FieldConfigurationModule::Estate)->fields(FieldFactory::new('Ort'))->make();
    $this->address = ModuleFactory::new(FieldConfigurationModule::Address)->fields(FieldFactory::new('Vorname'))->make();
    $this->credentials = new OnOfficeApiCredentials('token', 'secret');
});

it('serves canned modules through the Structure facade', function (): void {
    Structure::fake([$this->estate, $this->address]);

    $modules = Structure::forClient($this->credentials)->getModules();

    expect($modules->keys()->all())->toBe(['estate', 'address'])
        ->and($modules->module('estate'))->toBe($this->estate);
});

it('replaces a Structure instance that was resolved before faking', function (): void {
    Structure::forClient($this->credentials);

    Structure::fake([$this->estate]);

    expect(Structure::forClient($this->credentials)->getModules()->keys()->all())->toBe(['estate']);
});

it('narrows to the requested modules', function (): void {
    $fake = FieldConfiguration::fake([$this->estate, $this->address]);

    $modules = FieldConfiguration::retrieveForClient($this->credentials, [FieldConfigurationModule::Address], Language::English);

    expect($modules->keys()->all())->toBe(['address']);

    $fake->assertRetrieved(FieldConfigurationModule::Address, Language::English);
    $fake->assertRetrievedTimes(1);
});

it('still throws for unknown module keys', function (): void {
    Structure::fake();

    Structure::forClient($this->credentials)->getModules('estates');
})->throws(InvalidArgumentException::class);

it('fails assertions when nothing was retrieved', function (): void {
    $fake = Structure::fake();

    $fake->assertNotRetrieved();

    expect(fn () => $fake->assertRetrieved())->toThrow(AssertionFailedError::class);
});
