<?php

declare(strict_types=1);

namespace Innobrain\Structure\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Innobrain\OnOfficeAdapter\OnOfficeAdapterServiceProvider;
use Innobrain\Structure\StructureServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Override;

class TestCase extends Orchestra
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Innobrain\\Structure\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            StructureServiceProvider::class,
            OnOfficeAdapterServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
