<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Dotenv\Dotenv;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Console\Commands\ProbeConvertCommand;
use Workbench\App\Console\Commands\ProbeRawFieldsCommand;
use Workbench\App\Console\Commands\ProbeStructureCommand;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $packageRoot = dirname(__DIR__, 3);

        $env = is_file($packageRoot.'/.env')
            ? Dotenv::createMutable($packageRoot)->safeLoad()
            : [];

        if (isset($env['ON_OFFICE_TOKEN'], $env['ON_OFFICE_SECRET'])) {
            Config::set('onoffice.token', $env['ON_OFFICE_TOKEN']);
            Config::set('onoffice.secret', $env['ON_OFFICE_SECRET']);
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ProbeConvertCommand::class,
                ProbeRawFieldsCommand::class,
                ProbeStructureCommand::class,
            ]);
        }
    }
}
