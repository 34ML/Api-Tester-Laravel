<?php

namespace _34ml\ApiTester;

use _34ml\ApiTester\Commands\GenerateApiTestsCommand;
use _34ml\ApiTester\Commands\TestApiEndpointCommand;
use _34ml\ApiTester\Commands\ListApiRoutesCommand;
use Illuminate\Support\ServiceProvider;

class ApiTesterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/api-tester.php', 'api-tester');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/api-tester.php' => config_path('api-tester.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateApiTestsCommand::class,
                TestApiEndpointCommand::class,
                ListApiRoutesCommand::class,
            ]);
        }
    }
}