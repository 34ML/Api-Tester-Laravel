<?php

namespace _34ml\ApiTester\Commands;

use _34ml\ApiTester\http\Helpers\JsonSchemaValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class TestApiEndpointCommand extends Command
{
    protected $signature = 'api:test {uri : The API endpoint URI to test} {--method=GET : HTTP method to use} {--schema= : Path to JSON schema file for validation}';
    protected $description = 'Test a specific API endpoint and validate its response';

    public function handle()
    {
        $uri = $this->argument('uri');
        $method = strtoupper($this->option('method'));
        $schema_path = $this->option('schema');

        $this->info("🧪 Testing API endpoint: [$method] /$uri");

        // Ensure URI starts with 'api/'
        if (!str_starts_with($uri, 'api/')) {
            $uri = 'api/' . ltrim($uri, '/');
        }

        $baseUrl = config('app.url');
        if (!$baseUrl || $baseUrl === 'http://localhost') {
            $this->warn("⚠️ APP_URL not configured properly. Using localhost:8000");
            $baseUrl = 'http://localhost:8000';
        }

        $fullUrl = rtrim($baseUrl, '/') . '/' . ltrim($uri, '/');

        try {
            // Make the HTTP request
            $this->line("🔗 Requesting: $method $fullUrl");
            
            $response = match ($method) {
                'GET' => Http::get($fullUrl),
                'POST' => Http::post($fullUrl),
                'PUT' => Http::put($fullUrl),
                'PATCH' => Http::patch($fullUrl),
                'DELETE' => Http::delete($fullUrl),
                default => Http::get($fullUrl),
            };

            if ($response->failed()) {
                $this->error("❌ HTTP $method failed: " . $response->status());
                $this->error("📄 Response: " . $response->body());
                return 1;
            }

            $this->info("✅ HTTP $method successful: " . $response->status());

            // Display response summary
            $this->displayResponseSummary($response);

            // Validate against schema if provided
            if ($schema_path) {
                $this->validateAgainstSchema($response, $schema_path);
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error testing endpoint: " . $e->getMessage());
            return 1;
        }
    }

    protected function displayResponseSummary(\Illuminate\Http\Client\Response $response): void
    {
        $this->newLine();
        $this->info("📊 Response Summary:");
        
        $body = $response->json();
        
        if (is_array($body)) {
            $this->line("   Content-Type: " . $response->header('Content-Type'));
            $this->line("   Response Size: " . strlen($response->body()) . " bytes");
            
            if (isset($body['data'])) {
                $data = $body['data'];
                if (is_array($data)) {
                    $this->line("   Data Items: " . count($data));
                    if (count($data) > 0) {
                        $this->line("   First Item Keys: " . implode(', ', array_keys($data[0])));
                    }
                }
            } else {
                $this->line("   Top Level Keys: " . implode(', ', array_keys($body)));
            }
        } else {
            $this->line("   Response is not JSON");
        }
    }

    protected function validateAgainstSchema(\Illuminate\Http\Client\Response $response, string $schema_path): void
    {
        if (!file_exists($schema_path)) {
            $this->warn("⚠️ Schema file not found: $schema_path");
            return;
        }

        $this->newLine();
        $this->info("🔍 Validating response against schema...");

        try {
            $body = $response->json();
            $data = $body['data'] ?? $body;

            if (!is_array($data)) {
                $this->warn("⚠️ Cannot validate: response data is not an array");
                return;
            }

            JsonSchemaValidator::assert($data, $schema_path);
            $this->info("✅ Schema validation passed!");

        } catch (\Exception $e) {
            $this->error("❌ Schema validation failed: " . $e->getMessage());
        }
    }
} 