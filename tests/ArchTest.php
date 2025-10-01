<?php

declare(strict_types=1);

use Innobrain\Structure\Converters\Concerns\ConvertStrategy;

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

arch('all non traits are implementing the convert strategy interface')
    ->expect('Innobrain\Structure\Converters')
    ->classes()
    ->toImplement(ConvertStrategy::class);

arch('all traits only used in same directory')
    ->expect('Innobrain\Structure\Converters')
    ->traits()
    ->toOnlyBeUsedIn('Innobrain\Structure\Converters');

arch('all dtos are readonly')
    ->expect('Innobrain\Structure\Dtos')
    ->classes()
    ->toBeReadonly();

arch('all enums are string backed')
    ->expect('Innobrain\Structure\Enums')
    ->enums()
    ->toBeStringBackedEnum();
