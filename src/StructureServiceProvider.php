<?php

declare(strict_types=1);

namespace Innobrain\Structure;

use Innobrain\Structure\Commands\StructureCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class StructureServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('onoffice-structure')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_onoffice_structure_table')
            ->hasCommand(StructureCommand::class);
    }
}
