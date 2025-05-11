<?php

namespace Innobrain\Structure\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Innobrain\Structure\Structure
 */
class Structure extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Innobrain\Structure\Structure::class;
    }
}
