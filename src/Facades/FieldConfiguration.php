<?php

declare(strict_types=1);

namespace Innobrain\Structure\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Innobrain\Structure\FieldConfiguration
 */
class FieldConfiguration extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Innobrain\Structure\FieldConfiguration::class;
    }
}
