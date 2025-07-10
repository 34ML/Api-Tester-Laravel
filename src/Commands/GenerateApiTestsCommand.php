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
    protected $signature = 'api-tester:generate';
    protected $description = 'Generate JSON schemas from actual API responses';

    public function handle()
    {
        $routes = RouteScanner::getApiRoutes();

        foreach ($routes as $route) {
            $uri = $route['uri'];
          //  dd('uri: ' . $uri);
            $method = strtoupper($route['method']);
            //$name = str_replace('/', '_', trim($uri, '/'));
//$name = Str::ucfirst(trim($uri, '/')) . "Test";
            $name = Str::studly(str_replace(['/', '-', '{', '}'], ' ', trim($uri, '/'))) . 'Test';

            $this->info(" Requesting [$method] /$uri");
//            $user = \App\Models\User::factory()->create();
//            $token = $user->createToken('test-token')->plainTextToken;
            $baseUrl = config('app.url'); // Ensure APP_URL in .env is set correctly
            $fullUrl = rtrim($baseUrl, '/') . '/' . ltrim($uri, '/');
            $response = Http::get($fullUrl);

//  Add Authorization header

           // $response = Http::
//            withHeaders([
//                'Authorization' => 'Bearer ' . $token,
//            ])->
         //   get(url($uri));
            if ($response->failed()) {
                $this->warn("❌ Failed: $uri");
                continue;
            }

            $body = $response->json();

            //  Use response wrapper's "data" only
            if (!isset($body['data'])) {
                $this->warn("⚠️ No 'data' key found. Skipping $uri");
                continue;
            }

            $data = $body['data'];
            $schema = JsonSchemaGenerator::fromResponse($data);

            $schemaDir = config('api-tester.schema_path');
            File::ensureDirectoryExists($schemaDir);

            $filePath = $schemaDir . "/{$method}_{$name}.json";
            File::put($filePath, $schema);

            $this->info(" Saved schema to $filePath");
        }

        $this->info("🎉 All done!");
    }
}