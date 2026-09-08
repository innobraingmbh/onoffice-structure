<?php

declare(strict_types=1);

use Innobrain\Structure\Enums\FieldConfigurationModule;

describe('FieldConfigurationModule::values', function () {
    it('returns every module when nothing is requested', function () {
        expect(FieldConfigurationModule::values())->toHaveCount(count(FieldConfigurationModule::cases()))
            ->toContain('estate', 'address');
    });

    it('accepts module keys and enum cases and returns them in declaration order', function () {
        expect(FieldConfigurationModule::values(['estate', FieldConfigurationModule::Address, 'estate']))
            ->toBe(['address', 'estate']);
    });

    it('rejects unknown module keys', function () {
        FieldConfigurationModule::values(['estates']);
    })->throws(InvalidArgumentException::class, 'Unknown field configuration module [estates].');
});
