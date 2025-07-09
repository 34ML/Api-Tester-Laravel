<?php

namespace _34ml\ApiTester\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

class GenerateApiTests extends Command
{
    protected $signature = 'api:generate-tests';
    protected $description = 'Generate Pest tests for API endpoints and placeholder schema files';

    public function handle()
    {
        $routes = collect(Route::getRoutes())
            ->filter(fn($route) =>
                str_starts_with($route->uri(), 'api/') &&
                in_array('GET', $route->methods())
            );

        foreach ($routes as $route) {
            $uri = $route->uri();
            $testName = str_replace('/', '_', $uri);
            $schemaFile = base_path("tests/Schemas/{$testName}.schema.json");

            // Create empty schema placeholder
            if (!File::exists($schemaFile)) {
                File::ensureDirectoryExists(dirname($schemaFile));
                File::put($schemaFile, json_encode([
                    '$schema' => 'http://json-schema.org/draft-07/schema#',
                    'type' => 'object',
                    'properties' => new \stdClass()
                ], JSON_PRETTY_PRINT));
            }

            // Generate Pest test
            $testContent = <<<PHP
            <?php

            use _34ml\\ApiTester\\Support\\SchemaValidator;

            test('GET {$uri} returns valid schema', function () {
                \$response = \$this->getJson('/{$uri}');
                \$response->assertOk();

                \$data = \$response->json();
                \$schema = json_decode(file_get_contents(base_path('tests/Schemas/{$testName}.schema.json')), true);

                expect(SchemaValidator::validate(\$data, \$schema))->toBeTrue();
            });
            PHP;

            $testFile = base_path("tests/Feature/{$testName}_Test.php");
            File::put($testFile, $testContent);

            $this->info("✅ Test & schema stub created for /{$uri}");
        }
    }
}
