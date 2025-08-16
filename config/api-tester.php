<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Schema Storage Path
    |--------------------------------------------------------------------------
    |
    | This is the path where JSON schemas will be stored for validation.
    | These schemas are generated from actual API responses.
    |
    */
    'schema_path' => base_path('tests/Schemas'),

    /*
    |--------------------------------------------------------------------------
    | Test Storage Path
    |--------------------------------------------------------------------------
    |
    | This is the path where generated Pest test files will be stored.
    |
    */
    'test_path' => base_path('tests/Feature/ApiTester'),

    /*
    |--------------------------------------------------------------------------
    | Stub Storage Path
    |--------------------------------------------------------------------------
    |
    | This is the path where payload stubs will be stored for testing.
    |
    */
    'stub_path' => base_path('stubs/payloads'),

    /*
    |--------------------------------------------------------------------------
    | Default HTTP Headers
    |--------------------------------------------------------------------------
    |
    | Default headers to include in all API requests during testing.
    | Useful for authentication, content-type, etc.
    |
    */
    'default_headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | Authentication settings for testing protected API endpoints.
    |
    */
    'auth' => [
        'enabled' => false,
        'type' => 'bearer', // bearer, basic, api_key
        'token' => env('API_TEST_TOKEN'),
        'username' => env('API_TEST_USERNAME'),
        'password' => env('API_TEST_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Generation Options
    |--------------------------------------------------------------------------
    |
    | Options for controlling how tests are generated.
    |
    */
    'test_generation' => [
        'include_structure_tests' => true,
        'include_schema_tests' => true,
        'overwrite_existing' => false,
        'generate_mock_data' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema Generation Options
    |--------------------------------------------------------------------------
    |
    | Options for controlling how JSON schemas are generated.
    |
    */
    'schema_generation' => [
        'include_required_fields' => true,
        'strict_types' => true,
        'max_depth' => 5,
    ],
];
