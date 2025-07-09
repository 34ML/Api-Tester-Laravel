<?php
namespace _34ml\ApiTester;

use Illuminate\Support\ServiceProvider;
use _34ml\ApiTester\Commands\GenerateApiTests;

class ApiTesterServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([
            GenerateApiTests::class,
        ]);
    }
}
