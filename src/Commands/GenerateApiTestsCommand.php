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
    protected $description = 'Generate JSON schemas from actual API responses 
    to vlidate it when tests are generated';

    public function handle()
    {
        $routes = RouteScanner::getApiRoutes();

        foreach ($routes as $route) {
            $uri = $route['uri'];
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
    //         if ($response->failed()) {
    //             $this->warn("❌ Failed: $uri");
    //               $this->warn("Status: " . $response->status());
    // $this->warn("Response: " . $response->body()); 
              
    //             continue;
    //         }

   try {
    $response = Http::get($fullUrl);
} catch (\Exception $e) {
    $this->error("❌ Error connecting to $fullUrl");
    $this->error("Exception: " . $e->getMessage());
    continue;
}

if ($response->failed()) {
    $this->warn("❌ Failed: $uri");
    $this->warn("Status: " . $response->status());
   // $this->warn("Body: " . $response->json());
    $this->warn("Body: " . json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    $this->error("❌ Error at [$method] $uri");
    $this->error("Body: " . json_encode($response->json(), JSON_PRETTY_PRINT));

    continue;
}

$body = $response->json();

if (!is_array($body)) {
    $this->warn("⚠️ Invalid JSON or empty body. Skipping $uri");
    continue;
}

$data = $body['data'] ?? null;

if (is_null($data)) {
    $this->warn("⚠️ No 'data' key found. Skipping $uri");
    continue;
}

// Convert stdClass to array if needed
if (is_object($data)) {
    $data = json_decode(json_encode($data), true);
}

// If it's an empty array or object
if (empty($data)) {
    $this->warn("⚠️ Empty data object. Skipping $uri");
    continue;
}

// Pass the clean array/object to schema generator
try {
    $schema = JsonSchemaGenerator::fromResponse($data);
} catch (\Throwable $e) {
    $this->error("❌ Failed to generate schema for $uri");
    $this->error($e->getMessage());
    continue;
}

            $schemaDir = config('api-tester.schema_path');
            File::ensureDirectoryExists($schemaDir);

            $filePath = $schemaDir . "/{$method}_{$name}.json";
            File::put($filePath, $schema);

            $this->info(" Saved schema to $filePath");
        

        // 🧪 Step: Generate the Pest test file for this endpoint
$testDir = base_path('tests/Feature/ApiTester');
File::ensureDirectoryExists($testDir);

$testPath = $testDir . "/{$method}_{$name}.php";

// If test file already exists, skip
if (! File::exists($testPath)) {
    $url = '/' . ltrim($uri, '/');
 $relativeSchemaPath = str_replace(
     str_replace('\\', '/', base_path()) . '/',
     '',
     str_replace('\\', '/', $filePath)
);


    $testContent = <<<EOT
<?php

use _34ml\ApiTester\http\Helpers\JsonSchemaValidator;

it('validates {$method} {$url} against JSON schema', function () {
    \$response = \$this->getJson('{$url}');

    \$response->assertOk();

    \$data = \$response->json('data');
expect($data)->not->toBeEmpty();

JsonSchemaValidator::assert(\$data, base_path('{$relativeSchemaPath}'));
});
EOT;

    File::put($testPath, $testContent);

    $this->info(" ✅ Generated Pest test: $testPath");
}

    }
            $this->info("🎉 All done!");

}
}