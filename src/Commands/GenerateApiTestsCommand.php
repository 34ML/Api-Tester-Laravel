<?php

namespace _34ml\ApiTester\Commands;

use _34ml\ApiTester\http\Helpers\JsonSchemaGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use _34ml\ApiTester\Support\RouteScanner;
use Illuminate\Support\Str;

class GenerateApiTestsCommand extends Command
{
    protected $signature = 'api:generate-tests {--method=* : Specific HTTP methods to test (GET, POST, PUT, DELETE, PATCH)} {--uri=* : Specific URIs to test} {--force : Overwrite existing test files}';
    protected $description = 'Generate Pest-based tests for API endpoints with JSON schema validation';

    public function handle()
    {
        $this->info('🚀 Starting API test generation...');
        
        $routes = RouteScanner::getApiRoutes();
        
        if (empty($routes)) {
            $this->warn('⚠️ No API routes found. Make sure your routes are properly defined.');
            return 1;
        }

        $this->info("Found " . count($routes) . " API routes to test.");
        
        $success_count = 0;
        $error_count = 0;

        foreach ($routes as $route) {
            $uri = $route['uri'];
            $method = strtoupper($route['method']);
            
            // Skip if specific methods are requested and this one doesn't match
            if ($this->option('method') && !in_array($method, $this->option('method'))) {
                continue;
            }
            
            // Skip if specific URIs are requested and this one doesn't match
            if ($this->option('uri') && !in_array($uri, $this->option('uri'))) {
                continue;
            }

            $name = Str::studly(str_replace(['/', '-', '{', '}'], ' ', trim($uri, '/'))) . 'Test';
            
            $this->info("\n📡 Testing [$method] /$uri");

            try {
                $result = $this->processRoute($route, $name);
                if ($result) {
                    $success_count++;
                    $this->info("✅ Successfully processed: $uri");
                } else {
                    $error_count++;
                    $this->warn("⚠️ Skipped: $uri");
                }
            } catch (\Exception $e) {
                $error_count++;
                $this->error("❌ Error processing $uri: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info("🎉 Generation complete!");
        $this->info("✅ Successfully processed: $success_count routes");
        $this->info("⚠️ Skipped/Errors: $error_count routes");
        
        if ($success_count > 0) {
            $this->info("\n📁 Generated files:");
            $this->info("   - Schemas: " . config('api-tester.schema_path'));
            $this->info("   - Tests: " . base_path('tests/Feature/ApiTester'));
            $this->info("\n🧪 Run your tests with: php artisan test --filter=ApiTester");
        }

        return $error_count === 0 ? 0 : 1;
    }

    protected function processRoute(array $route, string $name): bool
    {
        $uri = $route['uri'];
        $method = strtoupper($route['method']);
        
        $baseUrl = config('app.url');
        if (!$baseUrl || $baseUrl === 'http://localhost') {
            $this->warn("⚠️ APP_URL not configured properly. Using localhost:8000");
            $baseUrl = 'http://localhost:8000';
        }
        
        $fullUrl = rtrim($baseUrl, '/') . '/' . ltrim($uri, '/');
        
        // Make the HTTP request
        $response = $this->makeHttpRequest($method, $fullUrl);
        if (!$response) {
            return false;
        }

        // Extract and validate response data
        $data = $this->extractResponseData($response, $uri);
        if ($data === null) {
            return false;
        }

        // Generate JSON schema
        $schema = $this->generateSchema($data, $uri);
        if (!$schema) {
            return false;
        }

        // Save schema file
        $schema_path = $this->saveSchema($schema, $method, $name);
        if (!$schema_path) {
            return false;
        }

        // Generate test file
        $test_path = $this->generateTest($method, $uri, $name, $schema_path);
        if (!$test_path) {
            return false;
        }

        return true;
    }

    protected function makeHttpRequest(string $method, string $url): ?\Illuminate\Http\Client\Response
    {
        try {
            $this->line("   🔗 Requesting: $method $url");
            
            $response = match ($method) {
                'GET' => Http::get($url),
                'POST' => Http::post($url),
                'PUT' => Http::put($url),
                'PATCH' => Http::patch($url),
                'DELETE' => Http::delete($url),
                default => Http::get($url),
            };

            if ($response->failed()) {
                $this->warn("   ❌ HTTP $method failed: " . $response->status());
                $this->warn("   📄 Response: " . $response->body());
                return null;
            }

            $this->line("   ✅ HTTP $method successful: " . $response->status());
            return $response;
            
        } catch (\Exception $e) {
            $this->error("   ❌ Connection error: " . $e->getMessage());
            return null;
        }
    }

    protected function extractResponseData(\Illuminate\Http\Client\Response $response, string $uri): ?array
    {
        $body = $response->json();
        
        if (!is_array($body)) {
            $this->warn("   ⚠️ Invalid JSON or empty body. Skipping $uri");
            return null;
        }

        // Try to extract data from common response structures
        $data = $body['data'] ?? $body['items'] ?? $body['results'] ?? $body;
        
        if (empty($data)) {
            $this->warn("   ⚠️ Empty data. Skipping $uri");
            return null;
        }

        // Convert stdClass to array if needed
        if (is_object($data)) {
            $data = json_decode(json_encode($data), true);
        }

        return $data;
    }

    protected function generateSchema(array $data, string $uri): ?string
    {
        try {
            $schema = JsonSchemaGenerator::fromResponse($data);
            return $schema;
        } catch (\Throwable $e) {
            $this->error("   ❌ Failed to generate schema for $uri: " . $e->getMessage());
            return null;
        }
    }

    protected function saveSchema(string $schema, string $method, string $name): ?string
    {
        try {
            $schemaDir = config('api-tester.schema_path');
            File::ensureDirectoryExists($schemaDir);

            $filePath = $schemaDir . "/{$method}_{$name}.json";
            File::put($filePath, $schema);

            $this->line("   💾 Schema saved: " . basename($filePath));
            return $filePath;
            
        } catch (\Exception $e) {
            $this->error("   ❌ Failed to save schema: " . $e->getMessage());
            return null;
        }
    }

    protected function generateTest(string $method, string $uri, string $name, string $schema_path): ?string
    {
        try {
            $testDir = base_path('tests/Feature/ApiTester');
            File::ensureDirectoryExists($testDir);

            $testPath = $testDir . "/{$method}_{$name}.php";
            
            // Check if test file already exists
            if (File::exists($testPath) && !$this->option('force')) {
                $this->line("   ⏭️ Test file exists, skipping: " . basename($testPath));
                return $testPath;
            }

            $url = '/' . ltrim($uri, '/');
            $relativeSchemaPath = str_replace(
                str_replace('\\', '/', base_path()) . '/',
                '',
                str_replace('\\', '/', $schema_path)
            );

            $testContent = $this->generateTestContent($method, $url, $name, $relativeSchemaPath);
            File::put($testPath, $testContent);

            $this->line("   🧪 Test generated: " . basename($testPath));
            return $testPath;
            
        } catch (\Exception $e) {
            $this->error("   ❌ Failed to generate test: " . $e->getMessage());
            return null;
        }
    }

    protected function generateTestContent(string $method, string $url, string $name, string $schema_path): string
    {
        $http_method = strtolower($method);
        $assert_method = match ($method) {
            'GET' => 'getJson',
            'POST' => 'postJson',
            'PUT' => 'putJson',
            'PATCH' => 'patchJson',
            'DELETE' => 'deleteJson',
            default => 'getJson',
        };

        return <<<EOT
<?php

use _34ml\ApiTester\http\Helpers\JsonSchemaValidator;

it('validates {$method} {$url} against JSON schema', function () {
    \$response = \$this->{$assert_method}('{$url}');

    \$response->assertOk();

    \$data = \$response->json('data') ?? \$response->json();
    
    // Ensure we have valid data to validate
    expect(\$data)->not->toBeEmpty();
    expect(\$data)->toBeArray();
    
    // Validate against the generated JSON schema
    JsonSchemaValidator::assert(\$data, base_path('{$schema_path}'));
});

it('validates response structure for {$method} {$url}', function () {
    \$response = \$this->{$assert_method}('{$url}');
    
    \$response->assertOk();
    
    \$data = \$response->json('data') ?? \$response->json();
    
    // Basic structure validation
    expect(\$data)->toBeArray();
    expect(\$data)->not->toBeEmpty();
    
    if (count(\$data) > 0) {
        \$first_item = \$data[0];
        expect(\$first_item)->toBeArray();
        
        // Validate required fields exist (adjust based on your API structure)
        expect(\$first_item)->toHaveKeys(['id']);
    }
});
EOT;
    }
}